import { test, expect, Page } from '@playwright/test'

// Хелперы для работы с системой отзывов
class ReviewSystemHelper {
  constructor(private page: Page) {}

  async login(email: string = 'user@example.com', password: string = 'password') {
    await this.page.goto('/login')
    await this.page.fill('input[type="email"]', email)
    await this.page.fill('input[type="password"]', password)
    await this.page.click('button[type="submit"]')
    await this.page.waitForURL(/\/profile|\/dashboard/, { timeout: 10000 })
  }

  async navigateToUserProfile(userId: number) {
    await this.page.goto(`/users/${userId}`)
    await this.page.waitForSelector('.user-profile', { timeout: 5000 })
  }

  async navigateToMyProfile() {
    await this.page.goto('/profile')
    await this.page.waitForSelector('.profile-dashboard', { timeout: 5000 })
  }

  async openReviewsTab() {
    await this.page.click('text=Отзывы')
    await this.page.waitForSelector('.reviews-tab-content', { timeout: 5000 })
  }

  async createReview(rating: number, comment: string, isAnonymous: boolean = false) {
    // Открываем форму добавления отзыва
    await this.page.click('button:has-text("Добавить отзыв")')
    await this.page.waitForSelector('.review-form-modal', { timeout: 5000 })

    // Выбираем рейтинг
    await this.page.click(`.star-rating button:nth-child(${rating})`)

    // Вводим комментарий
    await this.page.fill('textarea#comment', comment)

    // Устанавливаем анонимность
    if (isAnonymous) {
      await this.page.check('input#anonymous')
    }

    // Отправляем форму
    await this.page.click('button:has-text("Сохранить")')
    
    // Ждем закрытия модального окна
    await this.page.waitForSelector('.review-form-modal', { state: 'hidden', timeout: 5000 })
  }

  async editReview(reviewIndex: number, newRating: number, newComment: string) {
    // Находим кнопку редактирования для нужного отзыва
    const reviewCard = this.page.locator('.review-card').nth(reviewIndex)
    await reviewCard.hover()
    await reviewCard.locator('button[title="Редактировать"]').click()
    
    // Ждем открытия формы
    await this.page.waitForSelector('.review-form-modal', { timeout: 5000 })

    // Обновляем рейтинг
    await this.page.click(`.star-rating button:nth-child(${newRating})`)

    // Обновляем комментарий
    await this.page.fill('textarea#comment', newComment)

    // Сохраняем изменения
    await this.page.click('button:has-text("Сохранить")')
    
    // Ждем закрытия модального окна
    await this.page.waitForSelector('.review-form-modal', { state: 'hidden', timeout: 5000 })
  }

  async deleteReview(reviewIndex: number) {
    const reviewCard = this.page.locator('.review-card').nth(reviewIndex)
    await reviewCard.hover()
    await reviewCard.locator('button[title="Удалить"]').click()
    
    // Подтверждаем удаление в диалоге
    await this.page.on('dialog', dialog => dialog.accept())
    
    // Ждем обновления списка
    await this.page.waitForTimeout(1000)
  }

  async filterByRating(rating: number) {
    await this.page.selectOption('select[aria-label="Фильтр по рейтингу"]', `${rating}`)
    await this.page.waitForTimeout(500) // Ждем применения фильтра
  }

  async filterByStatus(status: string) {
    await this.page.selectOption('select[aria-label="Фильтр по статусу"]', status)
    await this.page.waitForTimeout(500) // Ждем применения фильтра
  }

  async goToPage(pageNumber: number) {
    await this.page.click(`.pagination button:has-text("${pageNumber}")`)
    await this.page.waitForTimeout(500) // Ждем загрузки страницы
  }

  async getReviewsCount(): Promise<number> {
    const reviews = await this.page.locator('.review-card').count()
    return reviews
  }

  async getReviewText(index: number): Promise<string> {
    const review = this.page.locator('.review-card').nth(index)
    return await review.textContent() || ''
  }
}

