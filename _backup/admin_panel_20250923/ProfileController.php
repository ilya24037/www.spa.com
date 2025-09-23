<?php

namespace App\Application\Http\Controllers\Profile;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ProfileUpdateRequest;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\AdModerationService;
use App\Domain\Master\Services\MasterService;
use App\Domain\Review\Services\ReviewModerationService;
use App\Domain\User\Models\User;
use App\Domain\User\Services\UserService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для управления профилем пользователя
 * Отвечает за редактирование и удаление профиля
 */
class ProfileController extends Controller
{
    protected AdModerationService $moderationService;
    protected MasterService $masterService;
    protected ReviewModerationService $reviewModerationService;
    protected UserService $userService;

    public function __construct(
        AdModerationService $moderationService,
        MasterService $masterService,
        ReviewModerationService $reviewModerationService,
        UserService $userService
    ) {
        $this->moderationService = $moderationService;
        $this->masterService = $masterService;
        $this->reviewModerationService = $reviewModerationService;
        $this->userService = $userService;
    }

    /**
     * Отображение личного кабинета
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Определяем таб из URL
        $currentPath = $request->path();
        $tab = 'inactive'; // По умолчанию
        
        if (str_contains($currentPath, '/active/')) {
            $tab = 'active';
        } elseif (str_contains($currentPath, '/draft/')) {
            $tab = 'draft';
        } elseif (str_contains($currentPath, '/inactive/')) {
            $tab = 'inactive';
        } elseif (str_contains($currentPath, '/old/')) {
            $tab = 'old';
        } elseif (str_contains($currentPath, '/archive/')) {
            $tab = 'archive';
        } else {
            // Если запрос из параметра
            $tab = $request->get('tab', 'inactive');
        }
        
        // Маппинг табов на статусы
        $statusMap = [
            'waiting' => 'waiting_payment',
            'inactive' => ['rejected', 'pending_moderation', 'expired', 'waiting_payment'], // Ждут действий
            'active' => ['active', 'published'],
            'drafts' => 'draft',
            'draft' => 'draft',
            'archived' => 'archived',
            'archive' => 'archived',
            'old' => 'archived'
        ];

        $status = $statusMap[$tab] ?? 'waiting_payment';

        // Получаем объявления пользователя
        $query = Ad::where('user_id', $user->id);

        // Обработка массива статусов для вкладки "Ждут действий"
        if (is_array($status)) {
            $query->whereIn('status', $status);
        } else {
            $query->where('status', $status);
        }

        $ads = $query
            ->select([
                'id', 'title', 'status', 'prices', 'address',
                'description', 'phone', 'contact_method',
                'photos', 'views_count', 'geo',
                'created_at', 'updated_at', 'moderation_reason', 'is_published'
            ])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        // Преобразуем в формат для ItemCard
        $profiles = $ads->map(function ($ad) {
            // Обработка фотографий с защитой от пустых объектов
            $photos = $ad->photos;
            if (is_string($photos)) {
                $photos = json_decode($photos, true) ?? [];
            }
            $photos = is_array($photos) ? $photos : [];
            
            // Фильтруем пустые объекты и извлекаем валидные URL
            $validPhotos = [];
            foreach ($photos as $photo) {
                if (is_array($photo) && !empty($photo)) {
                    // Объект с данными - извлекаем URL
                    $url = $photo['preview'] ?? $photo['url'] ?? $photo['src'] ?? null;
                    if ($url) {
                        $validPhotos[] = $url;
                    }
                } elseif (is_string($photo) && !empty($photo)) {
                    // Обычная строка URL
                    $validPhotos[] = $photo;
                }
                // Пустые объекты {} игнорируем
            }
            
            $mainImage = null;
            $photosCount = count($validPhotos);
            
            if ($photosCount > 0) {
                $mainImage = $validPhotos[0];
            }
            
            // Если нет фото, используем демо
            if (!$mainImage) {
                $mainImage = '/images/masters/demo-' . (($ad->id % 4) + 1) . '.jpg';
                $photosCount = rand(1, 4);
            }
            
            // service_location больше не используется
            $serviceLocation = [];
            
            // Берем цену за час из нового поля prices (как с фото - простая логика)
            $prices = $ad->prices;
            if (is_string($prices)) {
                $prices = json_decode($prices, true) ?? [];
            }
            $prices = is_array($prices) ? $prices : [];
            
            // Простая логика: берем цену за час из черновика
            $finalPrice = $prices['apartments_1h'] ?? $prices['outcall_1h'] ?? 0;
            
            return [
                // Идентификация
                'id' => $ad->id,
                'title' => $ad->title,
                'status' => $ad->status,
                'is_published' => $ad->is_published,
                'moderation_reason' => $ad->moderation_reason,

                // Цена
                'price_from' => $finalPrice,
                'prices' => $prices,

                // Медиа
                'photo' => $mainImage,
                'photos' => $validPhotos,
                'photos_count' => $photosCount,

                // Основная информация
                'address' => $ad->address ?? '',
                'description' => $ad->description,
                'phone' => $ad->phone,

                // Счетчики
                'views' => $ad->views_count ?? 0,
                'messages' => 0,
                'favorites' => 0,
                'calls' => 0,
            ];
        })->toArray();
        
        // Получаем счетчики
        $countsQuery = Ad::where('user_id', $user->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
        
        $counts = [
            'active' => $countsQuery['active'] ?? 0,
            'draft' => $countsQuery['draft'] ?? 0,
            'waiting_payment' => $countsQuery['waiting_payment'] ?? 0,
            'old' => $countsQuery['archived'] ?? 0,
            'archive' => $countsQuery['archived'] ?? 0,
            'unreadMessages' => 0
        ];
        
        // Статистика пользователя
        $userStats = [
            'rating' => 4.2,
            'reviews_count' => 5
        ];

        // Добавляем админскую информацию
        $data = [
            'profiles' => $profiles,
            'counts' => $counts,
            'userStats' => $userStats,
            'activeTab' => $tab,
            'title' => 'Мои объявления'
        ];

        // Если пользователь админ или модератор, добавляем дополнительную информацию
        if ($user->isStaff()) {
            $data['isAdmin'] = true;
            $moderationStats = $this->moderationService->getModerationStats();
            $data['pendingCount'] = $moderationStats['pending'];

            // Добавляем полную статистику для виджета
            $data['moderationStats'] = [
                'pending' => $moderationStats['pending'],
                'processedToday' => $moderationStats['approved_today'] + $moderationStats['rejected_today'],
                'rejectedToday' => $moderationStats['rejected_today'],
                'avgTime' => '~15 мин',
                'weekData' => $this->getWeekModerationData()
            ];
        }

        return Inertia::render('Dashboard', $data);
    }

    /**
     * Отображение формы профиля пользователя
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => [
                'id' => $request->user()->id,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
                'profile' => $request->user()->getProfile(),
                'master_profile' => $request->user()->getMasterProfile(),
                'can_create_master_profile' => $request->user()->canCreateMasterProfile(),
            ]
        ]);
    }

    /**
     * Обновление информации профиля пользователя
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $request->user()->fill($validated);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Удаление аккаунта пользователя
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        auth()->logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Страница модерации объявлений (только для админов и модераторов)
     */
    public function moderation()
    {
        abort_if(!auth()->user()->hasPermission('moderate_ads') && !auth()->user()->hasPermission('moderate_content'), 403);

        $ads = $this->moderationService->getAdsForModeration(20);

        return Inertia::render('Dashboard', [
            'profiles' => $ads,
            'moderationMode' => true,
            'moderationStats' => $this->moderationService->getModerationStats()
        ]);
    }

