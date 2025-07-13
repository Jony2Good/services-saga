<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockAmountDishes;
use App\Enums\OrderStatuses;
use App\Models\StockOrders;
use App\Service\StockService;
use Illuminate\Support\Facades\DB;

class TestController extends Controller
{
    /**
     * TODO: логируем весь запрос
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $orderId = $request->input('order_id');


        if (is_null($request->all())) {
            return [
                'error' => true,
                'message' => 'Ошибка по таймауту или система не доступна',
                'order_status' => OrderStatuses::RESERVE_FAILED->value,
            ];
        }

        $stockOrder = StockOrders::where('order_id', $orderId)
            ->where('status', OrderStatuses::RESERVE_SUCCESS->value)
            ->first();

        if (!$stockOrder) {
            return [
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
            'error' => false,
            'message' => 'Резерв успешно отменён',
            'order_status' => OrderStatuses::ABORTED->value,
        ];
    }
}
