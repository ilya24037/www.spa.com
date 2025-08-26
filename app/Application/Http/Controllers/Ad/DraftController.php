<?php

namespace App\Application\Http\Controllers\Ad;

use App\Application\Http\Controllers\Controller;
use App\Domain\Ad\Services\AdService;
use App\Domain\Ad\Services\DraftService;
use App\Domain\Ad\Models\Ad;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Контроллер для работы с черновиками объявлений
 * Простой и понятный, только черновики
 */
class DraftController extends Controller
{
    public function __construct(
        private AdService $adService,
        private DraftService $draftService
    ) {}

    /**
     * Показать черновик
     */
    public function show(Ad $ad): Response
    {
        $this->authorize('view', $ad);

        return Inertia::render('Draft/Show', [
            'ad' => $this->draftService->prepareForDisplay($ad)
        ]);
    }

    /**
     * Создать новый черновик или обновить существующий
     * Как было до рефакторинга - если передан ad_id в данных, обновляем его
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        
        // Метод store используется только для создания НОВЫХ черновиков
        // Для обновления существующих используется метод update
        
        // Обрабатываем загруженные файлы фотографий
        $data = $request->all();
        
        // Обработка полей prices (они приходят как prices[key])
        $prices = [];
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'prices[')) {
                $fieldName = str_replace(['prices[', ']'], '', $key);
                $prices[$fieldName] = $value;
            }
        }
        if (!empty($prices)) {
            $data['prices'] = $prices;
        }
        
        // Логируем все входящие данные для отладки bikini_zone
        \Log::info("🔍 DraftController: Входящие данные для создания черновика", [
            'all_data' => $request->all(),
            'has_bikini_zone' => $request->has('bikini_zone'),
            'bikini_zone_value' => $request->input('bikini_zone'),
            'parameters_data' => $request->input('parameters'),
            'has_parameters' => $request->has('parameters')
        ]);
        
        // Обеспечиваем наличие текстовых полей (даже если пустые)
        if (!isset($data['description'])) {
            $data['description'] = '';
        }
        if (!isset($data['additional_features'])) {
            $data['additional_features'] = '';
        }
        if (!isset($data['schedule_notes'])) {
            $data['schedule_notes'] = '';
        }
        
        // Обработка поля faq
        if ($request->has('faq')) {
            $faqData = $request->input('faq');
            // Если faq пришел как JSON строка, декодируем
            if (is_string($faqData)) {
                $decoded = json_decode($faqData, true);
                $data['faq'] = is_array($decoded) ? $decoded : [];
            } else {
                $data['faq'] = is_array($faqData) ? $faqData : [];
            }
        }
        
        // Преобразование media_settings в отдельные boolean поля
        if (isset($data['media_settings'])) {
            $settings = is_string($data['media_settings']) 
                ? json_decode($data['media_settings'], true) 
                : $data['media_settings'];
            
            if (is_array($settings)) {
                $data['show_photos_in_gallery'] = in_array('show_photos_in_gallery', $settings);
                $data['allow_download_photos'] = in_array('allow_download_photos', $settings);
                $data['watermark_photos'] = in_array('watermark_photos', $settings);
            }
            
            unset($data['media_settings']); // Удаляем, т.к. такого поля нет в БД
        }
        
        // Обработка полей верификации
        if ($request->has('verification_photo')) {
            $verificationPhoto = $request->input('verification_photo');
            
            // Если это base64 изображение - сохраняем как файл
            if ($verificationPhoto && str_starts_with($verificationPhoto, 'data:image')) {
                try {
                    // Извлекаем base64 данные
                    $base64Parts = explode(',', $verificationPhoto);
                    if (count($base64Parts) === 2) {
                        $imageData = base64_decode($base64Parts[1]);
                        
                        // Определяем расширение
                        $mimeType = str_replace('data:', '', explode(';', $base64Parts[0])[0]);
                        $extension = match($mimeType) {
                            'image/jpeg' => 'jpg',
                            'image/png' => 'png',
                            default => 'jpg'
                        };
                        
                        // Генерируем имя файла и сохраняем
                        $fileName = 'verification_' . uniqid() . '_' . time() . '.' . $extension;
                        $path = 'verification/' . Auth::id() . '/' . $fileName;
                        Storage::disk('public')->put($path, $imageData);
                        
                        $data['verification_photo'] = '/storage/' . $path;
                        \Log::info('📸 Verification photo saved: ' . $data['verification_photo']);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error saving verification photo: ' . $e->getMessage());
                    // Если не удалось сохранить - не сохраняем вообще
                    $data['verification_photo'] = null;
                }
            } elseif ($verificationPhoto && str_starts_with($verificationPhoto, '/storage/')) {
                // Если это уже путь к файлу - сохраняем как есть
                $data['verification_photo'] = $verificationPhoto;
            } else {
                // Иначе очищаем
                $data['verification_photo'] = null;
            }
        }
        if ($request->has('verification_status')) {
            $data['verification_status'] = $request->input('verification_status');
        }
        if ($request->has('verification_video')) {
            $data['verification_video'] = $request->input('verification_video');
        }
        if ($request->has('verification_comment')) {
            $data['verification_comment'] = $request->input('verification_comment');
        }
        if ($request->has('verification_expires_at')) {
            $data['verification_expires_at'] = $request->input('verification_expires_at');
        }
        
        // ✅ УПРОЩЕННАЯ ЛОГИКА: используем helper метод для обработки фотографий
        $photos = $this->processPhotosFromRequest($request);
        if (!empty($photos)) {
            $data['photos'] = $photos;
            \Log::info('📸 Store: Фото обработаны', ['count' => count($photos)]);
        }
        
        // Обработка видео файлов (пробуем оба формата как в update)
        $uploadedVideos = [];
        $existingVideos = [];
        $videoIndex = 0;
        $maxVideoIterations = 10;
        
        // Проверяем сначала единичный файл video (без индекса)
        if ($videoIndex === 0 && $request->hasFile('video')) {
            \Log::info('🎥 STORE: Обнаружен единичный файл video');
            $file = $request->file('video');
            
            // Если это массив файлов, берем первый
            if (is_array($file) && count($file) > 0) {
                $file = $file[0];
            }
            
            // Проверяем размер файла (макс 100MB для видео)
            if ($file && !is_array($file) && $file->getSize() <= 100 * 1024 * 1024) {
                try {
                    // Генерируем уникальное имя файла
                    $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('videos/' . Auth::id(), $fileName, 'public');
                    
                    // ВАЖНО: Сохраняем как простую строку URL (как в архиве)
                    $uploadedVideos[] = '/storage/' . $path;
                    
                    \Log::info('🎥 Единичное видео загружено:', [
                        'path' => '/storage/' . $path
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Ошибка загрузки единичного видео: ' . $e->getMessage());
                }
            }
            $videoIndex++;
        }
        
        while ($videoIndex < $maxVideoIterations) {
            // Пробуем ВСЕ форматы: video_0_file, video.0.file, video_0, video[0]
            $underscoreFileNotation = "video_{$videoIndex}_file";
            $underscoreNotation = "video_{$videoIndex}";
            $dotNotation = "video.{$videoIndex}.file";
            $bracketNotation = "video[{$videoIndex}]";
            
            $hasFile = $request->hasFile($underscoreFileNotation) || 
                      $request->hasFile($dotNotation) || 
                      $request->hasFile($bracketNotation);
            $hasValue = $request->has($underscoreNotation) || 
                       $request->has("video.{$videoIndex}") || 
                       $request->has($bracketNotation);
            
            if (!$hasFile && !$hasValue) {
                break; // Больше нет видео
            }
            
            if ($hasFile) {
                // Пробуем получить файл из всех возможных форматов
                $file = $request->file($underscoreFileNotation) ?: 
                       $request->file($dotNotation) ?: 
                       $request->file($bracketNotation);
                
                // Проверяем размер файла (макс 100MB для видео)
                if ($file && $file->getSize() > 100 * 1024 * 1024) {
                    $videoIndex++;
                    continue;
                }
                
                try {
                    if ($file) {
                        // Генерируем уникальное имя файла
                        $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('videos/' . Auth::id(), $fileName, 'public');
                        
                        // ВАЖНО: Сохраняем как простую строку URL (как в архиве)
                        $uploadedVideos[] = '/storage/' . $path;
                        
                        \Log::info('🎥 Видео загружено:', [
                            'index' => $videoIndex,
                            'path' => '/storage/' . $path
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Ошибка загрузки видео: ' . $e->getMessage());
                }
            } elseif ($hasValue) {
                // Получаем значение из любого формата
                $videoValue = $request->input($underscoreNotation) ?: 
                             $request->input("video.{$videoIndex}") ?: 
                             $request->input($bracketNotation);
                if (is_string($videoValue) && !empty($videoValue) && $videoValue !== '[]') {
                    // Проверяем, это JSON строка с объектом видео или обычная строка
                    $decoded = json_decode($videoValue, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        // Если это объект видео, извлекаем URL
                        if (isset($decoded['url'])) {
                            $existingVideos[] = $decoded['url'];
                        } else {
                            // Если нет URL, пропускаем
                            \Log::warning('🎥 Видео объект без URL:', ['video' => $decoded]);
                        }
                    } else {
                        // Обычная строка URL
                        $existingVideos[] = $videoValue;
                    }
                }
            }
            
            $videoIndex++;
        }
        
        // Обработка финального массива видео
        $finalVideos = [];
        
        // Проверяем явную очистку видео
        if ($request->input('video') === '[]') {
            $finalVideos = [];
        } else {
            // 1. Сначала добавляем существующие видео из запроса
            if (!empty($existingVideos)) {
                $finalVideos = $existingVideos;
            }
            
            // 2. Добавляем новые загруженные видео
            if (!empty($uploadedVideos)) {
                $finalVideos = array_merge($finalVideos, $uploadedVideos);
            }
        }
        
        if (!empty($finalVideos)) {
            $data['video'] = $finalVideos;
            \Log::info('🎥 Store: Видео сохранено', ['count' => count($finalVideos)]);
        }
        
        // Создаем новый черновик
        $ad = $this->draftService->saveOrUpdate(
            $data,
            Auth::user(),
            null // Всегда null, так как это создание нового
        );

        // Для Inertia запросов
        if ($request->header('X-Inertia')) {
            return redirect()
                ->to('/profile/items/draft/all')
                ->with('success', 'Черновик сохранен');
        }

        // Для AJAX запросов
        return response()->json([
            'success' => true,
            'message' => 'Черновик создан',
            'ad_id' => $ad->id
        ]);
    }

    /**
     * Обновить черновик
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            
            // 🔍 ДИАГНОСТИКА: Логируем все входящие данные
            \Log::info('🔍 DraftController: ВСЕ ВХОДЯЩИЕ ДАННЫЕ', [
                'request_all' => $request->all(),
                'request_files' => $request->allFiles(),
                'request_photos_keys' => array_keys(array_filter($request->all(), function($key) {
                    return str_starts_with($key, 'photos');
                }, ARRAY_FILTER_USE_KEY)),
                'request_method' => $request->method(),
                'request_content_type' => $request->header('Content-Type'),
                'request_is_multipart' => $request->isMethod('post') && str_contains($request->header('Content-Type'), 'multipart/form-data')
            ]);
            
            // Получаем существующие фото и видео из БД если редактируем
            $currentPhotos = [];
            $currentVideos = [];
            if ($id && $ad = \App\Domain\Ad\Models\Ad::find($id)) {
                if ($ad->photos) {
                    // photos уже является массивом благодаря JsonFieldsTrait
                    if (is_array($ad->photos)) {
                        $currentPhotos = $ad->photos;
                    } elseif (is_string($ad->photos)) {
                        // На случай если это строка JSON
                        $decoded = json_decode($ad->photos, true);
                        if (is_array($decoded)) {
                            $currentPhotos = $decoded;
                        }
                    }
                }
                if ($ad->video) {
                    // video уже является массивом благодаря JsonFieldsTrait
                    if (is_array($ad->video)) {
                        $currentVideos = $ad->video;
                    } elseif (is_string($ad->video)) {
                        // На случай если это строка JSON
                        $decoded = json_decode($ad->video, true);
                        if (is_array($decoded)) {
                            $currentVideos = $decoded;
                        }
                    }
                }
            }
            
            // ✅ УПРОЩЕННАЯ ЛОГИКА: используем helper метод
            $finalPhotos = $this->processPhotosFromRequest($request);
            
            \Log::info('📸 DraftController: Результаты обработки фотографий', [
                'photos_count' => count($finalPhotos)
            ]);
            
            // ✅ ИСПРАВЛЕНО: корректная проверка наличия поля photos в запросе
            // Проверяем, передано ли поле photos явно в запросе
            $photosInRequest = $request->has('photos') || 
                               $request->has('photos.0') || 
                               $request->hasFile('photos.0');
            
            if ($photosInRequest) {
                // Если поле photos передано - используем результат обработки
                // даже если массив пустой (это означает удаление всех фото)
                \Log::info('📸 Поле photos передано в запросе, используем результат обработки', [
                    'count' => count($finalPhotos),
                    'photos' => $finalPhotos
                ]);
            } else {
                // Если поле photos НЕ передано вообще - сохраняем существующие из БД
                $finalPhotos = $currentPhotos;
                \Log::info('📸 Поле photos НЕ передано, сохраняем из БД:', [
                    'count' => count($currentPhotos)
                ]);
            }
            
            \Log::info('📸 DraftController: Финальный массив фотографий', [
                'final_photos_count' => count($finalPhotos)
            ]);
            
            // ✅ УПРОЩЕННАЯ ЛОГИКА: всегда используем финальный массив
            $data['photos'] = $finalPhotos;
            \Log::info('📸 Устанавливаем photos:', [
                'count' => count($finalPhotos),
                'photos' => $finalPhotos
            ]);
            
            // Отладка входящих видео
            \Log::info('🎥 UPDATE: Анализ входящих видео данных', [
                'all_keys' => array_keys($request->all()),
                'has_video' => $request->has('video'),
                'has_video_0' => $request->has('video.0'),
                'has_video_0_file' => $request->hasFile('video.0.file'),
                'video_files' => $request->allFiles()
            ]);
            
            // Обработка видео файлов (пробуем оба формата)
            $uploadedVideos = [];
            $existingVideos = [];
            $videoIndex = 0;
            $maxVideoIterations = 10;
            
            // Проверяем сначала единичный файл video (без индекса)
            if ($videoIndex === 0 && $request->hasFile('video')) {
                \Log::info('🎥 UPDATE: Обнаружен единичный файл video');
                $file = $request->file('video');
                
                // Если это массив файлов, берем первый
                if (is_array($file) && count($file) > 0) {
                    $file = $file[0];
                }
                
                // Проверяем размер файла (макс 100MB для видео)
                if ($file && !is_array($file) && $file->getSize() <= 100 * 1024 * 1024) {
                    try {
                        // Генерируем уникальное имя файла
                        $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('videos/' . Auth::id(), $fileName, 'public');
                        
                        // ВАЖНО: Сохраняем как простую строку URL (как в архиве)
                        $uploadedVideos[] = '/storage/' . $path;
                        
                        \Log::info('🎥 Единичное видео загружено при обновлении:', [
                            'path' => '/storage/' . $path
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Ошибка загрузки единичного видео при обновлении: ' . $e->getMessage());
                    }
                }
                $videoIndex = 1; // Начинаем с индекса 1, так как 0 уже обработали
            }
            
            while ($videoIndex < $maxVideoIterations) {
                // Пробуем ВСЕ форматы: video_0_file, video.0.file, video_0, video[0]
                $underscoreFileNotation = "video_{$videoIndex}_file";
                $underscoreNotation = "video_{$videoIndex}";
                $dotNotation = "video.{$videoIndex}.file";
                $bracketNotation = "video[{$videoIndex}]";
                
                $hasFile = $request->hasFile($underscoreFileNotation) || 
                          $request->hasFile($dotNotation) || 
                          $request->hasFile($bracketNotation);
                $hasValue = $request->has($underscoreNotation) || 
                           $request->has("video.{$videoIndex}") || 
                           $request->has($bracketNotation);
                
                if (!$hasFile && !$hasValue) {
                    break; // Больше нет видео
                }
                
                if ($hasFile) {
                    // Пробуем получить файл из всех возможных форматов
                    $file = $request->file($underscoreFileNotation) ?: 
                           $request->file($dotNotation) ?: 
                           $request->file($bracketNotation);
                    
                    // Проверяем размер файла (макс 100MB для видео)
                    if ($file && $file->getSize() > 100 * 1024 * 1024) {
                        $videoIndex++;
                        continue;
                    }
                    
                    try {
                        if ($file) {
                            // Генерируем уникальное имя файла
                            $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                            $path = $file->storeAs('videos/' . Auth::id(), $fileName, 'public');
                            
                            // ВАЖНО: Сохраняем как простую строку URL (как в архиве)
                            $uploadedVideos[] = '/storage/' . $path;
                            
                            \Log::info('🎥 Видео загружено при обновлении:', [
                                'index' => $videoIndex,
                                'path' => '/storage/' . $path
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Ошибка загрузки видео при обновлении: ' . $e->getMessage());
                    }
                } elseif ($hasValue) {
                    // Получаем значение из любого формата
                    $videoValue = $request->input($underscoreNotation) ?: 
                                 $request->input("video.{$videoIndex}") ?: 
                                 $request->input($bracketNotation);
                    if (is_string($videoValue) && !empty($videoValue) && $videoValue !== '[]') {
                        // Проверяем, это JSON строка с объектом видео или обычная строка
                        $decoded = json_decode($videoValue, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            // Если это объект видео, извлекаем URL
                            if (isset($decoded['url'])) {
                                $existingVideos[] = $decoded['url'];
                            } else {
                                // Если нет URL, пропускаем
                                \Log::warning('🎥 Видео объект без URL при обновлении:', ['video' => $decoded]);
                            }
                        } else {
                            // Обычная строка URL
                            $existingVideos[] = $videoValue;
                        }
                    }
                }
                
                $videoIndex++;
            }
            
            // Обработка финального массива видео
            $finalVideos = [];
            
            // Проверяем явную очистку видео
            if ($request->input('video') === '[]') {
                \Log::info('🎥 Получен пустой массив video - очищаем все видео');
                $finalVideos = [];
            } else {
                // 1. Сначала добавляем существующие видео из запроса
                if (!empty($existingVideos)) {
                    $finalVideos = $existingVideos;
                }
                
                // 2. Добавляем новые загруженные видео
                if (!empty($uploadedVideos)) {
                    $finalVideos = array_merge($finalVideos, $uploadedVideos);
                }
            }
            
            // Логирование результатов обработки видео
            \Log::info('🎥 UPDATE: Результаты обработки видео', [
                'uploaded_videos' => $uploadedVideos,
                'uploaded_count' => count($uploadedVideos),
                'existing_videos' => $existingVideos,
                'existing_count' => count($existingVideos),
                'current_videos_from_db' => $currentVideos,
                'current_count' => count($currentVideos)
            ]);
            
            // Проверяем были ли отправлены video в любом виде
            $hasVideoInRequest = false;
            foreach ($request->all() as $key => $value) {
                if (str_starts_with($key, 'video_') || str_starts_with($key, 'video[') || $key === 'video' || str_starts_with($key, 'video.')) {
                    $hasVideoInRequest = true;
                    \Log::info('🎥 UPDATE: Найден video ключ в запросе', ['key' => $key, 'value' => $value]);
                    break;
                }
            }
            
            if ($hasVideoInRequest) {
                // Video были отправлены (даже если пустые) - используем их
                $data['video'] = $finalVideos;
                \Log::info('🎥 Устанавливаем video из запроса:', [
                    'count' => count($finalVideos),
                    'videos' => $finalVideos
                ]);
            } else {
                // Поле video не передано вообще - сохраняем существующие из БД
                $data['video'] = $currentVideos;
                \Log::info('🎥 Сохраняем существующие video из БД:', [
                    'count' => count($currentVideos),
                    'videos' => $currentVideos
                ]);
            }
            
            // Обработка поля verification_photo
            if ($request->has('verification_photo')) {
                $verificationPhoto = $request->input('verification_photo');
                
                // Если это base64 изображение - сохраняем как файл
                if ($verificationPhoto && str_starts_with($verificationPhoto, 'data:image')) {
                    try {
                        // Извлекаем base64 данные
                        $base64Parts = explode(',', $verificationPhoto);
                        if (count($base64Parts) === 2) {
                            $imageData = base64_decode($base64Parts[1]);
                            
                            // Определяем расширение
                            $mimeType = str_replace('data:', '', explode(';', $base64Parts[0])[0]);
                            $extension = match($mimeType) {
                                'image/jpeg' => 'jpg',
                                'image/png' => 'png',
                                default => 'jpg'
                            };
                            
                            // Генерируем имя файла и сохраняем
                            $fileName = 'verification_' . uniqid() . '_' . time() . '.' . $extension;
                            $path = 'verification/' . Auth::id() . '/' . $fileName;
                            Storage::disk('public')->put($path, $imageData);
                            
                            $data['verification_photo'] = '/storage/' . $path;
                            \Log::info('📸 Verification photo saved in update: ' . $data['verification_photo']);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error saving verification photo in update: ' . $e->getMessage());
                        // Если не удалось сохранить - не сохраняем вообще
                        $data['verification_photo'] = null;
                    }
                } elseif ($verificationPhoto && str_starts_with($verificationPhoto, '/storage/')) {
                    // Если это уже путь к файлу - сохраняем как есть
                    $data['verification_photo'] = $verificationPhoto;
                } elseif ($verificationPhoto === '' || $verificationPhoto === null) {
                    // Если пустое значение - очищаем поле
                    $data['verification_photo'] = null;
                }
            }
            
            // Используем метод saveOrUpdate, который существует в DraftService
            $draft = $this->draftService->saveOrUpdate($data, Auth::user(), $id);
            
            // Для Inertia запросов возвращаем редирект на список черновиков
            if ($request->header('X-Inertia')) {
                return redirect()->route('profile.items.draft')->with('success', 'Черновик обновлен успешно');
            }
            
            // Для обычных AJAX запросов возвращаем JSON
            return response()->json([
                'success' => true,
                'message' => 'Черновик обновлен успешно',
                'draft_id' => $draft->id
            ]);
        } catch (\Exception $e) {
            // Для Inertia запросов
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors(['error' => 'Ошибка при обновлении черновика: ' . $e->getMessage()]);
            }
            
            // Для AJAX запросов
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении черновика: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Опубликовать черновик (сделать активным)
     */
    public function publish(Request $request, Ad $ad): JsonResponse|RedirectResponse
    {
        \Log::info('🟢 DraftController::publish НАЧАЛО', [
            'ad_id' => $ad->id,
            'ad_status' => $ad->status,
            'user_id' => auth()->id(),
            'request_headers' => $request->headers->all()
        ]);
        
        try {
            $this->authorize('update', $ad);
            \Log::info('🟢 DraftController::publish Авторизация пройдена');
            
            // Используем AdService для публикации
            $publishedAd = $this->adService->publish($ad);
            \Log::info('🟢 DraftController::publish AdService::publish успешно', [
                'published_ad_id' => $publishedAd->id,
                'published_ad_status' => $publishedAd->status
            ]);
            
            // Для Inertia запросов
            if ($request->header('X-Inertia')) {
                \Log::info('🟢 DraftController::publish Inertia redirect');
                return redirect()
                    ->to('/profile/items/active/all')
                    ->with('success', 'Объявление опубликовано!');
            }
            
            // Для AJAX запросов
            \Log::info('🟢 DraftController::publish JSON response');
            return response()->json([
                'success' => true,
                'message' => 'Объявление успешно опубликовано!',
                'ad_id' => $publishedAd->id,
                'status' => $publishedAd->status,
                'redirect_url' => '/profile/items/active/all'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('🟢 DraftController::publish ОШИБКА', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ad_id' => $ad->id
            ]);
            
            // Для Inertia запросов
            if ($request->header('X-Inertia')) {
                return redirect()->back()->withErrors([
                    'publish' => 'Ошибка публикации: ' . $e->getMessage()
                ]);
            }
            
            // Для AJAX запросов
            return response()->json([
                'success' => false,
                'message' => 'Ошибка публикации: ' . $e->getMessage()
            ], 422);
        }
    }

    /**
     * Удалить черновик
     */
    public function destroy(Ad $ad): RedirectResponse
    {
        $this->authorize('delete', $ad);

        $this->draftService->delete($ad);

        return redirect()
            ->to('/profile/items/draft/all')
            ->with('success', 'Черновик удален');
    }
    
    /**
     * ✅ HELPER МЕТОДЫ ДЛЯ УПРОЩЕНИЯ КОДА (REFACTORING)
     */
    
    /**
     * Сохранить base64 изображение как файл
     * @param string $base64Data Base64 строка изображения
     * @return string|null Путь к сохраненному файлу или null
     */
    private function saveBase64Photo(string $base64Data): ?string
    {
        try {
            // Проверяем что это base64
            if (!str_starts_with($base64Data, 'data:image/')) {
                return null;
            }
            
            // Декодируем base64
            $parts = explode(',', $base64Data, 2);
            if (count($parts) !== 2) {
                return null;
            }
            
            $imageData = base64_decode($parts[1]);
            if (!$imageData) {
                return null;
            }
            
            // Определяем расширение
            preg_match('/data:image\/([^;]+)/', $base64Data, $matches);
            $extension = $matches[1] ?? 'jpg';
            
            // Генерируем имя файла
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $path = 'photos/' . Auth::id() . '/' . $fileName;
            
            // Сохраняем файл
            Storage::disk('public')->put($path, $imageData);
            
            return '/storage/' . $path;
        } catch (\Exception $e) {
            \Log::error('Ошибка сохранения base64 фото: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Обработать массив фотографий из запроса
     * Упрощенная логика для читаемости
     * @param Request $request Запрос
     * @param int $maxPhotos Максимальное количество фото для обработки
     * @return array Массив путей к фотографиям
     */
    private function processPhotosFromRequest(Request $request, int $maxPhotos = 50): array
    {
        $uploadedPhotos = [];
        $existingPhotos = [];
        
        // Проходим по всем возможным индексам
        for ($index = 0; $index < $maxPhotos; $index++) {
            // Проверяем оба формата: photos[0] и photos.0
            $bracketNotation = "photos[{$index}]";
            $dotNotation = "photos.{$index}";
            
            // Проверяем файл
            if ($request->hasFile($bracketNotation) || $request->hasFile($dotNotation)) {
                $file = $request->file($bracketNotation) ?: $request->file($dotNotation);
                
                // Проверка размера (10MB)
                if ($file && $file->getSize() <= 10 * 1024 * 1024) {
                    try {
                        $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('photos/' . Auth::id(), $fileName, 'public');
                        $uploadedPhotos[] = '/storage/' . $path;
                    } catch (\Exception $e) {
                        \Log::error('Ошибка загрузки фото: ' . $e->getMessage());
                    }
                }
            }
            // Проверяем значение (существующее фото или base64)
            elseif ($request->has($bracketNotation) || $request->has($dotNotation)) {
                $photoValue = $request->input($bracketNotation) ?: $request->input($dotNotation);
                
                if (is_string($photoValue) && !empty($photoValue) && $photoValue !== '[]') {
                    // Если это base64 - сохраняем как файл
                    if (str_starts_with($photoValue, 'data:image/')) {
                        $savedPath = $this->saveBase64Photo($photoValue);
                        if ($savedPath) {
                            $existingPhotos[] = $savedPath;
                        }
                    } else {
                        // Обычный URL
                        $existingPhotos[] = $photoValue;
                    }
                }
            } else {
                // Нет больше фото
                break;
            }
        }
        
        return array_merge($existingPhotos, $uploadedPhotos);
    }
}