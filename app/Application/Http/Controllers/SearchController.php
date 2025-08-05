<?php

namespace App\Application\Http\Controllers;

use App\Domain\Master\Services\MasterSearchService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SearchController extends Controller
{
    private MasterSearchService $searchService;

    public function __construct(MasterSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function index(Request $request)
    {
        $searchResults = $this->searchService->searchMasters($request);
        $categories = $this->searchService->getAvailableCategories();

        return Inertia::render('Search/Index', [
            'masters' => $searchResults['masters'],
            'pagination' => $searchResults['pagination'],
            'categories' => $categories,
            'filters' => $searchResults['filters']
        ]);
    }

    // API для автодополнения
    public function suggestions(Request $request)
    {
        $query = $request->get('q');
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Делегируем получение подсказок в сервис
        $suggestions = $this->searchService->getSuggestions($query);

        return response()->json($suggestions);
    }
}