test.describe('Review System - E2E Tests', () => {
  let helper: ReviewSystemHelper

  test.beforeEach(async ({ page }) => {
    helper = new ReviewSystemHelper(page)
    
    // Устанавливаем localStorage/cookies для аутентификации (если нужно)
    await page.addInitScript(() => {
      localStorage.setItem('auth_token', 'test_token_12345')
    })
  })

  test.describe('Просмотр отзывов', () => {
    test('должен отображать список отзывов на странице пользователя', async ({ page }) => {
      // Переходим на страницу пользователя
      await helper.navigateToUserProfile(2)
      
      // Проверяем наличие секции отзывов
      await expect(page.locator('.reviews-section')).toBeVisible()
      
      // Проверяем что отзывы загрузились
      const reviewsCount = await helper.getReviewsCount()
      expect(reviewsCount).toBeGreaterThan(0)
      
      // Проверяем элементы отзыва
      const firstReview = page.locator('.review-card').first()
      await expect(firstReview.locator('.star-rating')).toBeVisible()
      await expect(firstReview.locator('.review-comment')).toBeVisible()
      await expect(firstReview.locator('.review-author')).toBeVisible()
      await expect(firstReview.locator('.review-date')).toBeVisible()
    })

    test('должен показывать пустое состояние когда нет отзывов', async ({ page }) => {
      // Переходим на страницу пользователя без отзывов
      await helper.navigateToUserProfile(999)
      
      // Проверяем пустое состояние
      await expect(page.locator('text=Отзывов пока нет')).toBeVisible()
      await expect(page.locator('.empty-state-icon')).toBeVisible()
    })

    test('должен загружать отзывы при скролле (пагинация)', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      // Проверяем наличие пагинации
      const pagination = page.locator('.pagination')
      await expect(pagination).toBeVisible()
      
      // Переходим на вторую страницу
      await helper.goToPage(2)
      
      // Проверяем что URL обновился
      await expect(page).toHaveURL(/page=2/)
      
      // Проверяем что отзывы загрузились
      const reviewsCount = await helper.getReviewsCount()
      expect(reviewsCount).toBeGreaterThan(0)
    })
  })

  test.describe('Создание отзыва', () => {
    test.beforeEach(async ({ page }) => {
      // Авторизуемся перед каждым тестом
      await helper.login('client@example.com', 'password')
    })

    test('должен успешно создавать новый отзыв', async ({ page }) => {
      // Переходим на страницу мастера
      await helper.navigateToUserProfile(2)
      
      // Создаем отзыв
      const reviewText = `Отличный сервис! Тест ${Date.now()}`
      await helper.createReview(5, reviewText)
      
      // Проверяем успешное уведомление
      await expect(page.locator('.toast-success')).toBeVisible()
      await expect(page.locator('.toast-success')).toContainText('Отзыв добавлен')
      
      // Проверяем что отзыв появился в списке
      const latestReview = page.locator('.review-card').first()
      await expect(latestReview).toContainText(reviewText)
      await expect(latestReview.locator('.star-rating .active')).toHaveCount(5)
    })

    test('должен создавать анонимный отзыв', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      const reviewText = `Анонимный отзыв ${Date.now()}`
      await helper.createReview(4, reviewText, true)
      
      // Проверяем что отзыв создан
      await expect(page.locator('.toast-success')).toBeVisible()
      
      // Проверяем что отзыв отображается как анонимный
      const latestReview = page.locator('.review-card').first()
      await expect(latestReview).toContainText('Анонимный пользователь')
      await expect(latestReview).toContainText(reviewText)
    })

    test('должен валидировать минимальную длину комментария', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      // Открываем форму
      await page.click('button:has-text("Добавить отзыв")')
      await page.waitForSelector('.review-form-modal')
      
      // Вводим короткий комментарий
      await page.fill('textarea#comment', 'Короткий')
      
      // Пытаемся отправить
      await page.click('button:has-text("Сохранить")')
      
      // Проверяем ошибку валидации
      await expect(page.locator('.error-message')).toBeVisible()
      await expect(page.locator('.error-message')).toContainText('минимум 10 символов')
      
      // Модальное окно должно остаться открытым
      await expect(page.locator('.review-form-modal')).toBeVisible()
    })

    test('не должен позволять создавать отзыв без рейтинга', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      // Открываем форму
      await page.click('button:has-text("Добавить отзыв")')
      await page.waitForSelector('.review-form-modal')
      
      // Сбрасываем рейтинг (если есть такая возможность)
      // или проверяем что рейтинг обязателен
      
      // Вводим только комментарий
      await page.fill('textarea#comment', 'Отзыв без рейтинга для теста')
      
      // Пытаемся отправить
      await page.click('button:has-text("Сохранить")')
      
      // Форма не должна отправиться без рейтинга
      await expect(page.locator('.review-form-modal')).toBeVisible()
    })

    test('не должен позволять создавать дублирующий отзыв', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      // Создаем первый отзыв
      const reviewText = `Первый отзыв ${Date.now()}`
      await helper.createReview(5, reviewText)
      
      // Пытаемся создать второй отзыв для того же сервиса
      await page.click('button:has-text("Добавить отзыв")')
      await page.waitForSelector('.review-form-modal')
      
      // Должно появиться сообщение об ошибке или кнопка должна быть недоступна
      await expect(page.locator('.error-message')).toBeVisible()
      await expect(page.locator('.error-message')).toContainText('уже оставили отзыв')
    })
  })

  test.describe('Редактирование отзыва', () => {
    test.beforeEach(async ({ page }) => {
      await helper.login('reviewer@example.com', 'password')
      await helper.navigateToMyProfile()
      await helper.openReviewsTab()
    })

    test('должен успешно редактировать свой отзыв', async ({ page }) => {
      // Редактируем первый отзыв в списке
      const newComment = `Обновленный комментарий ${Date.now()}`
      await helper.editReview(0, 3, newComment)
      
      // Проверяем успешное уведомление
      await expect(page.locator('.toast-success')).toBeVisible()
      await expect(page.locator('.toast-success')).toContainText('Отзыв обновлен')
      
      // Проверяем что отзыв обновился
      const firstReview = page.locator('.review-card').first()
      await expect(firstReview).toContainText(newComment)
      await expect(firstReview.locator('.star-rating .active')).toHaveCount(3)
    })

    test('не должен позволять редактировать чужой отзыв', async ({ page }) => {
      // Переходим на страницу с чужими отзывами
      await helper.navigateToUserProfile(3)
      
      // Проверяем что кнопки редактирования нет
      const reviewCard = page.locator('.review-card').first()
      await reviewCard.hover()
      
      await expect(reviewCard.locator('button[title="Редактировать"]')).not.toBeVisible()
    })

    test('должен сохранять изменения при редактировании', async ({ page }) => {
      // Открываем форму редактирования
      const reviewCard = page.locator('.review-card').first()
      const originalText = await reviewCard.locator('.review-comment').textContent()
      
      await reviewCard.hover()
      await reviewCard.locator('button[title="Редактировать"]').click()
      await page.waitForSelector('.review-form-modal')
      
      // Проверяем что форма заполнена текущими данными
      const currentComment = await page.inputValue('textarea#comment')
      expect(currentComment).toBe(originalText)
      
      // Изменяем данные
      const updatedComment = `${originalText} (обновлено)`
      await page.fill('textarea#comment', updatedComment)
      await page.click('button:has-text("Сохранить")')
      
      // Проверяем что изменения сохранились
      await expect(reviewCard.locator('.review-comment')).toContainText('(обновлено)')
    })
  })

  test.describe('Удаление отзыва', () => {
    test.beforeEach(async ({ page }) => {
      await helper.login('reviewer@example.com', 'password')
      await helper.navigateToMyProfile()
      await helper.openReviewsTab()
    })

    test('должен удалять отзыв с подтверждением', async ({ page }) => {
      // Запоминаем количество отзывов
      const initialCount = await helper.getReviewsCount()
      
      // Настраиваем обработчик диалога
      page.on('dialog', dialog => dialog.accept())
      
      // Удаляем первый отзыв
      const reviewCard = page.locator('.review-card').first()
      const reviewText = await reviewCard.locator('.review-comment').textContent()
      
      await reviewCard.hover()
      await reviewCard.locator('button[title="Удалить"]').click()
      
      // Ждем обновления
      await page.waitForTimeout(1000)
      
      // Проверяем успешное уведомление
      await expect(page.locator('.toast-success')).toBeVisible()
      await expect(page.locator('.toast-success')).toContainText('Отзыв удален')
      
      // Проверяем что отзыв исчез из списка
      const newCount = await helper.getReviewsCount()
      expect(newCount).toBe(initialCount - 1)
      
      // Проверяем что конкретный отзыв удален
      await expect(page.locator(`.review-card:has-text("${reviewText}")`)).not.toBeVisible()
    })

    test('не должен удалять отзыв при отмене в диалоге', async ({ page }) => {
      const initialCount = await helper.getReviewsCount()
      
      // Настраиваем обработчик диалога на отмену
      page.on('dialog', dialog => dialog.dismiss())
      
      // Пытаемся удалить отзыв
      const reviewCard = page.locator('.review-card').first()
      await reviewCard.hover()
      await reviewCard.locator('button[title="Удалить"]').click()
      
      // Ждем немного
      await page.waitForTimeout(500)
      
      // Проверяем что количество отзывов не изменилось
      const newCount = await helper.getReviewsCount()
      expect(newCount).toBe(initialCount)
    })
  })

  test.describe('Фильтрация отзывов', () => {
    test('должен фильтровать отзывы по рейтингу', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      // Фильтруем по 5 звездам
      await helper.filterByRating(5)
      
      // Проверяем что все отображаемые отзывы имеют 5 звезд
      const reviews = page.locator('.review-card')
      const count = await reviews.count()
      
      for (let i = 0; i < count; i++) {
        const review = reviews.nth(i)
        await expect(review.locator('.star-rating .active')).toHaveCount(5)
      }
    })

    test('должен фильтровать отзывы по статусу (для модератора)', async ({ page }) => {
      await helper.login('moderator@example.com', 'password')
      await page.goto('/admin/reviews')
      
      // Фильтруем по статусу "На модерации"
      await helper.filterByStatus('pending')
      
      // Проверяем что все отзывы имеют статус pending
      const reviews = page.locator('.review-card')
      const count = await reviews.count()
      
      for (let i = 0; i < count; i++) {
        const review = reviews.nth(i)
        await expect(review.locator('.status-badge')).toContainText('На модерации')
      }
    })

    test('должен сбрасывать фильтры', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      // Применяем фильтр
      await helper.filterByRating(5)
      let count = await helper.getReviewsCount()
      const filteredCount = count
      
      // Сбрасываем фильтр
      await page.selectOption('select[aria-label="Фильтр по рейтингу"]', '')
      await page.waitForTimeout(500)
      
      // Проверяем что отзывов стало больше
      count = await helper.getReviewsCount()
      expect(count).toBeGreaterThanOrEqual(filteredCount)
    })
  })

  test.describe('Модерация отзывов', () => {
    test.beforeEach(async ({ page }) => {
      await helper.login('moderator@example.com', 'password')
      await page.goto('/admin/reviews')
    })

    test('должен одобрять отзыв', async ({ page }) => {
      // Фильтруем по статусу pending
      await helper.filterByStatus('pending')
      
      // Находим первый отзыв на модерации
      const reviewCard = page.locator('.review-card').first()
      await expect(reviewCard.locator('.status-badge')).toContainText('На модерации')
      
      // Одобряем отзыв
      await reviewCard.locator('button:has-text("Одобрить")').click()
      
      // Проверяем успешное уведомление
      await expect(page.locator('.toast-success')).toBeVisible()
      await expect(page.locator('.toast-success')).toContainText('Отзыв одобрен')
      
      // Проверяем что статус изменился
      await expect(reviewCard.locator('.status-badge')).toContainText('Одобрен')
    })

    test('должен отклонять отзыв с указанием причины', async ({ page }) => {
      // Фильтруем по статусу pending
      await helper.filterByStatus('pending')
      
      // Находим первый отзыв на модерации
      const reviewCard = page.locator('.review-card').first()
      
      // Отклоняем отзыв
      await reviewCard.locator('button:has-text("Отклонить")').click()
      
      // Указываем причину в модальном окне
      await page.waitForSelector('.rejection-modal')
      await page.fill('textarea[name="rejection_reason"]', 'Нарушение правил сообщества')
      await page.click('button:has-text("Подтвердить")')
      
      // Проверяем успешное уведомление
      await expect(page.locator('.toast-success')).toBeVisible()
      await expect(page.locator('.toast-success')).toContainText('Отзыв отклонен')
      
      // Проверяем что статус изменился
      await expect(reviewCard.locator('.status-badge')).toContainText('Отклонен')
    })

    test('должен массово одобрять отзывы', async ({ page }) => {
      await helper.filterByStatus('pending')
      
      // Выбираем несколько отзывов
      await page.check('.review-card:nth-child(1) input[type="checkbox"]')
      await page.check('.review-card:nth-child(2) input[type="checkbox"]')
      await page.check('.review-card:nth-child(3) input[type="checkbox"]')
      
      // Нажимаем массовое одобрение
      await page.click('button:has-text("Одобрить выбранные")')
      
      // Подтверждаем действие
      await page.click('.confirm-modal button:has-text("Подтвердить")')
      
      // Проверяем успешное уведомление
      await expect(page.locator('.toast-success')).toBeVisible()
      await expect(page.locator('.toast-success')).toContainText('3 отзыва одобрены')
    })
  })

  test.describe('Мобильная версия', () => {
    test.use({ viewport: { width: 375, height: 667 } })

    test('должен корректно отображать отзывы на мобильном устройстве', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      // Проверяем мобильную адаптацию
      await expect(page.locator('.reviews-section')).toBeVisible()
      
      // Проверяем что карточки отзывов адаптированы
      const reviewCard = page.locator('.review-card').first()
      const cardWidth = await reviewCard.boundingBox()
      expect(cardWidth?.width).toBeLessThan(375)
      
      // Проверяем что кнопки действий доступны
      await reviewCard.tap() // Используем tap для мобильных устройств
      await expect(reviewCard.locator('.mobile-actions')).toBeVisible()
    })

    test('должен открывать форму отзыва в полноэкранном режиме на мобильном', async ({ page }) => {
      await helper.login()
      await helper.navigateToUserProfile(2)
      
      // Открываем форму
      await page.tap('button:has-text("Добавить отзыв")')
      
      // Проверяем что форма занимает весь экран
      const modal = page.locator('.review-form-modal')
      await expect(modal).toBeVisible()
      
      const modalBox = await modal.boundingBox()
      expect(modalBox?.width).toBeGreaterThanOrEqual(375)
      expect(modalBox?.height).toBeGreaterThanOrEqual(600)
    })
  })

  test.describe('Доступность (Accessibility)', () => {
    test('должен поддерживать навигацию с клавиатуры', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      // Фокусируемся на первом отзыве с помощью Tab
      await page.keyboard.press('Tab')
      await page.keyboard.press('Tab')
      await page.keyboard.press('Tab')
      
      // Проверяем что элемент в фокусе
      const focusedElement = await page.evaluate(() => document.activeElement?.className)
      expect(focusedElement).toContain('review-card')
      
      // Открываем действия с помощью Enter
      await page.keyboard.press('Enter')
      
      // Проверяем что меню действий открылось
      await expect(page.locator('.review-actions-menu')).toBeVisible()
    })

    test('должен иметь правильные ARIA атрибуты', async ({ page }) => {
      await helper.navigateToUserProfile(2)
      
      // Проверяем ARIA атрибуты для секции отзывов
      const reviewsSection = page.locator('.reviews-section')
      await expect(reviewsSection).toHaveAttribute('role', 'region')
      await expect(reviewsSection).toHaveAttribute('aria-label', 'Отзывы пользователя')
      
      // Проверяем ARIA для рейтинга
      const starRating = page.locator('.star-rating').first()
      await expect(starRating).toHaveAttribute('role', 'img')
      await expect(starRating).toHaveAttribute('aria-label', /рейтинг/i)
      
      // Проверяем ARIA для кнопок
      const addButton = page.locator('button:has-text("Добавить отзыв")')
      await expect(addButton).toHaveAttribute('aria-label', 'Добавить новый отзыв')
    })

    test('должен объявлять изменения для screen reader', async ({ page }) => {
      await helper.login()
      await helper.navigateToUserProfile(2)
      
      // Создаем отзыв
      await helper.createReview(5, 'Тестовый отзыв для проверки доступности')
      
      // Проверяем что есть live region для уведомлений
      const liveRegion = page.locator('[role="alert"], [aria-live="polite"]')
      await expect(liveRegion).toContainText('Отзыв добавлен')
    })
  })
})