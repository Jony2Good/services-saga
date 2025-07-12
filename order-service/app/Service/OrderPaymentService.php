<?php

namespace App\Service;

use App\Enums\OrderStatuses;
use App\DTO\OrderPaymentDTO;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;

/**
 * 
 * TODO: подумать над тем, чтобы расширить проверку статусов заказа, чтобы избежать повторных запросов от клиента. 
 * К примеру, если статус в процессе, то должен быть ответ, чтобы не плодить несколько запросов. 
 * Тогда billing-service должен прислать финанльный статус заказа Либо использовать проверку Idempotency-Key на уровне запроса в billing-service 
 * и тогда убрать все статусы и проверять в billing-service данный заголовок 
 * 1. Генерируй UUID в order-service при первом платёжном действии
 * 2. Сохраняй его в заказе (orders.idempotency_key)
 * 3. При повторном запросе из фронта — переиспользуй его
 * При повторной оплате
 * 1. order-service должен сгенерировать новый Idempotency-Key
 * 2. отправить его в новый запрос → billing создаёт новую сагу
 * 3. получаешь историю всех попыток по одному order_id через saga_id + idempotency_key
 */
class OrderPaymentService
{
    public function payOrder(Order $order, OrderPaymentDTO $dto): array
    {
        if ($order->status === OrderStatuses::STARTED->value) {
            return OrderResponseBuiderService::businessError($order->status,  "Ошибка. Заказ № {$order->id} находится в процессе сборки");
        }

        if (in_array($order->status, [OrderStatuses::PAYMENT_SUCCESS->value, OrderStatuses::COMPLETED->value], true)) {            
            return OrderResponseBuiderService::businessError($order->status,  "Ошибка. Заказ № {$order->id} ранее был оплачен или уже исполнен");
        }

        if (!in_array($order->status, [OrderStatuses::PAYMENT_FAILED->value, OrderStatuses::ERROR->value, OrderStatuses::NEW->value], true)) {
            return OrderResponseBuiderService::businessError($order->status,  "Заказ № {$order->id} не может быть обработан. Создайте новый заказ");
        }

        //Устанавливаем статус у заказа на начало процесса, чтобы избежать повторных запросов
        $order->updateStatus(OrderStatuses::STARTED->value);

        $dto->total_price = $order->order_dishes_sum_total_price;

        try {
            // Отправляю заказ в billing-servie для оплаты
            $response = RMQService::handle($dto->toArray());

            if (is_null($response)) {
                $order->updateStatus(OrderStatuses::ERROR->value);

                return OrderResponseBuiderService::timeoutError($order->id);
            }

            // TODO: Требуется разделить логику проверки ошибок, т.к. ошибка PAYMENT_FAILED не равно ERROR. 
            // Во втором случае saga может не завершиться еще, а мы даем право оплаты. Нужно проверять статус saga
            if ($response['error']) {
                $order->updateStatus($response['order_status']);

                return  OrderResponseBuiderService::logicError(
                    $response['order_status'],
                    $response['message']
                );
            }

            $order->updateStatus($response['order_status']);

            return OrderResponseBuiderService::success(
                $response['order_status'],
                'Оплата прошла успешно. Начат процесс формирования заказа. Проверяйте статус заказа'
            );
        } catch (\Throwable $e) {
            Log::error("Ошибка оплаты заказа {$order->id}: " . $e->getMessage());

            $order->updateStatus(OrderStatuses::ERROR->value);

            return OrderResponseBuiderService::serverError($order->id);
        }
    }

    public function getUserIdFromJwt(Request $request): ?int
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        $payload = JWTAuth::setToken($token)->getPayload();

        return $payload->get('sub');
    }

    public function getUserDataFromJwt(Request $request, string $data): ?string
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        $payload = JWTAuth::setToken($token)->getPayload();

        return $payload->get($data);
    }
}
