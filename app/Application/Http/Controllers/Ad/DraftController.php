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
        
        // Обработка фотографий для нового черновика
        $uploadedPhotos = [];
        
        // Проверяем индексированные поля photos[0], photos[1], etc
        $index = 0;
        $maxIterations = 50; // Защита от бесконечного цикла
        
        while ($index < $maxIterations) {
            if ($request->hasFile("photos.{$index}")) {
                $file = $request->file("photos.{$index}");
                
                // Проверяем размер файла (макс 10MB)
                if ($file->getSize() > 10 * 1024 * 1024) {
                    $index++;
                    continue;
                }
                
                try {
                    
                    // Генерируем уникальное имя файла
                    $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('photos/' . Auth::id(), $fileName, 'public');
                    $uploadedPhotos[] = '/storage/' . $path;
                } catch (\Exception $e) {
                }
            } elseif (!$request->has("photos.{$index}")) {
                break; // Больше нет фотографий
            }
            $index++;
        }
        
        if (!empty($uploadedPhotos)) {
            $data['photos'] = $uploadedPhotos;
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
                ->route('profile.items.draft')
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
    public function update(Request $request, Ad $ad): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $ad);
        
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
        
        // Обеспечиваем сохранение пустых текстовых полей
        // ВАЖНО: Всегда устанавливаем текстовые поля, даже если пустые
        if (!isset($data['description'])) {
            $data['description'] = '';
        }
        if ($request->has('description')) {
            $data['description'] = $request->input('description', '');
        }
        
        if (!isset($data['additional_features'])) {
            $data['additional_features'] = '';
        }
        if ($request->has('additional_features')) {
            $data['additional_features'] = $request->input('additional_features', '');
        }
        
        if (!isset($data['schedule_notes'])) {
            $data['schedule_notes'] = '';
        }
        if ($request->has('schedule_notes')) {
            $data['schedule_notes'] = $request->input('schedule_notes', '');
        }
        
        // Обработка поля schedule
        if (!isset($data['schedule'])) {
            $data['schedule'] = [];
        }
        if ($request->has('schedule')) {
            $scheduleData = $request->input('schedule');
            // Если schedule пришел как JSON строка, декодируем
            if (is_string($scheduleData)) {
                $decoded = json_decode($scheduleData, true);
                $data['schedule'] = is_array($decoded) ? $decoded : [];
            } else {
                $data['schedule'] = is_array($scheduleData) ? $scheduleData : [];
            }
        }
        
        // Обработка поля online_booking
        if ($request->has('online_booking')) {
            $data['online_booking'] = $request->boolean('online_booking');
        } else {
            $data['online_booking'] = false; // Значение по умолчанию
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
        
        // Логируем данные schedule для отладки
        \Log::info("📅 DraftController: Данные schedule", [
            'request_has_schedule' => $request->has('schedule'),
            'schedule_input' => $request->input('schedule'),
            'schedule_data' => $data['schedule'],
            'schedule_notes_input' => $request->input('schedule_notes'),
            'schedule_notes_data' => $data['schedule_notes'],
            'online_booking_input' => $request->input('online_booking'),
            'online_booking_data' => $data['online_booking']
        ]);
        
        // Обработка media_settings
        if (isset($data['media_settings']) && is_array($data['media_settings'])) {
            // Преобразуем массив в boolean поля
            $data['show_photos_in_gallery'] = in_array('show_photos_in_gallery', $data['media_settings']);
            $data['allow_download_photos'] = in_array('allow_download_photos', $data['media_settings']);
            $data['watermark_photos'] = in_array('watermark_photos', $data['media_settings']);
            
            // Удаляем media_settings, так как в БД таких полей нет
            unset($data['media_settings']);
        }
        
        // Сначала получаем существующие фотографии из БД
        $currentPhotos = [];
        if ($ad->photos) {
            $decoded = json_decode($ad->photos, true);
            if (is_array($decoded)) {
                $currentPhotos = $decoded;
            }
        }
        
        \Log::info("📸 DraftController: Фотографии из БД", [
            'ad_id' => $ad->id,
            'photos_from_db' => count($currentPhotos),
            'current_photos' => $currentPhotos
        ]);
        
        // KISS: Простая обработка фотографий
        $uploadedPhotos = [];
        $existingPhotos = [];
        
        // Обработка видео
        $uploadedVideos = [];
        $existingVideos = [];
        
        // Проверяем индексированные поля photos[0], photos[1] и т.д.
        $index = 0;
        $maxIterations = 50; // Защита от бесконечного цикла
        
        while ($index < $maxIterations) {
            // Пробуем оба формата: photos.0 и photos[0]
            $dotNotation = "photos.{$index}";
            $bracketNotation = "photos[{$index}]";
            
            $hasFile = $request->hasFile($dotNotation) || $request->hasFile($bracketNotation);
            $hasValue = $request->has($dotNotation) || $request->has($bracketNotation);
            
            if (!$hasFile && !$hasValue) {
                break; // Больше нет фотографий
            }
            
            if ($hasFile) {
                $file = $request->file($dotNotation) ?: $request->file($bracketNotation);
                
                // Проверяем размер файла (макс 10MB)
                if ($file && $file->getSize() > 10 * 1024 * 1024) {
                    $index++;
                    continue;
                }
                
                try {
                    if ($file) {
                        // Генерируем уникальное имя файла
                        $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('photos/' . Auth::id(), $fileName, 'public');
                        $uploadedPhotos[] = '/storage/' . $path;
                    }
                } catch (\Exception $e) {
                    // Игнорируем ошибку загрузки
                }
            } elseif ($hasValue) {
                // Получаем значение из любого формата
                $photoValue = $request->input($dotNotation) ?: $request->input($bracketNotation);
                if (is_string($photoValue) && !empty($photoValue) && $photoValue !== '[]') {
                    
                    // ИСПРАВЛЕНИЕ: Обработка data:URL фотографий по аналогии с видео
                    if (str_starts_with($photoValue, 'data:image/')) {
                        try {
                            // Извлекаем MIME тип для определения расширения
                            preg_match('/data:image\/([^;]+)/', $photoValue, $matches);
                            $extension = $matches[1] ?? 'webp';
                            
                            // Декодируем base64 данные
                            $base64Data = explode(',', $photoValue, 2)[1];
                            $binaryData = base64_decode($base64Data);
                            
                            if ($binaryData !== false) {
                                // Сохраняем как файл
                                $fileName = uniqid() . '_' . time() . '.' . $extension;
                                $path = 'photos/' . Auth::id() . '/' . $fileName;
                                
                                \Storage::disk('public')->put($path, $binaryData);
                                $uploadedPhotos[] = '/storage/' . $path;
                                
                                \Log::info("✅ DraftController: Data:URL фото сохранено", [
                                    'index' => $index,
                                    'extension' => $extension,
                                    'saved_path' => '/storage/' . $path
                                ]);
                            } else {
                                \Log::error("❌ DraftController: Не удалось декодировать Base64 фото", [
                                    'index' => $index
                                ]);
                                // Если декодирование не удалось, оставляем как обычную строку
                                $existingPhotos[] = $photoValue;
                            }
                        } catch (\Exception $e) {
                            \Log::error("❌ DraftController: Ошибка обработки data:URL фото", [
                                'index' => $index,
                                'error' => $e->getMessage()
                            ]);
                            // В случае ошибки оставляем как обычную строку
                            $existingPhotos[] = $photoValue;
                        }
                    } else {
                        // Обычная строка (URL или путь)
                        $existingPhotos[] = $photoValue;
                    }
                }
            }
            
            $index++;
        }
        
        // Обработка видео аналогично фото
        
        $videoIndex = 0;
        while ($videoIndex < $maxIterations) {
            // Пробуем оба формата: video.0 и video[0]
            $dotNotation = "video.{$videoIndex}";
            $bracketNotation = "video[{$videoIndex}]";
            
            $hasFile = $request->hasFile($dotNotation) || $request->hasFile($bracketNotation);
            $hasValue = $request->has($dotNotation) || $request->has($bracketNotation);
            
            if (!$hasFile && !$hasValue) {
                break; // Больше нет видео
            }
            
            
            if ($hasFile) {
                $file = $request->file($dotNotation) ?: $request->file($bracketNotation);
                
                // Проверяем размер файла (макс 50MB для видео)
                if ($file && $file->getSize() > 50 * 1024 * 1024) {
                    \Log::warning("🎬 DraftController: Видео слишком большое", [
                        'index' => $videoIndex,
                        'size' => $file->getSize(),
                        'name' => $file->getClientOriginalName()
                    ]);
                    $videoIndex++;
                    continue;
                }
                
                try {
                    if ($file) {
                        // Генерируем уникальное имя файла
                        $fileName = uniqid() . '_' . time() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('videos/' . Auth::id(), $fileName, 'public');
                        $uploadedVideos[] = '/storage/' . $path;
                        
                        \Log::info("✅ DraftController: Видео загружено", [
                            'index' => $videoIndex,
                            'original_name' => $file->getClientOriginalName(),
                            'saved_path' => '/storage/' . $path
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error("❌ DraftController: Ошибка загрузки видео", [
                        'index' => $videoIndex,
                        'error' => $e->getMessage()
                    ]);
                }
            } elseif ($hasValue) {
                // Получаем значение из любого формата
                $videoValue = $request->input($dotNotation) ?: $request->input($bracketNotation);
                if (is_string($videoValue) && !empty($videoValue) && $videoValue !== '[]') {
                    
                    // ИСПРАВЛЕНИЕ: Обработка data:URL видео
                    if (str_starts_with($videoValue, 'data:video/')) {
                        try {
                            // Обработка data:URL видео
                            
                            // Извлекаем MIME тип для определения расширения
                            preg_match('/data:video\/([^;]+)/', $videoValue, $matches);
                            $extension = $matches[1] ?? 'webm';
                            
                            // Декодируем base64 данные
                            $base64Data = explode(',', $videoValue, 2)[1];
                            $binaryData = base64_decode($base64Data);
                            
                            if ($binaryData !== false) {
                                // Сохраняем как файл
                                $fileName = uniqid() . '_' . time() . '.' . $extension;
                                $path = 'videos/' . Auth::id() . '/' . $fileName;
                                
                                \Storage::disk('public')->put($path, $binaryData);
                                $uploadedVideos[] = '/storage/' . $path;
                                
                                \Log::info("✅ DraftController: Data:URL видео сохранено", [
                                    'index' => $videoIndex,
                                    'extension' => $extension,
                                    'saved_path' => '/storage/' . $path,
                                    'file_size' => strlen($binaryData)
                                ]);
                            } else {
                                \Log::error("❌ DraftController: Не удалось декодировать base64", [
                                    'index' => $videoIndex
                                ]);
                            }
                            
                        } catch (\Exception $e) {
                            \Log::error("❌ DraftController: Ошибка обработки data:URL видео", [
                                'index' => $videoIndex,
                                'error' => $e->getMessage()
                            ]);
                        }
                    } else {
                        // Обычный URL или путь к файлу
                        $existingVideos[] = $videoValue;
                        \Log::info("🎬 DraftController: Добавлено существующее видео", [
                            'index' => $videoIndex,
                            'value' => $videoValue
                        ]);
                    }
                }
            }
            
            $videoIndex++;
        }
        
        // Получаем существующие видео из БД
        $currentVideos = [];
        if ($ad->video) {
            $decoded = json_decode($ad->video, true);
            if (is_array($decoded)) {
                $currentVideos = $decoded;
            }
        }
        
        // KISS: Простая логика - то что пришло с фронта, то и сохраняем
        $finalPhotos = [];
        $finalVideos = [];
        
        
        // Проверяем, если пришел специальный маркер пустого массива
        if ($request->input('photos') === '[]') {
            $finalPhotos = [];
        } else {
            // ИСПРАВЛЕНО: Простое объединение всех фото без сложной логики
            
            // Добавляем все существующие фото из запроса (data:URL и обычные URL)
            if (!empty($existingPhotos)) {
                $finalPhotos = array_merge($finalPhotos, $existingPhotos);
            }
            
            // Добавляем новые загруженные файлы фото  
            if (!empty($uploadedPhotos)) {
                $finalPhotos = array_merge($finalPhotos, $uploadedPhotos);
            }
            
            // Если нет фото в запросе вообще - берем из БД
            if (empty($finalPhotos) && !empty($currentPhotos)) {
                $finalPhotos = $currentPhotos;
            }
        }
        
        \Log::info("📸 DraftController: ИСПРАВЛЕННЫЙ результат объединения фотографий", [
            'ad_id' => $ad->id,
            'existing_photos_count' => count($existingPhotos),
            'existing_photos' => $existingPhotos,
            'uploaded_photos_count' => count($uploadedPhotos), 
            'uploaded_photos' => $uploadedPhotos,
            'current_photos_count' => count($currentPhotos),
            'current_photos' => $currentPhotos,
            'final_photos_count' => count($finalPhotos),
            'final_photos' => $finalPhotos
        ]);
        
        // Обработка видео аналогично фото
        if ($request->input('video') === '[]') {
            $finalVideos = [];
        } else {
            // 1. Сначала добавляем существующие видео из запроса (те что остались после удаления)
            if (!empty($existingVideos)) {
                $finalVideos = $existingVideos;
            }
            
            // 2. Добавляем новые загруженные видео
            if (!empty($uploadedVideos)) {
                $finalVideos = array_merge($finalVideos, $uploadedVideos);
            }
        }
        
        // 3. Проверяем были ли отправлены photos в любом виде
        // Laravel не видит photos как отдельное поле, если отправлены photos[0], photos[1] и т.д.
        $hasPhotosInRequest = false;
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'photos[') || $key === 'photos') {
                $hasPhotosInRequest = true;
                break;
            }
        }
        
        if ($hasPhotosInRequest) {
            // Photos были отправлены (даже если пустые) - используем их
            $data['photos'] = $finalPhotos;
        } else {
            // Поле photos не передано вообще - сохраняем существующие из БД
            $data['photos'] = $currentPhotos;
        }
        
        // 4. Проверяем были ли отправлены video в любом виде
        $hasVideoInRequest = false;
        foreach ($request->all() as $key => $value) {
            if (str_starts_with($key, 'video[') || $key === 'video') {
                $hasVideoInRequest = true;
                break;
            }
        }
        
        if ($hasVideoInRequest) {
            // Video были отправлены (даже если пустые) - используем их
            $data['video'] = $finalVideos;
            \Log::info("🎬 DraftController: Финальные видео (из запроса)", [
                'count' => count($finalVideos),
                'videos' => $finalVideos
            ]);
        } else {
            // Поле video не передано вообще - сохраняем существующие из БД
            $data['video'] = $currentVideos;
            \Log::info("🎬 DraftController: Финальные видео (из БД)", [
                'count' => count($currentVideos),
                'videos' => $currentVideos
            ]);
        }

        $ad = $this->draftService->saveOrUpdate(
            $data,
            Auth::user(),
            $ad->id
        );


        // Для Inertia запросов
        if ($request->header('X-Inertia')) {
            return redirect()
                ->route('profile.items.draft')
                ->with('success', 'Черновик обновлен');
        }

        // Для AJAX запросов
        return response()->json([
            'success' => true,
            'message' => 'Черновик обновлен',
            'ad_id' => $ad->id
        ]);
    }

    /**
     * Удалить черновик
     */
    public function destroy(Ad $ad): RedirectResponse
    {
        $this->authorize('delete', $ad);

        $this->draftService->delete($ad);

        return redirect()
            ->route('profile.items.draft')
            ->with('success', 'Черновик удален');
    }
}