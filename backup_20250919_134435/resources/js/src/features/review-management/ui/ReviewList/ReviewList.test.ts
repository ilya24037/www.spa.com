import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { nextTick } from 'vue'
import ReviewList from './ReviewList.vue'
import { reviewApi } from '@/entities/review/api/reviewApi'
import * as toastModule from '@/shared/ui/molecules/Toast/useToast'

// Мокаем API
vi.mock('@/entities/review/api/reviewApi', () => ({
  reviewApi: {
    getReviews: vi.fn(),
    getUserReviews: vi.fn(),
    createReview: vi.fn(),
    updateReview: vi.fn(),
    deleteReview: vi.fn(),
    approveReview: vi.fn(),
    rejectReview: vi.fn()
  }
}))

// Мокаем toast
vi.mock('@/shared/ui/molecules/Toast/useToast', () => ({
  useToast: () => ({
    showSuccess: vi.fn(),
    showError: vi.fn()
  })
}))

// Мокаем компоненты
vi.mock('@/entities/review/ui/ReviewCard/ReviewCard.vue', () => ({
  default: {
    name: 'ReviewCard',
    props: ['review', 'compact'],
    template: '<div class="review-card">{{ review.comment }}</div>'
  }
}))

vi.mock('../ReviewFormModal/ReviewFormModal.vue', () => ({
  default: {
    name: 'ReviewFormModal',
    props: ['review'],
    emits: ['save', 'close'],
    template: '<div class="review-form-modal">Form Modal</div>'
  }
}))

