<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;
use App\Service\BillingSagaService;
use App\Service\NotificationService;
use App\Service\CommunicationService;

class ListenDeliveryQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:listen-delivery-queue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $queue = 'billing_delivery_queue';

        // Создаём очередь и биндим с нужными routing key
        $channel->exchange_declare($exchange, 'topic', false, true, false);
        $channel->queue_declare($queue, false, true, false, false);

        $channel->queue_bind($queue, $exchange, 'order.delivery.failed');
        $channel->queue_bind($queue, $exchange, 'order.delivery.success');

        $callback = function (AMQPMessage $msg) use ($channel) {
            try {
                $event = json_decode($msg->getBody(), true);

                Log::info("ответ из delivery", [1 => print_r($event, true)]);

                switch ($event['event']) {
                    case 'OrderDeliverySuccess':
                        $payload = BillingSagaService::orderDeliveried($event['data']);
                        break;

                    case 'OrderDeliveryFailed':
                        $payload = BillingSagaService::orderDeliveryFailed($event['data']);
                        CommunicationService::handle($payload, 'OrderDeliveryFailed', 'order.delivery.aborted');
                        break;

                    default:
                        return; 
                }

                NotificationService::notificationMessage($payload);

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
