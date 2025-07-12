<?php

namespace App\Service;

use App\Enums\OrderStatuses;
use App\Models\BillingAccount;
use App\Repository\BillingEventRepository;
use App\Repository\OrderRepository;
use Illuminate\Support\Facades\DB;
use App\Enums\BillingEventType;
use Ramsey\Uuid\Uuid;

class BillingSagaService
{
    public function startOrderSaga(int $userId, int $orderId, string $totalPrice)
    {
        return DB::transaction(function () use ($userId, $orderId, $totalPrice) {
            
            $sagaId = Uuid::uuid4()->toString();

            $account = BillingAccount::where('user_id', $userId)->lockForUpdate()->first();

            $response = collect([
                'order_id' => $orderId,
                'user_id' => $userId,
            ]);

            if (!$account) {
                return $response->merge([
                    'order_status' => OrderStatuses::ERROR->value,
                    'error' => true,
                    'message' => 'Счёт пользователя не найден',
                ])->all();
            }

            // Создаем запись о первоначальном статусе заказа. По дефолту будет 0, что значит NEW
            $orderStatus = OrderRepository::orderStatusCreate($account, [
                'order_id' => $orderId,
                'user_id' => $userId,
                'total_price' => $totalPrice,
                'saga_id' => $sagaId,
            ]);

            // Создаем первую запись для отслеживания saga
            BillingEventRepository::billingEventCreate([
                'saga_id' => $sagaId,
                'user_id' => $userId,
                'order_id' => $orderId,
                'event_type' => BillingEventType::SagaStart->value,
            ]);

            // Проверяем наличие денег на счете
            if ($account->balance < $totalPrice) {                
                OrderRepository::orderStatusUpdate($orderStatus, [
                    'status' => OrderStatuses::PAYMENT_FAILED->value,
                ]);

                BillingEventRepository::billingEventCreate([
                    'saga_id' => $sagaId,
                    'user_id' => $userId,
                    'order_id' => $orderId,
                    'event_type' => BillingEventType::InsufficientFunds->value,
                ]);

                return $response->merge([                    
                    'error'   => true,
                    'message' => 'На счету недостаточно средств',
                    'order_status' => OrderStatuses::PAYMENT_FAILED->value,
                ])->all();
            }

            $account->balance -= $totalPrice;
            $account->save();

            OrderRepository::orderStatusUpdate($orderStatus, [
                'status' => OrderStatuses::PAYMENT_SUCCESS->value,
            ]);

            BillingEventRepository::billingEventCreate([
                'saga_id' => $sagaId,
                'user_id' => $userId,
                'order_id' => $orderId,
                'event_type' => BillingEventType::MoneyDebited->value,
            ]);            

            return $response->merge([
                'error'   => false,
                'message' => 'Заказ оплачен',
                'order_status' => OrderStatuses::PAYMENT_SUCCESS->value,
            ])->all();
        });
    }
}
