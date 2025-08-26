<?php

namespace App\Infrastructure\Listeners\Master\Processors;

use App\Domain\Master\Repositories\MasterRepository;
use App\Infrastructure\Services\MediaService;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Обработчик медиа обновлений профиля
 */
class ProfileMediaProcessor
{
    private MasterRepository $masterRepository;
    private MediaService $mediaService;

    public function __construct(
        MasterRepository $masterRepository,
        MediaService $mediaService
    ) {
        $this->masterRepository = $masterRepository;
        $this->mediaService = $mediaService;
    }

    /**
     * Обработать медиа обновления
     */
    public function processMediaUpdates($masterProfile, array $updatedData, array $changes): void
    {
        foreach ($changes['media'] as $mediaType) {
            if (!isset($updatedData[$mediaType])) {
                continue;
            }

            try {
                switch ($mediaType) {
                    case 'avatar':
                        $this->updateAvatar($masterProfile, $updatedData['avatar']);
                        break;
                        
                    case 'portfolio_photos':
                        $this->updatePortfolioPhotos($masterProfile, $updatedData['portfolio_photos']);
                        break;
                        
                    case 'certificate_photos':
                        $this->updateCertificatePhotos($masterProfile, $updatedData['certificate_photos']);
                        break;
                }
            } catch (Exception $e) {
                Log::warning("Failed to update {$mediaType} for master profile", [
                    'master_profile_id' => $masterProfile->id,
                    'media_type' => $mediaType,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Обновить аватар
     */
    private function updateAvatar($masterProfile, $avatarData): void
    {
        if ($masterProfile->avatar_url) {
            $this->mediaService->deleteFile($masterProfile->avatar_url);
        }

        $processedAvatar = $this->mediaService->processProfilePhoto(
            $avatarData,
            $masterProfile->id,
            'avatar'
        );

        $masterProfile->update(['avatar_url' => $processedAvatar['url']]);
    }

    /**
     * Обновить фото портфолио
     */
    private function updatePortfolioPhotos($masterProfile, array $portfolioData): void
    {
        $currentPhotos = $this->masterRepository->getPortfolioPhotos($masterProfile->id);
        
        foreach ($portfolioData as $photoData) {
            if (isset($photoData['id']) && $photoData['id']) {
                $this->masterRepository->updatePortfolioPhoto($photoData['id'], $photoData);
            } else {
                $processedPhoto = $this->mediaService->processPortfolioPhoto(
                    $photoData,
                    $masterProfile->id
                );

                $this->masterRepository->addPortfolioPhoto($masterProfile->id, [
                    'url' => $processedPhoto['url'],
                    'thumbnail_url' => $processedPhoto['thumbnail_url'],
                    'description' => $photoData['description'] ?? null,
                    'is_primary' => $photoData['is_primary'] ?? false,
                ]);
            }
        }
    }

    /**
     * Обновить фото сертификатов
     */
    private function updateCertificatePhotos($masterProfile, array $certificateData): void
    {
        foreach ($certificateData as $certData) {
            if (isset($certData['id']) && $certData['id']) {
                $this->masterRepository->updateCertificate($certData['id'], $certData);
            } else {
                $processedCert = $this->mediaService->processCertificatePhoto(
                    $certData,
                    $masterProfile->id
                );

                $this->masterRepository->addCertificate($masterProfile->id, [
                    'name' => $certData['name'] ?? 'Сертификат',
                    'issuer' => $certData['issuer'] ?? null,
                    'issued_at' => $certData['issued_at'] ?? null,
                    'photo_url' => $processedCert['url'],
                ]);
            }
        }
    }
}