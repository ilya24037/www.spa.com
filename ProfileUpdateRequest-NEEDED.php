<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request для обновления профиля пользователя
 * СОЗДАН согласно CLAUDE.md принципам
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Определить может ли пользователь делать этот запрос
     */
    public function authorize(): bool
    {
        return true; // Авторизация через middleware
    }

    /**
     * Правила валидации для обновления профиля
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Zа-яА-ЯёЁ\s]+$/u' // Только буквы и пробелы
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+?[1-9]\d{1,14}$/', // Международный формат
                'max:20'
            ],
            'birth_date' => [
                'nullable',
                'date',
                'before:today',
                'after:1900-01-01'
            ],
            'city' => [
                'nullable',
                'string',
                'max:100'
            ],
            'about' => [
                'nullable',
                'string',
                'max:1000'
            ]
        ];
    }

    /**
     * Сообщения об ошибках на русском языке
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Поле "Имя" обязательно для заполнения',
            'name.min' => 'Имя должно содержать минимум 2 символа',
            'name.max' => 'Имя не должно превышать 255 символов',
            'name.regex' => 'Имя может содержать только буквы и пробелы',
            
            'email.required' => 'Поле "Email" обязательно для заполнения',
            'email.email' => 'Введите корректный email адрес',
            'email.unique' => 'Пользователь с таким email уже существует',
            'email.max' => 'Email не должен превышать 255 символов',
            
            'phone.regex' => 'Введите корректный номер телефона',
            'phone.max' => 'Номер телефона слишком длинный',
            
            'birth_date.date' => 'Введите корректную дату рождения',
            'birth_date.before' => 'Дата рождения не может быть в будущем',
            'birth_date.after' => 'Введите реальную дату рождения',
            
            'city.max' => 'Название города не должно превышать 100 символов',
            'about.max' => 'Описание не должно превышать 1000 символов',
        ];
    }

    /**
     * Имена полей для отображения в ошибках
     */
    public function attributes(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email',
            'phone' => 'телефон',
            'birth_date' => 'дата рождения',
            'city' => 'город',
            'about' => 'о себе',
        ];
    }

    /**
     * Подготовка данных для валидации
     */
    protected function prepareForValidation(): void
    {
        // Очищаем телефон от лишних символов
        if ($this->has('phone') && $this->phone) {
            $this->merge([
                'phone' => preg_replace('/[^\d+]/', '', $this->phone)
            ]);
        }

        // Очищаем имя от лишних пробелов
        if ($this->has('name')) {
            $this->merge([
                'name' => trim(preg_replace('/\s+/', ' ', $this->name))
            ]);
        }
    }
}