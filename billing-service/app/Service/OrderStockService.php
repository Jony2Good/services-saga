<?php

namespace App\Service;

use App\Models\OrderStatus;

class OrderStockService
{
    //Нужно прокидывать saga_id везеде иначе не понятно какую saga создавать в таблице billing_events
    public static function stocked(array $data)
    {
        if($data['error']) {

        }
        
        $orderStatus = OrderStatus::where('order_id', $data['order_id'])->first();
        $orderStatus->update(['status' => $data['order_status']]);
    }
}
