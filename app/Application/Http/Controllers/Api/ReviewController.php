<?php

declare(strict_types=1);

namespace App\Application\Http\Controllers\Api;

use App\Application\Http\Controllers\Controller;
use App\Application\Http\Requests\StoreReviewRequest;
use App\Application\Http\Requests\UpdateReviewRequest;
use App\Application\Http\Resources\ReviewResource;
use App\Domain\Review\DTOs\CreateReviewDTO;
use App\Domain\Review\DTOs\UpdateReviewDTO;
use App\Domain\Review\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    /**
     * Получить список отзывов для пользователя
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $userId = $request->get('user_id', auth()->id());
        $perPage = $request->get('per_page', 10);
        
        $reviews = $this->reviewService->getUserReviews((int)$userId, (int)$perPage);
        
        return ReviewResource::collection($reviews);
    }

    /**
     * Показать конкретный отзыв
     */
    public function show(int $id): ReviewResource|JsonResponse
    {
        $review = $this->reviewService->getById($id);
        
        if (!$review) {
            return response()->json(['message' => 'Отзыв не найден'], 404);
        }
        
        return new ReviewResource($review);
    }

    /**
     * Создать новый отзыв
     */
    public function store(StoreReviewRequest $request): ReviewResource|JsonResponse
    {
        try {
            $dto = CreateReviewDTO::fromRequest($request);
            $review = $this->reviewService->create($dto);
            
            return new ReviewResource($review);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка при создании отзыва'], 500);
        }
    }

    /**
     * Обновить отзыв
     */
    public function update(UpdateReviewRequest $request, int $id): ReviewResource|JsonResponse
    {
        try {
            $dto = UpdateReviewDTO::fromRequest($request);
            $review = $this->reviewService->update($id, $dto, auth()->user());
            
            return new ReviewResource($review);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка при обновлении отзыва'], 500);
        }
    }

    /**
     * Удалить отзыв
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->reviewService->delete($id, auth()->user());
            
            return response()->json(['message' => 'Отзыв успешно удален'], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ошибка при удалении отзыва'], 500);
        }
    }
}