<?php

namespace App\Application\Http\Requests\Ad;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request для создания объявления
 */
class CreateAdRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения этого запроса
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Правила валидации для запроса
     */
    public function rules(): array
    {
        return [
            // Параметры мастера - ОБЯЗАТЕЛЬНЫЕ (6 полей)
            'title' => 'required|string|max:255|min:2',
            'age' => 'required|integer|min:18|max:99',
            'height' => 'required|integer|min:140|max:220',
            'weight' => 'required|integer|min:40|max:200',
            'breast_size' => 'required|string|max:10',
            'hair_color' => 'required|string|max:50',
            
            // Контакты - ОБЯЗАТЕЛЬНЫЕ
            'phone' => 'required|string|regex:/^[+]?[0-9\s\-\(\)]{10,20}$/',
            
            // Услуги - ОБЯЗАТЕЛЬНЫЕ (минимум одна)
            'services' => 'required|array',
            'services.*' => 'array',
            
            // Основная информация - ОБЯЗАТЕЛЬНЫЕ
            'service_provider' => 'required|array|min:1',
            'service_provider.*' => 'string|max:100',
            'work_format' => 'required|string|in:individual,duo,group',
            'clients' => 'required|array|min:1',
            'clients.*' => 'string|max:50',
            'client_age_from' => 'nullable|integer|min:18|max:120',
            
            // Цены - ОБЯЗАТЕЛЬНЫЕ (проверка в withValidator)
            'prices' => 'required|array',
            'prices.apartments_1h' => 'nullable|numeric|min:0|max:1000000',
            'prices.apartments_2h' => 'nullable|numeric|min:0|max:1000000',
            'prices.apartments_night' => 'nullable|numeric|min:0|max:1000000',
            'prices.apartments_express' => 'nullable|numeric|min:0|max:1000000',
            'prices.outcall_1h' => 'nullable|numeric|min:0|max:1000000',
            'prices.outcall_2h' => 'nullable|numeric|min:0|max:1000000',
            'prices.outcall_night' => 'nullable|numeric|min:0|max:1000000',
            'prices.outcall_express' => 'nullable|numeric|min:0|max:1000000',
            
            // Параметры мастера - НЕОБЯЗАТЕЛЬНЫЕ
            'eye_color' => 'nullable|string|max:50',
            'nationality' => 'nullable|string|max:100',
            'bikini_zone' => 'nullable|string|max:50',
            'appearance' => 'nullable|string|max:100',
            'has_girlfriend' => 'nullable|boolean',
            
            // Ранее обязательные, теперь НЕОБЯЗАТЕЛЬНЫЕ (KISS принцип)
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
            'experience' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'travel_area' => 'nullable|string|max:200',
            
            // Остальные необязательные поля
            'specialty' => 'nullable|string|max:200',
            'service_location' => 'nullable|array',
            'service_location.*' => 'string|in:home,salon,both',
            'outcall_locations' => 'nullable|array',
            'outcall_locations.*' => 'string|max:100',
            'taxi_option' => 'nullable|string|in:separately,included',
            'features' => 'nullable|array',
            'features.*' => 'string|max:100',
            'additional_features' => 'nullable|string|max:1000',
            'price' => 'nullable|numeric|min:0|max:1000000',
            'price_unit' => 'nullable|string|in:service,hour,minute,day',
            'is_starting_price' => 'nullable|array',
            'starting_price' => 'nullable|string',
            'contacts_per_hour' => 'nullable|string|in:1,2,3,4,5,6',
            'discount' => 'nullable|numeric|min:0|max:100',
            'new_client_discount' => 'nullable|numeric|min:0|max:100',
            'gift' => 'nullable|string|max:500',
            'contact_method' => 'nullable|string|in:any,calls,messages',
            'whatsapp' => 'nullable|string|regex:/^[+]?[0-9\s\-\(\)]{10,20}$/',
            'telegram' => 'nullable|string|max:100',
            'services_additional_info' => 'nullable|string|max:2000',
            'schedule' => 'nullable|array',
            'schedule_notes' => 'nullable|string|max:1000',
            'online_booking' => 'nullable|boolean',
            
            // Медиа
            'photos' => 'required|array|min:3|max:20',
            'photos.*' => 'file|mimes:jpeg,jpg,png,bmp,gif,webp,heic,heif|max:10240',
            'video' => 'nullable|array',
            'show_photos_in_gallery' => 'nullable|boolean',
            'allow_download_photos' => 'nullable|boolean',
            'watermark_photos' => 'nullable|boolean',
            
            // География
            'geo' => 'nullable|array',
            'geo.city' => 'nullable|string',
            'geo.address' => 'nullable|string',
            'geo.coordinates' => 'nullable|array',
            'geo.zones' => 'nullable|array',
            'geo.metro_stations' => 'nullable|array',
            
            // FAQ
            'faq' => 'nullable|array',
        ];
    }

    /**
     * Кастомные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            // Параметры мастера - ОБЯЗАТЕЛЬНЫЕ
            'title.required' => 'Имя обязательно',
            'title.min' => 'Имя должно содержать минимум 2 символа',
            'title.max' => 'Имя не должно превышать 255 символов',
            
            'age.required' => 'Возраст обязателен',
            'age.integer' => 'Возраст должен быть числом',
            'age.min' => 'Возраст должен быть не менее 18 лет',
            'age.max' => 'Некорректный возраст',
            
            'height.required' => 'Рост обязателен',
            'height.integer' => 'Рост должен быть числом',
            'height.min' => 'Рост должен быть не менее 140 см',
            'height.max' => 'Рост должен быть не более 220 см',
            
            'weight.required' => 'Вес обязателен',
            'weight.integer' => 'Вес должен быть числом',
            'weight.min' => 'Вес должен быть не менее 40 кг',
            'weight.max' => 'Вес должен быть не более 200 кг',
            
            'breast_size.required' => 'Размер груди обязателен',
            'breast_size.max' => 'Размер груди не должен превышать 10 символов',
            
            'hair_color.required' => 'Цвет волос обязателен',
            'hair_color.max' => 'Цвет волос не должен превышать 50 символов',
            
            // Контакты
            'phone.required' => 'Телефон обязателен',
            'phone.regex' => 'Некорректный формат номера телефона',
            
            // Услуги
            'services.required' => 'Выберите хотя бы одну услугу',
            'services.array' => 'Некорректный формат услуг',
            
            // Основная информация
            'service_provider.required' => 'Укажите, кто оказывает услуги',
            'service_provider.min' => 'Выберите хотя бы один вариант',
            
            'work_format.required' => 'Выберите формат работы',
            'work_format.in' => 'Некорректный формат работы',
            
            'clients.required' => 'Укажите ваших клиентов',
            'clients.min' => 'Выберите хотя бы одну категорию клиентов',
            
            // Цены
            'prices.required' => 'Укажите стоимость услуг',
            'prices.array' => 'Некорректный формат цен',
            'prices.apartments_1h.numeric' => 'Цена должна быть числом',
            'prices.outcall_1h.numeric' => 'Цена должна быть числом',
            
            // Медиа
            'photos.required' => 'Добавьте минимум 3 фотографии',
            'photos.min' => 'Минимум 3 фотографии',
            'photos.max' => 'Максимум 20 фотографий',
            'photos.*.max' => 'Размер фото не должен превышать 10 МБ',
            'photos.*.mimes' => 'Неподдерживаемый формат. Разрешены: JPG, PNG, BMP, GIF, WebP, HEIC',
            
            // Остальные поля
            'whatsapp.regex' => 'Некорректный формат номера WhatsApp',
            'discount.min' => 'Скидка не может быть отрицательной',
            'discount.max' => 'Скидка не может быть больше 100%',
            'new_client_discount.min' => 'Скидка не может быть отрицательной',
            'new_client_discount.max' => 'Скидка не может быть больше 100%',
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

    /**
     * Дополнительная валидация после основных правил
     */
    protected function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $prices = $this->input('prices', []);
            
            // Проверяем наличие хотя бы одной цены за час (апартаменты или выезд)
            $hasApartmentPrice = isset($prices['apartments_1h']) && $prices['apartments_1h'] > 0;
            $hasOutcallPrice = isset($prices['outcall_1h']) && $prices['outcall_1h'] > 0;
            
            if (!$hasApartmentPrice && !$hasOutcallPrice) {
                $validator->errors()->add('prices', 'Укажите стоимость за 1 час (апартаменты или выезд)');
            }
            
            // Проверяем наличие хотя бы одной выбранной услуги
            $services = $this->input('services', []);
            $hasSelectedService = false;
            
            if (is_array($services)) {
                foreach ($services as $categoryServices) {
                    if (is_array($categoryServices)) {
                        foreach ($categoryServices as $service) {
                            if (isset($service['enabled']) && $service['enabled']) {
                                $hasSelectedService = true;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            if (!$hasSelectedService) {
                $validator->errors()->add('services', 'Выберите хотя бы одну услугу');
            }
        });
    }

    /**
     * Получить валидированные данные с правильными типами
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        
        // Преобразуем булевы значения
        $booleanFields = ['has_girlfriend', 'show_photos_in_gallery', 'allow_download_photos', 'watermark_photos', 'online_booking'];
        foreach ($booleanFields as $field) {
            if (isset($validated[$field])) {
                $validated[$field] = (bool) $validated[$field];
            }
        }
        
        return $validated;
    }
}