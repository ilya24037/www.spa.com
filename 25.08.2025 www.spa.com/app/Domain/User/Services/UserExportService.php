<?php

namespace App\Domain\User\Services;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Сервис экспорта пользователей
 */
class UserExportService
{
    /**
     * Получить пользователей для экспорта
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = User::with(['profile', 'settings']);

        // Применяем фильтры
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        if (!empty($filters['city'])) {
            $query->whereHas('profile', function($q) use ($filters) {
                $q->where('city', 'like', '%' . $filters['city'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Экспорт в CSV формат
     */
    public function exportToCsv(Collection $users, array $columns = []): string
    {
        if (empty($columns)) {
            $columns = $this->getDefaultColumns();
        }

        $csvData = [];
        $csvData[] = array_keys($columns); // Заголовки

        foreach ($users as $user) {
            $row = [];
            foreach ($columns as $key => $label) {
                $row[] = $this->getFieldValue($user, $key);
            }
            $csvData[] = $row;
        }

        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filePath = 'exports/' . $filename;

        $handle = fopen(storage_path('app/' . $filePath), 'w');
        
        // Добавляем BOM для корректного отображения UTF-8 в Excel
        fwrite($handle, "\xEF\xBB\xBF");

        foreach ($csvData as $row) {
            fputcsv($handle, $row, ';');
        }
        
        fclose($handle);

        return $filePath;
    }

    /**
     * Экспорт в JSON формат
     */
    public function exportToJson(Collection $users, array $columns = []): string
    {
        $data = [];
        
        foreach ($users as $user) {
            $userData = [];
            
            if (empty($columns)) {
                // Экспортируем все основные поля
                $userData = [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role?->value,
                    'status' => $user->status?->value,
                    'name' => $user->getProfile()?->name,
                    'phone' => $user->getProfile()?->phone,
                    'city' => $user->getProfile()?->city,
                    'created_at' => $user->created_at?->toISOString(),
                    'last_activity' => $user->last_activity?->toISOString(),
                ];
            } else {
                // Экспортируем только выбранные поля
                foreach ($columns as $key => $label) {
                    $userData[$key] = $this->getFieldValue($user, $key);
                }
            }
            
            $data[] = $userData;
        }

        $filename = 'users_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filePath = 'exports/' . $filename;

        Storage::put($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filePath;
    }

    /**
     * Создать отчет по пользователям
     */
    public function generateReport(array $filters = []): array
    {
        $users = $this->getForExport($filters);
        
        return [
            'generated_at' => now()->toISOString(),
            'filters_applied' => $filters,
            'total_users' => $users->count(),
            'summary' => [
                'by_role' => $users->groupBy('role')->map->count(),
                'by_status' => $users->groupBy('status')->map->count(),
                'registration_period' => [
                    'from' => $users->min('created_at'),
                    'to' => $users->max('created_at'),
                ],
            ],
            'users' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role?->value,
                    'status' => $user->status?->value,
                    'profile' => [
                        'name' => $user->getProfile()?->name,
                        'phone' => $user->getProfile()?->phone,
                        'city' => $user->getProfile()?->city,
                    ],
                    'created_at' => $user->created_at?->toISOString(),
                    'last_activity' => $user->last_activity?->toISOString(),
                ];
            }),
        ];
    }

    /**
     * Получить колонки по умолчанию для экспорта
     */
    private function getDefaultColumns(): array
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'role' => 'Роль',
            'status' => 'Статус',
            'name' => 'Имя',
            'phone' => 'Телефон',
            'city' => 'Город',
            'created_at' => 'Дата регистрации',
            'last_activity' => 'Последняя активность',
        ];
    }

    /**
     * Получить значение поля для экспорта
     */
    private function getFieldValue(User $user, string $field): ?string
    {
        return match($field) {
            'id' => (string) $user->id,
            'email' => $user->email,
            'role' => $user->role?->getLabel(),
            'status' => $user->status?->getLabel(),
            'name' => $user->getProfile()?->name,
            'phone' => $user->getProfile()?->phone,
            'city' => $user->getProfile()?->city,
            'created_at' => $user->created_at?->format('d.m.Y H:i'),
            'last_activity' => $user->last_activity?->format('d.m.Y H:i'),
            'email_verified' => $user->email_verified_at ? 'Да' : 'Нет',
            default => null,
        };
    }
}