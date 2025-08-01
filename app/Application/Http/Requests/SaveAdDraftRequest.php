<?php

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request для сохранения черновика объявления
 * Минимальная валидация - черновик может быть неполным
 */
class SaveAdDraftRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Правила валидации для черновика (минимальные)
     */
    public function rules(): array
    {
        return [
            'id' => 'nullable|integer|exists:ads,id',
            'category' => 'nullable|string|max:100',
            'title' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:200',
            'clients' => 'nullable|array',
            'service_location' => 'nullable|array',
            'outcall_locations' => 'nullable|array',
            'taxi_option' => 'nullable|string|in:separately,included',
            'work_format' => 'nullable|string|in:individual,duo,group',
            'service_provider' => 'nullable|array',
            'experience' => 'nullable|string|in:3260137,3260142,3260146,3260149,3260152',
            'education_level' => 'nullable|string|in:2,3,4,5,6,7',
            'features' => 'nullable|array',
            'additional_features' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:5000',
            'price' => 'nullable|numeric|min:0|max:1000000',
            'price_unit' => 'nullable|string|in:service,hour,minute,day',
            'is_starting_price' => 'nullable|array',
            'contacts_per_hour' => 'nullable|string|in:1,2,3,4,5,6',
            'discount' => 'nullable|numeric|min:0|max:100',
            'new_client_discount' => 'nullable|numeric|min:0|max:100',
            'gift' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:500',
            'travel_area' => 'nullable|string|max:200',
            'phone' => 'nullable|string|max:20',
            'contact_method' => 'nullable|string|in:any,calls,messages',
            'whatsapp' => 'nullable|string|max:20',
            'telegram' => 'nullable|string|max:100',
            
            // Физические параметры
            'age' => 'nullable|integer|min:18|max:99',
            'height' => 'nullable|integer|min:140|max:220',
            'weight' => 'nullable|integer|min:40|max:200',
            'breast_size' => 'nullable|string|max:10',
            'hair_color' => 'nullable|string|max:50',
            'eye_color' => 'nullable|string|max:50',
            'appearance' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'has_girlfriend' => 'nullable|boolean',
            
            // Услуги и расписание
            'services' => 'nullable|array',
            'services_additional_info' => 'nullable|string|max:2000',
            'schedule' => 'nullable|array',
            'schedule_notes' => 'nullable|string|max:1000',
            
            // Медиа
            'photos' => 'nullable|array|max:20',
            'video' => 'nullable|array',
            'show_photos_in_gallery' => 'nullable|boolean',
            'allow_download_photos' => 'nullable|boolean',
            'watermark_photos' => 'nullable|boolean',
        ];
    }

    /**
     * Сообщения об ошибках (только критические для черновика)
     */
    public function messages(): array
    {
        return [
            'title.max' => 'Название не должно превышать 255 символов',
            'description.max' => 'Описание не должно превышать 5000 символов',
            'price.numeric' => 'Стоимость должна быть числом',
            'price.min' => 'Стоимость не может быть отрицательной',
            'age.min' => 'Возраст должен быть не менее 18 лет',
            'photos.max' => 'Максимум 20 фотографий',
        ];
    }

    /**
     * Подготовить данные для валидации
     */
    protected function prepareForValidation(): void
    {
        // Устанавливаем title по умолчанию, если не указан
        if (!$this->has('title') || empty($this->title)) {
            $this->merge([
                'title' => 'Черновик объявления'
            ]);
        }
    }
}