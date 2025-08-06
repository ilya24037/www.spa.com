<?php

namespace App\Domain\Analytics\Events;

use App\Domain\Analytics\Models\PageView;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие отслеживания просмотра страницы
 */
class PageViewTracked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public PageView $pageView;

    /**
     * Create a new event instance.
     */
    public function __construct(PageView $pageView)
    {
        $this->pageView = $pageView;
    }

    /**
     * Получить данные для логирования
     */
    public function toArray(): array
    {
        return [
            'page_view_id' => $this->pageView->id,
            'user_id' => $this->pageView->user_id,
            'session_id' => $this->pageView->session_id,
            'url' => $this->pageView->url,
            'viewable_type' => $this->pageView->viewable_type,
            'viewable_id' => $this->pageView->viewable_id,
            'is_unique' => $this->pageView->isUniqueForUser(),
            'device_type' => $this->pageView->device_type,
            'viewed_at' => $this->pageView->viewed_at->toDateTimeString(),
        ];
    }
}