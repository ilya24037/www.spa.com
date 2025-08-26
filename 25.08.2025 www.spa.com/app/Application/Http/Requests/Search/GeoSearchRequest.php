<?php

namespace App\Application\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class GeoSearchRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'required|numeric|min:0.1|max:100',
            'type' => 'string|in:ads,masters,services',
            'limit' => 'integer|min:1|max:100'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lat.required' => 'Широта обязательна для геопоиска',
            'lat.between' => 'Широта должна быть от -90 до 90',
            'lng.required' => 'Долгота обязательна для геопоиска',
            'lng.between' => 'Долгота должна быть от -180 до 180',
            'radius.required' => 'Радиус поиска обязателен',
            'radius.min' => 'Минимальный радиус поиска 0.1 км',
            'radius.max' => 'Максимальный радиус поиска 100 км',
            'type.in' => 'Недопустимый тип поиска',
            'limit.max' => 'Максимум 100 результатов за запрос'
        ];
    }
}