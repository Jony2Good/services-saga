<?php

namespace App\Service;

use App\Models\BillingAccount;
use Illuminate\Support\Facades\DB;

class OrderPaymentService
{
    /**
     * 
     * TODO:
     * 1. Ответить в notification-service
     * 2. записать в таблицу что деньги списаны
     * 3. сделать в моделе константы для процессинга заказа
     * 4. при отсутствии средств ставить статус закончились средства
     * 5/ Сделать обработку статусов вначала тразакции
     * @param int $userId
     * @param int $totalPrice
     */
    public static function makePayment(int $userId, int $totalPrice): array
    {
        return DB::transaction(function () use ($userId, $totalPrice) {
            $account = BillingAccount::where('user_id', $userId)->lockForUpdate()->firstOrFail();

            $resultBalance = (int)$account['balance'] - $totalPrice;

            if ($resultBalance < 0) {
                return [
                    'error' => true,
                    'message' => 'На счету недостаточно средств'
                ];
            }

            $account->update(['balance' => $resultBalance]);



            return [
                'error' => false,
                'message' => 'Заказ оплачен'
            ];
        });
    }
}
