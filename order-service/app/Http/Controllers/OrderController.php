<?php

namespace App\Http\Controllers;

use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Request;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\OrderDishes;
use App\Models\Dish;
use App\Models\Order;
use App\Http\Resources\OrderResource;

class OrderController extends Controller
{

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

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $userId = $this->getUserIdFromJwt($request);
        $orders = Order::where('user_id', $userId)
            ->with(['orderDishes'])
            ->get();

        return OrderResource::collection($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        $userId = $this->getUserIdFromJwt($request);

        $dishesCollection = collect($validated['dishes']);

        $dishIds = $dishesCollection->pluck('id');

        $dishFromDb = Dish::whereIn('id', $dishIds)->get(['id', 'price'])->keyBy('id');

        $orderDishes = $dishesCollection->map(function ($dish) use ($dishFromDb) {
            $price = $dishFromDb[$dish['id']]->price ?? 0;
            return new OrderDishes([
                'dish_id' => $dish['id'],
                'amount' => $dish['amount'],
                'total_price' => $dish['amount'] * $price
            ]);
        });

        $order = Order::create([
            'user_id' => $userId,
        ]);

        $order->orderDishes()->saveMany($orderDishes->all());

        $order->load('orderDishes');

        return response()->json([
            "message" => 'Заказ сформирован',
            "data" => new OrderResource($order)
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        $userId = $this->getUserIdFromJwt($request);

        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->with(['orderDishes'])
            ->first();

        return new OrderResource($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOrderRequest $request, string $id)
    {
        $validated = $request->validated();
        $userId = $this->getUserIdFromJwt($request);

        $dishesCollection = collect($validated['dishes']);

        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->with(['orderDishes'])
            ->firstOrFail();

        $dishIds = $dishesCollection->pluck('id');

        $order->orderDishes()
            ->whereNotIn('dish_id', $dishIds)
            ->delete();

        $dishFromDb = Dish::whereIn('id', $dishIds)->get(['id', 'price'])->keyBy('id');

        $dishesCollection->each(function ($dish) use ($order, $dishFromDb) {
            $price = $dishFromDb[$dish['id']]->price ?? 0;
            $order->orderDishes()
                ->updateOrCreate(
                    ['dish_id' => $dish['id']],
                    [
                        'amount' => $dish['amount'],
                        'total_price' => $dish['amount'] * $price
                    ]
                );
        });

        $order->load('orderDishes');

        return response()->json([
            "message" => 'Заказ изменен',
            "data" => new OrderResource($order)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id, Request $request)
    {
        $userId = $this->getUserIdFromJwt($request);

        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->with(['orderDishes'])
            ->firstOrFail();

        $order->delete();

        return response()->json([
            'message' => 'Заказ удален',
            'user' => new OrderResource($order)
        ]);
    }
}
