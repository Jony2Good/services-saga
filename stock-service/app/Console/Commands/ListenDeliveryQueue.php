<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;
use App\Service\OrderCommunicationService;
use App\Service\BillingCommunicationService;

class ListenDeliveryQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delivery-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Слушаем очередь для получения сообщения из billing-service об отмене доставки';

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

        $queue = 'stock_delivery_queue';
        $exchange = 'events';
        $routingKey = 'order.delivery.aborted';

        $channel->exchange_declare($exchange, 'topic', false, true, false);

        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, $routingKey);

        $callback = function (AMQPMessage $msg) use ($channel) {
            try {
                $event = json_decode($msg->getBody(), true);

                Log::info('Запрос после отмены доставки', [1 => print_r($event, true)]);

                if ($event['event'] === 'OrderDeliveryFailed') {                   
                    BillingCommunicationService::aborted($event['data']);
                }

                $channel->basic_ack($msg->getDeliveryTag());
            } catch (\Throwable $e) {
                Log::error('Failed to process event: ' . $e->getMessage());
                $channel->basic_nack($msg->getDeliveryTag(), false, false);
            }
        };
        $channel->basic_consume($queue, '', false, false, false, false, $callback);

        $this->info('Ожидаем запрос в stock-service...');

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
