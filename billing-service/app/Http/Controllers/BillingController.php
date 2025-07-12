<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\BillingAccount;
use App\Http\Resources\BillingAccountResource;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class BillingController extends Controller
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

    public function store(CreateAccountRequest $request)
    {
        $validated = $request->validated();

        $account = BillingAccount::create([
            'user_id' => $validated['user'],
            'balance' => 0,
        ]);

        return response()->json([
            'message' => 'Успешно создан аккаунт и открыт счет',
            'account' => new BillingAccountResource($account)
        ], 200);
    }

    public function show(Request $request)
    {
        $userId = $this->getUserIdFromJwt($request);

        return BillingAccount::where('user_id', $userId)
            ->firstOrFail()
            ->toResource();
    }

    public function update(UpdateAccountRequest $request)
    {
        $validated = $request->validated();
        $userId = $this->getUserIdFromJwt($request);
        $account = BillingAccount::where('user_id', $userId)->firstOrFail();
        $account->update($validated);

        return response()->json([
            'message' => 'Баланс пополнен успешно',
            'account' => new BillingAccountResource($account)
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function pay(UpdateAccountRequest $request)
    {
        $validated = $request->validated();
        $userId = $this->getUserIdFromJwt($request);

        $account = BillingAccount::where('user_id', $userId)->lockForUpdate()->firstOrFail();
        $orderBalance = $validated['balance'];

        $resultBalance = (int)$account['balance'] - $orderBalance;

        if ($resultBalance < 0) {
            return response()->json([
                'message' => 'На счету недостаточно средств',
                'account' => new BillingAccountResource($account),
            ], 409, [], JSON_UNESCAPED_UNICODE);
        }

        $account->update(['balance' => $resultBalance]);

        return response()->json([
            'message' => 'Деньги успешно списаны со счета',
            'account' => new BillingAccountResource($account),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
