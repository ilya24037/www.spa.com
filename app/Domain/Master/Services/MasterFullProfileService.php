<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Repositories\UserRepository;
use App\Jobs\AutoModerateAdJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Сервис создания полного профиля мастера
 */
class MasterFullProfileService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Создать полный профиль мастера с услугами и фотографиями (для AddItemController)
     */
    public function createFullProfile(array $data): MasterProfile
    {
        return DB::transaction(function () use ($data) {
            // Создаем основной профиль
            $profile = $data['user']->masterProfiles()->create([
                'display_name' => $data['display_name'],
                'slug' => $this->generateSlug($data['display_name']),
                'description' => $data['description'],
                'age' => $data['age'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
                'city' => $data['city'],
                'district' => $data['district'] ?? null,
                'address' => $data['address'] ?? null,
                'salon_name' => $data['salon_name'] ?? null,
                'phone' => $data['phone'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'telegram' => $data['telegram'] ?? null,
                'price_from' => $data['price_from'],
                'price_to' => $data['price_to'] ?? null,
                'show_phone' => $data['show_phone'] ?? false,
                'category_type' => $data['category_type'] ?? 'massage',
                'is_adult_content' => $data['is_adult_content'] ?? false,
                'status' => 'active',
                'is_active' => true,
                'is_published' => false, // Новые объявления отправляются на модерацию
            ]);

            // Добавляем услуги
            if (!empty($data['services'])) {
                foreach ($data['services'] as $service) {
                    $profile->services()->create([
                        'name' => $service['name'],
                        'price' => $service['price'],
                        'duration_minutes' => $service['duration'],
                        'description' => $service['description'] ?? null,
                        'adult_content' => $data['is_adult_content'] ?? false,
                        'massage_category_id' => $service['category_id'] ?? null,
                    ]);
                }
            }

            // Добавляем зоны работы
            if (!empty($data['work_zones'])) {
                foreach ($data['work_zones'] as $zone) {
                    $profile->workZones()->create(['name' => $zone]);
                }
            }

            // Загружаем фотографии
            if (!empty($data['photos'])) {
                foreach ($data['photos'] as $index => $photo) {
                    $path = $photo->store('masters/photos', 'public');
                    $profile->photos()->create([
                        'path' => $path,
                        'is_main' => $index === 0,
                    ]);
                }
            }

            // Обновляем роль пользователя
            $this->updateUserRole($data['user']->id);

            // Запускаем автоматическую модерацию через 5 минут
            AutoModerateAdJob::dispatch($profile->id)
                ->delay(now()->addMinutes(5));

            return $profile;
        });
    }

    /**
     * Обновить роль пользователя
     */
    private function updateUserRole(int $userId): void
    {
        $user = $this->userRepository->find($userId);
        if ($user && !$user->hasRole('master')) {
            $user->assignRole('master');
        }
    }

    /**
     * Генерация уникального slug
     */
    private function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = 1;
        
        // Простая проверка на уникальность через User model
        while (MasterProfile::where('slug', $slug)->exists()) {
            $slug = Str::slug($name) . '-' . $count;
            $count++;
        }
        
        return $slug;
    }
}