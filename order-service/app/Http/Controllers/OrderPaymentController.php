<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Service\OrderPaymentService;
use App\DTO\OrderPaymentDTO;

class OrderPaymentController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($id, Request $request, OrderPaymentService $paymentService)
    {
        $userId = $paymentService->getUserIdFromJwt($request);

        $order = Order::where('user_id', $userId)
            ->where('id', $id)
            ->withSum('orderDishes', 'total_price')
            ->firstOrFail();

        $dto = new OrderPaymentDTO(
            user_id: $userId,
            order_id: $order->id,
            email: $paymentService->getUserDataFromJwt($request, 'email')
        );

        $result = $paymentService->payOrder($order, $dto);

        return response()->json(array_merge($dto->toArray(), [
            'message' => $result['message'],
            'error' => $result['error'],
            'order_status' => $result['order_status']
        ]), $result['status']);        
    }
}
