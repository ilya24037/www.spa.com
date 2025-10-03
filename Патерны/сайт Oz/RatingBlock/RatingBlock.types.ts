/**
 * Типы для RatingBlock из структуры Ozon
 * Система рейтингов и отзывов
 */

// Основной интерфейс рейтинга
export interface RatingData {
  rating: number // 0-5 с шагом 0.1
  reviewsCount: number
  detailedRating?: DetailedRating
  averageScore?: number
  recommendation?: number // процент рекомендующих
}

// Детализированный рейтинг по категориям
export interface DetailedRating {
  overall: RatingCategory
  quality: RatingCategory
  delivery: RatingCategory
  service: RatingCategory
  price: RatingCategory
  authenticity: RatingCategory
}

// Категория рейтинга
export interface RatingCategory {
  score: number // 0-5
  count: number // количество оценок
  percentage?: number // процент от общего
}

// Распределение оценок по звездам
export interface RatingDistribution {
  5: DistributionItem
  4: DistributionItem
  3: DistributionItem
  2: DistributionItem
  1: DistributionItem
}

export interface DistributionItem {
  count: number
  percentage: number
}

// Конфигурация отображения рейтинга
export interface RatingDisplayConfig {
  showStars?: boolean
  showNumeric?: boolean
  showReviews?: boolean
  showDistribution?: boolean
  showRecommendation?: boolean
  starSize?: StarSize
  colorScheme?: ColorScheme
  layout?: RatingLayout
  interactive?: boolean
  animated?: boolean
}

// Размеры звезд
export type StarSize = 'xs' | 'sm' | 'md' | 'lg' | 'xl'

// Цветовые схемы
export type ColorScheme = 'default' | 'gold' | 'gradient' | 'monochrome'

// Макеты рейтинга
export type RatingLayout = 
  | 'inline' // звезды и текст в линию
  | 'stacked' // звезды сверху, текст снизу
  | 'detailed' // с распределением
  | 'compact' // минималистичный
  | 'card' // карточка с полной информацией

// Интерфейс звезды
export interface StarConfig {
  filled: number // 0-1 (процент заполнения)
  size: number // размер в пикселях
  color: string
  borderColor?: string
  animationDelay?: number
}

// Типы иконок для рейтинга
export interface RatingIcons {
  star: string
  halfStar: string
  emptyStar: string
  review: string
  recommendation: string
}

// Состояния компонента рейтинга
export interface RatingState {
  isHovered: boolean
  hoveredValue: number | null
  isAnimating: boolean
  selectedValue: number | null
}

// События рейтинга
export interface RatingEvents {
  onRate?: (value: number) => void
  onHover?: (value: number | null) => void
  onClick?: () => void
  onReviewClick?: () => void
}

// Анимации рейтинга
export interface RatingAnimation {
  type: 'fade' | 'scale' | 'rotate' | 'pulse' | 'shine'
  duration: number
  delay?: number
  easing?: string
}

// Цвета рейтинга в стиле Ozon
export const RATING_COLORS = {
  gold: '#ffa500',
  goldLight: '#ffcc00',
  goldDark: '#ff8c00',
  gray: '#e1e3e6',
  text: '#70757a',
  textDark: '#001a34',
  green: '#00a854',
  red: '#f91155',
  gradient: {
    start: '#ffd700',
    end: '#ff8c00'
  }
} as const

// Размеры звезд в пикселях
export const STAR_SIZES = {
  xs: 12,
  sm: 16,
  md: 20,
  lg: 24,
  xl: 32
} as const

// Пороги для цветового кодирования
export const RATING_THRESHOLDS = {
  excellent: 4.5, // Отлично
  good: 4.0,      // Хорошо
  average: 3.0,   // Средне
  poor: 2.0       // Плохо
} as const

// Текстовые метки для рейтинга
export const RATING_LABELS = {
  5: 'Отлично',
  4: 'Хорошо',
  3: 'Нормально',
  2: 'Плохо',
  1: 'Ужасно'
} as const

// Форматтеры текста
export interface RatingFormatters {
  rating?: (value: number) => string
  reviews?: (count: number) => string
  recommendation?: (percent: number) => string
}

// Дефолтные форматтеры
export const DEFAULT_FORMATTERS: RatingFormatters = {
  rating: (value: number) => value.toFixed(1),
  reviews: (count: number) => {
    if (count === 0) return 'Нет отзывов'
    if (count === 1) return '1 отзыв'
    if (count < 5) return `${count} отзыва`
    return `${count} отзывов`
  },
  recommendation: (percent: number) => `${percent}% рекомендуют`
}

// Интерфейс для интерактивного рейтинга
export interface InteractiveRatingConfig {
  allowHalfStars?: boolean
  clearable?: boolean
  readonly?: boolean
  showTooltip?: boolean
  tooltipText?: (value: number) => string
  confirmBeforeRate?: boolean
  confirmText?: string
}

// Данные для аналитики
export interface RatingAnalytics {
  averageRating: number
  totalReviews: number
  distribution: RatingDistribution
  trends?: RatingTrend[]
  comparisonWithCategory?: number // сравнение со средним по категории
}

// Тренд рейтинга
export interface RatingTrend {
  period: string // "Последний месяц", "Последняя неделя"
  change: number // изменение в процентах
  direction: 'up' | 'down' | 'stable'
}

// Конфигурация для мини-рейтинга (в карточке товара)
export interface MiniRatingConfig {
  showOnlyStars?: boolean
  hideIfNoReviews?: boolean
  minReviewsToShow?: number
  compactMode?: boolean
}

// Интерфейс отзыва (для расширенного компонента)
export interface Review {
  id: string
  rating: number
  author: string
  date: Date
  text: string
  helpful: number
  notHelpful: number
  verified: boolean
  photos?: string[]
  advantages?: string[]
  disadvantages?: string[]
}

// Фильтры отзывов
export interface ReviewFilters {
  rating?: number[]
  withPhotos?: boolean
  verified?: boolean
  sortBy?: 'date' | 'rating' | 'helpful'
  period?: 'all' | 'month' | 'week'
}