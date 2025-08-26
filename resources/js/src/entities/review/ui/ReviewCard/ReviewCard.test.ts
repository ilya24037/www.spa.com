import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ReviewCard from './ReviewCard.vue'

describe('ReviewCard', () => {
  const mockReview = {
    id: 1,
    rating: 4,
    comment: 'Отличный сервис, рекомендую!',
    created_at: new Date().toISOString(),
    client_name: 'Иван Петров',
    service_name: 'Массаж'
  }

  describe('Отображение базовой информации', () => {
    it('должен отображать имя клиента', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview }
      })
      
      expect(wrapper.text()).toContain('Иван Петров')
    })

    it('должен отображать "Анонимный пользователь" если имя не указано', () => {
      const reviewWithoutName = { ...mockReview, client_name: null }
      const wrapper = mount(ReviewCard, {
        props: { review: reviewWithoutName }
      })
      
      expect(wrapper.text()).toContain('Анонимный пользователь')
    })

    it('должен отображать первую букву имени в аватаре', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview }
      })
      
      const avatar = wrapper.find('.rounded-full span')
      expect(avatar.text()).toBe('И')
    })

    it('должен отображать "У" в аватаре для анонимного пользователя', () => {
      const reviewWithoutName = { ...mockReview, client_name: null }
      const wrapper = mount(ReviewCard, {
        props: { review: reviewWithoutName }
      })
      
      const avatar = wrapper.find('.rounded-full span')
      expect(avatar.text()).toBe('У')
    })
  })

  describe('Отображение рейтинга', () => {
    it('должен отображать правильное количество звезд', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview }
      })
      
      const stars = wrapper.findAll('svg')
      expect(stars).toHaveLength(5)
      
      // Проверяем что 4 звезды активны (желтые)
      const activeStars = stars.filter(star => 
        star.classes().includes('text-yellow-400')
      )
      expect(activeStars).toHaveLength(4)
      
      // Проверяем что 1 звезда неактивна (серая)
      const inactiveStars = stars.filter(star => 
        star.classes().includes('text-gray-300')
      )
      expect(inactiveStars).toHaveLength(1)
    })

    it('должен отображать текстовый рейтинг', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview }
      })
      
      expect(wrapper.text()).toContain('4/5')
    })

    it('должен корректно отображать рейтинг 5 звезд', () => {
      const fiveStarReview = { ...mockReview, rating: 5 }
      const wrapper = mount(ReviewCard, {
        props: { review: fiveStarReview }
      })
      
      const stars = wrapper.findAll('svg')
      const activeStars = stars.filter(star => 
        star.classes().includes('text-yellow-400')
      )
      expect(activeStars).toHaveLength(5)
    })

    it('должен корректно отображать рейтинг 1 звезда', () => {
      const oneStarReview = { ...mockReview, rating: 1 }
      const wrapper = mount(ReviewCard, {
        props: { review: oneStarReview }
      })
      
      const stars = wrapper.findAll('svg')
      const activeStars = stars.filter(star => 
        star.classes().includes('text-yellow-400')
      )
      expect(activeStars).toHaveLength(1)
    })
  })

  describe('Отображение комментария', () => {
    it('должен отображать полный текст комментария', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview }
      })
      
      expect(wrapper.text()).toContain('Отличный сервис, рекомендую!')
    })

    it('должен применять класс line-clamp-2 в компактном режиме', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview, compact: true }
      })
      
      const comment = wrapper.find('p.text-gray-700')
      expect(comment.classes()).toContain('line-clamp-2')
      expect(comment.classes()).toContain('text-sm')
    })

    it('не должен применять line-clamp в обычном режиме', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview, compact: false }
      })
      
      const comment = wrapper.find('p.text-gray-700')
      expect(comment.classes()).not.toContain('line-clamp-2')
      expect(comment.classes()).toContain('text-base')
    })
  })

  describe('Отображение дополнительной информации', () => {
    it('должен отображать название услуги в полном режиме', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview, compact: false }
      })
      
      expect(wrapper.text()).toContain('Массаж')
      const serviceBadge = wrapper.find('.bg-blue-100')
      expect(serviceBadge.exists()).toBe(true)
    })

    it('не должен отображать название услуги в компактном режиме', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview, compact: true }
      })
      
      const serviceBadge = wrapper.find('.bg-blue-100')
      expect(serviceBadge.exists()).toBe(false)
    })

    it('не должен отображать badge если service_name не указан', () => {
      const reviewWithoutService = { ...mockReview, service_name: null }
      const wrapper = mount(ReviewCard, {
        props: { review: reviewWithoutService, compact: false }
      })
      
      const serviceBadge = wrapper.find('.bg-blue-100')
      expect(serviceBadge.exists()).toBe(false)
    })
  })

  describe('Форматирование даты', () => {
    it('должен отображать "Вчера" для вчерашней даты', () => {
      const yesterday = new Date()
      yesterday.setDate(yesterday.getDate() - 1)
      const reviewYesterday = { ...mockReview, created_at: yesterday.toISOString() }
      
      const wrapper = mount(ReviewCard, {
        props: { review: reviewYesterday }
      })
      
      expect(wrapper.text()).toContain('Вчера')
    })

    it('должен отображать количество дней для недавних дат', () => {
      const threeDaysAgo = new Date()
      threeDaysAgo.setDate(threeDaysAgo.getDate() - 3)
      const reviewThreeDaysAgo = { ...mockReview, created_at: threeDaysAgo.toISOString() }
      
      const wrapper = mount(ReviewCard, {
        props: { review: reviewThreeDaysAgo }
      })
      
      expect(wrapper.text()).toContain('3 дн. назад')
    })

    it('должен отображать количество недель для дат в пределах месяца', () => {
      const twoWeeksAgo = new Date()
      twoWeeksAgo.setDate(twoWeeksAgo.getDate() - 14)
      const reviewTwoWeeksAgo = { ...mockReview, created_at: twoWeeksAgo.toISOString() }
      
      const wrapper = mount(ReviewCard, {
        props: { review: reviewTwoWeeksAgo }
      })
      
      expect(wrapper.text()).toContain('2 нед. назад')
    })

    it('должен отображать полную дату для старых отзывов', () => {
      const oldDate = new Date('2024-01-15')
      const reviewOld = { ...mockReview, created_at: oldDate.toISOString() }
      
      const wrapper = mount(ReviewCard, {
        props: { review: reviewOld }
      })
      
      const formattedText = wrapper.text()
      expect(formattedText).toMatch(/\d{1,2}\s\w+\.?\s\d{4}/)
    })
  })

  describe('Props валидация', () => {
    it('должен принимать compact prop со значением по умолчанию false', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview }
      })
      
      expect(wrapper.props().compact).toBe(false)
    })

    it('должен принимать compact prop со значением true', () => {
      const wrapper = mount(ReviewCard, {
        props: { review: mockReview, compact: true }
      })
      
      expect(wrapper.props().compact).toBe(true)
    })
  })
})