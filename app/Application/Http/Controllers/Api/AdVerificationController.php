<?php

namespace App\Application\Http\Controllers\Api;

use App\Application\Http\Controllers\Controller;
use App\Domain\Ad\Models\Ad;
use App\Domain\Ad\Services\AdVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdVerificationController extends Controller
{
    private AdVerificationService $verificationService;
    
    public function __construct(AdVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }
    
    /**
     * Загрузить проверочное фото
     */
    public function uploadPhoto(Request $request, Ad $ad): JsonResponse
    {
        // Проверка прав доступа
        if ($ad->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа к этому объявлению'
            ], 403);
        }
        
        // Валидация
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,jpg,png|max:10240' // 10MB
        ]);
        
        $result = $this->verificationService->uploadVerificationPhoto(
            $ad, 
            $request->file('photo')
        );
        
        return response()->json($result, $result['success'] ? 200 : 422);
    }
    
    /**
     * Загрузить проверочное видео
     */
    public function uploadVideo(Request $request, Ad $ad): JsonResponse
    {
        // Проверка прав доступа
        if ($ad->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа к этому объявлению'
            ], 403);
        }
        
        // Валидация
        $request->validate([
            'video' => 'required|mimes:mp4,mov|max:51200' // 50MB
        ]);
        
        $result = $this->verificationService->uploadVerificationVideo(
            $ad, 
            $request->file('video')
        );
        
        return response()->json($result, $result['success'] ? 200 : 422);
    }
    
    /**
     * Получить статус верификации
     */
    public function getStatus(Ad $ad): JsonResponse
    {
        // Проверка прав доступа
        if ($ad->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа к этому объявлению'
            ], 403);
        }
        
        return response()->json([
            'success' => true,
            'status' => $ad->verification_status,
            'type' => $ad->verification_type,
            'comment' => $ad->verification_comment,
            'verified_at' => $ad->verified_at?->format('d.m.Y'),
            'expires_at' => $ad->verification_expires_at?->format('d.m.Y'),
            'is_expired' => $ad->isVerificationExpired(),
            'needs_update' => $ad->needsVerificationUpdate(),
            'badge' => $ad->getVerificationBadge(),
            'has_photo' => !empty($ad->verification_photo),
            'has_video' => !empty($ad->verification_video)
        ]);
    }
    
    /**
     * Удалить проверочное фото
     */
    public function deletePhoto(Ad $ad): JsonResponse
    {
        // Проверка прав доступа
        if ($ad->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет доступа к этому объявлению'
            ], 403);
        }
        
        $this->verificationService->deleteVerificationFiles($ad);
        
        return response()->json([
            'success' => true,
            'message' => 'Файлы верификации удалены'
        ]);
    }
    
    /**
     * Получить инструкции для верификации
     */
    public function getInstructions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'instructions' => $this->verificationService->getVerificationInstructions()
        ]);
    }
}