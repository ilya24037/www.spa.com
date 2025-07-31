<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления Feature Flags
 */
class FeatureFlagService
{
    private array $flags = [];
    private bool $cacheEnabled = true;
    private int $cacheTTL = 300; // 5 минут

    /**
     * Проверить, включена ли функция
     */
    public function isEnabled(string $feature, $user = null): bool
    {
        $flag = $this->getFlag($feature);
        
        if (!$flag) {
            return false;
        }

        // Глобально выключенная функция
        if (!$flag['enabled']) {
            return false;
        }

        // Проверка по проценту пользователей
        if (isset($flag['percentage']) && $flag['percentage'] < 100) {
            return $this->checkPercentage($feature, $user, $flag['percentage']);
        }

        // Проверка по конкретным пользователям
        if (isset($flag['users']) && !empty($flag['users'])) {
            return $this->checkUserAccess($user, $flag['users']);
        }

        // Проверка по группам/ролям
        if (isset($flag['roles']) && !empty($flag['roles'])) {
            return $this->checkRoleAccess($user, $flag['roles']);
        }

        // Проверка по окружению
        if (isset($flag['environments']) && !empty($flag['environments'])) {
            return in_array(app()->environment(), $flag['environments']);
        }

        return true;
    }

    /**
     * Получить флаг
     */
    public function getFlag(string $feature): ?array
    {
        $this->loadFlags();
        return $this->flags[$feature] ?? null;
    }

    /**
     * Получить все флаги
     */
    public function getAllFlags(): array
    {
        $this->loadFlags();
        return $this->flags;
    }

    /**
     * Установить флаг
     */
    public function setFlag(string $feature, array $config): void
    {
        DB::table('feature_flags')->updateOrInsert(
            ['name' => $feature],
            [
                'config' => json_encode($config),
                'updated_at' => now()
            ]
        );

        $this->clearCache();
        
        Log::info('Feature flag updated', [
            'feature' => $feature,
            'config' => $config
        ]);
    }

    /**
     * Включить функцию
     */
    public function enable(string $feature): void
    {
        $flag = $this->getFlag($feature) ?? [];
        $flag['enabled'] = true;
        $this->setFlag($feature, $flag);
    }

    /**
     * Выключить функцию
     */
    public function disable(string $feature): void
    {
        $flag = $this->getFlag($feature) ?? [];
        $flag['enabled'] = false;
        $this->setFlag($feature, $flag);
    }

    /**
     * Установить процент пользователей
     */
    public function setPercentage(string $feature, int $percentage): void
    {
        $flag = $this->getFlag($feature) ?? ['enabled' => true];
        $flag['percentage'] = max(0, min(100, $percentage));
        $this->setFlag($feature, $flag);
    }

    /**
     * Добавить пользователей
     */
    public function addUsers(string $feature, array $userIds): void
    {
        $flag = $this->getFlag($feature) ?? ['enabled' => true];
        $flag['users'] = array_unique(array_merge($flag['users'] ?? [], $userIds));
        $this->setFlag($feature, $flag);
    }

    /**
     * Удалить пользователей
     */
    public function removeUsers(string $feature, array $userIds): void
    {
        $flag = $this->getFlag($feature) ?? [];
        if (isset($flag['users'])) {
            $flag['users'] = array_diff($flag['users'], $userIds);
            $this->setFlag($feature, $flag);
        }
    }

    /**
     * Загрузить флаги
     */
    private function loadFlags(): void
    {
        if (!empty($this->flags)) {
            return;
        }

        if ($this->cacheEnabled) {
            $this->flags = Cache::remember('feature_flags', $this->cacheTTL, function () {
                return $this->loadFlagsFromDatabase();
            });
        } else {
            $this->flags = $this->loadFlagsFromDatabase();
        }
    }

    /**
     * Загрузить флаги из БД
     */
    private function loadFlagsFromDatabase(): array
    {
        $flags = [];
        
        // Загружаем из БД
        try {
            $dbFlags = DB::table('feature_flags')->get();
            foreach ($dbFlags as $flag) {
                $flags[$flag->name] = json_decode($flag->config, true);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to load feature flags from database', [
                'error' => $e->getMessage()
            ]);
        }

        // Мержим с конфигурацией
        $configFlags = config('features', []);
        foreach ($configFlags as $name => $config) {
            if (!isset($flags[$name])) {
                $flags[$name] = is_bool($config) ? ['enabled' => $config] : $config;
            }
        }

        return $flags;
    }

    /**
     * Проверка по проценту
     */
    private function checkPercentage(string $feature, $user, int $percentage): bool
    {
        if (!$user) {
            return false;
        }

        $userId = is_object($user) ? $user->id : $user;
        $hash = crc32($feature . ':' . $userId);
        $bucket = $hash % 100;
        
        return $bucket < $percentage;
    }

    /**
     * Проверка доступа пользователя
     */
    private function checkUserAccess($user, array $allowedUsers): bool
    {
        if (!$user) {
            return false;
        }

        $userId = is_object($user) ? $user->id : $user;
        return in_array($userId, $allowedUsers);
    }

    /**
     * Проверка доступа по ролям
     */
    private function checkRoleAccess($user, array $allowedRoles): bool
    {
        if (!$user || !is_object($user)) {
            return false;
        }

        if (method_exists($user, 'hasAnyRole')) {
            return $user->hasAnyRole($allowedRoles);
        }

        if (method_exists($user, 'hasRole')) {
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Очистить кеш
     */
    private function clearCache(): void
    {
        Cache::forget('feature_flags');
        $this->flags = [];
    }

    /**
     * Логировать использование функции
     */
    public function logUsage(string $feature, bool $enabled, $user = null): void
    {
        DB::table('feature_flag_usage')->insert([
            'feature' => $feature,
            'enabled' => $enabled,
            'user_id' => is_object($user) ? $user->id : $user,
            'created_at' => now()
        ]);
    }

    /**
     * Получить статистику использования
     */
    public function getUsageStats(string $feature, int $days = 7): array
    {
        $stats = DB::table('feature_flag_usage')
            ->where('feature', $feature)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('
                COUNT(*) as total_checks,
                SUM(CASE WHEN enabled = 1 THEN 1 ELSE 0 END) as enabled_count,
                COUNT(DISTINCT user_id) as unique_users,
                DATE(created_at) as date
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'feature' => $feature,
            'period_days' => $days,
            'daily_stats' => $stats->toArray(),
            'summary' => [
                'total_checks' => $stats->sum('total_checks'),
                'enabled_count' => $stats->sum('enabled_count'),
                'unique_users' => DB::table('feature_flag_usage')
                    ->where('feature', $feature)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->distinct('user_id')
                    ->count('user_id'),
                'adoption_rate' => $stats->sum('total_checks') > 0 
                    ? round(($stats->sum('enabled_count') / $stats->sum('total_checks')) * 100, 2)
                    : 0
            ]
        ];
    }
}