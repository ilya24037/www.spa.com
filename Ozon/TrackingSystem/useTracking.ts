/**
 * Composable для системы трекинга в стиле Ozon
 * Отслеживание событий, аналитика, батчинг
 */

import { ref, computed, onMounted, onUnmounted } from 'vue'
import type {
  TrackingEvent,
  TrackingActionType,
  TrackingConfig,
  EventQueue,
  UserSession,
  TrackingResponse,
  TrackingStats,
  ProductTracking,
  CartTracking,
  ConversionTracking,
  ScrollTracking,
  HeatmapData,
  PrivacySettings,
  TrackingDebugInfo
} from './TrackingSystem.types'
import { 
  DEFAULT_TRACKING_CONFIG,
  OZON_EVENT_MAPPING
} from './TrackingSystem.types'

export function useTracking(config?: Partial<TrackingConfig>) {
  // Конфигурация
  const settings = ref<TrackingConfig>({
    ...DEFAULT_TRACKING_CONFIG,
    ...config
  })
  
  // Состояние
  const eventQueue = ref<EventQueue>({
    events: [],
    maxSize: 100,
    flushThreshold: settings.value.batchSize || 20,
    lastFlush: Date.now()
  })
  
  const session = ref<UserSession>({
    id: generateSessionId(),
    startTime: Date.now(),
    lastActivity: Date.now(),
    pageViews: 0,
    events: []
  })
  
  const stats = ref<TrackingStats>({
    totalEvents: 0,
    eventsByType: {} as Record<TrackingActionType, number>,
    successRate: 100,
    averageLatency: 0,
    errors: 0
  })
  
  const scrollTracking = ref<ScrollTracking>({
    depth: [25, 50, 75, 100],
    milestones: new Set(),
    totalDistance: 0,
    direction: 'down',
    velocity: 0
  })
  
  const heatmapData = ref<HeatmapData[]>([])
  const debugLog = ref<TrackingDebugInfo[]>([])
  const privacySettings = ref<PrivacySettings>({
    trackingConsent: true,
    cookiesConsent: true,
    personalDataConsent: true,
    marketingConsent: true,
    analyticsConsent: true
  })
  
  // Таймеры
  let flushTimer: number | null = null
  let sessionTimer: number | null = null
  
  // Генерация ID сессии
  function generateSessionId(): string {
    return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
  }
  
  // Генерация ID пользователя
  function getUserId(): string | undefined {
    // Получаем из localStorage или генерируем новый
    let userId = localStorage.getItem('ozon_user_id')
    if (!userId) {
      userId = `user_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
      localStorage.setItem('ozon_user_id', userId)
    }
    return userId
  }
  
  // Основная функция трекинга
  const track = async (
    actionType: TrackingActionType,
    data?: Record<string, any>
  ): Promise<void> => {
    if (!settings.value.enabled || !privacySettings.value.trackingConsent) {
      return
    }
    
    // Применяем семплирование
    if (settings.value.sampling && settings.value.sampling < 100) {
      if (Math.random() * 100 > settings.value.sampling) {
        return
      }
    }
    
    // Создаем событие
    const event: TrackingEvent = {
      actionType,
      key: generateEventKey(actionType),
      timestamp: Date.now(),
      sessionId: session.value.id,
      userId: getUserId(),
      custom: data,
      metadata: collectMetadata()
    }
    
    // Добавляем в очередь
    addToQueue(event)
    
    // Обновляем статистику
    updateStats(actionType)
    
    // Обновляем сессию
    updateSession(event)
    
    // Дебаг лог
    if (settings.value.debug) {
      logDebug(event)
    }
    
    // Проверяем необходимость отправки
    if (shouldFlush()) {
      await flush()
    }
  }
  
  // Трекинг просмотра товара
  const trackProductView = (product: ProductTracking) => {
    track('view', {
      skuId: product.skuId,
      position: product.position,
      price: product.price,
      category: product.category,
      brand: product.brand,
      variant: product.variant,
      list: product.list,
      advertLite: product.advertLite
    })
  }
  
  // Трекинг клика по товару
  const trackProductClick = (product: ProductTracking) => {
    track('click', {
      skuId: product.skuId,
      position: product.position,
      price: product.price,
      category: product.category,
      brand: product.brand,
      list: product.list
    })
  }
  
  // Трекинг добавления в корзину
  const trackAddToCart = (product: ProductTracking, quantity: number = 1) => {
    track('cart_add', {
      skuId: product.skuId,
      quantity,
      price: product.price,
      value: product.price ? product.price * quantity : undefined,
      category: product.category,
      brand: product.brand
    })
  }
  
  // Трекинг покупки
  const trackPurchase = (cart: CartTracking) => {
    track('purchase', {
      items: cart.items,
      totalValue: cart.totalValue,
      currency: cart.currency,
      coupon: cart.coupon,
      shipping: cart.shipping,
      tax: cart.tax,
      itemCount: cart.items.length
    })
  }
  
  // Трекинг конверсии
  const trackConversion = (conversion: ConversionTracking) => {
    const event = {
      type: conversion.type,
      value: conversion.value,
      currency: conversion.currency,
      transactionId: conversion.transactionId,
      items: conversion.items
    }
    
    // Отправляем в основной трекинг
    track('purchase', event)
    
    // Отправляем в специальный endpoint конверсий
    if (settings.value.endpoints.conversions) {
      sendConversion(event)
    }
  }
  
  // Трекинг скролла
  const trackScroll = (scrollData: {
    scrollTop: number
    scrollHeight: number
    clientHeight: number
  }) => {
    const scrollPercent = Math.round(
      (scrollData.scrollTop / (scrollData.scrollHeight - scrollData.clientHeight)) * 100
    )
    
    // Проверяем вехи прокрутки
    scrollTracking.value.depth.forEach(milestone => {
      if (scrollPercent >= milestone && !scrollTracking.value.milestones.has(milestone)) {
        scrollTracking.value.milestones.add(milestone)
        track('scroll', {
          depth: milestone,
          scrollTop: scrollData.scrollTop,
          scrollHeight: scrollData.scrollHeight
        })
      }
    })
    
    // Обновляем данные скролла
    scrollTracking.value.totalDistance += Math.abs(
      scrollData.scrollTop - (scrollTracking.value.totalDistance || 0)
    )
  }
  
  // Трекинг поиска
  const trackSearch = (query: string, results: number) => {
    track('search', {
      query,
      results,
      queryLength: query.length,
      hasResults: results > 0
    })
  }
  
  // Трекинг фильтров
  const trackFilter = (filters: Record<string, any>) => {
    track('filter', {
      filters,
      filterCount: Object.keys(filters).length
    })
  }
  
  // Трекинг сортировки
  const trackSort = (sortBy: string, order: 'asc' | 'desc') => {
    track('sort', {
      sortBy,
      order
    })
  }
  
  // Добавление в очередь
  const addToQueue = (event: TrackingEvent) => {
    eventQueue.value.events.push(event)
    
    // Ограничиваем размер очереди
    if (eventQueue.value.events.length > eventQueue.value.maxSize) {
      eventQueue.value.events.shift()
    }
  }
  
  // Проверка необходимости отправки
  const shouldFlush = (): boolean => {
    const queue = eventQueue.value
    const timeSinceLastFlush = Date.now() - queue.lastFlush
    
    return (
      queue.events.length >= queue.flushThreshold ||
      timeSinceLastFlush >= (settings.value.flushInterval || 5000)
    )
  }
  
  // Отправка событий
  const flush = async (): Promise<void> => {
    if (eventQueue.value.events.length === 0) return
    
    const eventsToSend = [...eventQueue.value.events]
    eventQueue.value.events = []
    eventQueue.value.lastFlush = Date.now()
    
    try {
      const response = await sendEvents(eventsToSend)
      
      if (response.success) {
        stats.value.successRate = 
          (stats.value.successRate * stats.value.totalEvents + response.processed) /
          (stats.value.totalEvents + response.processed)
      } else {
        // Возвращаем события в очередь при ошибке
        eventQueue.value.events.unshift(...eventsToSend)
        stats.value.errors++
      }
    } catch (error) {
      // Возвращаем события в очередь при ошибке
      eventQueue.value.events.unshift(...eventsToSend)
      stats.value.errors++
      stats.value.lastError = error instanceof Error ? error.message : 'Unknown error'
    }
  }
  
  // Отправка событий на сервер
  const sendEvents = async (events: TrackingEvent[]): Promise<TrackingResponse> => {
    const startTime = Date.now()
    
    try {
      const response = await fetch(settings.value.endpoints.events, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          events,
          session: session.value,
          timestamp: Date.now()
        })
      })
      
      const latency = Date.now() - startTime
      stats.value.averageLatency = 
        (stats.value.averageLatency * stats.value.totalEvents + latency) /
        (stats.value.totalEvents + events.length)
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }
      
      return await response.json()
    } catch (error) {
      console.error('Failed to send tracking events:', error)
      throw error
    }
  }
  
  // Отправка конверсии
  const sendConversion = async (conversion: any) => {
    if (!settings.value.endpoints.conversions) return
    
    try {
      await fetch(settings.value.endpoints.conversions, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(conversion)
      })
    } catch (error) {
      console.error('Failed to send conversion:', error)
    }
  }
  
  // Сбор метаданных
  const collectMetadata = () => {
    return {
      url: window.location.href,
      referrer: document.referrer,
      viewport: {
        width: window.innerWidth,
        height: window.innerHeight,
        scrollX: window.scrollX,
        scrollY: window.scrollY,
        screenWidth: screen.width,
        screenHeight: screen.height
      },
      device: {
        type: getDeviceType(),
        browser: getBrowser(),
        browserVersion: getBrowserVersion(),
        os: getOS(),
        osVersion: getOSVersion(),
        userAgent: navigator.userAgent
      },
      location: {
        language: navigator.language,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
      },
      performance: getPerformanceData()
    }
  }
  
  // Определение типа устройства
  const getDeviceType = (): 'desktop' | 'mobile' | 'tablet' => {
    const width = window.innerWidth
    if (width < 768) return 'mobile'
    if (width < 1024) return 'tablet'
    return 'desktop'
  }
  
  // Получение браузера
  const getBrowser = (): string => {
    const ua = navigator.userAgent
    if (ua.includes('Chrome')) return 'Chrome'
    if (ua.includes('Safari')) return 'Safari'
    if (ua.includes('Firefox')) return 'Firefox'
    if (ua.includes('Edge')) return 'Edge'
    return 'Unknown'
  }
  
  // Получение версии браузера
  const getBrowserVersion = (): string => {
    const ua = navigator.userAgent
    const match = ua.match(/(Chrome|Safari|Firefox|Edge)\/(\d+)/)
    return match ? match[2] : 'Unknown'
  }
  
  // Получение ОС
  const getOS = (): string => {
    const ua = navigator.userAgent
    if (ua.includes('Windows')) return 'Windows'
    if (ua.includes('Mac')) return 'macOS'
    if (ua.includes('Linux')) return 'Linux'
    if (ua.includes('Android')) return 'Android'
    if (ua.includes('iOS')) return 'iOS'
    return 'Unknown'
  }
  
  // Получение версии ОС
  const getOSVersion = (): string => {
    // Упрощенная версия
    return 'Unknown'
  }
  
  // Получение данных производительности
  const getPerformanceData = () => {
    if (!window.performance) return {}
    
    const navigation = performance.getEntriesByType('navigation')[0] as any
    
    return {
      loadTime: navigation?.loadEventEnd - navigation?.fetchStart,
      renderTime: navigation?.domComplete - navigation?.domLoading,
      interactionTime: navigation?.domInteractive - navigation?.domLoading
    }
  }
  
  // Генерация ключа события
  const generateEventKey = (actionType: TrackingActionType): string => {
    return `${actionType}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
  }
  
  // Обновление статистики
  const updateStats = (actionType: TrackingActionType) => {
    stats.value.totalEvents++
    stats.value.eventsByType[actionType] = 
      (stats.value.eventsByType[actionType] || 0) + 1
  }
  
  // Обновление сессии
  const updateSession = (event: TrackingEvent) => {
    session.value.lastActivity = Date.now()
    session.value.events.push(event)
    
    if (event.actionType === 'view') {
      session.value.pageViews++
    }
    
    // Ограничиваем историю событий в сессии
    if (session.value.events.length > 100) {
      session.value.events = session.value.events.slice(-50)
    }
  }
  
  // Логирование для дебага
  const logDebug = (event: TrackingEvent) => {
    const debugInfo: TrackingDebugInfo = {
      event,
      timestamp: Date.now(),
      status: 'pending',
      attempts: 0
    }
    
    debugLog.value.push(debugInfo)
    
    if (debugLog.value.length > 50) {
      debugLog.value = debugLog.value.slice(-25)
    }
    
    console.log('[Tracking]', event.actionType, event)
  }
  
  // Инициализация автоматической отправки
  const startAutoFlush = () => {
    if (flushTimer) clearInterval(flushTimer)
    
    flushTimer = window.setInterval(() => {
      if (eventQueue.value.events.length > 0) {
        flush()
      }
    }, settings.value.flushInterval || 5000)
  }
  
  // Остановка автоматической отправки
  const stopAutoFlush = () => {
    if (flushTimer) {
      clearInterval(flushTimer)
      flushTimer = null
    }
  }
  
  // Обновление настроек приватности
  const updatePrivacySettings = (newSettings: Partial<PrivacySettings>) => {
    privacySettings.value = {
      ...privacySettings.value,
      ...newSettings
    }
    
    // Сохраняем в localStorage
    localStorage.setItem('ozon_privacy_settings', JSON.stringify(privacySettings.value))
  }
  
  // Загрузка настроек приватности
  const loadPrivacySettings = () => {
    const stored = localStorage.getItem('ozon_privacy_settings')
    if (stored) {
      try {
        privacySettings.value = JSON.parse(stored)
      } catch (error) {
        console.error('Failed to load privacy settings:', error)
      }
    }
  }
  
  // Очистка данных трекинга
  const clearTrackingData = () => {
    eventQueue.value.events = []
    session.value.events = []
    heatmapData.value = []
    debugLog.value = []
    scrollTracking.value.milestones.clear()
  }
  
  // Экспорт данных трекинга
  const exportTrackingData = () => {
    return {
      session: session.value,
      stats: stats.value,
      eventQueue: eventQueue.value.events,
      debugLog: debugLog.value,
      timestamp: Date.now()
    }
  }
  
  // Lifecycle
  onMounted(() => {
    loadPrivacySettings()
    startAutoFlush()
    
    // Отправка событий при закрытии страницы
    window.addEventListener('beforeunload', () => {
      if (eventQueue.value.events.length > 0) {
        // Используем sendBeacon для надежной отправки
        const data = JSON.stringify({
          events: eventQueue.value.events,
          session: session.value
        })
        navigator.sendBeacon(settings.value.endpoints.events, data)
      }
    })
  })
  
  onUnmounted(() => {
    stopAutoFlush()
    flush() // Отправляем оставшиеся события
  })
  
  return {
    // Основные методы
    track,
    trackProductView,
    trackProductClick,
    trackAddToCart,
    trackPurchase,
    trackConversion,
    trackScroll,
    trackSearch,
    trackFilter,
    trackSort,
    
    // Управление
    flush,
    clearTrackingData,
    exportTrackingData,
    updatePrivacySettings,
    
    // Состояние
    session: computed(() => session.value),
    stats: computed(() => stats.value),
    eventQueue: computed(() => eventQueue.value),
    scrollTracking: computed(() => scrollTracking.value),
    privacySettings: computed(() => privacySettings.value),
    debugLog: computed(() => debugLog.value),
    
    // Настройки
    settings: computed(() => settings.value),
    updateSettings: (newSettings: Partial<TrackingConfig>) => {
      settings.value = { ...settings.value, ...newSettings }
      if (newSettings.flushInterval) {
        stopAutoFlush()
        startAutoFlush()
      }
    }
  }
}

// Глобальный экземпляр
let globalTracking: ReturnType<typeof useTracking> | null = null

export function useGlobalTracking(config?: Partial<TrackingConfig>) {
  if (!globalTracking) {
    globalTracking = useTracking(config)
  }
  return globalTracking
}