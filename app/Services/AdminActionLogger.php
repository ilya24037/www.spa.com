<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class AdminActionLogger
{
    protected string $channel = 'admin_actions';

    /**
     * Log an admin action
     */
    public function log(string $action, Model $model = null, array $context = []): void
    {
        $user = Auth::user();

        $logData = [
            'action' => $action,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
        ];

        if ($model) {
            $logData['model'] = [
                'type' => get_class($model),
                'id' => $model->getKey(),
                'attributes' => $model->getAttributes(),
            ];
        }

        if (!empty($context)) {
            $logData['context'] = $context;
        }

        // Log to specific channel
        Log::channel($this->channel)->info("Admin Action: {$action}", $logData);

        // Also log critical actions to main log
        if ($this->isCriticalAction($action)) {
            Log::warning("Critical Admin Action: {$action}", $logData);
        }
    }

    /**
     * Log a bulk action
     */
    public function logBulkAction(string $action, array $modelIds, string $modelClass, array $context = []): void
    {
        $user = Auth::user();

        $logData = [
            'action' => "bulk_{$action}",
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip_address' => request()->ip(),
            'model_class' => $modelClass,
            'model_ids' => $modelIds,
            'count' => count($modelIds),
            'timestamp' => now()->toIso8601String(),
            'context' => $context,
        ];

        Log::channel($this->channel)->info("Bulk Admin Action: {$action}", $logData);
    }

    /**
     * Log an error during admin action
     */
    public function logError(string $action, \Exception $exception, array $context = []): void
    {
        $user = Auth::user();

        $logData = [
            'action' => $action,
            'error' => true,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip_address' => request()->ip(),
            'exception' => [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
            'timestamp' => now()->toIso8601String(),
            'context' => $context,
        ];

        Log::channel($this->channel)->error("Admin Action Error: {$action}", $logData);
    }

    /**
     * Log successful login to admin panel
     */
    public function logLogin(): void
    {
        $this->log('admin_login', null, [
            'session_id' => session()->getId(),
            'remember_me' => request()->boolean('remember'),
        ]);
    }

    /**
     * Log logout from admin panel
     */
    public function logLogout(): void
    {
        $this->log('admin_logout', null, [
            'session_id' => session()->getId(),
        ]);
    }

    /**
     * Log resource access
     */
    public function logResourceAccess(string $resource, string $action = 'index'): void
    {
        $this->log("resource_access", null, [
            'resource' => $resource,
            'action' => $action,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);
    }

    /**
     * Determine if an action is critical
     */
    protected function isCriticalAction(string $action): bool
    {
        $criticalActions = [
            'delete',
            'bulk_delete',
            'block',
            'bulk_block',
            'approve',
            'bulk_approve',
            'reject',
            'bulk_reject',
            'restore',
            'force_delete',
            'update_role',
            'update_permissions',
        ];

        return in_array($action, $criticalActions) ||
               str_starts_with($action, 'delete_') ||
               str_starts_with($action, 'block_');
    }

    /**
     * Get logs for a specific user
     */
    public function getUserLogs(int $userId, int $limit = 100): array
    {
        // This would typically read from the log file or database
        // For now, returning placeholder
        return [
            'user_id' => $userId,
            'logs' => [],
            'message' => 'Logs are stored in storage/logs/admin_actions.log',
        ];
    }

    /**
     * Get recent admin actions
     */
    public function getRecentActions(int $limit = 50): array
    {
        // This would typically read from the log file or database
        // For now, returning placeholder
        return [
            'recent_actions' => [],
            'message' => 'Logs are stored in storage/logs/admin_actions.log',
        ];
    }
}