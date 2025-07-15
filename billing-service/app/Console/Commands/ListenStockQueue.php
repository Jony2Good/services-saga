<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;
use App\Service\NotificationService;


class ListenStockQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:stock-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Слушает stock.confirmed и stock.aborted из stock-service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.hosts.0.host'),
            config('queue.connections.rabbitmq.hosts.0.port'),
            config('queue.connections.rabbitmq.hosts.0.user'),
            config('queue.connections.rabbitmq.hosts.0.password')
        );

        $channel = $connection->channel();

        $exchange = 'events';
        $queue = 'billing_stock_queue';

        // Создаём очередь и биндим с нужными routing key
        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);

        $channel->queue_bind($queue, $exchange, 'stock.confirmed');
        $channel->queue_bind($queue, $exchange, 'stock.aborted');

        $billingSaga = app(\App\Service\BillingSagaService::class);

        $command = app(\App\Service\CommunicationService::class);

        $callback = function (AMQPMessage $msg) use ($channel, $billingSaga, $command) {
            try {
                $event = json_decode($msg->getBody(), true);

                Log::info("ответ из stock", ['print' => print_r($event, true)]);

                // Разделить положительный и отрицательный ответ                                 

                $reply = $billingSaga::stocked($event['data']);

                Log::info("Обработка ответа в billing-service из склада", ['reply' => print_r($reply, true)]);

                // Отправляем запрос в delivery-service
                if ($reply['error'] === false) {

                    Log::info('Что уходит в delivery-service', ['reply' => print_r($reply, true)]);

                    $command::handle($reply, "OrderDeliveryEvent", "order.delivered");
                }

                if ($reply['error']) {
                    NotificationService::notificationMessage($reply);
                }

                $channel->basic_ack($msg->getDeliveryTag());
            } catch (\Throwable $e) {
                Log::error('Ошибка обработки stock-события: ' . $e->getMessage());
                $channel->basic_nack($msg->getDeliveryTag(), false, false);
            }
        };

        $channel->basic_consume($queue, '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
