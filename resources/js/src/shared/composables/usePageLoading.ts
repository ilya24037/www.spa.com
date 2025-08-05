// usePageLoading.ts
import { computed, onMounted, onUnmounted } from 'vue'
import type { Ref } from 'vue'
import type {
  UsePageLoadingOptions,
  UsePageLoadingReturn,
  PageLoadingState,
  PageLoadingError,
  PageLoaderType,
  PageLoadingAnalytics
} from '../ui/organisms/PageLoader/PageLoader.types'

/**
 * Composable для управления состояниями загрузки страниц
 * Предоставляет удобный API для отображения loading состояний
 */
export function usePageLoading(options: UsePageLoadingOptions = {}): UsePageLoadingReturn {
  const {
    type = 'default',
    autoStart = false,
    timeout = 30000, // 30 секунд
    retryCount = 3,
    showProgress = false,
    onStart,
    onProgress,
    onComplete,
    onError
  } = options

  // Реактивное состояние
  const state = ref<PageLoadingState>({
    isLoading: false,
    progress: 0,
    message: '',
    error: null,
    startTime: 0,
    estimatedDuration: undefined
  })

  // Дополнительные состояния
  const retryAttempts = ref<number>(0)
  const timeoutId = ref<number | null>(null)
  const progressIntervalId = ref<number | null>(null)

  // Computed свойства
  const isLoading = computed<boolean>(() => state.value.isLoading)
  const progress = computed<number>(() => state.value.progress)
  const message = computed<string>(() => state.value.message)
  const error = computed<PageLoadingError | null>(() => state.value.error)

  // Получение дефолтного сообщения для типа страницы
  const getDefaultMessage = (pageType: PageLoaderType): string => {
    const messages: Record<PageLoaderType, string> = {
      catalog: 'Загружаем мастеров...',
      profile: 'Загружаем профиль...',
      dashboard: 'Загружаем данные...',
      form: 'Подготавливаем форму...',
      content: 'Загружаем контент...',
      minimal: 'Загрузка...',
      default: 'Загрузка страницы...'
    }
    return messages[pageType]
  }

  // Очистка таймеров
  const clearTimers = (): void => {
    if (timeoutId.value) {
      clearTimeout(timeoutId.value)
      timeoutId.value = null
    }
    if (progressIntervalId.value) {
      clearInterval(progressIntervalId.value)
      progressIntervalId.value = null
    }
  }

  // Начало загрузки
  const startLoading = (customMessage?: string): void => {
    try {
      clearTimers()
      
      state.value = {
        isLoading: true,
        progress: 0,
        message: customMessage || getDefaultMessage(type),
        error: null,
        startTime: Date.now(),
        estimatedDuration: undefined
      }

      retryAttempts.value = 0

      // Логируем начало загрузки
      console.log(`PageLoading [${type}]: Started`, {
        message: state.value.message,
        timestamp: new Date().toISOString()
      })

      // Колбек начала загрузки
      onStart?.()

      // Настраиваем таймаут
      if (timeout > 0) {
        timeoutId.value = setTimeout(() => {
          const timeoutError: PageLoadingError = {
            type: 'timeout',
            message: `Превышено время ожидания загрузки (${timeout}ms)`,
            code: 408
          }
          errorLoading(timeoutError)
        }, timeout)
      }

      // Если включен прогресс, запускаем симуляцию
      if (showProgress) {
        simulateProgress()
      }

    } catch (err: unknown) {
      const loadingError: PageLoadingError = {
        type: 'client',
        message: 'Ошибка при запуске загрузки',
        originalError: err
      }
      errorLoading(loadingError)
    }
  }

  // Установка прогресса
  const setProgress = (newProgress: number, progressMessage?: string): void => {
    try {
      if (!state.value.isLoading) return

      const clampedProgress = Math.max(0, Math.min(100, newProgress))
      
      state.value.progress = clampedProgress
      if (progressMessage) {
        state.value.message = progressMessage
      }

      // Логируем прогресс
      console.log(`PageLoading [${type}]: Progress ${clampedProgress}%`, {
        message: state.value.message,
        timestamp: new Date().toISOString()
      })

      // Колбек прогресса
      onProgress?.(clampedProgress)

      // Автозавершение при 100%
      if (clampedProgress >= 100) {
        setTimeout(() => {
          if (state.value.isLoading) {
            completeLoading()
          }
        }, 500) // Небольшая задержка для плавности
      }

    } catch (err: unknown) {
      console.error(`PageLoading [${type}]: Error setting progress`, err)
    }
  }

  // Завершение загрузки
  const completeLoading = (): void => {
    try {
      if (!state.value.isLoading) return

      clearTimers()

      const duration = Date.now() - state.value.startTime

      // Логируем завершение
      console.log(`PageLoading [${type}]: Completed`, {
        duration: `${duration}ms`,
        timestamp: new Date().toISOString()
      })

      // Отправляем аналитику
      sendAnalytics({
        pageType: type,
        startTime: state.value.startTime,
        endTime: Date.now(),
        duration,
        success: true
      })

      state.value.isLoading = false
      state.value.progress = 100

      // Колбек завершения
      onComplete?.()

    } catch (err: unknown) {
      console.error(`PageLoading [${type}]: Error completing loading`, err)
    }
  }

  // Ошибка загрузки
  const errorLoading = (loadingError: PageLoadingError): void => {
    try {
      clearTimers()

      state.value.isLoading = false
      state.value.error = loadingError

      const duration = Date.now() - state.value.startTime

      // Логируем ошибку
      console.error(`PageLoading [${type}]: Error`, {
        error: loadingError,
        duration: `${duration}ms`,
        retryAttempts: retryAttempts.value,
        timestamp: new Date().toISOString()
      })

      // Отправляем аналитику
      sendAnalytics({
        pageType: type,
        startTime: state.value.startTime,
        endTime: Date.now(),
        duration,
        success: false,
        error: loadingError
      })

      // Колбек ошибки
      onError?.(loadingError)

    } catch (err: unknown) {
      console.error(`PageLoading [${type}]: Error handling error`, err)
    }
  }

  // Повторная попытка загрузки
  const retryLoading = (): void => {
    try {
      if (retryAttempts.value >= retryCount) {
        const maxRetriesError: PageLoadingError = {
          type: 'client',
          message: `Превышено количество попыток повтора (${retryCount})`,
          code: 429
        }
        errorLoading(maxRetriesError)
        return
      }

      retryAttempts.value++
      
      console.log(`PageLoading [${type}]: Retry attempt ${retryAttempts.value}/${retryCount}`)
      
      const retryMessage = `Повторная попытка ${retryAttempts.value}/${retryCount}...`
      startLoading(retryMessage)

    } catch (err: unknown) {
      const retryError: PageLoadingError = {
        type: 'client',
        message: 'Ошибка при повторной попытке загрузки',
        originalError: err
      }
      errorLoading(retryError)
    }
  }

  // Симуляция прогресса для лучшего UX
  const simulateProgress = (): void => {
    let currentProgress = 0
    const increment = Math.random() * 3 + 1 // 1-4% за раз
    
    progressIntervalId.value = setInterval(() => {
      if (!state.value.isLoading || currentProgress >= 90) {
        if (progressIntervalId.value) {
          clearInterval(progressIntervalId.value)
          progressIntervalId.value = null
        }
        return
      }

      currentProgress += increment * (1 - currentProgress / 100) // Замедляется к концу
      setProgress(Math.floor(currentProgress))
      
    }, 100 + Math.random() * 200) // 100-300ms интервал
  }

  // Отправка аналитики
  const sendAnalytics = (analytics: PageLoadingAnalytics): void => {
    try {
      // В реальном приложении здесь был бы вызов аналитического сервиса
      if (typeof window !== 'undefined' && window.gtag) {
        window.gtag('event', 'page_loading', {
          page_type: analytics.pageType,
          duration: analytics.duration,
          success: analytics.success,
          error_type: analytics.error?.type
        })
      }

      // Можно также отправить в собственную аналитику
      console.log('PageLoading Analytics:', analytics)
      
    } catch (err: unknown) {
      console.warn('Failed to send page loading analytics:', err)
    }
  }

  // Автозапуск при монтировании
  onMounted(() => {
    if (autoStart) {
      startLoading()
    }
  })

  // Очистка при размонтировании
  onUnmounted(() => {
    clearTimers()
  })

  return {
    isLoading,
    progress,
    message,
    error,
    startLoading,
    setProgress,
    completeLoading,
    errorLoading,
    retryLoading
  }
}

// Типы для window.gtag
declare global {
  interface Window {
    gtag?: (
      command: string,
      eventName: string,
      parameters: Record<string, any>
    ) => void
  }
}