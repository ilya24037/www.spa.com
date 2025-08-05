<?php

namespace App\Examples;

use App\Domain\Ad\Models\Ad;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\UserSettings;

/**
 * Примеры использования JsonFieldsTrait в реальном коде
 */
class JsonFieldsTraitUsageExamples
{
    /**
     * Пример работы с объявлениями (Ad)
     */
    public function adExamples()
    {
        $ad = Ad::find(1);
        
        // Работа с услугами
        $ad->setJsonField('services', ['массаж', 'спа', 'сауна']);
        $ad->appendToJsonField('services', 'ароматерапия');
        $ad->removeFromJsonField('services', 'сауна');
        
        // Проверка наличия услуги
        if ($ad->hasInJsonField('services', 'массаж')) {
            echo "Объявление включает массаж";
        }
        
        // Работа с геолокацией
        $ad->setJsonFieldKey('geo', 'lat', 55.7558);
        $ad->setJsonFieldKey('geo', 'lng', 37.6173);
        
        // Получение координат
        $latitude = $ad->getJsonFieldKey('geo', 'lat');
        $longitude = $ad->getJsonFieldKey('geo', 'lng');
        
        // Работа с фотографиями
        $ad->appendToJsonField('photos', [
            'url' => '/storage/photos/123.jpg',
            'thumbnail' => '/storage/photos/123_thumb.jpg',
            'is_main' => false,
            'order' => 1
        ]);
        
        // Работа с расписанием
        $ad->mergeJsonField('schedule', [
            'monday' => ['start' => '10:00', 'end' => '18:00'],
            'tuesday' => ['start' => '10:00', 'end' => '18:00'],
            'wednesday' => ['start' => '10:00', 'end' => '18:00']
        ]);
        
        // Получение всех фото
        $photos = $ad->getJsonField('photos', []);
        foreach ($photos as $index => $photo) {
            echo "Фото {$index}: {$photo['url']}";
        }
        
        $ad->save();
    }
    
    /**
     * Пример работы с профилем мастера (MasterProfile)
     */
    public function masterProfileExamples()
    {
        $master = MasterProfile::find(1);
        
        // Работа с сертификатами
        $master->appendToJsonField('certificates', [
            'name' => 'Диплом массажиста',
            'date' => '2023-01-15',
            'file' => '/storage/certificates/diploma.pdf'
        ]);
        
        // Работа с образованием
        $master->setJsonField('education', [
            [
                'institution' => 'Медицинский колледж',
                'degree' => 'Среднее специальное',
                'year' => 2020
            ],
            [
                'institution' => 'Курсы повышения квалификации',
                'degree' => 'Сертификат',
                'year' => 2023
            ]
        ]);
        
        // Работа с особенностями
        $master->setJsonField('features', [
            'опытный мастер',
            'индивидуальный подход',
            'работаю с выездом'
        ]);
        
        // Проверка наличия особенности
        if ($master->hasInJsonField('features', 'работаю с выездом')) {
            echo "Мастер работает с выездом";
        }
        
        // Валидация структуры сертификата
        $certificates = $master->getJsonField('certificates', []);
        foreach ($certificates as $cert) {
            if (!isset($cert['name']) || !isset($cert['date'])) {
                echo "Невалидная структура сертификата";
            }
        }
        
        $master->save();
    }
    
    /**
     * Пример работы с настройками пользователя (UserSettings)
     */
    public function userSettingsExamples()
    {
        $settings = UserSettings::find(1);
        
        // Работа с настройками приватности
        $settings->setJsonField('privacy_settings', [
            'show_phone' => false,
            'show_email' => false,
            'show_profile_to_guests' => true,
            'allow_messages' => true,
            'allow_reviews' => true
        ]);
        
        // Изменение отдельной настройки
        $settings->setJsonFieldKey('privacy_settings', 'show_phone', true);
        
        // Получение настройки
        $showPhone = $settings->getJsonFieldKey('privacy_settings', 'show_phone', false);
        
        // Добавление новой настройки приватности
        $settings->mergeJsonField('privacy_settings', [
            'show_online_status' => false,
            'show_last_seen' => false
        ]);
        
        // Валидация структуры настроек
        if ($settings->validateJsonStructure('privacy_settings', ['show_phone', 'show_email'])) {
            echo "Основные настройки приватности установлены";
        }
        
        // Получение всех настроек с дефолтными значениями
        $privacy = $settings->getJsonField('privacy_settings', [
            'show_phone' => false,
            'show_email' => false,
            'show_profile_to_guests' => true
        ]);
        
        $settings->save();
    }
    
    /**
     * Пример миграции старого кода на JsonFieldsTrait
     */
    public function migrationExample()
    {
        $ad = Ad::find(1);
        
        // Старый способ (до JsonFieldsTrait)
        // $services = json_decode($ad->services, true) ?: [];
        // $services[] = 'новая услуга';
        // $ad->services = json_encode($services);
        
        // Новый способ (с JsonFieldsTrait)
        $ad->appendToJsonField('services', 'новая услуга');
        
        // Старый способ работы с geo
        // $geo = json_decode($ad->geo, true) ?: [];
        // $geo['lat'] = 55.7558;
        // $geo['lng'] = 37.6173;
        // $ad->geo = json_encode($geo);
        
        // Новый способ
        $ad->setJsonFieldKey('geo', 'lat', 55.7558);
        $ad->setJsonFieldKey('geo', 'lng', 37.6173);
        
        $ad->save();
    }
}