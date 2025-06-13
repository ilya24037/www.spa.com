<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class MasterController extends Controller
{
    public function show($id)
    {
        // Тестовые данные мастера
        $master = [
            'id' => $id,
            'name' => 'Анна Иванова',
            'specialization' => 'Классический массаж',
            'age' => 28,
            'height' => 165,
            'rating' => 4.8,
            'reviewsCount' => 142,
            'pricePerHour' => 3000,
            'photo' => '/images/masters/1.jpg',
            'photosCount' => 5,
            'isAvailableNow' => true,
            'phone' => '+79991234567',
            'isFavorite' => false,
            'description' => 'Профессиональный массажист с опытом работы более 5 лет.',
            'services' => ['Классический массаж', 'Релакс массаж', 'Антицеллюлитный'],
            'photos' => [
                '/images/masters/1-1.jpg',
                '/images/masters/1-2.jpg',
                '/images/masters/1-3.jpg'
            ]
        ];

        return Inertia::render('Masters/Show', [
            'master' => $master
        ]);
    }

    public function create()
    {
        return Inertia::render('Masters/Create');
    }

    public function store(Request $request)
    {
        // Логика создания мастера
        return redirect()->route('masters.show', 1)->with('success', 'Анкета создана!');
    }

    public function edit($id)
    {
        $master = [
            'id' => $id,
            'name' => 'Анна Иванова',
            'specialization' => 'Классический массаж',
            // другие поля...
        ];

        return Inertia::render('Masters/Edit', [
            'master' => $master
        ]);
    }

    public function update(Request $request, $id)
    {
        // Логика обновления мастера
        return redirect()->route('masters.show', $id)->with('success', 'Анкета обновлена!');
    }

    public function destroy($id)
    {
        // Логика удаления мастера
        return redirect()->route('home')->with('success', 'Анкета удалена!');
    }
}