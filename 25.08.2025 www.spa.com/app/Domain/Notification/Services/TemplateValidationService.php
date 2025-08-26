<?php

namespace App\Domain\Notification\Services;

use App\Enums\NotificationType;
use App\Enums\NotificationChannel;

/**
 * Сервис валидации шаблонов уведомлений
 */
class TemplateValidationService
{
    /**
     * Валидировать структуру данных импорта
     */
    public function validateImportData(array $data): array
    {
        $errors = [];
        
        $errors = array_merge($errors, $this->validateRequiredFields($data));
        $errors = array_merge($errors, $this->validateNotificationType($data));
        $errors = array_merge($errors, $this->validateChannels($data));
        
        return $errors;
    }

    /**
     * Валидация обязательных полей
     */
    private function validateRequiredFields(array $data): array
    {
        $errors = [];
        $requiredFields = ['name', 'type', 'content'];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Required field '{$field}' is missing or empty";
            }
        }
        
        return $errors;
    }

    /**
     * Валидация типа уведомления
     */
    private function validateNotificationType(array $data): array
    {
        $errors = [];
        
        if (isset($data['type'])) {
            try {
                NotificationType::from($data['type']);
            } catch (\ValueError $e) {
                $errors[] = "Invalid notification type: {$data['type']}";
            }
        }
        
        return $errors;
    }

    /**
     * Валидация каналов уведомлений
     */
    private function validateChannels(array $data): array
    {
        $errors = [];
        
        if (isset($data['channels']) && is_array($data['channels'])) {
            $validChannels = array_column(NotificationChannel::cases(), 'value');
            
            foreach ($data['channels'] as $channel) {
                if (!in_array($channel, $validChannels)) {
                    $errors[] = "Invalid notification channel: {$channel}";
                }
            }
        }
        
        return $errors;
    }
}