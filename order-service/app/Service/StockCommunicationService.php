<?php

namespace App\Service;

use App\Models\Order;

class StockCommunicationService
{
    public static function handle(int $userId, int $orderId)
    {
        $order = Order::where('user_id', $userId)
            ->where('id', $orderId)
            ->with(['orderDishes'])
            ->firstOrFail();

        return [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'order_dishes' => $order->orderDishes->map(
                fn($dish) => [
                    'dish_id' => $dish->dish_id,
                    'amount' => $dish->amount,
                ]
            )->values()->toArray(),
        ];
    }
}
