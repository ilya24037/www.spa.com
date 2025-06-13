<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CompareController extends Controller
{
    public function index()
    {
        // Тестовые данные для сравнения
        $compareList = [];

        return Inertia::render('Compare/Index', [
            'compareList' => $compareList
        ]);
    }

    public function add(Request $request)
    {
        $masterId = $request->master_id;
        
        // Логика добавления в сравнение
        
        return response()->json([
            'success' => true,
            'message' => 'Добавлено к сравнению'
        ]);
    }

    public function remove($masterId)
    {
        // Логика удаления из сравнения
        
        return response()->json([
            'success' => true,
            'message' => 'Удалено из сравнения'
        ]);
    }
}