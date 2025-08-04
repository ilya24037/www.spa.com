/**
 * TypeScript типы для Card компонента
 */

export type CardVariant = 'default' | 'bordered' | 'elevated' | 'outlined'
export type CardSize = 'small' | 'medium' | 'large'

export interface CardProps {
  /** Заголовок карточки */
  title?: string
  
  /** Вариант стиля карточки */
  variant?: CardVariant
  
  /** Размер карточки */
  size?: CardSize
  
  /** Эффект при наведении */
  hoverable?: boolean
  
  /** Состояние загрузки */
  loading?: boolean
  
  /** Заблокированное состояние */
  disabled?: boolean
  
  /** Дополнительные CSS классы */
  customClass?: string
}

export interface CardSlots {
  /** Слот для заголовка */
  header?: () => any
  
  /** Основной контент */
  default: () => any
  
  /** Слот для подвала */
  footer?: () => any
}

export interface CardEmits {
  /** Клик по карточке */
  click: [event: MouseEvent]
}

export interface CardOptions {
  /** Вариант стиля */
  variant?: CardVariant
  
  /** Размер */
  size?: CardSize
  
  /** Интерактивность */
  hoverable?: boolean
}