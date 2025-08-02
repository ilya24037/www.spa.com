<?php

use App\Infrastructure\Feature\FeatureFlagService;

if (!function_exists('feature')) {
    /**
     * Проверить, включена ли функция
     *
     * @param string $feature
     * @param mixed $user
     * @return bool
     */
    function feature(string $feature, mixed $user = null): bool
    {
        return app(FeatureFlagService::class)->isEnabled($feature, $user ?? auth()->user());
    }
}

if (!function_exists('feature_flag')) {
    /**
     * Получить сервис Feature Flags
     *
     * @return FeatureFlagService
     */
    function feature_flag(): FeatureFlagService
    {
        return app(FeatureFlagService::class);
    }
}