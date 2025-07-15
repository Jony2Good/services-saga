<?php

namespace App\Service;

use App\Enums\OrderStatuses;
use App\Models\BillingAccount;
use App\Repository\BillingEventRepository;
use App\Repository\OrderRepository;
use Illuminate\Support\Facades\DB;
use App\Enums\BillingEventType;
use Ramsey\Uuid\Uuid;
use App\Models\OrderStatus;
use App\Models\BillingEvent;
use Illuminate\Support\Collection;


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
                'saga_id' => $sagaId,
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

    public static function stocked(?array $data)
    {       
        $dataCollection = collect([
            'order_id' => $data["order_id"],
            'user_id' => $data["user_id"],
            'saga_id' => $data["saga_id"],
        ]);

        $order = OrderStatus::where("order_id", $dataCollection->get("order_id"))->firstOrFail();
        $order->update(['status' => $data["order_status"]]);

        // Проверка списания средств со счета
        $events = BillingEvent::where("saga_id", $dataCollection->get("saga_id"))
            ->where('event_type', BillingEventType::MoneyDebited->value)
            ->get();

        if ($events->isEmpty()) {
            return  $dataCollection->merge([
                'order_status' => OrderStatuses::ERROR->value,
                'error' => true,
                'message' => 'Заказ не был оплачен, средства со счета не списывались',
            ])->all();
        }

        if ($data["error"]) {
            return self::refundMoney($dataCollection, $data, $order, BillingEventType::StockFailed->value);
        }

        if ($data["error"] === false) {
            BillingEventRepository::billingEventCreate([
                'saga_id' => $dataCollection->get("saga_id"),
                'user_id' => $dataCollection->get("user_id"),
                'order_id' => $dataCollection->get("order_id"),
                'event_type' => BillingEventType::StockReserved->value,
            ]);

            return $dataCollection->merge([
                'order_status' => $data["order_status"] ?? '',
                'error' => false,
                'message' => $data["message"] ?? '',
            ])->all();
        }
    }

    protected static function refundMoney(Collection $dataCollection, array $data, OrderStatus $order, string $type): array
    {
        $userBalance = BillingAccount::where("user_id", $dataCollection->get('user_id'))
            ->lockForUpdate()
            ->first();

        if (!$userBalance) {
            return $dataCollection->merge([
                'order_status' => OrderStatuses::ERROR->value,
                'error' => true,
                'message' => 'Аккаунт пользователя отсутствует в системе',
            ])->all();
        }

        $userBalance->balance += $order->total_price;
        $userBalance->save();

        BillingEventRepository::billingEventCreate([
            'saga_id' => $dataCollection->get("saga_id"),
            'user_id' => $dataCollection->get("user_id"),
            'order_id' => $dataCollection->get("order_id"),
            'event_type' => $type,
        ]);

        return $dataCollection->merge([
            'order_status' => $data["order_status"] ?? '',
            'error' => true,
            'message' => $data["message"] ?? '',
        ])->all();
    }

    public static function orderDeliveried(?array $data)
    {
        $dataCollection = collect([
            'order_id' => $data["order_id"],
            'user_id' => $data["user_id"],
            'saga_id' => $data["saga_id"],
        ]);

        $order = OrderStatus::where("order_id", $dataCollection->get("order_id"))->firstOrFail();

        $order->update(['status' =>  OrderStatuses::DELIVERY_SUCCESS->value]);

        BillingEventRepository::billingEventCreate([
            'saga_id' => $dataCollection->get("saga_id"),
            'user_id' => $dataCollection->get("user_id"),
            'order_id' => $dataCollection->get("order_id"),
            'event_type' => BillingEventType::DeliveryReserved->value,
        ]);

        return $dataCollection->merge([
            'order_status' => OrderStatuses::DELIVERY_SUCCESS->value,
            'error' => false,
            'message' => $data["message"] ?? '',
        ])->all();
    }

    public static function orderDeliveryFailed(?array $data)
    {
        $dataCollection = collect([
            'order_id' => $data["order_id"],
            'user_id' => $data["user_id"],
            'saga_id' => $data["saga_id"],
        ]);

        $order = OrderStatus::where("order_id", $dataCollection->get("order_id"))->firstOrFail();
        $order->update(['status' => OrderStatuses::DELIVERY_FAILED->value]);

        BillingEventRepository::billingEventCreate([
            'saga_id' => $dataCollection->get("saga_id"),
            'user_id' => $dataCollection->get("user_id"),
            'order_id' => $dataCollection->get("order_id"),
            'event_type' => BillingEventType::DeliveryFailed->value,
        ]);

        return $dataCollection->merge([
            'order_status' => OrderStatuses::DELIVERY_FAILED->value,
            'error' => false,
            'message' => $data["message"] ?? '',
        ])->all();
    }
}
