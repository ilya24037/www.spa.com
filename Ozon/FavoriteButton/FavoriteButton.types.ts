/**
 * Типы для FavoriteButton из структуры Ozon
 * Кнопка добавления в избранное с API интеграцией
 */

// Основной интерфейс кнопки избранного
export interface FavoriteButtonData {
  id: string
  isFav: boolean
  favLink: string // "favoriteBatchAddItems"
  unfavLink: string // "favoriteBatchDeleteItems"
  trackingInfo?: FavoriteTrackingInfo
  testInfo?: FavoriteTestInfo
}

// Трекинг для аналитики
export interface FavoriteTrackingInfo {
  click: {
    actionType: string // "favorite"
    key: string
    custom?: Record<string, any>
  }
}

// Информация для тестирования
export interface FavoriteTestInfo {
  favoriteButton?: {
    automatizationId: string // "favorite-simple-button"
  }
  unFavoriteButton?: {
    automatizationId: string // "unfavorite-simple-button"
  }
}

// Конфигурация компонента
export interface FavoriteButtonConfig {
  size?: ButtonSize
  variant?: ButtonVariant
  shape?: ButtonShape
  showLabel?: boolean
  labelPosition?: 'left' | 'right' | 'bottom'
  animated?: boolean
  showTooltip?: boolean
  tooltipPosition?: TooltipPosition
  showCount?: boolean
  confirmRemove?: boolean
  syncAcrossTabs?: boolean
}

// Размеры кнопки
export type ButtonSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'

// Варианты отображения
export type ButtonVariant = 
  | 'default'    // Стандартная кнопка
  | 'minimal'    // Минималистичная
  | 'filled'     // Заполненная
  | 'outlined'   // С контуром
  | 'ghost'      // Прозрачная
  | 'floating'   // Плавающая

// Форма кнопки
export type ButtonShape = 'circle' | 'rounded' | 'square'

// Позиция tooltip
export type TooltipPosition = 'top' | 'bottom' | 'left' | 'right'

// Состояние кнопки
export interface FavoriteButtonState {
  isFavorite: boolean
  isLoading: boolean
  isHovered: boolean
  isAnimating: boolean
  error: string | null
  count?: number
}

// События кнопки
export interface FavoriteButtonEvents {
  onToggle: (isFavorite: boolean) => void | Promise<void>
  onAdd?: () => void
  onRemove?: () => void
  onError?: (error: Error) => void
  onCountChange?: (count: number) => void
}

// API конфигурация
export interface FavoriteApiConfig {
  addUrl: string
  removeUrl: string
  countUrl?: string
  headers?: Record<string, string>
  method?: 'POST' | 'PUT' | 'DELETE'
  withCredentials?: boolean
}

// Ответ от API
export interface FavoriteApiResponse {
  success: boolean
  isFavorite: boolean
  count?: number
  message?: string
  error?: string
}

// Batch операции (как в Ozon)
export interface FavoriteBatchOperation {
  items: string[] // массив SKU
  action: 'add' | 'remove'
  category?: string
  metadata?: Record<string, any>
}

// Анимации кнопки
export interface FavoriteAnimation {
  type: AnimationType
  duration: number
  easing?: string
}

export type AnimationType = 
  | 'heart-beat'
  | 'pop'
  | 'shake'
  | 'bounce'
  | 'flip'
  | 'morph'
  | 'burst'

// Размеры в пикселях
export const BUTTON_SIZES = {
  xs: 20,
  sm: 24,
  md: 32,
  lg: 40,
  xl: 48
} as const

// Цвета состояний
export const FAVORITE_COLORS = {
  default: '#70757a',
  hover: '#001a34',
  active: '#f91155',
  loading: '#9ca0a5',
  error: '#ff4444',
  success: '#00a854'
} as const

// Тексты для UI
export const FAVORITE_LABELS = {
  add: 'Добавить в избранное',
  remove: 'Удалить из избранного',
  added: 'В избранном',
  loading: 'Загрузка...',
  error: 'Ошибка',
  confirmRemove: 'Удалить из избранного?'
} as const

// Конфигурация иконок
export interface FavoriteIcons {
  empty: string // SVG path или символ
  filled: string
  loading?: string
  error?: string
}

// Дефолтные иконки (SVG paths)
export const DEFAULT_ICONS: FavoriteIcons = {
  empty: 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z',
  filled: 'M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z'
}

// Настройки анимаций
export const ANIMATION_CONFIGS: Record<AnimationType, FavoriteAnimation> = {
  'heart-beat': {
    type: 'heart-beat',
    duration: 300,
    easing: 'ease-out'
  },
  'pop': {
    type: 'pop',
    duration: 400,
    easing: 'cubic-bezier(0.68, -0.55, 0.265, 1.55)'
  },
  'shake': {
    type: 'shake',
    duration: 500,
    easing: 'ease-in-out'
  },
  'bounce': {
    type: 'bounce',
    duration: 600,
    easing: 'cubic-bezier(0.175, 0.885, 0.32, 1.275)'
  },
  'flip': {
    type: 'flip',
    duration: 400,
    easing: 'ease-in-out'
  },
  'morph': {
    type: 'morph',
    duration: 300,
    easing: 'ease-in-out'
  },
  'burst': {
    type: 'burst',
    duration: 500,
    easing: 'ease-out'
  }
}

// Статистика избранного
export interface FavoriteStats {
  totalCount: number
  categories: Record<string, number>
  recentlyAdded: string[]
  mostFavorited: string[]
  lastUpdated: Date
}

// Настройки синхронизации
export interface FavoriteSyncConfig {
  enabled: boolean
  interval: number // ms
  storageKey: string
  broadcastChannel?: string
}

// LocalStorage структура
export interface FavoriteStorageData {
  items: Set<string>
  lastSync: number
  version: string
}