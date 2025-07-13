<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Illuminate\Support\Facades\Log;
use App\Service\StockCommunicationService;

class StockCommunicationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:order-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Слушаем очередь для получения сообщения из stock-service';

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
        $channel->queue_declare('stock_request', false, false, false, false);
        $channel->basic_qos(0, 1, null);
     

        $channel->basic_consume('stock_request', '', false, false, false, false, function ($req) use ($channel) {
            $body = json_decode($req->body, true);   
             Log::info('ответ', [1 => print_r( $body, true)]);         
           
            $reply = StockCommunicationService::handle($body['user_id'], $body['order_id']);  

             Log::info('ответ', [1 => print_r($reply, true)]);
        
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
        $this->info('Ожидаем запрос в order-service...');

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
