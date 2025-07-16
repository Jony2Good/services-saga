<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use App\Service\NotificationService;
use App\Service\CommunicationService;
use Illuminate\Support\Facades\Log;

class OrderPaymentHandlerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:payment-event';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обработка запроса от order-service';

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
        $channel->queue_declare('billing_request', false, false, false, false);
        $channel->basic_qos(0, 1, null);

        $billingSaga = app(\App\Service\BillingSagaService::class);
        $command = app(\App\Service\CommunicationService::class);

        $channel->basic_consume('billing_request', '', false, false, false, false, function ($req) use ($channel, $billingSaga, $command) {
            $body = json_decode($req->body, true);
            $totalPrice = (string)$body['total_price'];
            $orderId = $body['order_id'];
            $userId = $body['user_id'];
            $iKey = $body['iKey'] ?? null;

            $reply = $billingSaga->startOrderSaga($userId, $orderId, $totalPrice, $iKey);

            Log::info('ответ1', ['reply' => print_r($reply, true)]);

            if (empty($reply['iKey'])) {
                NotificationService::notificationMessage($reply);
            }

            if ($reply['error'] === false) {
                Log::info('Отправка запроса в stock-service из billing-service', ['reply' => print_r($reply, true)]);
                // Отправляем запрос в stock-service
                $command::handle($reply, 'OrderStockRequest', 'order.stocked');
            }

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
        $this->info('Ожидаем запрос в billing-service...');

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
