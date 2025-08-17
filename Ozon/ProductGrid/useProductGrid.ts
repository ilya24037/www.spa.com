/**
 * Composable для логики ProductGrid
 * Использует точную логику из Ozon
 */

import { ref, computed } from 'vue'
import type { ProductGridOptions, GridState, LoadParams } from './ProductGrid.types'

export function useProductGrid(options: ProductGridOptions) {
  // Состояние как в Ozon
  const state = ref<GridState>({
    isLoading: false,
    hasMore: true,
    isEmpty: false,
    currentOffset: options.initialOffset,
    totalLoaded: 0,
    timeStarted: Date.now()
  })

  // История загрузок для отладки
  const loadHistory = ref<Array<{
    offset: number
    limit: number
    timestamp: number
    duration: number
  }>>([])

  // Текущая страница (для пагинации)
  const currentPage = computed(() => {
    return Math.floor(state.value.totalLoaded / options.itemsOnPage) + 1
  })

  // Следующий offset (логика Ozon)
  const nextOffset = computed(() => {
    // В Ozon используется offset + itemsOnPage
    return state.value.currentOffset + options.itemsOnPage
  })

  // Загрузка следующей страницы
  const loadNextPage = async () => {
    if (state.value.isLoading || !state.value.hasMore) {
      return
    }

    const startTime = performance.now()
    state.value.isLoading = true

    try {
      // Симуляция загрузки (в реальном приложении здесь будет API вызов)
      await new Promise(resolve => setTimeout(resolve, 500))

      // Обновляем offset как в Ozon
      state.value.currentOffset = nextOffset.value
      state.value.totalLoaded += options.itemsOnPage

      // Логика определения hasMore из Ozon
      // Они используют paginationExtraEmptyPage для дополнительной проверки
      if (state.value.totalLoaded >= 300) { // Примерный лимит
        state.value.hasMore = false
      }

      // Записываем в историю
      const duration = performance.now() - startTime
      loadHistory.value.push({
        offset: state.value.currentOffset,
        limit: options.itemsOnPage,
        timestamp: Date.now(),
        duration
      })

      // Проверка на пустоту
      if (state.value.totalLoaded === 0) {
        state.value.isEmpty = true
      }

    } catch (error) {
      console.error('Error loading products:', error)
      state.value.hasMore = false
    } finally {
      state.value.isLoading = false
    }
  }

  // Сброс сетки
  const resetGrid = () => {
    state.value = {
      isLoading: false,
      hasMore: true,
      isEmpty: false,
      currentOffset: options.initialOffset,
      totalLoaded: 0,
      timeStarted: Date.now()
    }
    loadHistory.value = []
  }

  // Трекинг времени на виджете (как в Ozon timeSpent)
  const trackTimeSpent = () => {
    const getTimeSpent = () => Date.now() - state.value.timeStarted

    // Отправляем время каждые 10 секунд
    const interval = setInterval(() => {
      const timeSpent = getTimeSpent()
      console.log('Widget time spent:', timeSpent, 'ms')
      
      // В Ozon это отправляется в аналитику
      if (typeof window !== 'undefined' && window.performance) {
        performance.mark(`widget-time-${timeSpent}`)
      }
    }, 10000)

    // Cleanup
    const cleanup = () => {
      clearInterval(interval)
      const finalTime = getTimeSpent()
      console.log('Widget final time:', finalTime, 'ms')
    }

    // Возвращаем функцию cleanup
    return cleanup
  }

  // Параметры для API (формат Ozon)
  const getLoadParams = (): LoadParams => {
    return {
      offset: state.value.currentOffset,
      limit: options.itemsOnPage,
      algo: options.algo
    }
  }

  // Проверка видимости (для lazy loading)
  const checkVisibility = (element: HTMLElement): boolean => {
    const rect = element.getBoundingClientRect()
    const windowHeight = window.innerHeight || document.documentElement.clientHeight
    const windowWidth = window.innerWidth || document.documentElement.clientWidth

    const vertInView = (rect.top <= windowHeight) && ((rect.top + rect.height) >= 0)
    const horInView = (rect.left <= windowWidth) && ((rect.left + rect.width) >= 0)

    return vertInView && horInView
  }

  // Performance метрики (как в Ozon)
  const getPerformanceMetrics = () => {
    if (typeof window === 'undefined' || !window.performance) {
      return null
    }

    const metrics = {
      loadCount: loadHistory.value.length,
      averageLoadTime: loadHistory.value.length > 0
        ? loadHistory.value.reduce((acc, item) => acc + item.duration, 0) / loadHistory.value.length
        : 0,
      totalTimeSpent: Date.now() - state.value.timeStarted,
      itemsLoaded: state.value.totalLoaded
    }

    // Маркеры производительности как в Ozon
    performance.mark('widget-metrics')
    performance.measure('widget-performance', 'widget-metrics')

    return metrics
  }

  // Computed свойства для удобства
  const isLoading = computed(() => state.value.isLoading)
  const hasMore = computed(() => state.value.hasMore)
  const isEmpty = computed(() => state.value.isEmpty)
  const currentOffset = computed(() => state.value.currentOffset)
  const totalLoaded = computed(() => state.value.totalLoaded)

  return {
    // State
    isLoading,
    hasMore,
    isEmpty,
    currentOffset,
    totalLoaded,
    currentPage,
    nextOffset,
    
    // Methods
    loadNextPage,
    resetGrid,
    trackTimeSpent,
    getLoadParams,
    checkVisibility,
    getPerformanceMetrics,
    
    // Debug
    loadHistory: computed(() => loadHistory.value)
  }
}