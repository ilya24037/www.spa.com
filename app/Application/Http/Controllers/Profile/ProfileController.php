<?php

namespace App\Application\Http\Controllers\Profile;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\ProfileUpdateRequest;
use App\Application\Http\Requests\Admin\UpdateAdByAdminRequest;
use App\Application\Http\Requests\Admin\BulkActionRequest;
use App\Domain\Ad\Enums\AdStatus;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\AdModerationService;
use App\Domain\Admin\Models\AdminLog;
use App\Domain\Admin\Services\AdminActionsService;
use App\Domain\Admin\Traits\LogsAdminActions;
use App\Domain\Master\Services\MasterService;
use App\Domain\Review\Services\ReviewModerationService;
use App\Domain\User\Models\User;
use App\Domain\User\Services\UserService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    use LogsAdminActions;
    protected AdModerationService $moderationService;
    protected AdminActionsService $adminActionsService;
    protected MasterService $masterService;
    protected ReviewModerationService $reviewModerationService;
    protected UserService $userService;

    public function __construct(
        AdModerationService $moderationService,
        AdminActionsService $adminActionsService,
        MasterService $masterService,
        ReviewModerationService $reviewModerationService,
        UserService $userService
    ) {
        $this->moderationService = $moderationService;
        $this->adminActionsService = $adminActionsService;
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
        $tab = 'active'; // По умолчанию показываем активные
        
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
            $tab = $request->get('tab', 'active');
        }
        
        // Маппинг табов на статусы
        $statusMap = [
            'waiting' => 'waiting_payment',
            'inactive' => ['rejected', 'expired', 'waiting_payment'], // Ждут действий (БЕЗ pending_moderation)
            'active' => ['active', 'published', 'pending_moderation'], // Активные включают на модерации
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
                'status' => is_object($ad->status) ? $ad->status->value : $ad->status,
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
            // Активные включают и объявления на модерации
            'active' => ($countsQuery['active'] ?? 0) + ($countsQuery['pending_moderation'] ?? 0),
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
                'slug' => $request->user()->slug,
                'rating' => $request->user()->rating,
                'reviews_count' => $request->user()->reviews_count,
                'is_verified' => $request->user()->is_verified,
                'profile' => $request->user()->getProfile(),
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
     * @deprecated Используйте /admin/ads с фильтром по статусу модерации
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
     * Просмотр всех объявлений для администратора
     * @deprecated Используйте /admin/ads в новой админ-панели Filament
     */
    public function allAds(Request $request)
    {
        // Проверка прав
        $this->authorize('viewAllAds', Ad::class);

        $tab = $request->get('tab', 'all');
        $search = $request->get('search');

        // Базовый запрос
        $query = Ad::with(['user']);

        // Фильтрация по вкладкам
        switch ($tab) {
            case 'active':
                $query->where('status', 'active')->where('is_published', true);
                break;
            case 'moderation':
                $query->where('status', 'active')->where('is_published', false)
                      ->orWhere('status', 'pending_moderation');
                break;
            case 'draft':
                $query->where('status', 'draft');
                break;
            case 'rejected':
                $query->where('status', 'rejected');
                break;
            case 'expired':
                $query->where('status', 'expired');
                break;
            case 'archived':
                $query->where('status', 'archived');
                break;
            case 'blocked':
                $query->where('status', 'blocked');
                break;
        }

        // Поиск
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('id', $search)
                  ->orWhereHas('user', function($q) use ($search) {
                      $q->where('email', 'like', "%{$search}%");
                  });
            });
        }

        // Получаем объявления с пагинацией
        $ads = $query->orderBy('created_at', 'desc')->paginate(20);

        // Преобразуем для отображения (используем существующий формат)
        $profiles = $ads->map(function($ad) {
            // Используем тот же формат что и в index()
            $photos = is_string($ad->photos) ? json_decode($ad->photos, true) : $ad->photos;
            $mainImage = is_array($photos) && count($photos) > 0 ? $photos[0]['url'] ?? '' : '';

            return [
                'id' => $ad->id,
                'title' => $ad->title,
                'status' => $ad->status,
                'is_published' => $ad->is_published,
                'moderation_reason' => $ad->moderation_reason,
                'price_from' => $ad->starting_price ?? 0,
                'photo' => $mainImage,
                'photos_count' => is_array($photos) ? count($photos) : 0,
                'address' => $ad->address ?? '',
                'phone' => $ad->phone,
                'views' => $ad->views_count ?? 0,
                // Добавляем даты создания и обновления
                'created_at' => $ad->created_at->format('d.m.Y H:i'),
                'updated_at' => $ad->updated_at->format('d.m.Y H:i'),
                // Добавляем информацию о жалобах
                'complaints_count' => $ad->complaints_count,
                'has_unresolved_complaints' => $ad->has_unresolved_complaints,
                // Добавляем информацию о пользователе
                'user' => [
                    'id' => $ad->user->id,
                    'email' => $ad->user->email,
                    'role' => $ad->user->role
                ]
            ];
        });

        // Статистика по статусам
        $stats = [
            'all' => Ad::count(),
            'active' => Ad::where('status', 'active')->where('is_published', true)->count(),
            'moderation' => Ad::where(function($q) {
                $q->where('status', 'active')->where('is_published', false)
                  ->orWhere('status', 'pending_moderation');
            })->count(),
            'draft' => Ad::where('status', 'draft')->count(),
            'rejected' => Ad::where('status', 'rejected')->count(),
            'expired' => Ad::where('status', 'expired')->count(),
            'archived' => Ad::where('status', 'archived')->count(),
            'blocked' => Ad::where('status', 'blocked')->count(),
        ];

        return Inertia::render('Dashboard', [
            'profiles' => $profiles,
            'adminMode' => true,  // Новый флаг для админ-режима
            'activeTab' => $tab,
            'stats' => $stats,
            'title' => 'Управление объявлениями',
            'counts' => $stats,  // Для совместимости с Dashboard
            'pagination' => [
                'total' => $ads->total(),
                'current' => $ads->currentPage(),
                'per_page' => $ads->perPage()
            ]
        ]);
    }

    /**
     * Редактирование объявления администратором
     */
    public function editAd(Ad $ad)
    {
        $this->authorize('updateAsAdmin', $ad);

        return Inertia::render('Ad/Edit', [
            'ad' => $ad->load(['user']),
            'adminEdit' => true,
            'returnUrl' => '/profile/admin/ads'
        ]);
    }

    /**
     * Сохранение изменений администратором
     */
    public function updateAd(UpdateAdByAdminRequest $request, Ad $ad)
    {
        $this->authorize('updateAsAdmin', $ad);

        $updated = $this->adminActionsService->updateAdAsAdmin(
            $ad,
            $request->validated()
        );

        if ($updated) {
            return redirect()->route('profile.admin.ads')
                ->with('success', 'Объявление успешно обновлено');
        }

        return back()->with('error', 'Не удалось обновить объявление');
    }

    /**
     * Массовые действия над объявлениями
     */
    public function bulkAction(BulkActionRequest $request)
    {
        $this->authorize('bulkAction', Ad::class);

        $result = $this->adminActionsService->performBulkAction(
            $request->ids,
            $request->action,
            $request->reason
        );

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()
            ->with('error', $result['message'])
            ->withErrors($result['errors']);
    }

    /**
     * Одобрить объявление
     */
    public function approve(Ad $ad)
    {
        $this->authorize('approve', $ad);

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
        $this->authorize('reject', $ad);

        $request->validate(['reason' => 'required|string|max:500']);

        $success = $this->moderationService->rejectAd($ad, $request->reason);

        return back()->with(
            $success ? 'success' : 'error',
            $success ? 'Объявление отклонено' : 'Ошибка при отклонении объявления'
        );
    }

    /**
     * Управление пользователями (только для админов)
     * @deprecated Используйте /admin/users в новой админ-панели Filament
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

        // Преобразуем пользователей в формат для отображения
        $profiles = $users->map(function($user) {
            return [
                'id' => $user->id,
                'title' => $user->name ?? $user->email,
                'status' => $user->status ?? 'active',
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
                'email_verified_at' => $user->email_verified_at,
                'is_published' => $user->status === 'active',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => $user->role
                ]
            ];
        });

        return Inertia::render('Dashboard', [
            'profiles' => $profiles,
            'userManagementMode' => true,
            'title' => 'Управление пользователями',
            'pagination' => [
                'total' => $users->total(),
                'current' => $users->currentPage(),
                'per_page' => $users->perPage()
            ]
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
        abort_if(auth()->user()->role->value !== 'admin', 403);

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
     * Просмотр логов администраторов
     */
    public function adminLogs(Request $request)
    {
        abort_if(!auth()->user()->isAdmin(), 403);

        $logs = AdminLog::with(['admin'])
            ->when($request->admin_id, function($q, $adminId) {
                $q->where('admin_id', $adminId);
            })
            ->when($request->action, function($q, $action) {
                $q->where('action', $action);
            })
            ->when($request->from, function($q, $from) {
                $q->whereDate('created_at', '>=', $from);
            })
            ->when($request->to, function($q, $to) {
                $q->whereDate('created_at', '<=', $to);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Получаем список админов для фильтра
        $admins = User::whereIn('role', ['admin', 'moderator'])->get();

        // Получаем уникальные действия для фильтра
        $actions = AdminLog::distinct('action')->pluck('action');

        return Inertia::render('Admin/Logs', [
            'logs' => $logs,
            'admins' => $admins,
            'actions' => $actions,
            'filters' => $request->only(['admin_id', 'action', 'from', 'to'])
        ]);
    }

    /**
     * Управление мастерами (только для админов)
     * @deprecated Используйте /admin/master-profiles в новой админ-панели Filament
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

    /*
    |--------------------------------------------------------------------------
    | BACKWARD COMPATIBILITY REDIRECT METHODS
    |--------------------------------------------------------------------------
    |
    | Эти методы обеспечивают совместимость со старыми роутами, перенаправляя
    | пользователей на новую админ-панель Filament вместо старого интерфейса.
    | Все административные функции теперь доступны по адресу /admin
    |
    */

    /**
     * Перенаправление модерации на новую админ-панель
     * Старый роут: /profile/moderation → Новый: /admin/ads
     */
    public function moderationRedirect()
    {
        // Редирект с фильтром по статусу модерации
        return redirect('/admin/ads?tableFilters[status][value]=pending_moderation')
            ->with('info', 'Модерация объявлений теперь доступна в новой админ-панели Filament');
    }

    /**
     * Перенаправление управления объявлениями на новую админ-панель
     * Старый роут: /profile/admin/ads → Новый: /admin/ads
     */
    public function allAdsRedirect(Request $request)
    {
        // Формируем параметры для редиректа в Filament
        $params = [];

        // Конвертируем tab в фильтр по статусу
        $tab = $request->get('tab', 'all');
        if ($tab !== 'all') {
            $statusMap = [
                'active' => 'active',
                'moderation' => 'pending_moderation',
                'draft' => 'draft',
                'rejected' => 'rejected',
                'expired' => 'expired',
                'archived' => 'archived',
                'blocked' => 'blocked',
            ];

            if (isset($statusMap[$tab])) {
                $params['tableFilters[status][value]'] = $statusMap[$tab];
            }
        }

        // Конвертируем поиск
        if ($search = $request->get('search')) {
            $params['tableSearch'] = $search;
        }

        $url = '/admin/ads';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return redirect($url)
            ->with('info', 'Управление объявлениями теперь доступно в новой админ-панели Filament');
    }

    /**
     * Перенаправление редактирования объявлений на новую админ-панель
     * Старый роут: /profile/admin/ads/{id}/edit → Новый: /admin/ads/{id}/edit
     */
    public function editAdRedirect(Ad $ad)
    {
        return redirect("/admin/ads/{$ad->id}/edit")
            ->with('info', 'Редактирование объявлений теперь доступно в новой админ-панели');
    }

    /**
     * Перенаправление управления пользователями на новую админ-панель
     * Старый роут: /profile/users → Новый: /admin/users
     */
    public function usersRedirect(Request $request)
    {
        // Конвертируем параметры поиска
        $params = [];

        if ($search = $request->get('search')) {
            $params['tableSearch'] = $search;
        }

        $url = '/admin/users';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return redirect($url)
            ->with('info', 'Управление пользователями теперь доступно в новой админ-панели Filament');
    }

    /**
     * Перенаправление системы жалоб на новую админ-панель
     * Старый роут: /profile/complaints → Новый: /admin/complaints
     */
    public function complaintsRedirect()
    {
        return redirect('/admin/complaints')
            ->with('info', 'Система жалоб теперь доступна в новой админ-панели');
    }

    /**
     * Перенаправление управления мастерами на новую админ-панель
     * Старый роут: /profile/masters → Новый: /admin/master-profiles
     */
    public function mastersRedirect()
    {
        return redirect('/admin/master-profiles')
            ->with('info', 'Управление мастерами теперь доступно в новой админ-панели');
    }

    /**
     * Перенаправление модерации отзывов на новую админ-панель
     * Старый роут: /profile/reviews → Новый: /admin/reviews
     */
    public function reviewsRedirect()
    {
        return redirect('/admin/reviews')
            ->with('info', 'Модерация отзывов теперь доступна в новой админ-панели');
    }

    /**
     * Перенаправление логов на новую админ-панель
     * Старый роут: /profile/admin/logs → Новый: /admin/admin-logs
     */
    public function adminLogsRedirect(Request $request)
    {
        return redirect('/admin/admin-logs')
            ->with('info', 'Просмотр логов теперь доступен в новой админ-панели');
    }

    /**
     * Обработчики POST-запросов также перенаправляют на админ-панель
     */
    public function approveRedirect(Ad $ad)
    {
        return redirect('/admin/ads')
            ->with('info', 'Одобрение объявлений теперь доступно в новой админ-панели');
    }

    public function rejectRedirect(Ad $ad)
    {
        return redirect('/admin/ads')
            ->with('info', 'Отклонение объявлений теперь доступно в новой админ-панели');
    }

    public function bulkActionRedirect(BulkActionRequest $request)
    {
        return redirect('/admin/ads')
            ->with('info', 'Массовые действия теперь доступны в новой админ-панели');
    }

    public function toggleUserStatusRedirect(User $user)
    {
        return redirect('/admin/users')
            ->with('info', 'Управление статусом пользователей теперь доступно в новой админ-панели');
    }

    public function resolveComplaintRedirect(Ad $ad, Request $request)
    {
        return redirect('/admin/complaints')
            ->with('info', 'Обработка жалоб теперь доступна в новой админ-панели');
    }

    public function toggleMasterVerificationRedirect($masterId)
    {
        return redirect('/admin/master-profiles')
            ->with('info', 'Верификация мастеров теперь доступна в новой админ-панели');
    }

    public function moderateReviewRedirect($reviewId, Request $request)
    {
        return redirect('/admin/reviews')
            ->with('info', 'Модерация отзывов теперь доступна в новой админ-панели');
    }
}