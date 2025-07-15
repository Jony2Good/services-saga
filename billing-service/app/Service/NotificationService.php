<?php

namespace App\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;

class NotificationService
{    
    public static function notificationMessage(array $payload)
    {       
        $connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.hosts.0.host'),
            config('queue.connections.rabbitmq.hosts.0.port'),
            config('queue.connections.rabbitmq.hosts.0.user'),
            config('queue.connections.rabbitmq.hosts.0.password')
        );

        $channel = $connection->channel();

        $exchange = 'events';
        $routingKey = 'user.notification';

        $channel->exchange_declare($exchange, 'topic', false, true, false);

        $event = [
            'event' => 'UserOrderNotification',
            'version' => '1.0',
            'data' => $payload,
        ];

        $message = json_encode($event);

         Log::info('Что уходит в delivery-service', ['print' => print_r($message, true)]);

        $msg = new AMQPMessage($message, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

        $channel->basic_publish($msg, $exchange, $routingKey);

        $channel->close();
        $connection->close();
    }
}
