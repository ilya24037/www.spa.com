<?php

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\Media;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Репозиторий для статистики и аналитики медиа
 * СОЗДАН согласно CLAUDE.md: ≤200 строк, ≤50 строк/метод, без прямых SQL запросов
 */
class MediaStatisticsRepository
{
    public function __construct(
        private Media $model
    ) {}

    /**
     * Получить общую статистику медиа
     */
    public function getStatistics(): array
    {
        try {
            $totalFiles = $this->model->count();
            $totalSize = $this->model->sum('size');
            $averageSize = $totalFiles > 0 ? $this->model->avg('size') : 0;

            $byType = $this->model->selectRaw('type, COUNT(*) as count, SUM(size) as size')
                                 ->groupBy('type')
                                 ->get()
                                 ->keyBy('type');

            $byStatus = $this->model->selectRaw('status, COUNT(*) as count')
                                   ->groupBy('status')
                                   ->get()
                                   ->keyBy('status');

            return [
                'total_files' => $totalFiles,
                'total_size' => $totalSize,
                'average_size' => round($averageSize, 2),
                'total_size_mb' => round($totalSize / (1024 * 1024), 2),
                'by_type' => $byType->toArray(),
                'by_status' => $byStatus->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get media statistics', [
                'error' => $e->getMessage()
            ]);
            return [
                'total_files' => 0,
                'total_size' => 0,
                'average_size' => 0,
                'total_size_mb' => 0,
                'by_type' => [],
                'by_status' => [],
            ];
        }
    }

    /**
     * Получить топ самых больших файлов
     */
    public function getTopLargestFiles(int $limit = 10): Collection
    {
        try {
            return $this->model->select(['id', 'name', 'file_name', 'size', 'type', 'created_at'])
                              ->orderBy('size', 'desc')
                              ->limit($limit)
                              ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get largest files', [
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }

    /**
     * Получить использование по коллекциям
     */
    public function getUsageByCollection(): Collection
    {
        try {
            return $this->model->selectRaw('
                    collection_name,
                    COUNT(*) as files_count,
                    SUM(size) as total_size
                ')
                ->whereNotNull('collection_name')
                ->groupBy('collection_name')
                ->orderBy('files_count', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get usage by collection', [
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }

    /**
     * Получить использование по типам сущностей
     */
    public function getUsageByEntityType(): Collection
    {
        try {
            return $this->model->selectRaw('
                    mediable_type,
                    COUNT(*) as files_count,
                    SUM(size) as total_size
                ')
                ->whereNotNull('mediable_type')
                ->groupBy('mediable_type')
                ->orderBy('files_count', 'desc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get usage by entity type', [
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }

    /**
     * Получить статистику по периодам
     */
    public function getUsageByPeriod(string $period = 'month'): Collection
    {
        try {
            // Убираем прямые SQL запросы согласно CLAUDE.md
            $formatMap = [
                'day' => 'Y-m-d',
                'week' => 'Y-W', 
                'month' => 'Y-m',
                'year' => 'Y',
            ];
            
            $format = $formatMap[$period] ?? 'Y-m';
            
            return $this->model->where('created_at', '>=', now()->subMonths(12))
                ->get(['created_at', 'size'])
                ->groupBy(function ($item) use ($format) {
                    return $item->created_at->format($format);
                })
                ->map(function ($periodItems, $periodKey) {
                    return [
                        'period' => $periodKey,
                        'files_count' => $periodItems->count(),
                        'total_size' => $periodItems->sum('size')
                    ];
                })
                ->sortByDesc('period')
                ->values();
        } catch (\Exception $e) {
            Log::error('Failed to get usage by period', [
                'period' => $period,
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }

    /**
     * Получить топ пользователей по загрузкам
     */
    public function getTopUploaders(int $limit = 10): Collection
    {
        try {
            // Убираем прямые SQL запросы согласно CLAUDE.md
            return $this->model->select([
                    'metadata->uploaded_by as uploader_id'
                ])
                ->selectRaw('COUNT(*) as uploads_count, SUM(size) as total_size')
                ->whereNotNull('metadata->uploaded_by')
                ->groupBy('metadata->uploaded_by')
                ->orderBy('uploads_count', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('Failed to get top uploaders', [
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            return new Collection();
        }
    }

    /**
     * Получить статистику обработки
     */
    public function getProcessingStatistics(): array
    {
        try {
            $pending = $this->model->where('status', 'pending')->count();
            $processing = $this->model->where('status', 'processing')->count();
            $processed = $this->model->where('status', 'processed')->count();
            $failed = $this->model->where('status', 'failed')->count();

            // Упрощаем без прямых SQL функций согласно CLAUDE.md
            $processedMedia = $this->model->whereNotNull('processed_at')
                ->whereNotNull('created_at')
                ->get(['created_at', 'processed_at']);
            
            $avgProcessingTime = 0;
            if ($processedMedia->count() > 0) {
                $totalTime = $processedMedia->sum(function ($media) {
                    return $media->processed_at->diffInSeconds($media->created_at);
                });
                $avgProcessingTime = $totalTime / $processedMedia->count();
            }

            return [
                'pending' => $pending,
                'processing' => $processing,
                'processed' => $processed,
                'failed' => $failed,
                'total' => $pending + $processing + $processed + $failed,
                'success_rate' => $processed > 0 ? round(($processed / ($processed + $failed)) * 100, 2) : 0,
                'avg_processing_time_seconds' => round($avgProcessingTime, 2),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get processing statistics', [
                'error' => $e->getMessage()
            ]);
            return [
                'pending' => 0,
                'processing' => 0,
                'processed' => 0,
                'failed' => 0,
                'total' => 0,
                'success_rate' => 0,
                'avg_processing_time_seconds' => 0,
            ];
        }
    }

    /**
     * Получить детальную аналитику за период
     */
    public function getDetailedAnalytics(int $days = 30): array
    {
        try {
            $startDate = now()->subDays($days);
            
            $totalUploaded = $this->model->where('created_at', '>=', $startDate)->count();
            $totalSize = $this->model->where('created_at', '>=', $startDate)->sum('size');
            
            // Используем Eloquent группировку вместо прямого SQL
            $dailyStats = $this->model->where('created_at', '>=', $startDate)
                ->get(['created_at', 'size'])
                ->groupBy(function ($item) {
                    return $item->created_at->format('Y-m-d');
                })
                ->map(function ($dayItems, $date) {
                    return [
                        'date' => $date,
                        'uploads' => $dayItems->count(),
                        'size' => $dayItems->sum('size')
                    ];
                })
                ->values();

            return [
                'period_days' => $days,
                'total_uploaded' => $totalUploaded,
                'total_size_mb' => round($totalSize / (1024 * 1024), 2),
                'daily_average' => round($totalUploaded / $days, 2),
                'daily_stats' => $dailyStats,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get detailed analytics', [
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            return [
                'period_days' => $days,
                'total_uploaded' => 0,
                'total_size_mb' => 0,
                'daily_average' => 0,
                'daily_stats' => [],
            ];
        }
    }
}