<?php

namespace App\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class BillingCommunicationService
{
    public static function confirmed(?array $payload): void   
    {
        $answer = StockService::stocked($payload);
        self::publish($answer);  
    }

    public static function aborted(?array $payload): void
    {
        $answer = StockService::cancel($payload);
        self::publish($answer, "stock.aborted", "OrderStockAborted");
    }

     public static function publish(array $payload, string $routingKey = 'stock.confirmed', string $event = 'OrderStockConfirmed')
    {
        $connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.hosts.0.host'),
            config('queue.connections.rabbitmq.hosts.0.port'),
            config('queue.connections.rabbitmq.hosts.0.user'),
            config('queue.connections.rabbitmq.hosts.0.password')
        );

        $channel = $connection->channel();

        $exchange = 'events';
        $channel->exchange_declare($exchange, 'topic', false, true, false);

        $eventBody = [
            'event' => $event,
            'version' => '1.0',
            'data' => $payload,
        ];

        $message = new AMQPMessage(json_encode($eventBody), [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $channel->basic_publish($message, $exchange, $routingKey);

        $channel->close();
        $connection->close();
    }
}
