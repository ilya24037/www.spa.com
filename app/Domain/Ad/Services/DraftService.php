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
        
        $data = $ad->toArray();
        
        // ВАЖНО: Убедимся, что ID всегда присутствует и имеет правильный тип
        $data['id'] = (int) $ad->id;
        
        // Декодируем JSON поля
        $jsonFields = ['clients', 'services', 'features', 'photos', 'video', 'geo', 'prices', 'schedule', 'faq'];
        
        // Обработка поля schedule
        
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
                
                // Поле успешно декодировано
            }
        }
        
        // ✅ АРХИТЕКТУРНО ПРАВИЛЬНОЕ РЕШЕНИЕ:
        // После миграции 2025_08_28 outcall поля теперь хранятся в geo, где им и место!
        // Больше не нужен костыль с переносом данных между prices и geo
        
        // Преобразуем строковые boolean значения в geo в настоящие boolean
        if (isset($data['geo']) && is_array($data['geo'])) {
            // Поля которые должны быть boolean в geo (после миграции)
            $booleanFields = ['taxi_included', 'outcall_apartment', 'outcall_hotel', 
                            'outcall_house', 'outcall_sauna', 'outcall_office'];
            
            foreach ($booleanFields as $boolField) {
                if (isset($data['geo'][$boolField])) {
                    $value = $data['geo'][$boolField];
                    // Преобразуем '1', 1, true в true; '0', 0, false, null в false
                    if ($value === '1' || $value === 1 || $value === true) {
                        $data['geo'][$boolField] = true;
                    } else {
                        $data['geo'][$boolField] = false;
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
        
        // ИСПРАВЛЕНИЕ: Убедимся что schedule_notes всегда присутствует (даже если пустое)
        if (!isset($data['schedule_notes'])) {
            $data['schedule_notes'] = '';
        }
        
        // ИСПРАВЛЕНИЕ: Убедимся что поля акций и скидок всегда присутствуют (даже если пустые)
        if (!isset($data['new_client_discount'])) {
            $data['new_client_discount'] = '';
        }
        if (!isset($data['gift'])) {
            $data['gift'] = '';
        }
        
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
                    
                    // Очищены конфликтующие опции в FAQ
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
        
        // Подготовка schedule для сохранения
        
        foreach ($jsonFields as $field) {
            if (isset($data[$field])) {
                // КРИТИЧЕСКИ ВАЖНО: Проверяем что поле не null и не пустое
                if ($data[$field] === null || $data[$field] === '') {
                    $data[$field] = in_array($field, ['services', 'prices', 'geo', 'faq']) ? '{}' : '[]';
                    continue;
                }
                
                // Особая обработка для geo чтобы избежать двойного экранирования
                if ($field === 'geo' && is_string($data[$field]) && !empty($data[$field])) {
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
                        
                        // JSON кодирование завершено
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