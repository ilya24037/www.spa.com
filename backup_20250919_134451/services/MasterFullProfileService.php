<?php

namespace App\Domain\Master\Services;

use App\Domain\Master\Models\MasterProfile;
use App\Domain\User\Repositories\UserRepository;
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
                'bio' => $data['description'],
                'age' => $data['age'] ?? null,
                'experience_years' => $data['experience_years'] ?? null,
                'phone' => $data['phone'],
                'whatsapp' => $data['whatsapp'] ?? null,
                'telegram' => $data['telegram'] ?? null,
                'show_contacts' => $data['show_phone'] ?? false,
                'status' => 'active',
                'is_published' => false, // Новые объявления отправляются на модерацию
                'moderated_at' => null,
            ]);

            // Добавляем услуги
            if (!empty($data['services'])) {
                foreach ($data['services'] as $service) {
                    $profile->services()->create([
                        'name' => $service['name'],
                        'price' => $service['price'],
                        'duration_minutes' => $service['duration'],
                        'description' => $service['description'] ?? null,
                        'is_active' => true,
                        'sort_order' => 0,
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