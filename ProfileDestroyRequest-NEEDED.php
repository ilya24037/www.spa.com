<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request для удаления профиля пользователя
 * СОЗДАН согласно CLAUDE.md принципам безопасности
 */
class ProfileDestroyRequest extends FormRequest
{
    /**
     * Определить может ли пользователь делать этот запрос
     */
    public function authorize(): bool
    {
        return true; // Авторизация через middleware
    }

    /**
     * Правила валидации для удаления профиля
     */
    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'current_password', // Проверка текущего пароля
            ],
            'confirmation' => [
                'required',
                'string',
                'in:DELETE,УДАЛИТЬ', // Требуем точное подтверждение
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500'
            ]
        ];
    }

    /**
     * Сообщения об ошибках на русском языке
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Введите текущий пароль для подтверждения',
            'password.current_password' => 'Неверный пароль',
            
            'confirmation.required' => 'Введите подтверждение для удаления аккаунта',
            'confirmation.in' => 'Для подтверждения введите "DELETE" или "УДАЛИТЬ"',
            
            'reason.max' => 'Причина удаления не должна превышать 500 символов',
        ];
    }

    /**
     * Имена полей для отображения в ошибках
     */
    public function attributes(): array
    {
        return [
            'password' => 'пароль',
            'confirmation' => 'подтверждение',
            'reason' => 'причина удаления',
        ];
    }

    /**
     * Подготовка данных для валидации
     */
    protected function prepareForValidation(): void
    {
        // Приводим подтверждение к верхнему регистру
        if ($this->has('confirmation')) {
            $this->merge([
                'confirmation' => strtoupper(trim($this->confirmation))
            ]);
        }
    }

    /**
     * Дополнительная валидация после основных правил
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Проверяем что пользователь не имеет активных бронирований
            if ($this->user()->hasActiveBookings()) {
                $validator->errors()->add(
                    'active_bookings', 
                    'Нельзя удалить аккаунт с активными бронированиями'
                );
            }

            // Проверяем что пользователь не является мастером с активными услугами
            if ($this->user()->isMaster()) {
                $masterProfile = $this->user()->getMasterProfile();
                if ($masterProfile && $masterProfile->hasActiveServices()) {
                    $validator->errors()->add(
                        'active_services', 
                        'Сначала деактивируйте все услуги в профиле мастера'
                    );
                }
            }
        });
    }
}