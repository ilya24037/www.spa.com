import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import axios from 'axios'
import MockAdapter from 'axios-mock-adapter'
import ReviewList from '@/features/review-management/ui/ReviewList/ReviewList.vue'
import { reviewApi } from '@/entities/review/api/reviewApi'

// Создаем mock для axios
const mock = new MockAdapter(axios)

describe('Review CRUD Operations - Feature Tests', () => {
  const API_BASE = '/api/reviews'
  
  const mockReviews = [
    {
      id: 1,
      user_id: 1,
      reviewable_user_id: 2,
      ad_id: 10,
      rating: 5,
      comment: 'Отличный сервис, рекомендую!',
      status: 'approved',
      is_anonymous: false,
      created_at: '2024-08-25T10:00:00Z',
      updated_at: '2024-08-25T10:00:00Z',
      client_name: 'Иван Петров',
      service_name: 'Массаж'
    },
    {
      id: 2,
      user_id: 3,
      reviewable_user_id: 2,
      ad_id: 11,
      rating: 4,
      comment: 'Хорошо, но есть замечания',
      status: 'pending',
      is_anonymous: true,
      created_at: '2024-08-24T10:00:00Z',
      updated_at: '2024-08-24T10:00:00Z',
      client_name: null,
      service_name: 'СПА процедуры'
    }
  ]

  beforeEach(() => {
    mock.reset()
    vi.clearAllMocks()
  })

  afterEach(() => {
    mock.reset()
  })

  describe('CREATE - Создание отзыва', () => {
    it('должен успешно создавать новый отзыв', async () => {
      const newReview = {
        reviewable_user_id: 2,
        ad_id: 12,
        rating: 5,
        comment: 'Новый отзыв для теста',
        is_anonymous: false
      }

      const createdReview = {
        id: 3,
        user_id: 1,
        ...newReview,
        status: 'pending',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      }

      // Мокаем GET запрос для начальной загрузки
      mock.onGet(API_BASE).reply(200, {
        data: mockReviews,
        meta: { current_page: 1, last_page: 1, total: 2, per_page: 10 }
      })

      // Мокаем POST запрос для создания
      mock.onPost(API_BASE).reply(201, createdReview)

      // Мокаем GET запрос после создания (перезагрузка списка)
      mock.onGet(API_BASE).replyOnce(200, {
        data: [...mockReviews, createdReview],
        meta: { current_page: 1, last_page: 1, total: 3, per_page: 10 }
      })

      const result = await reviewApi.createReview(newReview)
      
      expect(result).toEqual(createdReview)
      expect(mock.history.post.length).toBe(1)
      expect(JSON.parse(mock.history.post[0].data)).toEqual(newReview)
    })

    it('должен валидировать обязательные поля при создании', async () => {
      const invalidReview = {
        rating: 6, // Невалидный рейтинг
        comment: 'short', // Слишком короткий комментарий
      }

      mock.onPost(API_BASE).reply(422, {
        message: 'The given data was invalid.',
        errors: {
          rating: ['The rating must be between 1 and 5.'],
          comment: ['The comment must be at least 10 characters.'],
          reviewable_user_id: ['The reviewable user id field is required.']
        }
      })

      try {
        await reviewApi.createReview(invalidReview)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(422)
        expect(error.response.data.errors).toBeDefined()
        expect(error.response.data.errors.rating).toBeDefined()
        expect(error.response.data.errors.comment).toBeDefined()
      }
    })

    it('не должен позволять создавать отзыв самому себе', async () => {
      const selfReview = {
        reviewable_user_id: 1, // ID текущего пользователя
        ad_id: 10,
        rating: 5,
        comment: 'Попытка оставить отзыв самому себе'
      }

      mock.onPost(API_BASE).reply(403, {
        message: 'You cannot review yourself'
      })

      try {
        await reviewApi.createReview(selfReview)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(403)
        expect(error.response.data.message).toContain('cannot review yourself')
      }
    })

    it('не должен позволять создавать дублирующий отзыв', async () => {
      const duplicateReview = {
        reviewable_user_id: 2,
        ad_id: 10, // Уже существует отзыв для этого объявления
        rating: 5,
        comment: 'Попытка создать дублирующий отзыв'
      }

      mock.onPost(API_BASE).reply(409, {
        message: 'You have already reviewed this service'
      })

      try {
        await reviewApi.createReview(duplicateReview)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(409)
        expect(error.response.data.message).toContain('already reviewed')
      }
    })
  })

  describe('READ - Чтение отзывов', () => {
    it('должен получать список всех отзывов', async () => {
      mock.onGet(API_BASE).reply(200, {
        data: mockReviews,
        meta: {
          current_page: 1,
          last_page: 1,
          total: 2,
          per_page: 10
        }
      })

      const result = await reviewApi.getReviews()
      
      expect(result.data).toEqual(mockReviews)
      expect(result.meta.total).toBe(2)
      expect(mock.history.get.length).toBe(1)
    })

    it('должен фильтровать отзывы по рейтингу', async () => {
      const filteredReviews = mockReviews.filter(r => r.rating === 5)
      
      mock.onGet(`${API_BASE}?rating=5`).reply(200, {
        data: filteredReviews,
        meta: {
          current_page: 1,
          last_page: 1,
          total: 1,
          per_page: 10
        }
      })

      const result = await reviewApi.getReviews({ rating: 5 })
      
      expect(result.data).toEqual(filteredReviews)
      expect(result.data.length).toBe(1)
      expect(result.data[0].rating).toBe(5)
    })

    it('должен фильтровать отзывы по статусу', async () => {
      const pendingReviews = mockReviews.filter(r => r.status === 'pending')
      
      mock.onGet(`${API_BASE}?status=pending`).reply(200, {
        data: pendingReviews,
        meta: {
          current_page: 1,
          last_page: 1,
          total: 1,
          per_page: 10
        }
      })

      const result = await reviewApi.getReviews({ status: 'pending' })
      
      expect(result.data).toEqual(pendingReviews)
      expect(result.data.length).toBe(1)
      expect(result.data[0].status).toBe('pending')
    })

    it('должен получать отзывы конкретного пользователя', async () => {
      const userId = 2
      const userReviews = mockReviews // Все отзывы для пользователя 2
      
      mock.onGet(`${API_BASE}/user/${userId}`).reply(200, {
        data: userReviews,
        meta: {
          current_page: 1,
          last_page: 1,
          total: 2,
          per_page: 10
        }
      })

      const result = await reviewApi.getUserReviews(userId)
      
      expect(result.data).toEqual(userReviews)
      expect(result.data.every(r => r.reviewable_user_id === userId)).toBe(true)
    })

    it('должен поддерживать пагинацию', async () => {
      mock.onGet(`${API_BASE}?page=2`).reply(200, {
        data: [],
        meta: {
          current_page: 2,
          last_page: 2,
          total: 20,
          per_page: 10
        }
      })

      const result = await reviewApi.getReviews({ page: 2 })
      
      expect(result.meta.current_page).toBe(2)
      expect(mock.history.get[0].params.page).toBe(2)
    })

    it('должен получать конкретный отзыв по ID', async () => {
      const reviewId = 1
      mock.onGet(`${API_BASE}/${reviewId}`).reply(200, mockReviews[0])

      const result = await reviewApi.getReview(reviewId)
      
      expect(result).toEqual(mockReviews[0])
      expect(result.id).toBe(reviewId)
    })
  })

  describe('UPDATE - Обновление отзыва', () => {
    it('должен успешно обновлять существующий отзыв', async () => {
      const reviewId = 1
      const updateData = {
        rating: 3,
        comment: 'Обновленный комментарий после повторного посещения'
      }

      const updatedReview = {
        ...mockReviews[0],
        ...updateData,
        updated_at: new Date().toISOString()
      }

      mock.onPut(`${API_BASE}/${reviewId}`).reply(200, updatedReview)

      const result = await reviewApi.updateReview(reviewId, updateData)
      
      expect(result).toEqual(updatedReview)
      expect(result.rating).toBe(3)
      expect(result.comment).toContain('Обновленный комментарий')
      expect(mock.history.put.length).toBe(1)
      expect(JSON.parse(mock.history.put[0].data)).toEqual(updateData)
    })

    it('должен проверять права на редактирование', async () => {
      const reviewId = 2 // Отзыв другого пользователя
      const updateData = {
        rating: 5,
        comment: 'Попытка изменить чужой отзыв'
      }

      mock.onPut(`${API_BASE}/${reviewId}`).reply(403, {
        message: 'You are not authorized to edit this review'
      })

      try {
        await reviewApi.updateReview(reviewId, updateData)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(403)
        expect(error.response.data.message).toContain('not authorized')
      }
    })

    it('должен валидировать данные при обновлении', async () => {
      const reviewId = 1
      const invalidData = {
        rating: 0, // Невалидный рейтинг
        comment: '' // Пустой комментарий
      }

      mock.onPut(`${API_BASE}/${reviewId}`).reply(422, {
        message: 'The given data was invalid.',
        errors: {
          rating: ['The rating must be between 1 and 5.'],
          comment: ['The comment field is required.']
        }
      })

      try {
        await reviewApi.updateReview(reviewId, invalidData)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(422)
        expect(error.response.data.errors.rating).toBeDefined()
        expect(error.response.data.errors.comment).toBeDefined()
      }
    })

    it('не должен позволять редактировать отзыв после 24 часов', async () => {
      const reviewId = 1
      const updateData = {
        rating: 5,
        comment: 'Попытка поздней правки'
      }

      mock.onPut(`${API_BASE}/${reviewId}`).reply(403, {
        message: 'Reviews can only be edited within 24 hours of creation'
      })

      try {
        await reviewApi.updateReview(reviewId, updateData)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(403)
        expect(error.response.data.message).toContain('24 hours')
      }
    })

    it('должен возвращать 404 для несуществующего отзыва', async () => {
      const nonExistentId = 999
      const updateData = {
        rating: 5,
        comment: 'Обновление несуществующего отзыва'
      }

      mock.onPut(`${API_BASE}/${nonExistentId}`).reply(404, {
        message: 'Review not found'
      })

      try {
        await reviewApi.updateReview(nonExistentId, updateData)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(404)
        expect(error.response.data.message).toContain('not found')
      }
    })
  })

  describe('DELETE - Удаление отзыва', () => {
    it('должен успешно удалять отзыв', async () => {
      const reviewId = 1
      
      mock.onDelete(`${API_BASE}/${reviewId}`).reply(204)

      await reviewApi.deleteReview(reviewId)
      
      expect(mock.history.delete.length).toBe(1)
      expect(mock.history.delete[0].url).toBe(`${API_BASE}/${reviewId}`)
    })

    it('должен проверять права на удаление', async () => {
      const reviewId = 2 // Отзыв другого пользователя
      
      mock.onDelete(`${API_BASE}/${reviewId}`).reply(403, {
        message: 'You are not authorized to delete this review'
      })

      try {
        await reviewApi.deleteReview(reviewId)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(403)
        expect(error.response.data.message).toContain('not authorized')
      }
    })

    it('должен возвращать 404 для несуществующего отзыва', async () => {
      const nonExistentId = 999
      
      mock.onDelete(`${API_BASE}/${nonExistentId}`).reply(404, {
        message: 'Review not found'
      })

      try {
        await reviewApi.deleteReview(nonExistentId)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(404)
        expect(error.response.data.message).toContain('not found')
      }
    })

    it('должен использовать soft delete (мягкое удаление)', async () => {
      const reviewId = 1
      
      // После удаления отзыв должен быть помечен как удаленный, но не удален физически
      const deletedReview = {
        ...mockReviews[0],
        deleted_at: new Date().toISOString()
      }

      mock.onDelete(`${API_BASE}/${reviewId}`).reply(200, deletedReview)

      const result = await reviewApi.deleteReview(reviewId)
      
      expect(result.deleted_at).toBeDefined()
      expect(result.id).toBe(reviewId)
    })
  })

  describe('Модерация отзывов', () => {
    it('должен одобрять отзыв (только для модераторов)', async () => {
      const reviewId = 2 // Отзыв со статусом pending
      
      const approvedReview = {
        ...mockReviews[1],
        status: 'approved',
        moderated_at: new Date().toISOString(),
        moderated_by: 1
      }

      mock.onPost(`${API_BASE}/${reviewId}/approve`).reply(200, approvedReview)

      const result = await reviewApi.approveReview(reviewId)
      
      expect(result.status).toBe('approved')
      expect(result.moderated_at).toBeDefined()
      expect(result.moderated_by).toBeDefined()
    })

    it('должен отклонять отзыв (только для модераторов)', async () => {
      const reviewId = 2
      const reason = 'Нарушение правил сообщества'
      
      const rejectedReview = {
        ...mockReviews[1],
        status: 'rejected',
        rejection_reason: reason,
        moderated_at: new Date().toISOString(),
        moderated_by: 1
      }

      mock.onPost(`${API_BASE}/${reviewId}/reject`).reply(200, rejectedReview)

      const result = await reviewApi.rejectReview(reviewId, reason)
      
      expect(result.status).toBe('rejected')
      expect(result.rejection_reason).toBe(reason)
      expect(result.moderated_at).toBeDefined()
    })

    it('должен проверять права на модерацию', async () => {
      const reviewId = 2
      
      mock.onPost(`${API_BASE}/${reviewId}/approve`).reply(403, {
        message: 'You do not have permission to moderate reviews'
      })

      try {
        await reviewApi.approveReview(reviewId)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(403)
        expect(error.response.data.message).toContain('permission')
      }
    })

    it('не должен позволять модерировать уже обработанный отзыв', async () => {
      const reviewId = 1 // Уже одобренный отзыв
      
      mock.onPost(`${API_BASE}/${reviewId}/approve`).reply(409, {
        message: 'This review has already been moderated'
      })

      try {
        await reviewApi.approveReview(reviewId)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(409)
        expect(error.response.data.message).toContain('already been moderated')
      }
    })
  })

  describe('Интеграционные сценарии', () => {
    it('должен корректно обрабатывать полный цикл CRUD операций', async () => {
      // 1. Создаем отзыв
      const newReview = {
        reviewable_user_id: 2,
        ad_id: 15,
        rating: 5,
        comment: 'Отличный сервис для интеграционного теста',
        is_anonymous: false
      }

      const createdReview = {
        id: 10,
        user_id: 1,
        ...newReview,
        status: 'pending',
        created_at: new Date().toISOString(),
        updated_at: new Date().toISOString()
      }

      mock.onPost(API_BASE).reply(201, createdReview)
      const created = await reviewApi.createReview(newReview)
      expect(created.id).toBe(10)

      // 2. Читаем созданный отзыв
      mock.onGet(`${API_BASE}/10`).reply(200, createdReview)
      const fetched = await reviewApi.getReview(10)
      expect(fetched).toEqual(createdReview)

      // 3. Обновляем отзыв
      const updateData = {
        rating: 4,
        comment: 'Обновленный комментарий после размышлений'
      }

      const updatedReview = {
        ...createdReview,
        ...updateData,
        updated_at: new Date().toISOString()
      }

      mock.onPut(`${API_BASE}/10`).reply(200, updatedReview)
      const updated = await reviewApi.updateReview(10, updateData)
      expect(updated.rating).toBe(4)

      // 4. Удаляем отзыв
      mock.onDelete(`${API_BASE}/10`).reply(204)
      await reviewApi.deleteReview(10)

      // 5. Проверяем что отзыв удален
      mock.onGet(`${API_BASE}/10`).reply(404, {
        message: 'Review not found'
      })

      try {
        await reviewApi.getReview(10)
        expect.fail('Should have thrown an error')
      } catch (error: any) {
        expect(error.response.status).toBe(404)
      }
    })

    it('должен корректно работать с пагинацией и фильтрами одновременно', async () => {
      const filters = {
        rating: 5,
        status: 'approved',
        page: 2
      }

      mock.onGet(`${API_BASE}?rating=5&status=approved&page=2`).reply(200, {
        data: [mockReviews[0]],
        meta: {
          current_page: 2,
          last_page: 3,
          total: 25,
          per_page: 10
        }
      })

      const result = await reviewApi.getReviews(filters)
      
      expect(result.meta.current_page).toBe(2)
      expect(result.data[0].rating).toBe(5)
      expect(result.data[0].status).toBe('approved')
    })
  })
})