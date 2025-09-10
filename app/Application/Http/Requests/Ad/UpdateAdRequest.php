<?php

namespace App\Application\Http\Requests\Ad;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request для обновления объявления
 */
class UpdateAdRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса
     */
    public function authorize(): bool
    {
        // Проверяем, что пользователь является владельцем объявления
        $ad = $this->route('ad');
        return auth()->check() && auth()->id() === $ad->user_id;
    }

    /**
     * Правила валидации для запроса
     */
    public function rules(): array
    {
        // Получаем объявление из роута
        $ad = $this->route('ad');
        
        // Если объявление активное - смягчаем валидацию
        $isActive = $ad && $ad->status === 'active';
        
        \Log::info('🟢 UpdateAdRequest: Определяем правила валидации', [
            'ad_id' => $ad?->id,
            'ad_status' => $ad?->status,
            'is_active' => $isActive,
            'validation_mode' => $isActive ? 'МЯГКАЯ (активное)' : 'СТРОГАЯ (новое/черновик)'
        ]);
        
        return [
            'title' => $isActive ? 'nullable|string|max:255|min:2' : 'required|string|max:255|min:2',
            'specialty' => 'nullable|string|max:200',
            'clients' => 'array',
            'clients.*' => 'string|max:50',
            'client_age_from' => 'nullable|integer|min:18|max:120',
            'service_location' => $isActive ? 'nullable|array' : 'required|array|min:1',
            'service_location.*' => 'string|in:home,salon,both',
            'outcall_locations' => 'nullable|array',
            'outcall_locations.*' => 'string|max:100',
            'taxi_option' => 'nullable|string|in:separately,included',
            'work_format' => $isActive ? 'nullable|string|in:individual,duo,group' : 'required|string|in:individual,duo,group',
            'service_provider' => 'nullable|array',
            'service_provider.*' => 'string|max:100',
            'experience' => $isActive ? 'nullable|string' : 'required|string|in:3260137,3260142,3260146,3260149,3260152',

            'features' => 'nullable|array',
            'features.*' => 'string|max:100',
            'additional_features' => 'nullable|string|max:1000',
            'description' => $isActive ? 'nullable|string|max:5000' : 'required|string|min:50|max:5000',
            'price' => $isActive ? 'nullable|numeric|min:0|max:1000000' : 'required|numeric|min:0|max:1000000',
            'price_unit' => $isActive ? 'nullable|string|in:service,hour,minute,day' : 'required|string|in:service,hour,minute,day',
            'is_starting_price' => 'nullable|array',
            'contacts_per_hour' => 'nullable|string|in:1,2,3,4,5,6',
            'discount' => 'nullable|numeric|min:0|max:100',
            'new_client_discount' => 'nullable|numeric|min:0|max:100',
            'gift' => 'nullable|string|max:500',
            'address' => $isActive ? 'nullable|string|max:500' : 'required|string|max:500',
            'travel_area' => $isActive ? 'nullable|string|max:200' : 'required|string|max:200',
            'phone' => $isActive ? 'nullable|string|regex:/^[+]?[0-9\s\-\(\)]{10,20}$/' : 'required|string|regex:/^[+]?[0-9\s\-\(\)]{10,20}$/',
            'contact_method' => $isActive ? 'nullable|string|in:any,calls,messages' : 'required|string|in:any,calls,messages',
            'whatsapp' => 'nullable|string|regex:/^[+]?[0-9\s\-\(\)]{10,20}$/',
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
            'bikini_zone' => 'nullable|string|max:50',
            'has_girlfriend' => 'nullable|boolean',
            
            // Услуги и расписание
            'services' => 'nullable|array',
            'services_additional_info' => 'nullable|string|max:2000',
            'schedule' => 'nullable|array',
            'schedule_notes' => 'nullable|string|max:1000',
            
            // Медиа
            'photos' => 'nullable|array|max:20',
            'photos.*' => 'string|max:1000',
            'video' => 'nullable|array',
            'show_photos_in_gallery' => 'nullable|boolean',
            'allow_download_photos' => 'nullable|boolean',
            'watermark_photos' => 'nullable|boolean',
            
            // FAQ
            'faq' => 'nullable|array',
            'faq.*' => 'nullable',
        ];
    }

    /**
     * Кастомные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Введите имя',
            'title.min' => 'Имя должно содержать минимум 2 символа',
            'title.max' => 'Имя не должно превышать 255 символов',

            'service_location.required' => 'Выберите место оказания услуг',
            'service_location.min' => 'Выберите хотя бы один тип услуг',
            'work_format.required' => 'Выберите формат работы',
            'experience.required' => 'Укажите опыт работы',
            'description.required' => 'Добавьте описание услуг',
            'description.min' => 'Описание должно содержать минимум 50 символов',
            'description.max' => 'Описание не должно превышать 5000 символов',
            'price.required' => 'Укажите стоимость услуг',
            'price.numeric' => 'Стоимость должна быть числом',
            'price.min' => 'Стоимость не может быть отрицательной',
            'price.max' => 'Слишком большая стоимость',
            'price_unit.required' => 'Выберите единицу измерения цены',
            'address.required' => 'Укажите адрес',
            'travel_area.required' => 'Укажите район выезда',
            'phone.required' => 'Укажите номер телефона',
            'phone.regex' => 'Некорректный формат номера телефона',
            'contact_method.required' => 'Выберите способ связи',
            'age.min' => 'Возраст должен быть не менее 18 лет',
            'age.max' => 'Некорректный возраст',
            'height.min' => 'Рост должен быть не менее 140 см',
            'height.max' => 'Рост должен быть не более 220 см',
            'weight.min' => 'Вес должен быть не менее 40 кг',
            'weight.max' => 'Вес должен быть не более 200 кг',
            'photos.max' => 'Максимум 20 фотографий',
        ];
    }

    /**
     * Подготовить данные для валидации
     */
    protected function prepareForValidation(): void
    {
        // Очищаем номер телефона от лишних символов
        if ($this->has('phone') && $this->phone) {
            $this->merge([
                'phone' => preg_replace('/[^\d+]/', '', $this->phone)
            ]);
        }
        
        // Очищаем WhatsApp от лишних символов
        if ($this->has('whatsapp') && $this->whatsapp) {
            $this->merge([
                'whatsapp' => preg_replace('/[^\d+]/', '', $this->whatsapp)
            ]);
        }
    }
}