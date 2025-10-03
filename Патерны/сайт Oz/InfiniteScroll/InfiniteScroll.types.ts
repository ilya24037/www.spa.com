/**
 * Типы для InfiniteScroll из архитектуры Ozon
 * Бесконечная прокрутка с виртуализацией
 */

// Основная конфигурация
export interface InfiniteScrollConfig {
  threshold?: number // Порог срабатывания (px от конца)
  rootMargin?: string // Отступы для IntersectionObserver
  direction?: ScrollDirection
  strategy?: LoadStrategy
  virtualScroll?: boolean
  buffer?: number // Буфер элементов для виртуализации
  debounceDelay?: number
  throttleDelay?: number
  retryAttempts?: number
  retryDelay?: number
}

// Направление прокрутки
export type ScrollDirection = 'vertical' | 'horizontal' | 'both'

// Стратегия загрузки
export type LoadStrategy = 
  | 'intersection' // IntersectionObserver
  | 'scroll'       // Scroll events
  | 'manual'       // Ручная загрузка
  | 'auto'         // Автоматическая

// Состояние компонента
export interface InfiniteScrollState {
  isLoading: boolean
  isComplete: boolean
  hasError: boolean
  currentPage: number
  totalPages?: number
  itemsLoaded: number
  totalItems?: number
  scrollPosition: number
  viewportHeight: number
  retryCount: number
}

// Параметры загрузки страницы
export interface LoadPageParams {
  page: number
  pageSize: number
  offset?: number
  sortBy?: string
  filters?: Record<string, any>
  signal?: AbortSignal
}

// Ответ от API
export interface PageResponse<T> {
  items: T[]
  page: number
  pageSize: number
  totalPages: number
  totalItems: number
  hasNext: boolean
  hasPrev: boolean
}

// События компонента
export interface InfiniteScrollEvents {
  onLoadMore: (params: LoadPageParams) => Promise<PageResponse<any>>
  onScroll?: (event: ScrollEvent) => void
  onReachEnd?: () => void
  onReachStart?: () => void
  onError?: (error: Error) => void
  onRetry?: () => void
  onReset?: () => void
}

// Данные о прокрутке
export interface ScrollEvent {
  scrollTop: number
  scrollLeft: number
  scrollHeight: number
  scrollWidth: number
  clientHeight: number
  clientWidth: number
  direction: 'up' | 'down' | 'left' | 'right'
  velocity: number
  timestamp: number
}

// Виртуальная прокрутка
export interface VirtualScrollConfig {
  itemHeight?: number // Фиксированная высота элемента
  estimatedItemHeight?: number // Примерная высота для динамических элементов
  overscan?: number // Количество элементов за пределами viewport
  getItemHeight?: (index: number) => number // Функция для динамической высоты
  cacheSize?: number // Размер кеша высот
}

// Данные виртуального скролла
export interface VirtualScrollData {
  startIndex: number
  endIndex: number
  visibleItems: any[]
  offsetTop: number
  offsetBottom: number
  totalHeight: number
}

// Опции отображения
export interface DisplayOptions {
  showLoader?: boolean
  loaderComponent?: any
  showEndMessage?: boolean
  endMessage?: string
  showError?: boolean
  errorComponent?: any
  showEmpty?: boolean
  emptyComponent?: any
  transition?: TransitionConfig
}

// Конфигурация анимаций
export interface TransitionConfig {
  name?: string
  duration?: number
  easing?: string
  stagger?: number
}

// Метрики производительности
export interface PerformanceMetrics {
  loadTime: number
  renderTime: number
  scrollFPS: number
  memoryUsage?: number
  itemsInView: number
  totalRendered: number
}

// Настройки оптимизации
export interface OptimizationConfig {
  lazyImages?: boolean
  placeholders?: boolean
  recycleItems?: boolean
  batchSize?: number
  maxConcurrentLoads?: number
  cachePages?: boolean
  cacheTimeout?: number
}

// Конфигурация пагинации
export interface PaginationConfig {
  pageSize: number
  maxPages?: number
  preloadPages?: number
  strategy?: 'offset' | 'cursor' | 'page'
  cursorField?: string
}

// Состояние пагинации
export interface PaginationState {
  currentPage: number
  pageSize: number
  totalPages: number
  totalItems: number
  hasNextPage: boolean
  hasPrevPage: boolean
  cursor?: string
  offset: number
}

// Кеш страниц
export interface PageCache<T> {
  pages: Map<number, CachedPage<T>>
  maxSize: number
  ttl: number // Time to live в ms
}

export interface CachedPage<T> {
  items: T[]
  timestamp: number
  page: number
  isStale: boolean
}

// Настройки дебаунса/throttle
export interface RateLimitConfig {
  debounce?: number
  throttle?: number
  leading?: boolean
  trailing?: boolean
  maxWait?: number
}

// Sentinel элемент для IntersectionObserver
export interface SentinelConfig {
  position: 'top' | 'bottom' | 'both'
  rootMargin: string
  threshold: number | number[]
  className?: string
  style?: Record<string, any>
}

// Константы
export const DEFAULT_CONFIG: InfiniteScrollConfig = {
  threshold: 200,
  rootMargin: '100px',
  direction: 'vertical',
  strategy: 'intersection',
  virtualScroll: false,
  buffer: 5,
  debounceDelay: 150,
  throttleDelay: 100,
  retryAttempts: 3,
  retryDelay: 1000
}

export const DEFAULT_PAGINATION: PaginationConfig = {
  pageSize: 20,
  maxPages: undefined,
  preloadPages: 1,
  strategy: 'page'
}

export const DEFAULT_VIRTUAL_SCROLL: VirtualScrollConfig = {
  estimatedItemHeight: 100,
  overscan: 3,
  cacheSize: 100
}

export const DEFAULT_OPTIMIZATION: OptimizationConfig = {
  lazyImages: true,
  placeholders: true,
  recycleItems: false,
  batchSize: 10,
  maxConcurrentLoads: 2,
  cachePages: true,
  cacheTimeout: 5 * 60 * 1000 // 5 минут
}

// Стратегии обработки ошибок
export interface ErrorHandling {
  strategy: 'retry' | 'ignore' | 'stop' | 'fallback'
  maxRetries?: number
  retryDelay?: number
  fallbackData?: any[]
  onError?: (error: Error, attempt: number) => void
}

// Индикаторы загрузки
export interface LoadingIndicators {
  type: 'spinner' | 'skeleton' | 'progress' | 'custom'
  position: 'inline' | 'overlay' | 'bottom' | 'top'
  size?: 'small' | 'medium' | 'large'
  color?: string
  text?: string
}

// Фильтры и сортировка
export interface FilterSort {
  filters: Record<string, any>
  sort: {
    field: string
    order: 'asc' | 'desc'
  }
  search?: string
}

// Аналитика скролла
export interface ScrollAnalytics {
  totalScrollDistance: number
  averageScrollSpeed: number
  timeSpentScrolling: number
  reachedEndCount: number
  loadMoreCount: number
  errorCount: number
  retryCount: number
}