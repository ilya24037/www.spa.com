<?php

namespace App\Application\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request для массовых действий администратора
 * Следует принципам SOLID и DRY
 */
class BulkActionRequest extends FormRequest
{
    /**
     * Доступные действия для массовых операций
     */
    const AVAILABLE_ACTIONS = [
        'approve',
        'reject',
        'block',
        'archive',
        'delete'
    ];

    /**
     * Максимальное количество объявлений для массовой операции
     */
    const MAX_BULK_ITEMS = 100;

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
            'ids' => [
                'required',
                'array',
                'min:1',
                'max:' . self::MAX_BULK_ITEMS
            ],
            'ids.*' => [
                'required',
                'integer',
                'distinct',
                'exists:ads,id'
            ],
            'action' => [
                'required',
                'string',
                Rule::in(self::AVAILABLE_ACTIONS)
            ],
            'reason' => [
                'required_if:action,reject,block',
                'nullable',
                'string',
                'max:500'
            ]
        ];
    }

    /**
     * Сообщения об ошибках валидации
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Не выбраны объявления для действия',
            'ids.array' => 'Некорректный формат данных',
            'ids.min' => 'Выберите хотя бы одно объявление',
            'ids.max' => 'Можно выбрать не более ' . self::MAX_BULK_ITEMS . ' объявлений за раз',
            'ids.*.exists' => 'Одно или несколько объявлений не найдены',
            'ids.*.distinct' => 'Обнаружены дубликаты объявлений',
            'action.required' => 'Не указано действие',
            'action.in' => 'Недопустимое действие',
            'reason.required_if' => 'Укажите причину отклонения или блокировки',
            'reason.max' => 'Причина не должна превышать 500 символов',
        ];
    }

    /**
     * Получить описание действия для логирования
     */
    public function getActionDescription(): string
    {
        $descriptions = [
            'approve' => 'Массовое одобрение',
            'reject' => 'Массовое отклонение',
            'block' => 'Массовая блокировка',
            'archive' => 'Массовая архивация',
            'delete' => 'Массовое удаление',
        ];

        return $descriptions[$this->action] ?? $this->action;
    }

    /**
     * Проверка: критическое ли действие
     */
    public function isCriticalAction(): bool
    {
        return in_array($this->action, ['delete', 'block']);
    }

    /**
     * Атрибуты для валидации
     */
    public function attributes(): array
    {
        return [
            'ids' => 'объявления',
            'action' => 'действие',
            'reason' => 'причина',
        ];
    }
}