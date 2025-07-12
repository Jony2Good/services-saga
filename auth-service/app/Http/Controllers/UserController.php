<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use App\Http\Resources\UserResource;

class UserController extends Controller
{   

    /**
     * Update the specified resource in storage.
     */
    public function update(UserUpdateRequest $request)
    {
        $validated = $request->validated();      
                
        $user = User::findOrFail(auth()->id());
        $user->update($validated);

        return response()->json([
            'message' => 'Данные пользователя изменены',
            'user' => new UserResource($user)
        ], 200, [], JSON_UNESCAPED_UNICODE); 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        $user = User::findOrFail(auth()->id());
        $user->delete(); 

        return response()->json([
            'message' => 'Пользователь успешно удален',
            'user' => new UserResource($user)
        ], 200, [], JSON_UNESCAPED_UNICODE); 
    }
}
