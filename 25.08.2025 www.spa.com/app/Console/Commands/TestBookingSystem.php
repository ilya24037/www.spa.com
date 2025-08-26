<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Master\Models\MasterProfile;
use App\Domain\Service\Models\Service;
use App\Domain\Booking\Services\BookingService;
use Carbon\Carbon;

class TestBookingSystem extends Command
{
    protected $signature = 'booking:test {--master_id=1 : ID мастера для тестирования}';
    protected $description = 'Тестирование системы бронирования';

    public function handle()
    {
        $this->info('🧪 Тестирование системы бронирования...');
        
        $masterId = $this->option('master_id');
        
        // 1. Проверяем мастера
        $master = MasterProfile::with('schedules')->find($masterId);
        if (!$master) {
            $this->error("❌ Мастер с ID {$masterId} не найден");
            return 1;
        }
        
        $this->info("✅ Мастер: {$master->display_name}");
        $this->info("📅 Расписаний: " . $master->schedules->count());
        
        // 2. Проверяем услуги мастера
        $services = $master->services;
        if ($services->count() === 0) {
            $this->error("❌ У мастера нет услуг");
            return 1;
        }
        
        $service = $services->first();
        $this->info("🛠️ Услуга: {$service->name} (₽{$service->price}, {$service->duration_minutes} мин)");
        
        // 3. Тестируем получение доступных слотов
        $bookingService = app(BookingService::class);
        
        try {
            $slots = $bookingService->getAvailableSlots($master->id, $service->id, 7);
            
            $this->info("\n📋 ДОСТУПНЫЕ СЛОТЫ НА 7 ДНЕЙ:");
            
            if (empty($slots)) {
                $this->warn("⚠️ Нет доступных слотов");
            } else {
                foreach ($slots as $date => $timeSlots) {
                    $dayName = Carbon::parse($date)->locale('ru')->isoFormat('dddd, D MMMM');
                    $this->line("📅 {$dayName} ({$date}):");
                    
                    if (empty($timeSlots)) {
                        $this->line("   ❌ Нет свободного времени");
                    } else {
                        $times = array_column($timeSlots, 'time');
                        $timesList = implode(', ', array_slice($times, 0, 5));
                        if (count($times) > 5) {
                            $timesList .= '... (еще ' . (count($times) - 5) . ' слотов)';
                        }
                        $this->line("   ✅ Доступно " . count($timeSlots) . " слотов: {$timesList}");
                    }
                }
            }
            
            // 4. Тестируем создание тестового бронирования
            if (!empty($slots)) {
                // Ищем слот на завтра, чтобы точно обойти временные ограничения
                $tomorrow = now()->addDay()->format('Y-m-d');
                $testDate = isset($slots[$tomorrow]) ? $tomorrow : array_key_first($slots);
                $testSlots = $slots[$testDate];
                
                if (!empty($testSlots)) {
                    // Берем слот не раньше 10:00 для гарантии
                    $firstTime = $testSlots[0]['time'];
                    foreach ($testSlots as $slot) {
                        if ($slot['time'] >= '10:00') {
                            $firstTime = $slot['time'];
                            break;
                        }
                    }
                    
                    $this->info("\n🎯 СОЗДАЕМ ТЕСТОВОЕ БРОНИРОВАНИЕ:");
                    $this->info("📅 Дата: {$testDate}");
                    $this->info("⏰ Время: {$firstTime}");
                    
                    try {
                        // Используем первого пользователя как тестового клиента
                        $testUser = \App\Models\User::first();
                        if (!$testUser) {
                            $this->error("❌ Нет пользователей в системе для тестирования");
                            return 1;
                        }
                        
                        $testBooking = $bookingService->createBooking([
                            'client_id' => $testUser->id,
                            'master_profile_id' => $master->id,
                            'service_id' => $service->id,
                            'booking_date' => $testDate,
                            'booking_time' => $firstTime,
                            'service_location' => 'salon',
                            'client_name' => 'Тестовый Клиент',
                            'client_phone' => '+7-999-123-45-67',
                            'client_email' => 'test@example.com',
                            'address' => null,
                            'address_details' => null,
                            'client_comment' => 'Тестовое бронирование через консоль',
                            'payment_method' => 'cash'
                        ]);
                        
                        $this->info("✅ ТЕСТОВОЕ БРОНИРОВАНИЕ СОЗДАНО!");
                        $this->info("📋 Номер: {$testBooking->booking_number}");
                        $this->info("💰 Стоимость: ₽{$testBooking->total_price}");
                        $this->info("📊 Статус: {$testBooking->status}");
                        
                    } catch (\Exception $e) {
                        $this->error("❌ Ошибка создания бронирования: " . $e->getMessage());
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Ошибка получения слотов: " . $e->getMessage());
            return 1;
        }
        
        $this->info("\n🎉 ТЕСТИРОВАНИЕ ЗАВЕРШЕНО!");
        
        return 0;
    }
} 