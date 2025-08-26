<?php

namespace App\Application\Http\Resources\Booking;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Application\Http\Resources\User\UserResource;
use App\Application\Http\Resources\Master\MasterResource;
use App\Application\Http\Resources\Service\ServiceResource;
use App\Application\Http\Resources\Payment\PaymentResource;

/**
 * API Resource для бронирований
 * Структурирует данные бронирования для API ответов
 */
class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'status' => $this->status,
            'type' => $this->type,
            'platform' => $this->platform,
            
            // Временная информация
            'schedule' => [
                'booking_date' => $this->booking_date,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'duration' => $this->duration,
                'duration_minutes' => $this->duration_minutes,
                'scheduled_at' => $this->scheduled_at,
                'confirmed_at' => $this->confirmed_at,
                'completed_at' => $this->completed_at,
                'cancelled_at' => $this->cancelled_at,
            ],
            
            // Ценовая информация
            'pricing' => [
                'base_price' => $this->base_price,
                'service_price' => $this->service_price,
                'delivery_fee' => $this->delivery_fee,
                'travel_fee' => $this->travel_fee,
                'discount_amount' => $this->discount_amount,
                'total_price' => $this->total_price,
                'deposit_amount' => $this->deposit_amount,
                'paid_amount' => $this->paid_amount,
            ],
            
            // Платежная информация
            'payment' => [
                'payment_method' => $this->payment_method,
                'payment_status' => $this->payment_status,
                'payment_due_at' => $this->payment_due_at,
                'is_paid' => $this->isPaid(),
                'is_partially_paid' => $this->isPartiallyPaid(),
                'remaining_amount' => $this->getRemainingAmount(),
            ],
            
            // Адреса и местоположение
            'location' => [
                'address' => $this->address,
                'address_details' => $this->address_details,
                'client_address' => $this->client_address,
                'master_address' => $this->master_address,
                'geo_lat' => $this->geo_lat,
                'geo_lng' => $this->geo_lng,
            ],
            
            // Контактная информация
            'contacts' => $this->when($this->canViewContacts($request->user()), [
                'client_name' => $this->client_name,
                'client_phone' => $this->client_phone,
                'client_email' => $this->client_email,
                'master_phone' => $this->master_phone,
            ]),
            
            // Комментарии и заметки
            'comments' => [
                'client_comment' => $this->client_comment,
                'notes' => $this->notes,
                'internal_notes' => $this->when($this->canViewInternalNotes($request->user()), 
                    $this->internal_notes
                ),
                'cancellation_reason' => $this->cancellation_reason,
                'rejection_reason' => $this->rejection_reason,
            ],
            
            // Дополнительная информация
            'requirements' => [
                'equipment_required' => $this->equipment_required,
                'preparation_required' => $this->preparation_required,
                'special_requirements' => $this->special_requirements,
            ],
            
            // Связанные данные (загружаются при необходимости)
            'client' => new UserResource($this->whenLoaded('client')),
            'master' => new UserResource($this->whenLoaded('master')),
            'master_profile' => new MasterResource($this->whenLoaded('masterProfile')),
            'service' => new ServiceResource($this->whenLoaded('service')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'review' => $this->whenLoaded('review'),
            
            // Статусы и флаги
            'flags' => [
                'can_cancel' => $this->canCancel(),
                'can_confirm' => $this->canConfirm($request->user()),
                'can_complete' => $this->canComplete($request->user()),
                'can_reschedule' => $this->canReschedule($request->user()),
                'can_review' => $this->canReview($request->user()),
                'is_overdue' => $this->isOverdue(),
                'is_today' => $this->isToday(),
                'is_upcoming' => $this->isUpcoming(),
            ],
            
            // Временные метки
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            
            // Вычисляемые поля
            'time_until_booking' => $this->getTimeUntilBooking(),
            'status_display' => $this->getStatusDisplay(),
            'progress_percentage' => $this->getProgressPercentage(),
            
            // Административные поля (только для администраторов/владельцев)
            $this->mergeWhen($this->canViewAdminFields($request->user()), [
                'commission_amount' => $this->commission_amount,
                'commission_rate' => $this->commission_rate,
                'master_earnings' => $this->master_earnings,
                'platform_earnings' => $this->platform_earnings,
                'dispute_status' => $this->dispute_status,
                'quality_score' => $this->quality_score,
                'feedback_score' => $this->feedback_score,
            ])
        ];
    }
    
    /**
     * Проверяет, может ли пользователь видеть контактную информацию
     */
    private function canViewContacts($user): bool
    {
        if (!$user) return false;
        
        return $user->id === $this->client_id || 
               $user->id === $this->master_id || 
               $user->isAdmin();
    }
    
    /**
     * Проверяет, может ли пользователь видеть внутренние заметки
     */
    private function canViewInternalNotes($user): bool
    {
        if (!$user) return false;
        
        return $user->id === $this->master_id || $user->isAdmin();
    }
    
    /**
     * Проверяет, может ли пользователь видеть административные поля
     */
    private function canViewAdminFields($user): bool
    {
        if (!$user) return false;
        
        return $user->isAdmin() || 
               ($user->id === $this->master_id && $this->status === 'completed');
    }
}