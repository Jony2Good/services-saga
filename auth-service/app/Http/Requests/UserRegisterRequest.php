<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|regex:/^[\p{Cyrillic}\s]+$/u|min:1|max:100',           
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:5'           
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Поле name обязательно для заполнения.',
            'name.string' => 'Поле name должно быть строкой.',
            'name.regex' => 'Поле name может содержать только кириллицу и пробельные символы.',
            'name.min' => 'name должно содержать минимум :min символ.',
            'name.max' => 'name не может быть длиннее :max символов.',
            
            'email.required' => 'Поле Электронная почта обязательно для заполнения.',
            'email.email' => 'Поле Электронная почта должно содержать действительный адрес электронной почты.',
            'email.unique' => 'Пользователь с указанной Электронной почтой уже существует в системе.',
            'email.max' => 'Адрес электронной почты не может быть длиннее :max символов.',

            'password.required' => 'Поле Пароль обязательно для заполнения.',
            'password.string' => 'Поле Пароль должно быть строкой.',
            'password.min' => 'Пароль должен содержать минимум :min символов.'
        ];           
    }
}
