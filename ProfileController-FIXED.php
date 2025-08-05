<?php

namespace App\Application\Http\Controllers\Profile;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ProfileUpdateRequest;
use App\Application\Http\Requests\ProfileDestroyRequest;
use App\Domain\User\Services\UserService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для управления профилем пользователя
 * Отвечает за редактирование и удаление профиля
 * ОБНОВЛЕН согласно DDD принципам и CLAUDE.md стандартам
 */
class ProfileController extends Controller
{
    private UserService $userService;
    
    // Константы для магических чисел
    private const PROFILE_COMPLETION_FULL = 100;
    private const PROFILE_COMPLETION_PARTIAL = 75;
    private const RECENT_BOOKINGS_LIMIT = 5;
    
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Отображение личного кабинета
     */
    public function index(Request $request): Response
    {
        try {
            $user = $request->user();
            
            // Получаем статистику через сервис (кешированную)
            $stats = $this->userService->getUserStats($user);
            
            return Inertia::render('Dashboard', [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profile' => $user->getProfile(),
                    'master_profile' => $user->getMasterProfile(),
                ],
                'stats' => [
                    'bookings_count' => $stats['bookings_count'] ?? 0,
                    'active_bookings' => $user->hasActiveBookings(),
                    'profile_completion' => $user->hasCompleteProfile() 
                        ? self::PROFILE_COMPLETION_FULL 
                        : self::PROFILE_COMPLETION_PARTIAL,
                    // ✅ DDD: Используем новые интеграционные методы
                    'reviews_count' => $stats['reviews_count'] ?? 0,
                    'favorites_count' => $stats['favorites_count'] ?? 0,
                    'ads_count' => $stats['ads_count'] ?? 0,
                ],
                'recent_bookings' => $user->getBookings()
                    ->take(self::RECENT_BOOKINGS_LIMIT)
                    ->map(function ($booking) {
                        return [
                            'id' => $booking->id,
                            'status' => $booking->status,
                            'scheduled_at' => $booking->scheduled_at,
                            'service_type' => $booking->service_type,
                            'price' => $booking->price,
                        ];
                    }),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to load user dashboard', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return Inertia::render('Dashboard', [
                'error' => 'Не удалось загрузить данные профиля'
            ]);
        }
    }

    /**
     * Отображение формы профиля пользователя
     */
    public function edit(Request $request): Response
    {
        try {
            $user = $request->user();
            
            return Inertia::render('Profile/Edit', [
                'mustVerifyEmail' => $user instanceof MustVerifyEmail,
                'status' => session('status'),
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role,
                    'profile' => $user->getProfile(),
                    'master_profile' => $user->getMasterProfile(),
                    'can_create_master_profile' => $user->canCreateMasterProfile(),
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to load profile edit form', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('dashboard')
                ->with('error', 'Не удалось загрузить форму редактирования профиля');
        }
    }

    /**
     * Обновление информации профиля пользователя
     * ✅ ИСПРАВЛЕНО: Используем UserService и обработку ошибок
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            $validatedData = $request->validated();
            
            // ✅ DDD: Используем сервисный слой вместо прямой работы с моделью
            $updated = $this->userService->updateProfile($user, $validatedData);
            
            if ($updated) {
                Log::info('User profile updated successfully', [
                    'user_id' => $user->id,
                    'updated_fields' => array_keys($validatedData)
                ]);
                
                return Redirect::route('profile.edit')
                    ->with('status', 'profile-updated')
                    ->with('success', 'Профиль успешно обновлен');
            }
            
            return Redirect::route('profile.edit')
                ->with('error', 'Не удалось обновить профиль');
                
        } catch (\InvalidArgumentException $e) {
            Log::warning('Profile update validation failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->withErrors(['validation' => $e->getMessage()]);
            
        } catch (\Exception $e) {
            Log::error('Profile update failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Ошибка при обновлении профиля');
        }
    }

    /**
     * Удаление аккаунта пользователя
     * ✅ ИСПРАВЛЕНО: Используем UserService и обработку ошибок
     */
    public function destroy(ProfileDestroyRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();
            
            // ✅ DDD: Используем сервисный слой для безопасного удаления
            $deleted = $this->userService->deleteUserData($user);
            
            if ($deleted) {
                // Logout только после успешного удаления
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                Log::info('User account deleted successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                
                return Redirect::to('/')
                    ->with('success', 'Аккаунт успешно удален');
            }
            
            return back()->with('error', 'Не удалось удалить аккаунт');
            
        } catch (\Exception $e) {
            Log::error('Account deletion failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Ошибка при удалении аккаунта');
        }
    }
}