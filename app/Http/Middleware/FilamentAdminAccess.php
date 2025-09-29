<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FilamentAdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Логируем для диагностики
        \Log::info('FilamentAdminAccess: Checking access', [
            'user_exists' => $user ? 'yes' : 'no',
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'user_role_type' => $user ? gettype($user->role) : 'N/A'
        ]);

        if (!$user) {
            \Log::info('FilamentAdminAccess: No user, redirecting to login');
            return redirect()->route('login');
        }

        // Проверка роли - доступ только для админов и модераторов
        $allowedRoles = [
            UserRole::ADMIN,
            UserRole::MODERATOR
        ];

        // Проверяем роль (может быть как enum, так и строка)
        $hasAccess = false;
        if ($user->role instanceof UserRole) {
            $hasAccess = in_array($user->role, $allowedRoles, true);
            \Log::info('FilamentAdminAccess: Role is enum', ['has_access' => $hasAccess]);
        } else {
            // Если роль хранится как строка
            $allowedRoleValues = array_map(fn($role) => $role->value, $allowedRoles);
            $hasAccess = in_array($user->role, $allowedRoleValues, true);
            \Log::info('FilamentAdminAccess: Role is string', [
                'user_role' => $user->role,
                'allowed_values' => $allowedRoleValues,
                'has_access' => $hasAccess
            ]);
        }

        if (!$hasAccess) {
            \Log::warning('FilamentAdminAccess: Access denied', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role
            ]);
            abort(403, 'У вас нет доступа к административной панели');
        }

        \Log::info('FilamentAdminAccess: Access granted', [
            'user_id' => $user->id,
            'user_email' => $user->email
        ]);

        return $next($request);
    }
}