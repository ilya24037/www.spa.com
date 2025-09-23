<?php

namespace App\Application\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request для редактирования объявлений администратором
 * Следует принципам SOLID - единственная ответственность за валидацию
 */
class UpdateAdByAdminRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для этого запроса
     */
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|max:255|min:3',
            'description' => 'sometimes|string|max:5000|min:10',
            'starting_price' => 'sometimes|numeric|min:0|max:999999',
            'address' => 'sometimes|string|max:500',
            'phone' => 'sometimes|string|regex:/^[\d\s\+\-\(\)]+$/|max:20',
            'status' => 'sometimes|string|in:active,draft,rejected,pending_moderation,archived,blocked',
            'is_published' => 'sometimes|boolean',
            'moderation_reason' => 'sometimes|nullable|string|max:500',
        ];
    }

    /**
     * Сообщения об ошибках валидации
     */
    public function messages(): array
    {
        return [
            'title.min' => 'Заголовок должен быть не менее 3 символов',
            'title.max' => 'Заголовок не должен превышать 255 символов',
            'description.min' => 'Описание должно быть не менее 10 символов',
            'description.max' => 'Описание не должно превышать 5000 символов',
            'starting_price.min' => 'Цена не может быть отрицательной',
            'starting_price.max' => 'Цена слишком большая',
            'phone.regex' => 'Неверный формат телефона',
            'status.in' => 'Недопустимый статус объявления',
        ];
    }

    /**
     * Подготовка данных для валидации
     */
    protected function prepareForValidation(): void
    {
        // Очищаем телефон от лишних символов
        if ($this->has('phone')) {
            $this->merge([
                'phone' => preg_replace('/[^\d\+]/', '', $this->phone)
            ]);
        }

        // Приводим цену к числу
        if ($this->has('starting_price')) {
            $this->merge([
                'starting_price' => (float) str_replace(',', '.', $this->starting_price)
            ]);
        }
    }
}