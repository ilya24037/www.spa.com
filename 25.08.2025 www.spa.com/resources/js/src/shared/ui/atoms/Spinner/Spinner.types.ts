/**
 * TypeScript типы для Spinner компонента
 */

import type { Component } from 'vue'

export type SpinnerVariant = 'ring' | 'dots' | 'pulse' | 'bars' | 'circle' | 'custom'
export type SpinnerSize = 'small' | 'medium' | 'large' | 'extra-large'
export type SpinnerColor = 'primary' | 'secondary' | 'success' | 'warning' | 'error'

export interface SpinnerProps {
  /** Тип спиннера */
  variant?: SpinnerVariant
  
  /** Размер спиннера */
  size?: SpinnerSize
  
  /** Цвет спиннера */
  color?: SpinnerColor
  
  /** Кастомный размер (px или строка с единицами) */
  customSize?: number | string
  
  /** Кастомный цвет */
  customColor?: string
  
  /** Кастомная иконка для варианта 'custom' */
  customIcon?: Component | string
  
  /** Центрировать спиннер */
  centered?: boolean
  
  /** Показать как overlay на всю страницу */
  overlay?: boolean
  
  /** Цвет overlay */
  overlayColor?: string
  
  /** Текст загрузки */
  text?: string
  
  /** ARIA-метка для доступности */
  ariaLabel?: string
  
  /** Дополнительные CSS классы */
  customClass?: string
}

export interface SpinnerSlots {
  /** Слот для кастомного текста загрузки */
  default?: () => any
}

// Конфигурация для useSpinner композабла
export interface SpinnerOptions {
  /** Базовый вариант спиннера */
  variant?: SpinnerVariant
  
  /** Базовый размер */
  size?: SpinnerSize
  
  /** Базовый цвет */
  color?: SpinnerColor
  
  /** Показывать overlay по умолчанию */
  overlay?: boolean
  
  /** Минимальное время показа (мс) */
  minDuration?: number
  
  /** Автоматически скрывать через N мс */
  autoHide?: number
}

// Состояние спиннера
export interface SpinnerState {
  /** Видимость спиннера */
  visible: boolean
  
  /** Текущий текст */
  text: string
  
  /** Текущий вариант */
  variant: SpinnerVariant
  
  /** Overlay активен */
  overlay: boolean
}

// Предустановленные конфигурации
export interface SpinnerPresets {
  /** Маленький инлайн спиннер */
  inline: Partial<SpinnerProps>
  
  /** Спиннер для кнопок */
  button: Partial<SpinnerProps>
  
  /** Спиннер для загрузки страницы */
  page: Partial<SpinnerProps>
  
  /** Спиннер для модальных окон */
  modal: Partial<SpinnerProps>
  
  /** Спиннер для карточек */
  card: Partial<SpinnerProps>
  
  /** Полноэкранный спиннер */
  fullscreen: Partial<SpinnerProps>
}