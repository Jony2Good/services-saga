<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;
use App\Http\Requests\UserRegisterRequest;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Events\EventPublisher;
use App\Jobs\ProduceUserRegistryJob;

class AuthController extends Controller
{
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(Auth::refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::factory()->getTTL() * 60
        ]);
    }

    public function register(UserRegisterRequest $request)
    {
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ];

        $user = User::create($userData);

        $jobData = [
            'user_id' => $user->id,
            'registrated' => true,        
        ];
        
        EventPublisher::handle($jobData);

        return response()->json([
            'message' => 'Пользователь зарегестрирован',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
        ]);
    }

    public function validateToken()
    {
        try {
            if (!JWTAuth::parseToken()->authenticate()) {
                return response()->json(['error' => 'Пользователь не найден'], 404);
            }
            return response()->json(['valid' => true]);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Токен на стороне клиента сформирован с ошибкой'], 401);
        }
    }
}
