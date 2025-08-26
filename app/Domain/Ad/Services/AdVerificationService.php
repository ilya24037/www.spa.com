<?php

namespace App\Domain\Ad\Services;

use App\Domain\Ad\Models\Ad;
use App\Infrastructure\Services\MediaStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

/**
 * Сервис для работы с верификацией объявлений
 */
class AdVerificationService
{
    private MediaStorageService $mediaStorage;
    
    public function __construct(MediaStorageService $mediaStorage)
    {
        $this->mediaStorage = $mediaStorage;
    }
    
    /**
     * Загрузить проверочное фото
     */
    public function uploadVerificationPhoto(Ad $ad, UploadedFile $file): array
    {
        try {
            // Валидация файла
            if (!$this->validateImage($file)) {
                return [
                    'success' => false,
                    'message' => 'Недопустимый формат файла. Разрешены JPG, PNG до 10MB'
                ];
            }
            
            // Удаляем старое фото если есть
            if ($ad->verification_photo) {
                Storage::disk('public')->delete($ad->verification_photo);
            }
            
            // Сохраняем новое фото в защищённую директорию
            $path = $file->store('verification/photos/' . $ad->id, 'public');
            
            // Обновляем модель
            $ad->verification_photo = $path;
            $ad->verification_status = 'pending';
            $ad->verification_type = $ad->verification_video ? 'both' : 'photo';
            $ad->verification_metadata = array_merge(
                $ad->verification_metadata ?? [],
                [
                    'photo_uploaded_at' => now()->toDateTimeString(),
                    'photo_original_name' => $file->getClientOriginalName(),
                    'photo_size' => $file->getSize()
                ]
            );
            $ad->save();
            
            Log::info('Verification photo uploaded', [
                'ad_id' => $ad->id,
                'path' => $path
            ]);
            
            return [
                'success' => true,
                'message' => 'Фото загружено и отправлено на проверку',
                'path' => $path
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to upload verification photo', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке фото'
            ];
        }
    }
    
    /**
     * Загрузить проверочное видео
     */
    public function uploadVerificationVideo(Ad $ad, UploadedFile $file): array
    {
        try {
            // Валидация файла
            if (!$this->validateVideo($file)) {
                return [
                    'success' => false,
                    'message' => 'Недопустимый формат файла. Разрешены MP4, MOV до 50MB'
                ];
            }
            
            // Удаляем старое видео если есть
            if ($ad->verification_video) {
                Storage::disk('public')->delete($ad->verification_video);
            }
            
            // Сохраняем новое видео
            $path = $file->store('verification/videos/' . $ad->id, 'public');
            
            // Обновляем модель
            $ad->verification_video = $path;
            $ad->verification_status = 'pending';
            $ad->verification_type = $ad->verification_photo ? 'both' : 'video';
            $ad->verification_metadata = array_merge(
                $ad->verification_metadata ?? [],
                [
                    'video_uploaded_at' => now()->toDateTimeString(),
                    'video_original_name' => $file->getClientOriginalName(),
                    'video_size' => $file->getSize()
                ]
            );
            $ad->save();
            
            Log::info('Verification video uploaded', [
                'ad_id' => $ad->id,
                'path' => $path
            ]);
            
            return [
                'success' => true,
                'message' => 'Видео загружено и отправлено на проверку',
                'path' => $path
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to upload verification video', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при загрузке видео'
            ];
        }
    }
    
    /**
     * Верифицировать объявление (для админов)
     */
    public function verifyAd(Ad $ad, string $status, ?string $comment = null): array
    {
        try {
            if (!in_array($status, ['verified', 'rejected'])) {
                return [
                    'success' => false,
                    'message' => 'Недопустимый статус'
                ];
            }
            
            $ad->verification_status = $status;
            $ad->verification_comment = $comment;
            
            if ($status === 'verified') {
                $ad->verified_at = now();
                // Устанавливаем срок действия на 4 месяца
                $ad->verification_expires_at = now()->addMonths(4);
            } else {
                $ad->verified_at = null;
                $ad->verification_expires_at = null;
            }
            
            $ad->verification_metadata = array_merge(
                $ad->verification_metadata ?? [],
                [
                    'reviewed_at' => now()->toDateTimeString(),
                    'reviewed_by' => auth()->id()
                ]
            );
            
            $ad->save();
            
            Log::info('Ad verification reviewed', [
                'ad_id' => $ad->id,
                'status' => $status,
                'reviewer_id' => auth()->id()
            ]);
            
            return [
                'success' => true,
                'message' => $status === 'verified' 
                    ? 'Объявление верифицировано' 
                    : 'Верификация отклонена'
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to verify ad', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Ошибка при верификации'
            ];
        }
    }
    
    /**
     * Проверить истекшие верификации
     */
    public function checkExpiredVerifications(): int
    {
        $expiredCount = Ad::where('verification_status', 'verified')
            ->where('verification_expires_at', '<', now())
            ->update([
                'verification_status' => 'none',
                'verification_comment' => 'Срок верификации истёк'
            ]);
            
        if ($expiredCount > 0) {
            Log::info('Expired verifications updated', [
                'count' => $expiredCount
            ]);
        }
        
        return $expiredCount;
    }
    
    /**
     * Удалить файлы верификации
     */
    public function deleteVerificationFiles(Ad $ad): void
    {
        if ($ad->verification_photo) {
            Storage::disk('public')->delete($ad->verification_photo);
            $ad->verification_photo = null;
        }
        
        if ($ad->verification_video) {
            Storage::disk('public')->delete($ad->verification_video);
            $ad->verification_video = null;
        }
        
        $ad->verification_status = 'none';
        $ad->verification_type = null;
        $ad->verified_at = null;
        $ad->verification_expires_at = null;
        $ad->verification_comment = null;
        $ad->verification_metadata = null;
        
        $ad->save();
    }
    
    /**
     * Получить инструкции для верификации
     */
    public function getVerificationInstructions(): array
    {
        return [
            'photo' => [
                'title' => 'Фото с листком бумаги',
                'steps' => [
                    'Напишите на листке от руки текущую дату',
                    'Добавьте надпись "для FEIPITER"',
                    'Сделайте фото с листком так, чтобы было видно лицо',
                    'Загрузите фото в формате JPG или PNG'
                ],
                'requirements' => [
                    'Надписи должны быть написаны от руки',
                    'Фото должно быть актуальным',
                    'Хорошее освещение',
                    'Размер файла до 10MB'
                ]
            ],
            'video' => [
                'title' => 'Видео с произнесением даты',
                'steps' => [
                    'Запишите видео, где произносите текущую дату',
                    'Покажите себя с разных ракурсов',
                    'Сделайте жест "✌" (два пальца)',
                    'Длительность 5-10 секунд'
                ],
                'requirements' => [
                    'Четкое произношение даты',
                    'Хорошее освещение',
                    'Формат MP4 или MOV',
                    'Размер файла до 50MB'
                ]
            ]
        ];
    }
    
    /**
     * Валидация изображения
     */
    private function validateImage(UploadedFile $file): bool
    {
        $allowedMimes = ['image/jpeg', 'image/png'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        return in_array($file->getMimeType(), $allowedMimes) && 
               $file->getSize() <= $maxSize;
    }
    
    /**
     * Валидация видео
     */
    private function validateVideo(UploadedFile $file): bool
    {
        $allowedMimes = ['video/mp4', 'video/quicktime'];
        $maxSize = 50 * 1024 * 1024; // 50MB
        
        return in_array($file->getMimeType(), $allowedMimes) && 
               $file->getSize() <= $maxSize;
    }
}