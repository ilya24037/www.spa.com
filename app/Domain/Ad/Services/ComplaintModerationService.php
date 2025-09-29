<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Complaint;
use App\Domain\Admin\Services\AdminActionsService;
use App\Domain\User\Models\User;
use App\Enums\AdStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComplaintModerationService
{
    public function __construct(
        private AdminActionsService $adminActions
    ) {}

    /**
     * Разрешить жалобу
     */
    public function resolve(Complaint $complaint, User $admin, string $note = null, bool $blockAd = false): void
    {
        DB::transaction(function () use ($complaint, $admin, $note, $blockAd) {
            // Разрешаем жалобу
            $complaint->resolve($admin, $note);

            // Блокируем объявление если необходимо
            if ($blockAd && $complaint->ad) {
                $complaint->ad->update([
                    'status' => AdStatus::BLOCKED->value,
                    'moderation_comment' => 'Заблокировано по результатам рассмотрения жалобы #' . $complaint->id,
                ]);

                Log::info('Ad blocked due to complaint resolution', [
                    'ad_id' => $complaint->ad->id,
                    'complaint_id' => $complaint->id,
                    'admin_id' => $admin->id
                ]);
            }

            // Логируем действие
            $this->adminActions->logAction(
                'complaint_resolved',
                Complaint::class,
                $complaint->id,
                [
                    'resolution_note' => $note,
                    'ad_blocked' => $blockAd,
                    'ad_id' => $complaint->ad_id,
                ]
            );

            Log::info('Complaint resolved', [
                'complaint_id' => $complaint->id,
                'admin_id' => $admin->id,
                'ad_blocked' => $blockAd
            ]);
        });
    }

    /**
     * Отклонить жалобу
     */
    public function reject(Complaint $complaint, User $admin, string $note = null): void
    {
        DB::transaction(function () use ($complaint, $admin, $note) {
            // Отклоняем жалобу
            $complaint->reject($admin, $note);

            // Логируем действие
            $this->adminActions->logAction(
                'complaint_rejected',
                Complaint::class,
                $complaint->id,
                [
                    'rejection_note' => $note,
                    'ad_id' => $complaint->ad_id,
                ]
            );

            Log::info('Complaint rejected', [
                'complaint_id' => $complaint->id,
                'admin_id' => $admin->id
            ]);
        });
    }

    /**
     * Массовое разрешение жалоб
     */
    public function bulkResolve(array $complaintIds, User $admin, string $note = null, bool $blockAds = false): int
    {
        $resolved = 0;

        DB::transaction(function () use ($complaintIds, $admin, $note, $blockAds, &$resolved) {
            $complaints = Complaint::whereIn('id', $complaintIds)
                ->where('status', 'pending')
                ->get();

            foreach ($complaints as $complaint) {
                $this->resolve($complaint, $admin, $note, $blockAds);
                $resolved++;
            }

            // Логируем массовое действие
            $this->adminActions->logAction(
                'complaints_bulk_resolved',
                Complaint::class,
                null,
                [
                    'count' => $resolved,
                    'complaint_ids' => $complaintIds,
                    'ads_blocked' => $blockAds,
                ]
            );
        });

        return $resolved;
    }

    /**
     * Массовое отклонение жалоб
     */
    public function bulkReject(array $complaintIds, User $admin, string $note = null): int
    {
        $rejected = 0;

        DB::transaction(function () use ($complaintIds, $admin, $note, &$rejected) {
            $complaints = Complaint::whereIn('id', $complaintIds)
                ->where('status', 'pending')
                ->get();

            foreach ($complaints as $complaint) {
                $this->reject($complaint, $admin, $note);
                $rejected++;
            }

            // Логируем массовое действие
            $this->adminActions->logAction(
                'complaints_bulk_rejected',
                Complaint::class,
                null,
                [
                    'count' => $rejected,
                    'complaint_ids' => $complaintIds,
                ]
            );
        });

        return $rejected;
    }

    /**
     * Получить статистику жалоб
     */
    public function getStatistics(): array
    {
        return [
            'total' => Complaint::count(),
            'pending' => Complaint::where('status', 'pending')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'rejected' => Complaint::where('status', 'rejected')->count(),
            'today' => Complaint::whereDate('created_at', today())->count(),
            'this_week' => Complaint::where('created_at', '>=', now()->startOfWeek())->count(),
            'this_month' => Complaint::where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }
}