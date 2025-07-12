<?php
namespace App\Traits;

use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

trait ExtractUserIdFromJwt
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

    public function getUserDataFromJwt(Request $request, string $data): ?string
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $token = substr($authHeader, 7);
        $payload = JWTAuth::setToken($token)->getPayload();        

        return $payload->get($data);
    }
}