describe('ReviewList', () => {
  const mockReviews = [
    {
      id: 1,
      rating: 5,
      comment: 'Отличный сервис!',
      created_at: '2024-01-01',
      client_name: 'Иван',
      status: 'approved'
    },
    {
      id: 2,
      rating: 4,
      comment: 'Хорошо',
      created_at: '2024-01-02',
      client_name: 'Мария',
      status: 'pending'
    }
  ]

  const mockResponse = {
    data: mockReviews,
    meta: {
      current_page: 1,
      last_page: 2,
      total: 10
    }
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('Загрузка отзывов', () => {
    it('должен загружать отзывы при монтировании', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      expect(reviewApi.getReviews).toHaveBeenCalledTimes(1)
      expect(wrapper.findAll('.review-card')).toHaveLength(2)
    })

    it('должен загружать отзывы пользователя если передан userId', async () => {
      vi.mocked(reviewApi.getUserReviews).mockResolvedValue(mockResponse)
      
      const wrapper = mount(ReviewList, {
        props: { userId: 123 }
      })
      await flushPromises()
      
      expect(reviewApi.getUserReviews).toHaveBeenCalledWith(123, expect.any(Object))
      expect(reviewApi.getReviews).not.toHaveBeenCalled()
    })

    it('должен показывать состояние загрузки', async () => {
      vi.mocked(reviewApi.getReviews).mockImplementation(() => 
        new Promise(resolve => setTimeout(() => resolve(mockResponse), 100))
      )
      
      const wrapper = mount(ReviewList)
      
      // Проверяем наличие skeleton loader
      expect(wrapper.find('.animate-pulse').exists()).toBe(true)
      expect(wrapper.findAll('.animate-pulse')).toHaveLength(3)
      
      await flushPromises()
      
      // После загрузки skeleton должен исчезнуть
      expect(wrapper.find('.animate-pulse').exists()).toBe(false)
    })

    it('должен показывать ошибку при неудачной загрузке', async () => {
      vi.mocked(reviewApi.getReviews).mockRejectedValue(new Error('Network error'))
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      expect(wrapper.find('.border-red-200').exists()).toBe(true)
      expect(wrapper.text()).toContain('Произошла ошибка')
      expect(wrapper.text()).toContain('Не удалось загрузить отзывы')
    })

    it('должен повторно загружать при клике на "Попробовать снова"', async () => {
      vi.mocked(reviewApi.getReviews)
        .mockRejectedValueOnce(new Error('Network error'))
        .mockResolvedValueOnce(mockResponse)
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      // Проверяем наличие ошибки
      expect(wrapper.find('.border-red-200').exists()).toBe(true)
      
      // Кликаем на кнопку повтора
      await wrapper.find('button').trigger('click')
      await flushPromises()
      
      // Проверяем что ошибка исчезла и отзывы загрузились
      expect(wrapper.find('.border-red-200').exists()).toBe(false)
      expect(wrapper.findAll('.review-card')).toHaveLength(2)
    })
  })

  describe('Пустое состояние', () => {
    it('должен показывать пустое состояние когда нет отзывов', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue({ 
        data: [], 
        meta: { current_page: 1, last_page: 1, total: 0 } 
      })
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      expect(wrapper.text()).toContain('Отзывов пока нет')
    })

    it('должен показывать кнопку "Оставить первый отзыв" если canAddReview=true', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue({ 
        data: [], 
        meta: { current_page: 1, last_page: 1, total: 0 } 
      })
      
      const wrapper = mount(ReviewList, {
        props: { canAddReview: true }
      })
      await flushPromises()
      
      expect(wrapper.text()).toContain('Оставить первый отзыв')
    })

    it('не должен показывать кнопку добавления если canAddReview=false', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue({ 
        data: [], 
        meta: { current_page: 1, last_page: 1, total: 0 } 
      })
      
      const wrapper = mount(ReviewList, {
        props: { canAddReview: false }
      })
      await flushPromises()
      
      expect(wrapper.text()).not.toContain('Оставить первый отзыв')
      expect(wrapper.text()).not.toContain('Добавить отзыв')
    })
  })

  describe('Фильтрация', () => {
    it('должен фильтровать по рейтингу', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      // Выбираем фильтр "5 звёзд"
      const ratingSelect = wrapper.find('select')
      await ratingSelect.setValue('5')
      
      expect(reviewApi.getReviews).toHaveBeenLastCalledWith(
        expect.objectContaining({ rating: '5', page: 1 })
      )
    })

    it('должен показывать фильтр по статусу если showStatusFilter=true', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      
      const wrapper = mount(ReviewList, {
        props: { showStatusFilter: true }
      })
      await flushPromises()
      
      const selects = wrapper.findAll('select')
      expect(selects).toHaveLength(2)
      
      // Проверяем наличие опций статуса
      const statusSelect = selects[1]
      const options = statusSelect.findAll('option')
      const optionTexts = options.map(o => o.text())
      
      expect(optionTexts).toContain('Все статусы')
      expect(optionTexts).toContain('Одобренные')
      expect(optionTexts).toContain('На модерации')
      expect(optionTexts).toContain('Отклонённые')
    })

    it('не должен показывать фильтр по статусу по умолчанию', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      const selects = wrapper.findAll('select')
      expect(selects).toHaveLength(1) // Только фильтр рейтинга
    })
  })

  describe('CRUD операции', () => {
    beforeEach(async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
    })

    it('должен открывать форму добавления при клике на кнопку', async () => {
      const wrapper = mount(ReviewList, {
        props: { canAddReview: true }
      })
      await flushPromises()
      
      // Кликаем на кнопку добавления
      const addButton = wrapper.find('button')
      await addButton.trigger('click')
      
      // Проверяем что модальное окно открылось
      expect(wrapper.find('.review-form-modal').exists()).toBe(true)
    })

    it('должен сохранять новый отзыв', async () => {
      vi.mocked(reviewApi.createReview).mockResolvedValue({ id: 3 })
      
      const wrapper = mount(ReviewList, {
        props: { canAddReview: true }
      })
      await flushPromises()
      
      // Открываем форму
      await wrapper.find('button').trigger('click')
      
      // Эмулируем сохранение
      const modal = wrapper.findComponent({ name: 'ReviewFormModal' })
      await modal.vm.$emit('save', { rating: 5, comment: 'Новый отзыв' })
      await flushPromises()
      
      expect(reviewApi.createReview).toHaveBeenCalledWith({ 
        rating: 5, 
        comment: 'Новый отзыв' 
      })
      expect(reviewApi.getReviews).toHaveBeenCalledTimes(2) // Перезагрузка
    })

    it('должен удалять отзыв с подтверждением', async () => {
      vi.mocked(reviewApi.deleteReview).mockResolvedValue({})
      window.confirm = vi.fn(() => true)
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      // Находим кнопку удаления (TrashIcon)
      const deleteButton = wrapper.find('[title="Удалить"]')
      await deleteButton.trigger('click')
      await flushPromises()
      
      expect(window.confirm).toHaveBeenCalledWith('Вы уверены, что хотите удалить этот отзыв?')
      expect(reviewApi.deleteReview).toHaveBeenCalledWith(1)
      expect(reviewApi.getReviews).toHaveBeenCalledTimes(2) // Перезагрузка
    })

    it('не должен удалять отзыв без подтверждения', async () => {
      window.confirm = vi.fn(() => false)
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      const deleteButton = wrapper.find('[title="Удалить"]')
      await deleteButton.trigger('click')
      await flushPromises()
      
      expect(reviewApi.deleteReview).not.toHaveBeenCalled()
    })
  })

  describe('Модерация', () => {
    it('должен показывать кнопки модерации для pending отзывов', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      
      const wrapper = mount(ReviewList, {
        props: { canModerate: true }
      })
      await flushPromises()
      
      // Второй отзыв имеет статус pending
      const moderationButtons = wrapper.findAll('.bg-green-600, .bg-red-600')
      expect(moderationButtons.length).toBeGreaterThan(0)
      expect(wrapper.text()).toContain('Одобрить')
      expect(wrapper.text()).toContain('Отклонить')
    })

    it('должен одобрять отзыв', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      vi.mocked(reviewApi.approveReview).mockResolvedValue({})
      
      const wrapper = mount(ReviewList, {
        props: { canModerate: true }
      })
      await flushPromises()
      
      const approveButton = wrapper.find('.bg-green-600')
      await approveButton.trigger('click')
      await flushPromises()
      
      expect(reviewApi.approveReview).toHaveBeenCalledWith(2) // ID второго отзыва
      expect(reviewApi.getReviews).toHaveBeenCalledTimes(2) // Перезагрузка
    })

    it('должен отклонять отзыв', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      vi.mocked(reviewApi.rejectReview).mockResolvedValue({})
      
      const wrapper = mount(ReviewList, {
        props: { canModerate: true }
      })
      await flushPromises()
      
      const rejectButton = wrapper.find('.bg-red-600')
      await rejectButton.trigger('click')
      await flushPromises()
      
      expect(reviewApi.rejectReview).toHaveBeenCalledWith(2) // ID второго отзыва
      expect(reviewApi.getReviews).toHaveBeenCalledTimes(2) // Перезагрузка
    })
  })

  describe('Пагинация', () => {
    it('должен показывать пагинацию когда больше одной страницы', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      const paginationButtons = wrapper.findAll('.px-3.py-1.rounded')
      expect(paginationButtons).toHaveLength(2) // 2 страницы
      expect(paginationButtons[0].text()).toBe('1')
      expect(paginationButtons[1].text()).toBe('2')
    })

    it('должен выделять активную страницу', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      const firstPageButton = wrapper.find('.bg-blue-600')
      expect(firstPageButton.text()).toBe('1')
    })

    it('должен загружать страницу при клике', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue(mockResponse)
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      const secondPageButton = wrapper.findAll('.px-3.py-1.rounded')[1]
      await secondPageButton.trigger('click')
      
      expect(reviewApi.getReviews).toHaveBeenLastCalledWith(
        expect.objectContaining({ page: 2 })
      )
    })

    it('не должен показывать пагинацию для одной страницы', async () => {
      vi.mocked(reviewApi.getReviews).mockResolvedValue({
        data: mockReviews,
        meta: { current_page: 1, last_page: 1, total: 2 }
      })
      
      const wrapper = mount(ReviewList)
      await flushPromises()
      
      expect(wrapper.find('.px-3.py-1.rounded').exists()).toBe(false)
    })
  })
})