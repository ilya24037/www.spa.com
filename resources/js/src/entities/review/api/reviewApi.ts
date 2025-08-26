import axios from 'axios'
import type { 
  Review, 
  CreateReviewDTO, 
  UpdateReviewDTO, 
  ReviewFilters, 
  ReviewsResponse 
} from '../model/types'

export const reviewApi = {
  // Получить список отзывов
  async getReviews(filters?: ReviewFilters): Promise<ReviewsResponse> {
    const { data } = await axios.get<ReviewsResponse>('/api/reviews', {
      params: filters
    })
    return data
  },

  // Получить отзыв по ID
  async getReview(id: number): Promise<Review> {
    const { data } = await axios.get<Review>(`/api/reviews/${id}`)
    return data
  },

  // Создать новый отзыв
  async createReview(dto: CreateReviewDTO): Promise<Review> {
    const { data } = await axios.post<Review>('/api/reviews', dto)
    return data
  },

  // Обновить отзыв
  async updateReview(id: number, dto: UpdateReviewDTO): Promise<Review> {
    const { data } = await axios.put<Review>(`/api/reviews/${id}`, dto)
    return data
  },

  // Удалить отзыв
  async deleteReview(id: number): Promise<void> {
    await axios.delete(`/api/reviews/${id}`)
  },

  // Одобрить отзыв (для модерации)
  async approveReview(id: number): Promise<Review> {
    const { data } = await axios.post<Review>(`/api/reviews/${id}/approve`)
    return data
  },

  // Отклонить отзыв (для модерации)
  async rejectReview(id: number): Promise<Review> {
    const { data } = await axios.post<Review>(`/api/reviews/${id}/reject`)
    return data
  },

  // Получить отзывы пользователя
  async getUserReviews(userId: number, filters?: ReviewFilters): Promise<ReviewsResponse> {
    const { data } = await axios.get<ReviewsResponse>(`/api/users/${userId}/reviews`, {
      params: filters
    })
    return data
  },

  // Получить мои отзывы (текущего пользователя)
  async getMyReviews(filters?: ReviewFilters): Promise<ReviewsResponse> {
    const { data } = await axios.get<ReviewsResponse>('/api/my/reviews', {
      params: filters
    })
    return data
  }
}