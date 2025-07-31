<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

/**
 * Валидация данных для создания бронирования
 */
class StoreBookingRequest extends FormRequest
{
    /**
     * Определяем авторизован ли пользователь для выполнения этого запроса
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Правила валидации
     */
    public function rules(): array
    {
        return [
            'master_profile_id' => 'required|exists:master_profiles,id',
            'service_id' => 'required|exists:services,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'booking_time' => 'required|date_format:H:i',
            'client_name' => 'required|string|max:255',
            'client_phone' => [
                'required',
                'string',
                'max:20',
                'regex:/^[\+]?[0-9\s\-\(\)]{10,20}$/'
            ],
            'client_email' => 'nullable|email|max:255',
            'client_comment' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:500',
            'address_details' => 'nullable|string|max:255',
            'service_location' => 'required|in:home,salon',
            'payment_method' => 'required|in:cash,card,online,transfer'
        ];
    }

    /**
     * Кастомные сообщения об ошибках
     */
    public function messages(): array
    {
        return [
            'master_profile_id.required' => 'Необходимо выбрать мастера',
            'master_profile_id.exists' => 'Выбранный мастер не найден',
            'service_id.required' => 'Необходимо выбрать услугу',
            'service_id.exists' => 'Выбранная услуга не найдена',
            'booking_date.required' => 'Необходимо выбрать дату',
            'booking_date.date' => 'Неверный формат даты',
            'booking_date.after_or_equal' => 'Дата не может быть в прошлом',
            'booking_time.required' => 'Необходимо выбрать время',
            'booking_time.date_format' => 'Неверный формат времени (ЧЧ:ММ)',
            'client_name.required' => 'Укажите ваше имя',
            'client_name.max' => 'Имя не должно превышать 255 символов',
            'client_phone.required' => 'Укажите номер телефона',
            'client_phone.regex' => 'Неверный формат номера телефона',
            'client_email.email' => 'Неверный формат email',
            'client_comment.max' => 'Комментарий не должен превышать 500 символов',
            'address.max' => 'Адрес не должен превышать 500 символов',
            'address_details.max' => 'Детали адреса не должны превышать 255 символов',
            'service_location.required' => 'Необходимо выбрать место оказания услуги',
            'service_location.in' => 'Неверное место оказания услуги',
            'payment_method.required' => 'Необходимо выбрать способ оплаты',
            'payment_method.in' => 'Неверный способ оплаты'
        ];
    }

    /**
     * Подготовить данные для валидации
     */
    protected function prepareForValidation(): void
    {
        // Очищаем и форматируем номер телефона
        if ($this->has('client_phone')) {
            $phone = preg_replace('/[^\d\+]/', '', $this->client_phone);
            $this->merge(['client_phone' => $phone]);
        }

        // Форматируем время в правильный формат
        if ($this->has('booking_time')) {
            $time = $this->booking_time;
            if (strlen($time) === 5 && strpos($time, ':') === 2) {
                // Время уже в формате HH:MM
            } else {
                // Пытаемся распарсить и отформатировать
                try {
                    $parsedTime = Carbon::createFromFormat('H:i', $time);
                    $this->merge(['booking_time' => $parsedTime->format('H:i')]);
                } catch (\Exception $e) {
                    // Оставляем как есть, валидация поймает ошибку
                }
            }
        }
    }

    /**
     * Дополнительная валидация с учетом бизнес-логики
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Проверяем, что время бронирования не в прошлом
            if ($this->has('booking_date') && $this->has('booking_time')) {
                try {
                    $bookingDateTime = Carbon::parse($this->booking_date . ' ' . $this->booking_time);
                    
                    if ($bookingDateTime->isPast()) {
                        $validator->errors()->add('booking_time', 'Нельзя забронировать время в прошлом');
                    }
                    
                    // Проверяем минимальное время до бронирования (2 часа)
                    if ($bookingDateTime->diffInHours(now()) < 2) {
                        $validator->errors()->add('booking_time', 'Бронирование возможно минимум за 2 часа');
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add('booking_time', 'Неверный формат даты или времени');
                }
            }

            // Проверяем, что услуга принадлежит выбранному мастеру
            if ($this->has('master_profile_id') && $this->has('service_id')) {
                $masterProfile = \App\Models\MasterProfile::find($this->master_profile_id);
                if ($masterProfile) {
                    $serviceExists = $masterProfile->services()
                        ->where('id', $this->service_id)
                        ->exists();
                    
                    if (!$serviceExists) {
                        $validator->errors()->add('service_id', 'Выбранная услуга недоступна у этого мастера');
                    }
                }
            }

            // Проверяем адрес для выездных услуг
            if ($this->service_location === 'home' && empty($this->address)) {
                $validator->errors()->add('address', 'Для выездной услуги необходимо указать адрес');
            }
        });
    }

    /**
     * Получить очищенные данные для создания бронирования
     */
    public function getBookingData(): array
    {
        return [
            'master_profile_id' => $this->master_profile_id,
            'service_id' => $this->service_id,
            'booking_date' => $this->booking_date,
            'booking_time' => $this->booking_time,
            'client_name' => $this->client_name,
            'client_phone' => $this->client_phone,
            'client_email' => $this->client_email,
            'client_comment' => $this->client_comment,
            'address' => $this->address,
            'address_details' => $this->address_details,
            'service_location' => $this->service_location,
            'payment_method' => $this->payment_method,
        ];
    }
} 