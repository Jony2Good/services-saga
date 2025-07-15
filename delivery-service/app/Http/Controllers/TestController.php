<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Delivery;
use App\Enums\OrderStatuses;

class TestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $orderId = $request->input('order_id');
        $userId = $request->input('user_id');
        $sagaId = $request->input('saga_id');

        $wasDelivered = Delivery::where('order_id', $orderId)
            ->where('status', OrderStatuses::DELIVERY_SUCCESS->value)
            ->exists();

        if ($wasDelivered) {
            return [
                'order_id' => $orderId,
                'user_id' => $userId,
                'saga_id' => $sagaId,
                'error' => true,
                'message' => 'Заказ ранее был доставлен',
                'order_status' => OrderStatuses::ERROR->value,
            ];
        }

        $lastDelivery = Delivery::latest('id')->first();

        $status = $lastDelivery && $lastDelivery->status === OrderStatuses::DELIVERY_SUCCESS->value
            ? OrderStatuses::DELIVERY_FAILED->value
            : OrderStatuses::DELIVERY_SUCCESS->value;

        $isFailed = $status === OrderStatuses::DELIVERY_FAILED->value;

        Delivery::create([
            'order_id' => $orderId,
            'user_id' => $userId,
            'saga_id' => $sagaId,
            'status' => $status,
        ]);

        return [
            'order_id' => $orderId,
            'user_id' => $userId,
            'saga_id' => $sagaId,
            'error' => $isFailed,
            'message' => $isFailed ? 'Доставка товара не возможна' : 'Товар доставлен',
            'order_status' => $isFailed ? OrderStatuses::RESERVE_FAILED->value : OrderStatuses::RESERVE_SUCCESS->value,
        ];
    }
}
