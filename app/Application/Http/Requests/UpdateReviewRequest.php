<?php

declare(strict_types=1);

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'sometimes|string|min:10|max:1000',
            'is_anonymous' => 'sometimes|boolean',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'remove_photos' => 'nullable|array',
            'remove_photos.*' => 'integer|exists:media,id',
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'rating.min' => 'Минимальный рейтинг - 1',
            'rating.max' => 'Максимальный рейтинг - 5',
            'comment.min' => 'Комментарий должен содержать минимум 10 символов',
            'comment.max' => 'Комментарий не может превышать 1000 символов',
            'photos.max' => 'Максимальное количество фотографий - 5',
            'photos.*.image' => 'Файл должен быть изображением',
            'photos.*.max' => 'Размер фотографии не должен превышать 5 МБ',
            'remove_photos.*.exists' => 'Фотография для удаления не найдена',
        ];
    }
}