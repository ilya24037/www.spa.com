<?php

namespace Tests\Unit\Support\Traits;

use Tests\TestCase;
use App\Domain\Ad\Models\Ad;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Models\UserSettings;
use Tests\Traits\SafeRefreshDatabase;
use Illuminate\Support\Facades\Log;

class JsonFieldsTraitTest extends TestCase
{
    use SafeRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Создаем тестового пользователя
        $this->user = \App\Domain\User\Models\User::factory()->create();
    }

    /**
     * Тест базовых операций с JSON полями
     */
    public function test_basic_json_operations()
    {
        $ad = Ad::factory()->create(['user_id' => $this->user->id]);
        
        // Тест setJsonField
        $ad->setJsonField('services', ['массаж', 'спа', 'сауна']);
        $this->assertEquals(['массаж', 'спа', 'сауна'], $ad->services);
        
        // Тест getJsonField
        $services = $ad->getJsonField('services');
        $this->assertEquals(['массаж', 'спа', 'сауна'], $services);
        
        // Тест getJsonField с дефолтным значением
        $empty = $ad->getJsonField('non_existent_field', ['default']);
        $this->assertEquals(['default'], $empty);
    }
    
    /**
     * Тест работы с массивами
     */
    public function test_array_operations()
    {
        $ad = Ad::factory()->create(['user_id' => $this->user->id]);
        
        // Установка начального массива
        $ad->setJsonField('services', ['массаж', 'спа']);
        
        // Добавление элемента
        $ad->appendToJsonField('services', 'сауна');
        $this->assertContains('сауна', $ad->getJsonField('services'));
        $this->assertCount(3, $ad->getJsonField('services'));
        
        // Удаление элемента
        $ad->removeFromJsonField('services', 'спа');
        $this->assertNotContains('спа', $ad->getJsonField('services'));
        $this->assertCount(2, $ad->getJsonField('services'));
        
        // Проверка наличия элемента
        $this->assertTrue($ad->hasInJsonField('services', 'массаж'));
        $this->assertFalse($ad->hasInJsonField('services', 'спа'));
    }
    
    /**
     * Тест работы с объектами (ассоциативными массивами)
     */
    public function test_object_operations()
    {
        $ad = Ad::factory()->create(['user_id' => $this->user->id]);
        
        // Установка значений по ключам
        $ad->setJsonFieldKey('geo', 'lat', 55.7558);
        $ad->setJsonFieldKey('geo', 'lng', 37.6173);
        
        // Получение значений по ключам
        $this->assertEquals(55.7558, $ad->getJsonFieldKey('geo', 'lat'));
        $this->assertEquals(37.6173, $ad->getJsonFieldKey('geo', 'lng'));
        
        // Получение с дефолтным значением
        $this->assertEquals('Moscow', $ad->getJsonFieldKey('geo', 'city', 'Moscow'));
        
        // Проверка всего объекта
        $geo = $ad->getJsonField('geo');
        $this->assertIsArray($geo);
        $this->assertArrayHasKey('lat', $geo);
        $this->assertArrayHasKey('lng', $geo);
    }
    
    /**
     * Тест слияния данных
     */
    public function test_merge_operations()
    {
        $master = MasterProfile::factory()->create(['user_id' => $this->user->id]);
        
        // Начальные данные
        $master->setJsonField('schedule', [
            'monday' => '10:00-18:00',
            'tuesday' => '10:00-18:00'
        ]);
        
        // Слияние новых данных
        $master->mergeJsonField('schedule', [
            'wednesday' => '10:00-20:00',
            'thursday' => '10:00-20:00'
        ]);
        
        $schedule = $master->getJsonField('schedule');
        $this->assertCount(4, $schedule);
        $this->assertEquals('10:00-18:00', $schedule['monday']);
        $this->assertEquals('10:00-20:00', $schedule['wednesday']);
    }
    
    /**
     * Тест очистки JSON поля
     */
    public function test_clear_json_field()
    {
        $ad = Ad::factory()->create([
            'user_id' => $this->user->id,
            'services' => ['массаж', 'спа', 'сауна']
        ]);
        
        $this->assertNotEmpty($ad->getJsonField('services'));
        
        $ad->clearJsonField('services');
        $this->assertEmpty($ad->getJsonField('services'));
        $this->assertEquals([], $ad->services);
    }
    
    /**
     * Тест валидации структуры
     */
    public function test_structure_validation()
    {
        $ad = Ad::factory()->create(['user_id' => $this->user->id]);
        
        // Установка структурированных данных
        $ad->setJsonField('photos', [
            ['url' => '/photo1.jpg', 'thumbnail' => '/thumb1.jpg'],
            ['url' => '/photo2.jpg', 'thumbnail' => '/thumb2.jpg']
        ]);
        
        // Проверка структуры первого фото
        $photos = $ad->getJsonField('photos');
        $this->assertTrue(
            isset($photos[0]['url']) && isset($photos[0]['thumbnail']),
            'Первое фото должно иметь url и thumbnail'
        );
        
        // Тест валидации структуры через метод
        $ad->setJsonField('geo', ['lat' => 55.7558, 'lng' => 37.6173]);
        $this->assertTrue($ad->validateJsonStructure('geo', ['lat', 'lng']));
        $this->assertFalse($ad->validateJsonStructure('geo', ['lat', 'lng', 'city']));
    }
    
    /**
     * Тест обработки невалидного JSON
     */
    public function test_invalid_json_handling()
    {
        $settings = UserSettings::factory()->create(['user_id' => $this->user->id]);
        
        // Прямая установка невалидного JSON в БД
        \DB::table('user_settings')
            ->where('id', $settings->id)
            ->update(['privacy_settings' => 'invalid json {']);
        
        $settings->refresh();
        
        // Должен вернуть дефолтное значение при ошибке
        $privacy = $settings->getJsonField('privacy_settings', ['default' => true]);
        $this->assertEquals(['default' => true], $privacy);
    }
    
    /**
     * Тест работы с пустыми/null значениями
     */
    public function test_null_and_empty_handling()
    {
        $ad = Ad::factory()->create(['user_id' => $this->user->id]);
        
        // Null значение
        $ad->services = null;
        $ad->save();
        
        $services = $ad->getJsonField('services', ['default']);
        $this->assertEquals(['default'], $services);
        
        // Пустая строка
        $ad->features = '';
        $ad->save();
        
        $features = $ad->getJsonField('features', ['default']);
        $this->assertEquals(['default'], $features);
    }
    
    /**
     * Тест автоматической инициализации casts
     */
    public function test_automatic_casts_initialization()
    {
        $ad = new Ad();
        
        // Проверяем что JsonFieldsTrait автоматически добавил поля в casts
        $jsonFields = $ad->getJsonFields();
        foreach ($jsonFields as $field) {
            $this->assertTrue(
                $ad->hasCast($field, 'array'),
                "Поле {$field} должно автоматически кастоваться в array"
            );
        }
    }
    
    /**
     * Тест сохранения и загрузки из БД
     */
    public function test_database_persistence()
    {
        $ad = Ad::factory()->create(['user_id' => $this->user->id]);
        
        // Устанавливаем различные типы данных
        $ad->setJsonField('services', ['массаж', 'спа']);
        $ad->setJsonField('geo', ['lat' => 55.7558, 'lng' => 37.6173]);
        $ad->setJsonField('schedule', [
            'monday' => ['start' => '10:00', 'end' => '18:00'],
            'tuesday' => ['start' => '10:00', 'end' => '18:00']
        ]);
        $ad->save();
        
        // Загружаем из БД
        $freshAd = Ad::find($ad->id);
        
        // Проверяем что данные сохранились корректно
        $this->assertEquals(['массаж', 'спа'], $freshAd->getJsonField('services'));
        $this->assertEquals(55.7558, $freshAd->getJsonFieldKey('geo', 'lat'));
        $this->assertEquals('10:00', $freshAd->getJsonFieldKey('schedule', 'monday')['start']);
    }
}