<?php

namespace App\Http\Controllers;

use App\Models\BillingEvent;
use App\Repository\BillingEventRepository;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use App\Enums\BillingEventType;
use App\Models\BillingAccount;
use App\Enums\OrderStatuses;

class TestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        $response = collect([
            'order_id' => $request->input("order_id"),
            'user_id' => $request->input("user_id"),
            'saga_id' => $request->input("saga_id"),
        ]);


        dd($response->get('order_id'));

        $order = OrderStatus::where("order_id", $request->input("order_id"))->firstOrFail();
        $order->update(['status' => $request->input("order_status")]);

        $events = BillingEvent::where("saga_id", $request->input("saga_id"))
            ->where('event_type', BillingEventType::MoneyDebited->value)
            ->get();

        if ($request->input("error") && $events->isNotEmpty()) {
            $userBalance = BillingAccount::where("user_id", $request->input("user_id"))->lockForUpdate()->first();

            if (!$userBalance) {
                return $response->merge([
                    'order_status' => OrderStatuses::ERROR->value,
                    'error' => true,
                    'message' => 'Аккаунт пользователя отсутствует в системе',
                ])->all();
            };

            $userBalance->balance += $order->total_price;

            $userBalance->save();

            BillingEventRepository::billingEventCreate([
                'saga_id' => $request->input("saga_id"),
                'user_id' => $request->input("user_id"),
                'order_id' => $request->input("order_id"),
                'event_type' => BillingEventType::StockFailed->value,
            ]);

            return $response->merge([
                'order_status' => $request->input("order_status"),
                'error' => true,
                'message' => $request->input("message"),
            ])->all();
        }

        if ($request->input("error") === false) {
            BillingEventRepository::billingEventCreate([
                'saga_id' => $request->input("saga_id"),
                'user_id' => $request->input("user_id"),
                'order_id' => $request->input("order_id"),
                'event_type' => BillingEventType::StockReserved->value,
            ]);

            return $response->merge([
                'order_status' => $request->input("order_status"),
                'error' => false,
                'message' => $request->input("message"),
            ])->all();
        }
    }
}