    /**
     * Одобрить объявление
     */
    public function approve(Ad $ad)
    {
        abort_if(!auth()->user()->hasPermission('moderate_ads') && !auth()->user()->hasPermission('moderate_content'), 403);

        $success = $this->moderationService->approveAd($ad);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Объявление одобрено' : 'Ошибка при одобрении объявления'
        );
    }

    /**
     * Отклонить объявление
     */
    public function reject(Ad $ad, Request $request)
    {
        abort_if(!auth()->user()->hasPermission('moderate_ads') && !auth()->user()->hasPermission('moderate_content'), 403);

        $request->validate(['reason' => 'required|string|max:500']);

        $success = $this->moderationService->rejectAd($ad, $request->reason);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Объявление отклонено' : 'Ошибка при отклонении объявления'
        );
    }

    /**
     * Управление пользователями (только для админов)
     */
    public function users(Request $request)
    {
        abort_if(!auth()->user()->hasPermission('manage_users'), 403);

        $users = User::query()
            ->when($request->search, function($q, $search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Dashboard', [
            'profiles' => $users,
            'userManagementMode' => true
        ]);
    }

    /**
     * Блокировка/разблокировка пользователя
     */
    public function toggleUserStatus(User $user)
    {
        abort_if(!auth()->user()->hasPermission('manage_users'), 403);

        // Используем методы из трейта HasUserRoles
        $success = $user->isBlocked() ? $user->activate() : $user->suspend();

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Статус пользователя изменён' : 'Не удалось изменить статус'
        );
    }

    /**
     * Система жалоб (для админов и модераторов)
     */
    public function complaints()
    {
        abort_if(!auth()->user()->hasPermission('manage_complaints'), 403);

        // Используем поле rejection_reason для хранения жалоб
        $complaints = Ad::whereNotNull('rejection_reason')
            ->where('status', 'reported')
            ->with(['user'])
            ->paginate(20);

        return Inertia::render('Dashboard', [
            'profiles' => $complaints,
            'complaintsMode' => true
        ]);
    }

    /**
     * Обработка жалобы
     */
    public function resolveComplaint(Ad $ad, Request $request)
    {
        abort_if(!auth()->user()->hasPermission('manage_complaints'), 403);

        $ad->update([
            'status' => $request->action === 'block' ? 'blocked' : 'active',
            'rejection_reason' => null
        ]);

        return back()->with('success', 'Жалоба обработана');
    }

    /**
     * Управление мастерами (только для админов)
     */
    public function masters()
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        // Используем MasterService через репозиторий
        $masters = \App\Domain\Master\Models\MasterProfile::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Dashboard', [
            'profiles' => $masters,
            'mastersMode' => true
        ]);
    }

    /**
     * Верификация мастера
     */
    public function toggleMasterVerification($masterId)
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $master = \App\Domain\Master\Models\MasterProfile::findOrFail($masterId);

        // Используем MasterService для модерации
        if (!$master->is_verified) {
            $this->masterService->approveProfile($masterId, auth()->user());
        } else {
            $this->masterService->deactivateProfile($masterId, 'Снята верификация администратором');
        }

        return back()->with('success', 'Статус верификации изменен');
    }

    /**
     * Модерация отзывов (для админов и модераторов)
     */
    public function reviews()
    {
        abort_if(!auth()->user()->hasPermission('moderate_reviews') && !auth()->user()->hasPermission('moderate_content'), 403);

        // Используем ReviewModerationService::getPendingReviews()
        $reviews = $this->reviewModerationService->getPendingReviews(20);

        return Inertia::render('Dashboard', [
            'profiles' => $reviews,
            'reviewsMode' => true
        ]);
    }

    /**
     * Получить данные модерации за неделю
     */
    private function getWeekModerationData(): array
    {
        $weekData = [];
        $days = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

        // Генерируем случайные данные для демонстрации
        foreach ($days as $day) {
            $weekData[$day] = rand(5, 20);
        }

        return $weekData;
    }

    /**
     * Модерация отзыва
     */
    public function moderateReview($reviewId, Request $request)
    {
        abort_if(!auth()->user()->hasPermission('moderate_reviews') && !auth()->user()->hasPermission('moderate_content'), 403);

        // Используем ReviewModerationService для модерации
        if ($request->action === 'approve') {
            $this->reviewModerationService->approve($reviewId, auth()->user());
        } elseif ($request->action === 'reject') {
            $this->reviewModerationService->reject(
                $reviewId,
                auth()->user(),
                $request->reason ?? 'Отклонено администратором'
            );
        } else {
            $this->reviewModerationService->markAsSpam($reviewId, auth()->user());
        }

        return back()->with('success', 'Отзыв обработан');
    }
}