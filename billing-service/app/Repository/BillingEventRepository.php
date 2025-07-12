<?php

namespace App\Repository;
use App\Models\BillingEvent;

class BillingEventRepository
{
    public static function billingEventCreate(array $data): BillingEvent
    {
        return BillingEvent::create($data);
    }
}
