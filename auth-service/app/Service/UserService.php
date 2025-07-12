<?php

namespace App\Service;
use App\Models\User;

class UserService
{
    public static function getUserEmail(int $id)
    {
        return [
            'user_id' => $id,
            'email' => User::where('id', $id)->value('email') 
        ];
    }
}
