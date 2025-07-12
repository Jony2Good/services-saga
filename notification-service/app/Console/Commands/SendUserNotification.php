<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserOrderNotificationMail;
use App\Models\UserOrderNotification;
use App\Service\CommunicationAuthService;

class SendUserNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notification-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отправка письма пользователю';

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

        $queue = 'notification_queue';
        $exchange = 'events';
        $routingKey = 'user.notification';

        $channel->exchange_declare($exchange, 'topic', false, true, false);

        $channel->queue_declare($queue, false, true, false, false);
        $channel->queue_bind($queue, $exchange, $routingKey);

        $callback = function (AMQPMessage $msg) use ($channel) {
            try {
                $event = json_decode($msg->getBody(), true);

                if ($event['event'] === 'UserOrderNotification') {
                    $data = $event['data'];

                    $userId = $data['user_id'];
                    $orderId = $data['order_id'];
                    $messageText = $data['message'];
                    $errorText = $data['error'];

                    $response = CommunicationAuthService::handle(['user_id' => $data['user_id']]);

                    if ($response && isset($response['email'])) {
                        Mail::to($response['email'])->send(new UserOrderNotificationMail($messageText, $errorText));

                        UserOrderNotification::create([
                            'user_id' => $userId,
                            'order_id' => $orderId,
                            'email' => $response['email'],
                            'message' => $messageText,
                            'error' => $errorText
                        ]);
                    } else {
                        \Log::warning("Не удалось получить email от auth-service для user_id=$userId");
                    }
                }
                $channel->basic_ack($msg->getDeliveryTag());
            } catch (\Throwable $e) {
                \Log::error('Ошибка обработки события UserOrderNotification', [
                    'exception' => $e->getMessage(),
                    'payload' => $msg->getBody()
                ]);
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
