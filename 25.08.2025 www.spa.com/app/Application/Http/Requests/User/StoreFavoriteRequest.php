<?php

namespace App\Application\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreFavoriteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:master,ad,service',
            'item_id' => 'required|integer|min:1',
            'item_data' => 'nullable|array'
        ];
    }
    
    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Тип элемента обязателен',
            'type.in' => 'Неподдерживаемый тип элемента',
            'item_id.required' => 'ID элемента обязателен',
            'item_id.integer' => 'ID элемента должен быть числом',
            'item_id.min' => 'ID элемента должен быть положительным числом'
        ];
    }
}