<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\MasterProfile;

Route::middleware('api')->group(function () {
    // Получить информацию о фотографиях мастера
    Route::get('/masters/{master}/photos', function ($masterId) {
        $master = MasterProfile::find($masterId);
        if (!$master) {
            return response()->json(['error' => 'Мастер не найден'], 404);
        }
        
        return response()->json([
            'master_id' => $master->id,
            'master_name' => $master->display_name,
            'folder_name' => $master->folder_name,
            'count' => $master->photos()->count(),
            'photos' => $master->photos()->orderBy('sort_order')->get()->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'filename' => $photo->filename,
                    'url' => $photo->original_url,
                    'thumb_url' => $photo->thumb_url,
                    'is_main' => $photo->is_main,
                    'sort_order' => $photo->sort_order
                ];
            })
        ]);
    });
    
    // Получить список всех мастеров
    Route::get('/masters', function () {
        $masters = MasterProfile::select('id', 'first_name', 'last_name', 'display_name')
            ->orderBy('first_name')
            ->get()
            ->map(function ($master) {
                return [
                    'id' => $master->id,
                    'name' => $master->display_name,
                    'folder' => $master->folder_name,
                    'photos_count' => $master->photos()->count()
                ];
            });
            
        return response()->json($masters);
    });
}); 