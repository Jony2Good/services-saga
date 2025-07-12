<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
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
            'dishes' => 'required|array',
            'dishes.*.id' => 'required|integer|exists:dishes,id',
            'dishes.*.amount' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'dishes.required' => 'Поле dishes не должно быть пустым',
            'dishes.array' => 'Поле dishes должно быть массивом',
            'dishes.*.id.required' => 'Поле ID блюда обязательно для заполнения в каждом элементе массива dishes.',
            'dishes.*.id.integer' => 'Поле ID блюда должно быть целым числом в каждом элементе массива dishes.',
            'dishes.*.id.exists' => 'Выбранное блюдо не существует в таблице dishes.',

            'dishes.*.amount.required' => 'Поле amount обязательно для заполнения в каждом элементе массива dishes.',
            'dishes.*.amount.integer' => 'Поле amount должно быть целым числом в каждом элементе массива dishes.',
            'dishes.*.amount.min' => 'Поле amount должно быть не менее 1 в каждом элементе массива dishes.'
        ];
    }
}
