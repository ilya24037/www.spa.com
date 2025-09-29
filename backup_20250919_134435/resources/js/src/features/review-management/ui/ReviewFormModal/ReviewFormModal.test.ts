import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import ReviewFormModal from './ReviewFormModal.vue'

describe('ReviewFormModal', () => {
  const mockReview = {
    id: 1,
    rating: 4,
    comment: 'Отличный сервис, рекомендую!',
    is_anonymous: false,
    created_at: '2024-01-01',
    client_name: 'Иван'
  }

  describe('Рендеринг формы', () => {
    it('должен отображать заголовок "Добавить отзыв" для нового отзыва', () => {
      const wrapper = mount(ReviewFormModal)
      
      expect(wrapper.find('h3').text()).toBe('Добавить отзыв')
    })

    it('должен отображать заголовок "Редактировать отзыв" при редактировании', () => {
      const wrapper = mount(ReviewFormModal, {
        props: { review: mockReview }
      })
      
      expect(wrapper.find('h3').text()).toBe('Редактировать отзыв')
    })

    it('должен отображать 5 звезд для рейтинга', () => {
      const wrapper = mount(ReviewFormModal)
      
      const stars = wrapper.findAll('button[type="button"] svg')
      expect(stars).toHaveLength(5)
    })

    it('должен отображать текстовое поле для комментария', () => {
      const wrapper = mount(ReviewFormModal)
      
      const textarea = wrapper.find('textarea#comment')
      expect(textarea.exists()).toBe(true)
      expect(textarea.attributes('placeholder')).toBe('Расскажите о вашем опыте...')
    })

    it('должен отображать чекбокс для анонимности', () => {
      const wrapper = mount(ReviewFormModal)
      
      const checkbox = wrapper.find('input#anonymous')
      expect(checkbox.exists()).toBe(true)
      expect(checkbox.attributes('type')).toBe('checkbox')
    })

    it('должен отображать кнопки "Отмена" и "Сохранить"', () => {
      const wrapper = mount(ReviewFormModal)
      
      const buttons = wrapper.findAll('button')
      const buttonTexts = buttons.map(b => b.text())
      
      expect(buttonTexts).toContain('Отмена')
      expect(buttonTexts).toContain('Сохранить')
    })
  })

  describe('Инициализация данных', () => {
    it('должен устанавливать значения по умолчанию для нового отзыва', () => {
      const wrapper = mount(ReviewFormModal)
      
      // Проверяем рейтинг по умолчанию (5 звезд)
      const activeStars = wrapper.findAll('.text-yellow-400')
      expect(activeStars).toHaveLength(5)
      
      // Проверяем пустой комментарий
      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('')
      
      // Проверяем что чекбокс не отмечен
      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(false)
    })

    it('должен загружать данные существующего отзыва', () => {
      const wrapper = mount(ReviewFormModal, {
        props: { review: mockReview }
      })
      
      // Проверяем рейтинг (4 звезды)
      const activeStars = wrapper.findAll('.text-yellow-400')
      expect(activeStars).toHaveLength(4)
      
      // Проверяем комментарий
      const textarea = wrapper.find('textarea')
      expect(textarea.element.value).toBe('Отличный сервис, рекомендую!')
      
      // Проверяем чекбокс анонимности
      const checkbox = wrapper.find('input[type="checkbox"]')
      expect(checkbox.element.checked).toBe(false)
    })

    it('должен обновлять форму при изменении props.review', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Изначально пустая форма
      expect(wrapper.find('textarea').element.value).toBe('')
      
      // Обновляем props
      await wrapper.setProps({ review: mockReview })
      await nextTick()
      
      // Проверяем что форма обновилась
      expect(wrapper.find('textarea').element.value).toBe('Отличный сервис, рекомендую!')
    })
  })

  describe('Взаимодействие с рейтингом', () => {
    it('должен изменять рейтинг при клике на звезды', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Кликаем на 3-ю звезду
      const thirdStar = wrapper.findAll('button[type="button"]')[2]
      await thirdStar.trigger('click')
      
      // Проверяем что активны только 3 звезды
      const activeStars = wrapper.findAll('.text-yellow-400')
      expect(activeStars).toHaveLength(3)
    })

    it('должен обновлять все звезды при выборе рейтинга', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Кликаем на 1-ю звезду
      const firstStar = wrapper.findAll('button[type="button"]')[0]
      await firstStar.trigger('click')
      
      let activeStars = wrapper.findAll('.text-yellow-400')
      expect(activeStars).toHaveLength(1)
      
      // Кликаем на 5-ю звезду
      const fifthStar = wrapper.findAll('button[type="button"]')[4]
      await fifthStar.trigger('click')
      
      activeStars = wrapper.findAll('.text-yellow-400')
      expect(activeStars).toHaveLength(5)
    })
  })

  describe('Валидация формы', () => {
    it('должен показывать ошибку для короткого комментария', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Вводим короткий комментарий
      const textarea = wrapper.find('textarea')
      await textarea.setValue('Короткий')
      
      // Отправляем форму
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      // Проверяем ошибку
      expect(wrapper.text()).toContain('Отзыв должен содержать минимум 10 символов')
    })

    it('должен показывать ошибку для слишком длинного комментария', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Вводим очень длинный комментарий
      const longComment = 'а'.repeat(1001)
      const textarea = wrapper.find('textarea')
      await textarea.setValue(longComment)
      
      // Отправляем форму
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      // Проверяем ошибку
      expect(wrapper.text()).toContain('Отзыв не должен превышать 1000 символов')
    })

    it('не должен отправлять форму с невалидными данными', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Не заполняем комментарий
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      // Проверяем что событие save не было эмитировано
      expect(wrapper.emitted('save')).toBeUndefined()
    })

    it('должен успешно отправлять валидную форму', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Заполняем форму валидными данными
      const textarea = wrapper.find('textarea')
      await textarea.setValue('Это отличный сервис, всем рекомендую!')
      
      // Выбираем рейтинг
      const fourthStar = wrapper.findAll('button[type="button"]')[3]
      await fourthStar.trigger('click')
      
      // Отмечаем анонимность
      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setValue(true)
      
      // Отправляем форму
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      // Проверяем что событие save было эмитировано с правильными данными
      expect(wrapper.emitted('save')).toBeTruthy()
      expect(wrapper.emitted('save')[0]).toEqual([{
        rating: 4,
        comment: 'Это отличный сервис, всем рекомендую!',
        is_anonymous: true
      }])
    })
  })

  describe('События закрытия', () => {
    it('должен эмитировать close при клике на кнопку закрытия', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Находим кнопку с иконкой X
      const closeButton = wrapper.find('.text-gray-400')
      await closeButton.trigger('click')
      
      expect(wrapper.emitted('close')).toBeTruthy()
      expect(wrapper.emitted('close')).toHaveLength(1)
    })

    it('должен эмитировать close при клике на оверлей', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Кликаем на оверлей
      const overlay = wrapper.find('.bg-black.bg-opacity-50')
      await overlay.trigger('click')
      
      expect(wrapper.emitted('close')).toBeTruthy()
    })

    it('должен эмитировать close при клике на кнопку "Отмена"', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Находим кнопку "Отмена"
      const cancelButton = wrapper.find('button.border-gray-300')
      await cancelButton.trigger('click')
      
      expect(wrapper.emitted('close')).toBeTruthy()
    })
  })

  describe('Состояние отправки', () => {
    it('должен показывать состояние загрузки при отправке', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Заполняем форму
      const textarea = wrapper.find('textarea')
      await textarea.setValue('Это отличный сервис, всем рекомендую!')
      
      // Отправляем форму
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      // Проверяем текст кнопки
      const submitButton = wrapper.find('button[type="submit"]')
      expect(submitButton.text()).toBe('Сохранение...')
      expect(submitButton.attributes('disabled')).toBeDefined()
    })

    it('должен сбрасывать состояние загрузки через 1 секунду', async () => {
      vi.useFakeTimers()
      
      const wrapper = mount(ReviewFormModal)
      
      // Заполняем форму
      const textarea = wrapper.find('textarea')
      await textarea.setValue('Это отличный сервис, всем рекомендую!')
      
      // Отправляем форму
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      // Проверяем что кнопка заблокирована
      let submitButton = wrapper.find('button[type="submit"]')
      expect(submitButton.text()).toBe('Сохранение...')
      
      // Ждем 1 секунду
      vi.advanceTimersByTime(1000)
      await nextTick()
      
      // Проверяем что кнопка разблокирована
      submitButton = wrapper.find('button[type="submit"]')
      expect(submitButton.text()).toBe('Сохранить')
      expect(submitButton.attributes('disabled')).toBeUndefined()
      
      vi.useRealTimers()
    })
  })

  describe('Обработка анонимности', () => {
    it('должен правильно обрабатывать чекбокс анонимности', async () => {
      const wrapper = mount(ReviewFormModal)
      
      const checkbox = wrapper.find('input[type="checkbox"]')
      
      // Изначально не отмечен
      expect(checkbox.element.checked).toBe(false)
      
      // Отмечаем
      await checkbox.setValue(true)
      expect(checkbox.element.checked).toBe(true)
      
      // Снимаем отметку
      await checkbox.setValue(false)
      expect(checkbox.element.checked).toBe(false)
    })

    it('должен передавать состояние анонимности при сохранении', async () => {
      const wrapper = mount(ReviewFormModal)
      
      // Заполняем форму
      const textarea = wrapper.find('textarea')
      await textarea.setValue('Анонимный отзыв для теста')
      
      // Отмечаем анонимность
      const checkbox = wrapper.find('input[type="checkbox"]')
      await checkbox.setValue(true)
      
      // Отправляем
      const form = wrapper.find('form')
      await form.trigger('submit')
      
      // Проверяем данные
      const emittedData = wrapper.emitted('save')[0][0]
      expect(emittedData.is_anonymous).toBe(true)
    })
  })
})