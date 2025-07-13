<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;
use App\Service\OrderStockService;

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

        $callback = function (AMQPMessage $msg) use ($channel) {
            try {
                $event = json_decode($msg->getBody(), true);

                Log::info("ответ из stock", [1 => print_r($event, true)]);

                match ($event['event']) {
                    'OrderStockConfirmed' => OrderStockService::stocked($event['data']),
                    'OrderStockAborted' => 111111,
                    default => Log::warning("Неизвестное событие из stock: {$event['event']}"),
                };

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
