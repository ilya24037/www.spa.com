<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
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
                // Подготавливаем данные
        $data = $this->prepareData($data);
        $data['user_id'] = $user->id;
        
        // Приводим ID к integer если он передан
        $adId = $adId ? (int) $adId : null;

        // 🎯 ЛОГИКА КАК НА АВИТО: сохраняем статус активных объявлений
        if ($adId && $adId > 0) {
            $existingAd = Ad::find($adId);
            if ($existingAd && $existingAd->status !== 'draft') {
                // Не меняем статус активных/модерируемых объявлений
                // Оставляем их статус как есть
                unset($data['status']);
            } else {
                // Для новых или черновых объявлений ставим draft
                $data['status'] = 'draft';
            }
        } else {
            // Новое объявление всегда draft
            $data['status'] = 'draft';
        }

        // Если передан ID, ищем существующее объявление
        if ($adId && $adId > 0) {
            
            // Сначала попробуем найти объявление принадлежащее пользователю
            $ad = Ad::where('id', $adId)
                ->where('user_id', $user->id)
                ->first();
                
            if ($ad) {
                $ad->update($data);
                $ad->wasRecentlyCreated = false; // Явно указываем что это обновление
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
        $ad = Ad::create($data);
        $ad->wasRecentlyCreated = true; // Явно указываем что это создание
        
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
        
        // Декодируем JSON поля
        $jsonFields = ['clients', 'services', 'features', 'photos', 'video', 'geo', 'prices', 'schedule', 'faq'];
        
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
        
        // Убедимся что description всегда присутствует (даже если пустое)
        if (!isset($data['description'])) {
            $data['description'] = '';
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
        if ($ad->status !== 'draft') {
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
        // Валидация взаимоисключающих опций в FAQ
        if (isset($data['faq'])) {
            $data['faq'] = $this->validateFaqExclusivity($data['faq']);
        }
        
        // Кодируем массивы в JSON
        // Исключаем 'faq' из ручного кодирования, так как модель обрабатывает его автоматически через $jsonFields
        $jsonFields = ['clients', 'service_provider', 'services', 'features', 'photos', 'video', 'geo', 'prices', 'schedule'];
        
        // Логируем данные schedule перед обработкой
        if (isset($data['schedule'])) {
            Log::info("📅 DraftService: Данные schedule перед JSON кодированием", [
                'schedule_data' => $data['schedule'],
                'schedule_type' => gettype($data['schedule']),
                'schedule_is_array' => is_array($data['schedule'])
            ]);
        }
        
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                // Особая обработка для geo чтобы избежать двойного экранирования
                if ($field === 'geo' && is_string($data[$field])) {
                    // Проверяем, является ли строка валидным JSON
                    $decoded = json_decode($data[$field], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        // Это уже JSON, оставляем как есть
                        continue;
                    }
                }
                
                if (!is_string($data[$field])) {
                    // Для больших массивов используем более эффективное кодирование
                    try {
                        $encoded = json_encode($data[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        
                        // Проверяем размер закодированных данных
                        if (strlen($encoded) > 65000) { // Ограничение для TEXT поля в MySQL
                            // Для разных полей разные стратегии
                            if ($field === 'photos' || $field === 'video') {
                                // Для медиа оставляем пустой массив
                                $data[$field] = '[]';
                            } elseif ($field === 'services' || $field === 'features') {
                                // Для сервисов и фич оставляем пустой объект/массив
                                $data[$field] = in_array($field, ['services', 'prices', 'geo']) ? '{}' : '[]';
                            } else {
                                // Для остальных полей
                                $data[$field] = in_array($field, ['services', 'prices', 'geo']) ? '{}' : '[]';
                            }
                        } else {
                            $data[$field] = $encoded;
                        }
                        
                        // Логируем результат JSON кодирования для schedule
                        if ($field === 'schedule') {
                            Log::info("📅 DraftService: Результат JSON кодирования schedule", [
                                'encoded_schedule' => $data[$field],
                                'encoded_length' => strlen($data[$field])
                            ]);
                        }
                    } catch (\Exception $e) {
                        // В случае ошибки кодирования устанавливаем пустое значение
                        $data[$field] = in_array($field, ['services', 'prices', 'geo']) ? '{}' : '[]';
                    }
                } // Добавлена закрывающая скобка для if (!is_string($data[$field]))
            }
        }
        
        // Устанавливаем значение по умолчанию для заголовка
        $data['title'] = $data['title'] ?? 'Черновик';
        
        return $data;
    }
}