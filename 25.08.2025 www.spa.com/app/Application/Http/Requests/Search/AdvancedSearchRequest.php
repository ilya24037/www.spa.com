<?php

namespace App\Application\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class AdvancedSearchRequest extends FormRequest
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
            'type' => 'required|string|in:ads,masters,services,global',
            'filters' => 'array',
            'filters.*' => 'string',
            'location' => 'array',
            'location.lat' => 'required_with:location|numeric|between:-90,90',
            'location.lng' => 'required_with:location|numeric|between:-180,180',
            'sort' => 'string|in:relevance,price_asc,price_desc,date,rating,popularity,distance',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Тип поиска обязателен',
            'type.in' => 'Недопустимый тип поиска',
            'location.lat.required_with' => 'Широта обязательна при указании местоположения',
            'location.lng.required_with' => 'Долгота обязательна при указании местоположения',
            'sort.in' => 'Недопустимый тип сортировки',
            'per_page.max' => 'Максимум 100 результатов на страницу'
        ];
    }
}