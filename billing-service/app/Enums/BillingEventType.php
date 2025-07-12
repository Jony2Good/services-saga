<?php

namespace App\Enums;

enum BillingEventType: string
{   
    case SagaStart = 'saga_start';
    case MoneyDebited = 'money_debited';
    case InsufficientFunds = 'insufficient_funds';
    case RefundIssued = 'refund_issued';
    case SagaCompleted = 'saga_completed';
    case SagaFailed = 'saga_failed';
    case StockReserved = "stock_order_reserved";
    case StockFailed = 'stock_failed';
    case DeliveryReserved = 'delivery_reserved';
    case DeliveryFailed = 'delivery_failed';
}
