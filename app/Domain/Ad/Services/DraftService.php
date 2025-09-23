<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Простой сервис для работы с черновиками
 * Следует принципу KISS - максимально простая логика
 */
class DraftService
{
    /**
     * Сохранить или обновить черновик
     * Принимает mixed $adId для совместимости с разными типами данных
     */
    public function saveOrUpdate(array $data, User $user, mixed $adId = null): Ad
    {
        // Логируем входящие данные для отслеживания проблемных полей
        Log::info('🔍 DraftService::saveOrUpdate - Входящие данные', [
            'has_work_format' => isset($data['work_format']),
            'work_format_value' => $data['work_format'] ?? null,
            'has_service_provider' => isset($data['service_provider']),
            'service_provider_value' => $data['service_provider'] ?? null,
            'has_whatsapp' => isset($data['whatsapp']),
            'whatsapp_value' => $data['whatsapp'] ?? null,
            'has_telegram' => isset($data['telegram']),
            'telegram_value' => $data['telegram'] ?? null,
            'all_keys' => array_keys($data)
        ]);

        // Подготавливаем данные
        $data = $this->prepareData($data);
        $data['user_id'] = $user->id;

        // Логируем данные после подготовки
        Log::info('🔍 DraftService::saveOrUpdate - После prepareData', [
            'has_work_format' => isset($data['work_format']),
            'work_format_value' => $data['work_format'] ?? null,
            'has_service_provider' => isset($data['service_provider']),
            'service_provider_value' => $data['service_provider'] ?? null,
            'has_whatsapp' => isset($data['whatsapp']),
            'whatsapp_value' => $data['whatsapp'] ?? null,
            'has_telegram' => isset($data['telegram']),
            'telegram_value' => $data['telegram'] ?? null
        ]);
        
        // Приводим ID к integer если он передан
        $adId = $adId ? (int) $adId : null;

        // 🎯 ЛОГИКА: используем переданный статус или draft по умолчанию
        if ($adId && $adId > 0) {
            $existingAd = Ad::find($adId);

            // Определяем статусы ожидания, для которых разрешаем изменение статуса
            $waitingStatuses = [
                AdStatus::REJECTED,
                AdStatus::PENDING_MODERATION,
                AdStatus::EXPIRED,
                AdStatus::WAITING_PAYMENT
            ];

            if ($existingAd && $existingAd->status !== AdStatus::DRAFT && !in_array($existingAd->status, $waitingStatuses)) {
                // Не меняем статус только для активных объявлений
                // Для черновиков и статусов ожидания разрешаем изменение
                unset($data['status']);
            } else {
                // Для черновиков и статусов ожидания используем переданный статус или draft
                $data['status'] = $data['status'] ?? AdStatus::DRAFT;
            }
        } else {
            // Новое объявление использует переданный статус или draft
            $data['status'] = $data['status'] ?? AdStatus::DRAFT;
        }

        // Если передан ID, ищем существующее объявление
        if ($adId && $adId > 0) {
            
            // Сначала попробуем найти объявление принадлежащее пользователю
            $ad = Ad::where('id', $adId)
                ->where('user_id', $user->id)
                ->first();
                
            if ($ad) {
                // Если статус меняется на 'active', устанавливаем is_published = false (на модерацию)
                if (isset($data['status']) && ($data['status'] === AdStatus::ACTIVE || $data['status'] === 'active')) {
                    $data['is_published'] = false;
                    \Log::info('🟢 DraftService: Устанавливаем статус active и is_published = false', [
                        'ad_id' => $ad->id,
                        'old_status' => $ad->status,
                        'new_status' => $data['status'],
                        'is_published' => $data['is_published'],
                        'status_type' => gettype($data['status'])
                    ]);
                }
                
                \Log::info('🟢 DraftService: Обновляем объявление', [
                    'ad_id' => $ad->id,
                    'data_keys' => array_keys($data),
                    'status' => $data['status'] ?? 'не указан',
                    'is_published' => $data['is_published'] ?? 'не указан'
                ]);
                
                $ad->update($data);
                $ad->wasRecentlyCreated = false; // Явно указываем что это обновление

                // Перезагружаем модель для проверки сохранения
                $ad->refresh();

                \Log::info('🟢 DraftService: Объявление обновлено', [
                    'ad_id' => $ad->id,
                    'new_status' => $ad->status,
                    'new_is_published' => $ad->is_published,
                    'saved_specialty' => $ad->specialty,
                    'saved_work_format' => $ad->work_format,
                    'saved_service_provider' => $ad->service_provider
                ]);
                
                return $ad;
            }
            
            // Если не нашли с проверкой пользователя, проверяем существует ли вообще
            $adWithoutUserCheck = Ad::find($adId);
            if ($adWithoutUserCheck) {
                // Объявление существует, но принадлежит другому пользователю
                
                // ВАЖНО: Если это ошибка авторизации, все равно пытаемся обновить
                // Возможно, проблема в том, что Auth::user() возвращает не того пользователя
                if (auth()->check() && auth()->id() == $adWithoutUserCheck->user_id) {
                    $adWithoutUserCheck->update($data);
                    $adWithoutUserCheck->wasRecentlyCreated = false;
                    return $adWithoutUserCheck;
                }
            }
        }
        
        // Создаем новый черновик
        Log::info('🟢 DraftService: Создание нового объявления', [
            'data_keys' => array_keys($data),
            'has_specialty' => isset($data['specialty']),
            'specialty' => $data['specialty'] ?? null,
            'has_work_format' => isset($data['work_format']),
            'work_format' => $data['work_format'] ?? null,
            'has_service_provider' => isset($data['service_provider']),
            'service_provider' => $data['service_provider'] ?? null
        ]);

        $ad = Ad::create($data);
        $ad->wasRecentlyCreated = true; // Явно указываем что это создание

        // Проверяем что сохранилось
        $ad->refresh();
        Log::info('✅ DraftService: Объявление создано и проверено', [
            'ad_id' => $ad->id,
            'saved_specialty' => $ad->specialty,
            'saved_work_format' => $ad->work_format,
            'saved_service_provider' => $ad->service_provider
        ]);

        return $ad;
    }

