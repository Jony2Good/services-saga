<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserNotificationResourse;
use Illuminate\Http\Request;
use App\Models\UserOrderNotification;
use Tymon\JWTAuth\Facades\JWTAuth;


class UserNotificationController extends Controller
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
    
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
         $userId = $this->getUserIdFromJwt($request);

         $notifications = UserOrderNotification::where('user_id', $userId)->get();

         return UserNotificationResourse::collection($notifications);
    }
}
