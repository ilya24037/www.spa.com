// PageLoader.types.ts
export interface PageLoaderProps {
  type?: PageLoaderType
  message?: string
  showSkeletons?: boolean
  skeletonCount?: number
  showProgress?: boolean
  progress?: number
  duration?: number
  fullScreen?: boolean
  overlay?: boolean
  className?: string
}

export interface PageLoaderEmits {
  // Пока нет событий
}

// Типы загрузчиков страниц
export type PageLoaderType =
  | 'catalog'     // Каталог мастеров/объявлений
  | 'profile'     // Профиль пользователя/мастера
  | 'dashboard'   // Личный кабинет
  | 'form'        // Формы создания/редактирования
  | 'content'     // Страницы с контентом
  | 'minimal'     // Минимальный загрузчик
  | 'default'     // По умолчанию

// Конфигурация для разных типов страниц
export interface PageLoaderConfig {
  showSkeletons: boolean
  skeletonCount: number
  showSpinner: boolean
  showProgress: boolean
  message: string
  fullScreen: boolean
  overlay: boolean
  animation: PageLoaderAnimation
}

export type PageLoaderAnimation = 
  | 'fade'
  | 'slide'
  | 'scale'
  | 'pulse'
  | 'none'

// Скелетоны для разных типов контента
export interface SkeletonConfig {
  type: SkeletonType
  count: number
  className?: string
  showAvatar?: boolean
  showTitle?: boolean
  showSubtitle?: boolean
  showContent?: boolean
  showActions?: boolean
}

export type SkeletonType =
  | 'card'        // Карточки (мастера, объявления)
  | 'list'        // Список элементов
  | 'profile'     // Профиль пользователя
  | 'form'        // Форма
  | 'content'     // Текстовый контент
  | 'stats'       // Статистика/счетчики
  | 'navigation'  // Навигация/меню

// Конфигурации для разных типов страниц
export interface PageTypeConfigs {
  catalog: PageLoaderConfig
  profile: PageLoaderConfig
  dashboard: PageLoaderConfig
  form: PageLoaderConfig
  content: PageLoaderConfig
  minimal: PageLoaderConfig
  default: PageLoaderConfig
}

// Состояние загрузки страницы
export interface PageLoadingState {
  isLoading: boolean
  progress: number
  message: string
  error?: PageLoadingError
  startTime: number
  estimatedDuration?: number
}

// Ошибки загрузки
export interface PageLoadingError {
  type: 'network' | 'timeout' | 'server' | 'client'
  message: string
  code?: number
  originalError?: unknown
}

// Хуки для загрузки страниц
export interface UsePageLoadingOptions {
  type?: PageLoaderType
  autoStart?: boolean
  timeout?: number
  retryCount?: number
  showProgress?: boolean
  onStart?: () => void
  onProgress?: (progress: number) => void
  onComplete?: () => void
  onError?: (error: PageLoadingError) => void
}

export interface UsePageLoadingReturn {
  isLoading: import("vue").Ref<boolean>
  progress: import("vue").Ref<number>
  message: import("vue").Ref<string>
  error: import("vue").Ref<PageLoadingError | null>
  startLoading: (message?: string) => void
  setProgress: (progress: number, message?: string) => void
  completeLoading: () => void
  errorLoading: (error: PageLoadingError) => void
  retryLoading: () => void
}

// Аналитика загрузки страниц
export interface PageLoadingAnalytics {
  pageType: PageLoaderType
  startTime: number
  endTime?: number
  duration?: number
  success: boolean
  error?: PageLoadingError
  userAgent?: string
  referrer?: string
}

// Кеширование состояний загрузки
export interface PageLoadingCache {
  [pageUrl: string]: {
    lastLoadTime: number
    duration: number
    success: boolean
    cachedUntil: number
  }
}

// Мета-информация компонента
export interface PageLoaderMetadata {
  componentName: string
  version: string
  supportedTypes: PageLoaderType[]
  dependencies: string[]
}

// Типы для тестирования
export interface PageLoaderTestProps {
  type: PageLoaderType
  mockDuration?: number
  mockError?: PageLoadingError
  testMode?: boolean
}

// Accessibility
export interface PageLoaderA11y {
  ariaLabel: string
  ariaDescription: string
  role: string
  announceStart: boolean
  announceProgress: boolean
  announceComplete: boolean
}