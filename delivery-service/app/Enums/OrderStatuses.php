<?php

namespace App\Enums;

enum OrderStatuses: int
{
    case NEW = 0;
    case PAYMENT_SUCCESS = 1;
    case RESERVE_SUCCESS = 2;
    case DELIVERY_SUCCESS = 3;
    case PAYMENT_FAILED = 4;   
    case RESERVE_FAILED = 5;
    case DELIVERY_FAILED = 6;
    case ERROR = 7;
    case STARTED = 8;
    case COMPLETED = 9;
    case ABORTED = 10;
}
