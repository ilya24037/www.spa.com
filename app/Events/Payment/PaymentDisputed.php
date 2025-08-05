<?php

namespace App\Events\Payment;

use App\Domain\Payment\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Событие спора/диспута по платежу
 */
class PaymentDisputed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment,
        public array $disputeData = []
    ) {}

    /**
     * Каналы для трансляции события
     */
    public function broadcastOn(): array
    {
        return [
            // Приватный канал для пользователя
            new PrivateChannel('user.' . $this->payment->user_id),
            
            // Канал присутствия для админов (высокий приоритет)
            new PresenceChannel('admin.payments'),
            new PresenceChannel('admin.disputes'),
            
            // Приватный канал для мастера (если платеж связан с бронированием)
            ...$this->getMasterChannels(),
        ];
    }

    /**
     * Название события для трансляции
     */
    public function broadcastAs(): string
    {
        return 'payment.disputed';
    }

    /**
     * Данные для трансляции
     */
    public function broadcastWith(): array
    {
        return [
            'payment' => [
                'id' => $this->payment->id,
                'payment_number' => $this->payment->payment_number,
                'status' => $this->payment->status->value,
                'status_label' => $this->payment->status->getLabel(),
                'amount' => $this->payment->amount,
                'formatted_amount' => $this->payment->formatted_amount,
                'type' => $this->payment->type->value,
                'type_label' => $this->payment->type->getLabel(),
                'method' => $this->payment->method->value,
                'method_label' => $this->payment->method->getLabel(),
                'gateway' => $this->payment->gateway,
            ],
            'user' => [
                'id' => $this->payment->user_id,
                'name' => $this->payment->user->name,
                'email' => $this->payment->user->email,
            ],
            'dispute' => [
                'id' => $this->disputeData['id'] ?? null,
                'reason' => $this->disputeData['reason'] ?? 'unknown',
                'reason_label' => $this->getDisputeReasonLabel(),
                'amount' => ($this->disputeData['amount'] ?? 0) / 100, // Из центов
                'formatted_amount' => $this->getFormattedDisputeAmount(),
                'status' => $this->disputeData['status'] ?? 'needs_response',
                'evidence_required' => $this->disputeData['evidence_required'] ?? true,
                'response_deadline' => $this->getResponseDeadline(),
                'created_at' => isset($this->disputeData['created']) ? 
                    \Carbon\Carbon::createFromTimestamp($this->disputeData['created'])->toISOString() : 
                    now()->toISOString(),
            ],
            'payable' => $this->getPayableData(),
            'notification' => [
                'title' => 'Открыт спор по платежу',
                'message' => $this->getNotificationMessage(),
                'type' => 'payment_disputed',
                'priority' => 'urgent',
                'style' => 'danger',
                'sound' => true,
                'actions' => $this->getNotificationActions(),
            ],
            'next_actions' => $this->getNextActions(),
            'evidence_info' => $this->getEvidenceInfo(),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Получить каналы для мастера
     */
    protected function getMasterChannels(): array
    {
        if ($this->payment->payable_type === 'App\Models\Booking') {
            $booking = $this->payment->payable;
            if ($booking && $booking->master_id) {
                return [new PrivateChannel('master.' . $booking->master_id)];
            }
        }
        
        return [];
    }

    /**
     * Получить данные связанной сущности
     */
    protected function getPayableData(): ?array
    {
        if (!$this->payment->payable) {
            return null;
        }

        return match($this->payment->payable_type) {
            'App\Models\Booking' => [
                'type' => 'booking',
                'id' => $this->payment->payable->id,
                'booking_number' => $this->payment->payable->booking_number ?? null,
                'service_name' => $this->payment->payable->service?->name,
                'start_time' => $this->payment->payable->start_time?->toISOString(),
                'master_name' => $this->payment->payable->master?->name,
                'status' => $this->payment->payable->status,
            ],
            default => [
                'type' => 'other',
                'id' => $this->payment->payable->id,
            ],
        };
    }

    /**
     * Получить лейбл причины диспута
     */
    protected function getDisputeReasonLabel(): string
    {
        return match($this->disputeData['reason'] ?? '') {
            'fraudulent' => 'Мошенническая операция',
            'unrecognized' => 'Неизвестная операция',
            'duplicate' => 'Дублированный платеж',
            'product_unacceptable' => 'Неприемлемое качество услуги',
            'product_not_received' => 'Услуга не оказана',
            'general' => 'Общие претензии',
            'credit_not_processed' => 'Возврат не обработан',
            'canceled_recurring' => 'Отмененная подписка',
            'other' => 'Другое',
            default => 'Неизвестная причина',
        };
    }

    /**
     * Получить отформатированную сумму диспута
     */
    protected function getFormattedDisputeAmount(): string
    {
        $amount = ($this->disputeData['amount'] ?? 0) / 100;
        return number_format($amount, 2, ',', ' ') . ' ₽';
    }

    /**
     * Получить крайний срок ответа
     */
    protected function getResponseDeadline(): string
    {
        // Обычно 7 дней на ответ по диспуту
        return now()->addDays(7)->toISOString();
    }

    /**
     * Получить сообщение уведомления
     */
    protected function getNotificationMessage(): string
    {
        $amount = $this->payment->formatted_amount;
        $reason = $this->getDisputeReasonLabel();

        return "По вашему платежу на сумму {$amount} открыт спор. " .
               "Причина: {$reason}. " .
               "Необходимо предоставить доказательства в течение 7 дней.";
    }

    /**
     * Получить действия для уведомления
     */
    protected function getNotificationActions(): array
    {
        return [
            [
                'label' => 'Ответить на спор',
                'url' => route('disputes.respond', $this->disputeData['id'] ?? 'unknown'),
                'primary' => true,
                'urgent' => true,
            ],
            [
                'label' => 'Детали платежа',
                'url' => route('payments.show', $this->payment->id),
            ],
            [
                'label' => 'Загрузить доказательства',
                'url' => route('disputes.evidence', $this->disputeData['id'] ?? 'unknown'),
            ],
            [
                'label' => 'Связаться с поддержкой',
                'url' => route('support.chat'),
                'urgent' => true,
            ],
        ];
    }

    /**
     * Получить следующие действия
     */
    protected function getNextActions(): array
    {
        return [
            'СРОЧНО: Ответьте на спор в течение 7 дней',
            'Подготовьте документы, подтверждающие оказание услуги',
            'Загрузите фотографии, переписку, чеки',
            'Предоставьте детальное описание оказанных услуг',
            'Обратитесь в поддержку за помощью',
        ];
    }

    /**
     * Получить информацию о необходимых доказательствах
     */
    protected function getEvidenceInfo(): array
    {
        return [
            'types' => [
                'service_documentation' => 'Документы об оказанных услугах',
                'customer_communication' => 'Переписка с клиентом',
                'receipt' => 'Чеки и квитанции',
                'photos' => 'Фотографии процесса оказания услуг',
                'shipping_documentation' => 'Документы о доставке (если применимо)',
                'uncategorized_file' => 'Другие доказательства',
            ],
            'deadline' => $this->getResponseDeadline(),
            'max_files' => 10,
            'max_file_size' => '25MB',
            'supported_formats' => ['PDF', 'JPG', 'PNG', 'DOC', 'DOCX'],
            'tips' => [
                'Загружайте четкие и читаемые документы',
                'Включите всю переписку с клиентом',
                'Добавьте фотографии оказанных услуг',
                'Предоставьте детальное описание каждого файла',
                'Не загружайте персональные данные третьих лиц',
            ],
        ];
    }

    /**
     * Определить должно ли событие транслироваться
     */
    public function broadcastWhen(): bool
    {
        return !empty($this->disputeData);
    }

    /**
     * Высокоприоритетная очередь для диспутов
     */
    public function broadcastQueue(): string
    {
        return 'urgent-broadcasts';
    }

    /**
     * Событие должно обрабатываться немедленно
     */
    public function shouldBroadcastNow(): bool
    {
        return true;
    }
}