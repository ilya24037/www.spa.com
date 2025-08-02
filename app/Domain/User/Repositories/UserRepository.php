<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Models\User;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * Репозиторий для работы с пользователями
 */
class UserRepository
{
    /**
     * Найти пользователя по ID с загрузкой связей
     */
    public function find(int $id, bool $withRelations = true): ?User
    {
        $query = User::query();
        
        if ($withRelations) {
            $query->with(['profile', 'settings']);
        }
        
        return $query->find($id);
    }

    /**
     * Найти пользователя по ID или выбросить исключение
     */
    public function findOrFail(int $id, bool $withRelations = true): User
    {
        $query = User::query();
        
        if ($withRelations) {
            $query->with(['profile', 'settings']);
        }
        
        return $query->findOrFail($id);
    }

    /**
     * Найти пользователя по email
     */
    public function findByEmail(string $email, bool $withRelations = false): ?User
    {
        $query = User::where('email', $email);
        
        if ($withRelations) {
            $query->with(['profile', 'settings']);
        }
        
        return $query->first();
    }

    /**
     * Найти пользователей по роли
     */
    public function findByRole(UserRole $role, int $perPage = 15): LengthAwarePaginator
    {
        return User::with(['profile', 'settings'])
            ->where('role', $role)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Найти пользователей по статусу
     */
    public function findByStatus(UserStatus $status, int $perPage = 15): LengthAwarePaginator
    {
        return User::with(['profile', 'settings'])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Поиск пользователей с фильтрами
     */
    public function search(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with(['profile', 'settings']);

        // Фильтр по роли
        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Фильтр по статусу
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Фильтр по активности email
        if (isset($filters['email_verified'])) {
            if ($filters['email_verified']) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Поиск по имени/email
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function($q) use ($searchTerm) {
                $q->where('email', 'like', '%' . $searchTerm . '%')
                  ->orWhereHas('profile', function($subQ) use ($searchTerm) {
                      $subQ->where('name', 'like', '%' . $searchTerm . '%')
                           ->orWhere('phone', 'like', '%' . $searchTerm . '%');
                  });
            });
        }

        // Фильтр по городу
        if (!empty($filters['city'])) {
            $query->whereHas('profile', function($q) use ($filters) {
                $q->where('city', 'like', '%' . $filters['city'] . '%');
            });
        }

        // Фильтр по дате регистрации
        if (!empty($filters['registered_from'])) {
            $query->where('created_at', '>=', $filters['registered_from']);
        }

        if (!empty($filters['registered_to'])) {
            $query->where('created_at', '<=', $filters['registered_to']);
        }

        // Сортировка
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        switch ($sortBy) {
            case 'name':
                $query->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
                      ->orderBy('user_profiles.name', $sortOrder)
                      ->select('users.*');
                break;
            case 'email':
                $query->orderBy('email', $sortOrder);
                break;
            case 'last_activity':
                // Если есть поле last_activity_at
                $query->orderBy('updated_at', $sortOrder);
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Получить активных пользователей
     */
    public function getActive(int $limit = 50): Collection
    {
        return User::with(['profile'])
            ->where('status', UserStatus::ACTIVE)
            ->orderBy('updated_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить недавно зарегистрированных пользователей
     */
    public function getRecent(int $days = 7, int $limit = 20): Collection
    {
        return User::with(['profile', 'settings'])
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить пользователей, которые давно не заходили
     */
    public function getInactive(int $days = 30, int $limit = 100): Collection
    {
        return User::with(['profile'])
            ->where('updated_at', '<=', now()->subDays($days))
            ->where('status', UserStatus::ACTIVE)
            ->orderBy('updated_at', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получить пользователей с неподтвержденным email
     */
    public function getUnverified(int $days = 7): Collection
    {
        return User::with(['profile'])
            ->whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays($days))
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Получить заблокированных пользователей
     */
    public function getBlocked(): Collection
    {
        return User::with(['profile'])
            ->whereIn('status', [UserStatus::SUSPENDED, UserStatus::BANNED])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Получить статистику пользователей
     */
    public function getStats(): array
    {
        $total = User::count();
        
        return [
            'total' => $total,
            'active' => User::where('status', UserStatus::ACTIVE)->count(),
            'inactive' => User::where('status', UserStatus::INACTIVE)->count(),
            'pending' => User::where('status', UserStatus::PENDING)->count(),
            'suspended' => User::where('status', UserStatus::SUSPENDED)->count(),
            'banned' => User::where('status', UserStatus::BANNED)->count(),
            'deleted' => User::where('status', UserStatus::DELETED)->count(),
            
            // По ролям
            'clients' => User::where('role', UserRole::CLIENT)->count(),
            'masters' => User::where('role', UserRole::MASTER)->count(),
            'admins' => User::where('role', UserRole::ADMIN)->count(),
            'moderators' => User::where('role', UserRole::MODERATOR)->count(),
            
            // По времени
            'registered_today' => User::whereDate('created_at', today())->count(),
            'registered_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            'registered_month' => User::where('created_at', '>=', now()->subMonth())->count(),
            
            // Верификация
            'verified' => User::whereNotNull('email_verified_at')->count(),
            'unverified' => User::whereNull('email_verified_at')->count(),
            
            // Профили
            'with_complete_profiles' => User::whereHas('profile', function($q) {
                // Предполагаем, что есть метод для проверки полноты профиля
            })->count(),
        ];
    }

    /**
     * Получить статистику по регистрациям за период
     */
    public function getRegistrationStats(int $days = 30): array
    {
        $stats = [];
        $startDate = now()->subDays($days);
        
        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $stats[$date->format('Y-m-d')] = User::whereDate('created_at', $date)->count();
        }
        
        return $stats;
    }

    /**
     * Массовое обновление статуса пользователей
     */
    public function updateStatusBulk(array $userIds, UserStatus $status): int
    {
        return User::whereIn('id', $userIds)->update(['status' => $status]);
    }

    /**
     * Создать пользователя с профилем
     */
    public function create(array $userData): User
    {
        return User::create([
            'email' => $userData['email'],
            'password' => $userData['password'],
            'role' => $userData['role'] ?? UserRole::CLIENT,
            'status' => $userData['status'] ?? UserStatus::PENDING,
        ]);
        // Профиль и настройки создадутся автоматически через boot метод
    }

    /**
     * Обновить профиль пользователя
     */
    public function updateProfile(User $user, array $profileData): bool
    {
        $profile = $user->getProfile();
        return $profile->update($profileData);
    }

    /**
     * Обновить настройки пользователя
     */
    public function updateSettings(User $user, array $settingsData): bool
    {
        $settings = $user->getSettings();
        return $settings->update($settingsData);
    }

    /**
     * Найти пользователей по списку ID
     */
    public function findByIds(array $ids, bool $withRelations = true): Collection
    {
        $query = User::whereIn('id', $ids);
        
        if ($withRelations) {
            $query->with(['profile', 'settings']);
        }
        
        return $query->get();
    }

    /**
     * Получить пользователей для экспорта
     */
    public function getForExport(array $filters = []): Collection
    {
        $query = User::with(['profile', 'settings']);

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Удалить неактивных пользователей
     */
    public function deleteInactive(int $days = 365): int
    {
        return User::where('status', UserStatus::INACTIVE)
            ->where('updated_at', '<=', now()->subDays($days))
            ->delete();
    }

    /**
     * Найти дубликаты по email
     */
    public function findDuplicateEmails(): Collection
    {
        return User::select('email')
            ->groupBy('email')
            ->havingRaw('COUNT(*) > 1')
            ->with(['profile'])
            ->get();
    }
}