    /**
     * Подготовить черновик для отображения
     */
    public function prepareForDisplay(Ad $ad): array
    {
        // ✅ ПРИНУДИТЕЛЬНОЕ ЛОГИРОВАНИЕ ВХОДА
        Log::info("📸 DraftService::prepareForDisplay НАЧАЛО", [
            'ad_id' => $ad->id,
            'ad_status' => $ad->status,
            'ad_exists' => $ad->exists,
            'ad_attributes' => $ad->getAttributes(),
            'ad_keys' => array_keys($ad->getAttributes())
        ]);
        
        $data = $ad->toArray();
        
        // ВАЖНО: Убедимся, что ID всегда присутствует и имеет правильный тип
        $data['id'] = (int) $ad->id;
        
        // Декодируем JSON поля (включая service_provider для правильной загрузки при редактировании)
        $jsonFields = ['clients', 'service_provider', 'services', 'features', 'photos', 'video', 'geo', 'prices', 'schedule', 'faq'];
        
        // Логируем данные schedule для отладки
        if (isset($data['schedule'])) {
            Log::info("📅 DraftService::prepareForDisplay: Данные schedule", [
                'schedule_exists' => isset($data['schedule']),
                'schedule_value' => $data['schedule'],
                'schedule_type' => gettype($data['schedule']),
                'schedule_is_string' => is_string($data['schedule']),
                'schedule_is_array' => is_array($data['schedule']),
                'schedule_is_null' => is_null($data['schedule'])
            ]);
        } else {
            Log::info("📅 DraftService::prepareForDisplay: Поле schedule НЕ НАЙДЕНО в данных", [
                'available_fields' => array_keys($data),
                'data_keys_count' => count(array_keys($data))
            ]);
        }
        
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $decoded = json_decode($data[$field], true) ?: [];
                
                // ИСПРАВЛЕНИЕ: Для video преобразуем URL строки в объекты Video
                if ($field === 'video' && is_array($decoded)) {
                    $videoObjects = [];
                    foreach ($decoded as $index => $videoItem) {
                        if (is_string($videoItem)) {
                            // Преобразуем URL строку в объект Video
                            $videoObjects[] = [
                                'id' => 'video_' . $index . '_' . time(),
                                'url' => $videoItem,
                                'file' => null,
                                'isUploading' => false
                            ];
                        } else if (is_array($videoItem)) {
                            // Если уже объект, добавляем недостающие поля
                            $videoObjects[] = array_merge([
                                'id' => $videoItem['id'] ?? 'video_' . $index . '_' . time(),
                                'url' => null,
                                'file' => null,
                                'isUploading' => false
                            ], $videoItem);
                        }
                    }
                    $data[$field] = $videoObjects;
                } else {
                    $data[$field] = $decoded;
                }
                
                // Логируем результат декодирования для schedule
                if ($field === 'schedule') {
                    Log::info("📅 DraftService::prepareForDisplay: Результат декодирования schedule", [
                        'field' => $field,
                        'original_value' => $ad->getAttribute($field),
                        'decoded_value' => $data[$field],
                        'decoded_type' => gettype($data[$field]),
                        'decoded_is_array' => is_array($data[$field])
                    ]);
                }
            }
        }
        
        // При загрузке данных проверяем наличие outcall полей
        // Они могут быть как в prices (старый формат), так и в geo (новый формат)
        if (isset($data['geo']) && is_array($data['geo'])) {
            // Поля которые должны быть в geo
            $outcallFields = ['outcall_apartment', 'outcall_hotel', 'outcall_house', 
                           'outcall_sauna', 'outcall_office', 'taxi_included'];
            
            // Если поля есть в prices но не в geo - переносим (обратная совместимость)
            if (isset($data['prices']) && is_array($data['prices'])) {
                foreach ($outcallFields as $field) {
                    if (!isset($data['geo'][$field]) && isset($data['prices'][$field])) {
                        $data['geo'][$field] = $data['prices'][$field];
                    }
                }
            }
            
            // Убедимся что все outcall поля имеют правильный boolean тип
            foreach ($outcallFields as $field) {
                if (isset($data['geo'][$field])) {
                    $value = $data['geo'][$field];
                    
                    // Преобразуем в boolean
                    if ($value === '1' || $value === 1 || $value === true || $value === 'true') {
                        $data['geo'][$field] = true;
                    } elseif ($value === '0' || $value === 0 || $value === false || $value === 'false' || $value === null) {
                        $data['geo'][$field] = false;
                    } else {
                        $data['geo'][$field] = (bool)$value;
                    }
                }
            }
        }
        
        
        
        // Преобразуем другие boolean поля на верхнем уровне
        $topLevelBooleanFields = ['is_starting_price', 'online_booking'];
        foreach ($topLevelBooleanFields as $boolField) {
            if (isset($data[$boolField])) {
                $value = $data[$boolField];
                // Преобразуем '1', 1, true в true; '0', 0, false, null в false
                if ($value === '1' || $value === 1 || $value === true) {
                    $data[$boolField] = true;
                } else {
                    $data[$boolField] = false;
                }
            }
        }
        
        // Убедимся что title всегда присутствует (даже если пустое)
        if (!isset($data['title'])) {
            $data['title'] = '';
        }
        
        // Убедимся что description всегда присутствует (даже если пустое)
        if (!isset($data['description'])) {
            $data['description'] = '';
        }
        
        // Обработка starting_price - убедимся что поле передается во frontend
        if (!isset($data['starting_price'])) {
            $data['starting_price'] = null;
        }
        
        // ✅ ПРИНУДИТЕЛЬНОЕ ЛОГИРОВАНИЕ В КОНЦЕ
        Log::info("📸 DraftService::prepareForDisplay ЗАВЕРШЕНО", [
            'final_data_keys' => array_keys($data),
            'final_data_count' => count($data)
        ]);
        
        return $data;
    }

    /**
     * Удалить черновик
     */
    public function delete(Ad $ad): bool
    {
        // Только черновики можно удалять
        if ($ad->status !== AdStatus::DRAFT) {
            throw new \InvalidArgumentException('Только черновики можно удалять');
        }
        
        return $ad->delete();
    }

    /**
     * Валидация взаимоисключающих опций в FAQ
     * Обеспечивает что опция "Нет" не может быть выбрана вместе с другими опциями
     */
    private function validateFaqExclusivity($faq): array
    {
        if (!is_array($faq)) {
            return [];
        }
        
        // Вопросы с взаимоисключающей опцией "Нет" (questionId => value опции "Нет")
        $exclusiveNoQuestions = [
            'faq_2' => 4, // "Есть ласки и тактильный контакт?" - опция "Нет"
            'faq_3' => 4  // "Возможны встречи в формате GFE?" - опция "Нет"
        ];
        
        foreach ($exclusiveNoQuestions as $questionId => $noValue) {
            if (isset($faq[$questionId]) && is_array($faq[$questionId])) {
                $values = $faq[$questionId];
                
                // Если опция "Нет" выбрана вместе с другими опциями
                if (in_array($noValue, $values) && count($values) > 1) {
                    // Оставляем только "Нет", убираем все остальные опции
                    $faq[$questionId] = [$noValue];
                    
                    Log::info("FAQ взаимоисключение: Очищены конфликтующие опции", [
                        'question_id' => $questionId,
                        'original_values' => $values,
                        'cleaned_values' => [$noValue]
                    ]);
                }
            }
        }
        
        return $faq;
    }
    
    /**
     * Подготовить данные для сохранения
     */
    private function prepareData(array $data): array
    {
        // Логируем критические поля в начале обработки
        Log::info('📋 DraftService::prepareData - НАЧАЛО', [
            'work_format' => $data['work_format'] ?? 'НЕ ПЕРЕДАНО',
            'service_provider' => $data['service_provider'] ?? 'НЕ ПЕРЕДАНО',
            'whatsapp' => $data['whatsapp'] ?? 'НЕ ПЕРЕДАНО',
            'telegram' => $data['telegram'] ?? 'НЕ ПЕРЕДАНО',
            'photos' => $data['photos'] ?? 'НЕ ПЕРЕДАНО',
            'photos_type' => isset($data['photos']) ? gettype($data['photos']) : 'НЕ ПЕРЕДАНО',
            'photos_count' => isset($data['photos']) && is_array($data['photos']) ? count($data['photos']) : 'НЕ МАССИВ'
        ]);

        // Валидация взаимоисключающих опций в FAQ
        if (isset($data['faq'])) {
            $data['faq'] = $this->validateFaqExclusivity($data['faq']);
        }
        
        // Обработка JSON полей
        // ВАЖНО: Frontend может отправлять данные как JSON строки или как массивы
        // Нужно проверить и декодировать JSON строки обратно в массивы
        // ИСКЛЮЧАЕМ photos и video - они уже обработаны в AdController
        $jsonFields = ['clients', 'service_provider', 'features', 'services', 'schedule',
                       'geo', 'custom_travel_areas', 'prices', 'faq'];

        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                // Если это уже JSON строка - декодируем обратно в массив
                if (is_string($data[$field])) {
                    $decoded = json_decode($data[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $data[$field] = $decoded;
                    }
                }
                // Теперь данные в виде массива, модель сама закодирует через JsonFieldsTrait
            }
        }
        
        // Данные schedule уже обработаны в первом цикле, дополнительное логирование не нужно
        
        // Специальная обработка для photos и video - они уже обработаны в AdController
        if (isset($data['photos']) && is_array($data['photos'])) {
            // Фотографии уже обработаны в AdController::processPhotosFromRequest
            // Просто убеждаемся что это массив строк (путей к файлам)
            Log::info('📋 DraftService::prepareData - photos уже обработаны', [
                'photos_count' => count($data['photos']),
                'photos_sample' => array_slice($data['photos'], 0, 2)
            ]);
        }
        
        if (isset($data['video']) && is_array($data['video'])) {
            // Видео уже обработаны в AdController::processVideoFromRequest
            // Просто убеждаемся что это массив строк (путей к файлам)
            Log::info('📋 DraftService::prepareData - video уже обработаны', [
                'video_count' => count($data['video']),
                'video_sample' => array_slice($data['video'], 0, 2)
            ]);
        }
        
        // KISS: Убрано лишнее кодирование JSON
        // Модель Ad уже имеет JsonFieldsTrait который автоматически кодирует/декодирует JSON поля
        // Нам нужно только убедиться что данные в виде массивов, а не строк
        // Второй цикл кодирования создавал двойное кодирование и нарушал принцип KISS
        
        // Не устанавливаем значение по умолчанию для заголовка - оставляем пустым

        // КРИТИЧЕСКИ ВАЖНО: Логируем финальные данные перед возвратом
        Log::info('📋 DraftService::prepareData - РЕЗУЛЬТАТ', [
            'work_format' => $data['work_format'] ?? 'ОТСУТСТВУЕТ',
            'service_provider' => $data['service_provider'] ?? 'ОТСУТСТВУЕТ',
            'whatsapp' => $data['whatsapp'] ?? 'ОТСУТСТВУЕТ',
            'telegram' => $data['telegram'] ?? 'ОТСУТСТВУЕТ',
            'all_keys' => array_keys($data)
        ]);

        return $data;
    }
}