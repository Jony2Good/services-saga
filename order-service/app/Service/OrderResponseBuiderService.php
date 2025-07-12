<?php

namespace App\Service;

use App\Enums\OrderStatuses;

class OrderResponseBuiderService
{
    public static function success(int $status, string $message): array
    {
        return [
            'error' => false,
            'order_status' => $status,
            'message' => $message,
            'status' => 200,
        ];
    }

    public static function businessError(int $status, string $message): array
    {
        return [
            'error' => true,
            'order_status' => $status,
            'message' => $message,
            'status' => 200,
        ];
    }

    public static function logicError(int $status, string $message): array
    {
        return [
            'error' => true,
            'order_status' => $status,
            'message' => $message,
            'status' => 400,
        ];
    }

    public static function timeoutError(int $orderId): array
    {
        return [
            'error' => true,
            'order_status' => OrderStatuses::ERROR->value,
            'message' => "Не удалось обработать оплату заказа № {$orderId}: истёк таймаут",
            'status' => 504,
        ];
    }

    public static function serverError(int $orderId): array
    {
        return [
            'error' => true,
            'order_status' => OrderStatuses::ERROR->value,
            'message' => "Ошибка сервера при обработке оплаты по заказу № {$orderId}",
            'status' => 500,
        ];
    }
}
