<?php

namespace App\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class CommunicationAuthService
{
   public static function handle(array $payload)
    {
        $connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.hosts.0.host'),
            config('queue.connections.rabbitmq.hosts.0.port'),
            config('queue.connections.rabbitmq.hosts.0.user'),
            config('queue.connections.rabbitmq.hosts.0.password')
        );

        $channel = $connection->channel();

        // Создаём временную очередь‑коллбэк
        list($callbackQueueName,,) = $channel->queue_declare(
            "",    // пустое имя — сервер сгенерирует уникальное
            false, // passive
            false, // durable
            true,  // exclusive — очередь будет удалена при разрыве соединения
            true   // auto_delete
        );

        $corrId = \Illuminate\Support\Str::uuid()->toString();

        $response = null;

        $channel->basic_consume(
            $callbackQueueName,
            '',    // consumer tag
            false, // no_local
            false, // no_ack
            false, // exclusive
            false, // no_wait
            function (AMQPMessage $msg) use (&$response, $corrId) {
                if ($msg->get('correlation_id') === $corrId) {
                    $response = json_decode($msg->getBody(), true);
                }
            }
        );

        $msg = new AMQPMessage(json_encode($payload), [
            'correlation_id' => $corrId,
            'reply_to'       => $callbackQueueName,
            'content_type'   => 'application/json',
        ]);

        $channel->basic_publish($msg, '', 'notification_request');

        // 7. Ожидаем ответа 
        $timeout = 5; // секунд
        $start   = time();

        while (is_null($response) && (time() - $start) < $timeout) {
            $channel->wait(null, false, 1);
        }

        // 8. Закрываем соединение
        $channel->close();
        $connection->close();

        return $response;
    }
}
