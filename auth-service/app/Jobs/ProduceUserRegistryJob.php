<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ProduceUserRegistryJob implements ShouldQueue
{
    use Queueable;

    protected array $userData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $userData)
    {
        $this->userData = $userData;
        $this->onQueue('billing_queue');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.hosts.0.host'),
            config('queue.connections.rabbitmq.hosts.0.port'),
            config('queue.connections.rabbitmq.hosts.0.user'),
            config('queue.connections.rabbitmq.hosts.0.password')
        );

        $channel = $connection->channel();

        $exchange = 'events';
        $routingKey = 'user.registered';

        $channel->exchange_declare($exchange, 'topic', false, true, false);

        $event = [
            'event' => 'UserRegistered',
            'version' => '1.0',
            'data' => $this->userData,
        ];

        $message = json_encode($event);

        $msg = new AMQPMessage($message, [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);

         $channel->basic_publish($msg, $exchange, $routingKey);

        \Log::info('Published event: ' . $message);

        $channel->close();
        $connection->close();
    }
}
