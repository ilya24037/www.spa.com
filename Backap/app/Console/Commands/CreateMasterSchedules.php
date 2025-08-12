<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MasterProfile;
use App\Models\Schedule;

class CreateMasterSchedules extends Command
{
    protected $signature = 'schedules:create-for-masters {--force : Перезаписать существующие расписания}';
    protected $description = 'Создает базовое расписание для всех мастеров';

    public function handle()
    {
        $this->info('🔄 Создание расписаний для мастеров...');
        
        $masters = MasterProfile::all();
        $force = $this->option('force');
        
        if ($masters->count() === 0) {
            $this->error('❌ Мастера не найдены в базе данных');
            return 1;
        }

        $created = 0;
        $skipped = 0;

        foreach ($masters as $master) {
            // Проверяем есть ли уже расписание
            if (!$force && $master->schedules()->count() > 0) {
                $skipped++;
                continue;
            }

            // Удаляем старое расписание если --force
            if ($force) {
                $master->schedules()->delete();
            }

            // Создаем стандартное расписание (Пн-Пт 9:00-18:00, Сб 10:00-16:00)
            $schedules = [
                // Понедельник
                [
                    'master_profile_id' => $master->id,
                    'day_of_week' => 1,
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                    'is_working_day' => true,
                    'slot_duration' => 60, // 1 час
                    'buffer_time' => 15,   // 15 мин между сеансами
                    'is_flexible' => true
                ],
                // Вторник
                [
                    'master_profile_id' => $master->id,
                    'day_of_week' => 2,
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                    'is_working_day' => true,
                    'slot_duration' => 60,
                    'buffer_time' => 15,
                    'is_flexible' => true
                ],
                // Среда
                [
                    'master_profile_id' => $master->id,
                    'day_of_week' => 3,
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                    'is_working_day' => true,
                    'slot_duration' => 60,
                    'buffer_time' => 15,
                    'is_flexible' => true
                ],
                // Четверг
                [
                    'master_profile_id' => $master->id,
                    'day_of_week' => 4,
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                    'is_working_day' => true,
                    'slot_duration' => 60,
                    'buffer_time' => 15,
                    'is_flexible' => true
                ],
                // Пятница
                [
                    'master_profile_id' => $master->id,
                    'day_of_week' => 5,
                    'start_time' => '09:00',
                    'end_time' => '18:00',
                    'break_start' => '13:00',
                    'break_end' => '14:00',
                    'is_working_day' => true,
                    'slot_duration' => 60,
                    'buffer_time' => 15,
                    'is_flexible' => true
                ],
                // Суббота (короткий день)
                [
                    'master_profile_id' => $master->id,
                    'day_of_week' => 6,
                    'start_time' => '10:00',
                    'end_time' => '16:00',
                    'break_start' => null,
                    'break_end' => null,
                    'is_working_day' => true,
                    'slot_duration' => 60,
                    'buffer_time' => 15,
                    'is_flexible' => true
                ],
                // Воскресенье (выходной)
                [
                    'master_profile_id' => $master->id,
                    'day_of_week' => 0, // В Carbon воскресенье = 0
                    'start_time' => '09:00', // Устанавливаем время, но день выходной
                    'end_time' => '18:00',
                    'break_start' => null,
                    'break_end' => null,
                    'is_working_day' => false, // Главное - это false
                    'slot_duration' => 60,
                    'buffer_time' => 15,
                    'is_flexible' => false
                ]
            ];

            foreach ($schedules as $scheduleData) {
                Schedule::create($scheduleData);
            }

            $created++;
            $this->info("✅ Создано расписание для мастера: {$master->display_name} (ID: {$master->id})");
        }

        $this->info("\n📊 РЕЗУЛЬТАТЫ:");
        $this->info("✅ Создано расписаний: {$created}");
        $this->info("⏩ Пропущено: {$skipped}");
        $this->info("📋 Всего мастеров: {$masters->count()}");
        
        if ($created > 0) {
            $this->info("\n🎉 Теперь система бронирования готова к работе!");
        }

        return 0;
    }
} 