/**
 * Типы для TrackingSystem из архитектуры Ozon
 * Система аналитики и трекинга событий
 */

// Основное событие трекинга
export interface TrackingEvent {
  actionType: TrackingActionType
  key: string
  timestamp?: number
  sessionId?: string
  userId?: string
  custom?: Record<string, any>
  metadata?: EventMetadata
}

// Типы действий (из Ozon)
export type TrackingActionType = 
  | 'view'           // Просмотр
  | 'click'          // Клик
  | 'aux_click'      // Средняя кнопка мыши
  | 'right_click'    // Правый клик
  | 'hover'          // Наведение
  | 'scroll'         // Прокрутка
  | 'favorite'       // Избранное
  | 'cart_add'       // Добавление в корзину
  | 'cart_remove'    // Удаление из корзины
  | 'purchase'       // Покупка
  | 'search'         // Поиск
  | 'filter'         // Фильтрация
  | 'sort'           // Сортировка
  | 'share'          // Поделиться
  | 'review'         // Отзыв
  | 'compare'        // Сравнение

// Метаданные события
export interface EventMetadata {
  url?: string
  referrer?: string
  viewport?: ViewportData
  device?: DeviceData
  location?: LocationData
  performance?: PerformanceData
}

// Данные viewport
export interface ViewportData {
  width: number
  height: number
  scrollX: number
  scrollY: number
  screenWidth: number
  screenHeight: number
}

// Данные устройства
export interface DeviceData {
  type: 'desktop' | 'mobile' | 'tablet'
  browser: string
  browserVersion: string
  os: string
  osVersion: string
  userAgent: string
}

// Данные локации
export interface LocationData {
  country?: string
  region?: string
  city?: string
  timezone?: string
  language?: string
}

// Данные производительности
export interface PerformanceData {
  loadTime?: number
  renderTime?: number
  interactionTime?: number
  fps?: number
  memory?: number
}

// Трекинг товара (специфично для Ozon)
export interface ProductTracking {
  skuId: string
  position?: number
  price?: number
  category?: string
  brand?: string
  variant?: string
  list?: string
  advertLite?: string // Рекламный идентификатор
}

// Трекинг корзины
export interface CartTracking {
  items: CartItem[]
  totalValue: number
  currency: string
  coupon?: string
  shipping?: number
  tax?: number
}

export interface CartItem {
  skuId: string
  quantity: number
  price: number
  name?: string
  category?: string
}

// Трекинг конверсии
export interface ConversionTracking {
  type: ConversionType
  value?: number
  currency?: string
  transactionId?: string
  items?: CartItem[]
}

export type ConversionType = 
  | 'registration'
  | 'login'
  | 'subscription'
  | 'purchase'
  | 'lead'
  | 'custom'

// Конфигурация трекинга
export interface TrackingConfig {
  enabled: boolean
  endpoints: TrackingEndpoints
  providers: TrackingProvider[]
  sampling?: number // Процент событий для отправки (0-100)
  batchSize?: number
  flushInterval?: number // ms
  retryAttempts?: number
  debug?: boolean
  anonymize?: boolean
}

// Endpoints для отправки данных
export interface TrackingEndpoints {
  events: string
  conversions?: string
  errors?: string
  performance?: string
}

// Провайдеры аналитики
export interface TrackingProvider {
  name: ProviderName
  enabled: boolean
  config: Record<string, any>
}

export type ProviderName = 
  | 'google_analytics'
  | 'yandex_metrika'
  | 'facebook_pixel'
  | 'vk_pixel'
  | 'custom'

// Сессия пользователя
export interface UserSession {
  id: string
  startTime: number
  lastActivity: number
  pageViews: number
  events: TrackingEvent[]
  userData?: UserData
}

export interface UserData {
  id?: string
  isAuthenticated: boolean
  segment?: string
  properties?: Record<string, any>
}

// Очередь событий
export interface EventQueue {
  events: TrackingEvent[]
  maxSize: number
  flushThreshold: number
  lastFlush: number
}

// Результат отправки
export interface TrackingResponse {
  success: boolean
  processed: number
  failed: number
  errors?: string[]
}

// Фильтры событий
export interface EventFilter {
  include?: TrackingActionType[]
  exclude?: TrackingActionType[]
  custom?: (event: TrackingEvent) => boolean
}

// Маппинг событий для разных провайдеров
export interface EventMapping {
  [key: string]: {
    ga?: string // Google Analytics
    ym?: string // Yandex Metrika
    fb?: string // Facebook
    vk?: string // VK
  }
}

// Константы
export const DEFAULT_TRACKING_CONFIG: TrackingConfig = {
  enabled: true,
  endpoints: {
    events: '/api/tracking/events'
  },
  providers: [],
  sampling: 100,
  batchSize: 20,
  flushInterval: 5000,
  retryAttempts: 3,
  debug: false,
  anonymize: false
}

// Маппинг событий Ozon на стандартные
export const OZON_EVENT_MAPPING: EventMapping = {
  view: {
    ga: 'view_item',
    ym: 'detail',
    fb: 'ViewContent',
    vk: 'view_product'
  },
  click: {
    ga: 'select_item',
    ym: 'click',
    fb: 'InitiateCheckout',
    vk: 'init_checkout'
  },
  cart_add: {
    ga: 'add_to_cart',
    ym: 'add',
    fb: 'AddToCart',
    vk: 'add_to_cart'
  },
  purchase: {
    ga: 'purchase',
    ym: 'purchase',
    fb: 'Purchase',
    vk: 'purchase'
  },
  favorite: {
    ga: 'add_to_wishlist',
    ym: 'add_to_wishlist',
    fb: 'AddToWishlist',
    vk: 'add_to_wishlist'
  }
}

// Интерфейс для отслеживания скролла
export interface ScrollTracking {
  depth: number[] // Процент прокрутки [25, 50, 75, 100]
  milestones: Set<number> // Достигнутые вехи
  totalDistance: number
  direction: 'up' | 'down'
  velocity: number
}

// Интерфейс для A/B тестирования
export interface ABTestTracking {
  experimentId: string
  variantId: string
  userId: string
  events: TrackingEvent[]
}

// Интерфейс для heat map
export interface HeatmapData {
  x: number
  y: number
  timestamp: number
  type: 'click' | 'move' | 'hover'
  target?: string
}

// Интерфейс для воронки
export interface FunnelStep {
  name: string
  event: TrackingActionType
  users: number
  conversionRate?: number
}

// Статистика трекинга
export interface TrackingStats {
  totalEvents: number
  eventsByType: Record<TrackingActionType, number>
  successRate: number
  averageLatency: number
  errors: number
  lastError?: string
}

// Настройки приватности
export interface PrivacySettings {
  trackingConsent: boolean
  cookiesConsent: boolean
  personalDataConsent: boolean
  marketingConsent: boolean
  analyticsConsent: boolean
}

// Интерфейс для дебаггинга
export interface TrackingDebugInfo {
  event: TrackingEvent
  timestamp: number
  status: 'pending' | 'sent' | 'failed'
  response?: any
  error?: string
  attempts: number
}