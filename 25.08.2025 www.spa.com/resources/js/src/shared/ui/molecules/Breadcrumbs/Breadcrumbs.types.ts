/**
 * TypeScript типы для Breadcrumbs компонента
 */

import type { Component } from 'vue'

export type BreadcrumbSize = 'small' | 'medium' | 'large'
export type BreadcrumbSeparator = 'chevron' | 'slash' | 'arrow'

export interface BreadcrumbItem {
  /** Текст элемента */
  title: string
  
  /** URL ссылки (для обычных ссылок) */
  href?: string
  
  /** Route объект (для router-link) */
  to?: string | object
  
  /** Уникальный ключ элемента */
  key?: string
  
  /** Иконка элемента */
  icon?: Component | string
  
  /** Внешняя ссылка (открывается в новой вкладке) */
  external?: boolean
  
  /** Элемент является домашней страницей */
  isHome?: boolean
  
  /** Элемент является сокращением (...) */
  isEllipsis?: boolean
  
  /** Дополнительные данные */
  meta?: Record<string, any>
}

export interface BreadcrumbsProps {
  /** Массив элементов навигации */
  items: BreadcrumbItem[]
  
  /** Размер компонента */
  size?: BreadcrumbSize
  
  /** Тип разделителя */
  separator?: BreadcrumbSeparator
  
  /** Кастомная иконка разделителя */
  separatorIcon?: Component | string
  
  /** Показывать иконки элементов */
  showIcons?: boolean
  
  /** Автоматически добавлять домашнюю страницу */
  showHome?: boolean
  
  /** Максимальное количество элементов (остальные сворачиваются в ...) */
  maxItems?: number
  
  /** Включить JSON-LD разметку для SEO */
  enableJsonLd?: boolean
  
  /** ARIA-метка для навигации */
  ariaLabel?: string
  
  /** Дополнительные CSS классы */
  customClass?: string
}

export interface BreadcrumbItemClickEvent {
  /** Элемент по которому кликнули */
  item: BreadcrumbItem
  
  /** Индекс элемента */
  index: number
}

export interface BreadcrumbsEmits {
  /** Клик по элементу навигации */
  'item-click': [event: BreadcrumbItemClickEvent]
}

export interface BreadcrumbsSlots {
  /** Слот для кастомного разделителя */
  separator?: (props: { index: number, item: BreadcrumbItem }) => any
  
  /** Слот для кастомного элемента */
  item?: (props: { item: BreadcrumbItem, index: number, isLast: boolean }) => any
  
  /** Слот для иконки элемента */
  icon?: (props: { item: BreadcrumbItem, index: number }) => any
}

// Пресеты для быстрой настройки
export interface BreadcrumbPresets {
  /** Стандартная навигация */
  default: Partial<BreadcrumbsProps>
  
  /** Компактная навигация */
  compact: Partial<BreadcrumbsProps>
  
  /** Навигация с иконками */
  withIcons: Partial<BreadcrumbsProps>
  
  /** SEO-оптимизированная навигация */
  seo: Partial<BreadcrumbsProps>
}

// Опции для useBreadcrumbs композабла
export interface BreadcrumbsOptions {
  /** Автоматически добавлять домашнюю страницу */
  autoHome?: boolean
  
  /** Максимальная длина заголовка */
  maxTitleLength?: number
  
  /** Функция форматирования заголовка */
  formatTitle?: (title: string) => string
  
  /** Функция генерации href из route */
  generateHref?: (route: any) => string
}

// Конфигурация для автоматической генерации breadcrumbs из роутов
export interface RouteBreadcrumbConfig {
  /** Исключить определенные роуты */
  exclude?: string[]
  
  /** Кастомные названия для роутов */
  titles?: Record<string, string>
  
  /** Кастомные иконки для роутов */
  icons?: Record<string, Component | string>
  
  /** Функция преобразования роута в breadcrumb */
  transform?: (route: any) => BreadcrumbItem | null
}