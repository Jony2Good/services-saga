<?php

namespace App\Service;

use App\Models\StockAmountDishes;
use App\Enums\OrderStatuses;
use App\Models\StockOrders;
use Illuminate\Support\Facades\DB;

class StockService
{
    public static function stocked(?array $payload, ?array $data)
    {
        if (is_null($payload)) {
            return [
                'error' => true,
                'message' => 'Ошибка по таймауту или система не доступна',
                'order_status' => OrderStatuses::RESERVE_FAILED->value,
            ];
        }

        $sagaId = $data['saga_id'] ?? '';
        $orderDishes = collect($payload['order_dishes'] ?? '');
        $orderId = $payload['order_id'] ?? '';
        $userId = $payload['user_id'] ??'';

        return DB::transaction(function () use ($orderId, $orderDishes, $sagaId, $userId) {
            $stockOrder = StockOrders::firstOrCreate(['order_id' => $orderId, 'saga_id' => $sagaId]);

            // Удаляем и сохраняем новые записи о заказах и блюдах
            $stockOrder->orderDishes()->delete();
            $stockOrder->orderDishes()->createMany($orderDishes->toArray());

            $stockAmounts = StockAmountDishes::pluck('amount', 'id');

            // Сравниваем количество товара в заказае и на складе
            $insufficientDishes = $orderDishes->first(function ($dish) use ($stockAmounts) {
                $dishId = $dish['dish_id'];
                $amount = $dish['amount'];
                return !isset($stockAmounts[$dishId]) || $amount > $stockAmounts[$dishId];
            });

            // Возвращаем негативный ответ если товара на складе меньше
            if ($insufficientDishes) {
                $stockOrder->update([
                    'status' => OrderStatuses::RESERVE_FAILED->value,
                ]);

                return [
                    'order_id' => $orderId,
                    'user_id' => $userId,
                    'saga_id' => $sagaId,
                    'error' => true,
                    'message' => 'Недостаточно товаров на складе',
                    'order_status' => OrderStatuses::RESERVE_FAILED->value,
                ];
            };

            // Уменьшаем товар на складе на количество в заказае
            $orderDishes->each(function ($dish) use ($stockAmounts) {
                $dishId = $dish['dish_id'];
                $newAmount = $stockAmounts[$dishId] - $dish['amount'];

                StockAmountDishes::where('id', $dishId)->update([
                    'amount' => $newAmount
                ]);
            });

            $stockOrder->update([
                'status' => OrderStatuses::RESERVE_SUCCESS->value,
            ]);

            return [
                'order_id' => $orderId,
                'user_id' => $userId,
                'saga_id' => $sagaId,
                'error' => false,
                'message' => 'Товар зарезервирован',
                'order_status' => OrderStatuses::RESERVE_SUCCESS->value,
            ];
        });
    }

    public static function cancel(?array $payload) 
    {     
        if (is_null($payload)) {
            return [
                'error' => true,
                'message' => 'Ошибка по таймауту или система не доступна',
                'order_status' => OrderStatuses::RESERVE_FAILED->value,
            ];
        }

        $orderId = $payload['order_id'];
        $userId = $payload['user_id'] ??'';

        $stockOrder = StockOrders::where('order_id', $orderId)
            ->where('status', OrderStatuses::RESERVE_SUCCESS->value)
            ->first();

        if (!$stockOrder) {
            return [
                'order_id' => $orderId,
                'user_id' => $userId,
                'error' => true,
                'message' => 'Резерв товаров в заказе не найден или уже откатан',
                'order_status' => OrderStatuses::RESERVE_FAILED->value,
            ];
        }

        $orderAmounts = $stockOrder->orderDishes()->pluck('amount', 'dish_id');

        $stockAmounts = StockAmountDishes::lockForUpdate()->pluck('amount', 'id');

        $orderAmounts->only($stockAmounts->keys())->each(function ($orderAmount, $dishId) use ($stockAmounts) {
            $newAmount = $stockAmounts[$dishId] + $orderAmount;

            StockAmountDishes::where('id', $dishId)->update([
                'amount' => $newAmount
            ]);
        });

        $stockOrder->update([
            'status' => OrderStatuses::ABORTED->value,
        ]);

        return [
            'order_id' => $orderId,
            'user_id' => $userId,
            'saga_id' =>  $stockOrder->saga_id,
            'error' => true,
            'message' => 'Резерв успешно отменён',
            'order_status' => OrderStatuses::ABORTED->value,
        ];
    }
}
