<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Message\AMQPMessage;
use App\Service\UserService;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class NotitifcationCommunicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Слушает очередь сообщений от notification-service';

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
        $channel->queue_declare('notification_request', false, false, false, false);
        $channel->basic_qos(null, 1, null);
       

        $channel->basic_consume('notification_request', '', false, false, false, false, function ($req) use ($channel) {
            $body = json_decode($req->body, true);           
            $userId = $body['user_id'];

            $reply = UserService::getUserEmail($userId);          

            // Формируем ответ
            $msg = new AMQPMessage(json_encode($reply), [
                'correlation_id' => $req->get('correlation_id'),
                'content_type'   => 'application/json',
            ]);

            // Публикуем ответ в очередь reply_to
            $channel->basic_publish($msg, '', $req->get('reply_to'));

            // Подтверждаем обработку запроса
            $channel->basic_ack($req->delivery_info['delivery_tag']);
        });

        // Пишем в консоль, чтобы не зависнуть без уведомления
        $this->info('Ожидаем запрос в notification_request...');

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
