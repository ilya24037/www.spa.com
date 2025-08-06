<?php

namespace App\Domain\User\Auth;

use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Менеджер API токенов пользователей
 */
class TokenManager
{
    /**
     * Создание API токена для пользователя
     */
    public function createApiToken(User $user, string $name = 'api-token'): string
    {
        // Удаляем старые токены
        $user->tokens()->where('name', $name)->delete();
        
        // Создаем новый токен
        $token = $user->createToken($name);
        
        Log::info('API token created', [
            'user_id' => $user->id,
            'token_name' => $name,
        ]);
        
        return $token->plainTextToken;
    }

    /**
     * Отзыв всех API токенов пользователя
     */
    public function revokeAllTokens(User $user): bool
    {
        try {
            $user->tokens()->delete();
            
            Log::info('All API tokens revoked', [
                'user_id' => $user->id,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Token revocation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Отзыв конкретного токена
     */
    public function revokeToken(User $user, string $tokenName): bool
    {
        try {
            $user->tokens()->where('name', $tokenName)->delete();
            
            Log::info('API token revoked', [
                'user_id' => $user->id,
                'token_name' => $tokenName,
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Token revocation failed', [
                'user_id' => $user->id,
                'token_name' => $tokenName,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Получить все активные токены пользователя
     */
    public function getUserTokens(User $user): array
    {
        return $user->tokens()
            ->select(['id', 'name', 'created_at', 'last_used_at'])
            ->get()
            ->toArray();
    }
}