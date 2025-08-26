<?php

declare(strict_types=1);

namespace App\Application\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReviewRequest extends FormRequest
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
            'reviewable_user_id' => 'required|integer|exists:users,id',
            'ad_id' => 'nullable|integer|exists:ads,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
            'is_anonymous' => 'boolean',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
        ];
    }

    /**
     * Get custom error messages
     */
    public function messages(): array
    {
        return [
            'reviewable_user_id.required' => 'Необходимо указать пользователя для отзыва',
            'reviewable_user_id.exists' => 'Пользователь не найден',
            'rating.required' => 'Необходимо указать рейтинг',
            'rating.min' => 'Минимальный рейтинг - 1',
            'rating.max' => 'Максимальный рейтинг - 5',
            'comment.required' => 'Необходимо написать комментарий',
            'comment.min' => 'Комментарий должен содержать минимум 10 символов',
            'comment.max' => 'Комментарий не может превышать 1000 символов',
            'photos.max' => 'Максимальное количество фотографий - 5',
            'photos.*.image' => 'Файл должен быть изображением',
            'photos.*.max' => 'Размер фотографии не должен превышать 5 МБ',
        ];
    }
}