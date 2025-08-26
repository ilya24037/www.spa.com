<?php

namespace App\Application\Http\Controllers;

use Illuminate\Http\Request;
use App\Domain\Master\Services\MasterService;
use App\Domain\Media\Services\MediaService;

class TestController extends Controller
{
    private MasterService $masterService;
    private MediaService $mediaService;

    public function __construct(MasterService $masterService, MediaService $mediaService)
    {
        $this->masterService = $masterService;
        $this->mediaService = $mediaService;
    }

    public function addPhotos()
    {
        try {
            // Найдем мастера через сервис
            $master = $this->masterService->findByDisplayName('Елена Сидорова');
            
            if (!$master) {
                return response()->json(['error' => 'Мастер Елена Сидорова не найден!'], 404);
            }

            // Удалим существующие фотографии через сервис
            $this->mediaService->clearMasterPhotos($master);

            // Добавим тестовые фотографии
            $photos = [
                [
                    'path' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=400&h=600&fit=crop&crop=face',
                    'is_main' => true,
                    'order' => 1
                ],
                [
                    'path' => 'https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=400&h=600&fit=crop&crop=face',
                    'is_main' => false,
                    'order' => 2
                ],
                [
                    'path' => 'https://images.unsplash.com/photo-1594824388853-0d0e4a8a1b4c?w=400&h=600&fit=crop&crop=face',
                    'is_main' => false,
                    'order' => 3
                ],
                [
                    'path' => 'https://images.unsplash.com/photo-1607990281513-2c110a25bd8c?w=400&h=600&fit=crop&crop=face',
                    'is_main' => false,
                    'order' => 4
                ],
                [
                    'path' => 'https://images.unsplash.com/photo-1588516903720-8ceb67f9ef84?w=400&h=600&fit=crop&crop=face',
                    'is_main' => false,
                    'order' => 5
                ],
                [
                    'path' => 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=600&fit=crop&crop=face',
                    'is_main' => false,
                    'order' => 6
                ]
            ];

            // Добавляем фотографии через сервис
            $addedPhotos = $this->mediaService->addTestPhotos($master, $photos);

            // Обновляем мастера через сервис
            $this->masterService->updateMasterStatus($master, [
                'avatar' => $photos[0]['path'],
                'is_verified' => true,
                'is_premium' => true,
                'premium_until' => now()->addMonths(3),
                'rating' => 4.9
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Успешно добавлено ' . count($photos) . ' фотографий для мастера: ' . $master->display_name,
                'photos_count' => count($addedPhotos),
                'master_url' => route('masters.show', ['slug' => $master->slug, 'master' => $master->id])
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Добавить локальные фотографии (из папки public/images/masters)
     */
    public function addLocalPhotos()
    {
        try {
            // Найдем мастера через сервис
            $master = $this->masterService->findByDisplayName('Елена Сидорова');
            
            if (!$master) {
                return response()->json(['error' => 'Мастер Елена Сидорова не найден!'], 404);
            }

            // Удалим существующие фотографии через сервис
            $this->mediaService->clearMasterPhotos($master);

            // Локальные фотографии (поместите их в public/images/masters/)
            $localPhotos = [
                'images/masters/elena1.jpg',
                'images/masters/elena2.jpg', 
                'images/masters/elena3.jpg',
                'images/masters/elena4.jpg',
                'images/masters/elena5.jpg',
                'images/masters/elena6.jpg'
            ];

            // Добавляем локальные фотографии через сервис
            $addedPhotos = $this->mediaService->addLocalPhotos($master, $localPhotos);

            // Обновляем мастера через сервис
            $this->masterService->updateMasterStatus($master, [
                'avatar' => $addedPhotos[0]['url'] ?? asset('images/no-photo.jpg'),
                'is_verified' => true,
                'is_premium' => true,
                'premium_until' => now()->addMonths(3),
                'rating' => 4.9
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Добавлено ' . count($addedPhotos) . ' фотографий для мастера: ' . $master->display_name,
                'photos' => $addedPhotos,
                'master_url' => route('masters.show', ['slug' => $master->slug, 'master' => $master->id]),
                'instructions' => 'Поместите реальные фотографии в папку public/images/masters/ с именами elena1.jpg, elena2.jpg и т.д.'
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
} 
