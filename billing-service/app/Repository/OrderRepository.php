<?php

namespace App\Repository;

use App\Models\OrderStatus;
use App\Models\BillingAccount;

class OrderRepository
{
    public static function orderStatusCreate(BillingAccount $account, array $data): OrderStatus
    {
        return $account->orderStatus()->create($data);
    }

    public static function orderStatusUpdate(OrderStatus $orderStatus, array $data): bool
    {
        return $orderStatus->update($data);
    }
}
