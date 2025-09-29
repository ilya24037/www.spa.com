/**
 * TypeScript типы для ErrorBoundary компонента
 */

import type { Component } from 'vue'

export interface ErrorInfo {
  /** Объект ошибки */
  error: Error
  
  /** Сообщение об ошибке */
  errorMessage: string
  
  /** Stack trace ошибки */
  stack: string
  
  /** Информация о компоненте где произошла ошибка */
  componentInfo: string
  
  /** Время возникновения ошибки */
  timestamp: string
  
  /** User Agent браузера */
  userAgent: string
}

export interface ErrorBoundaryProps {
  /** Заголовок ошибки */
  errorTitle?: string
  
  /** Сообщение об ошибке */
  errorMessage?: string
  
  /** Показывать кнопку перезагрузки */
  showReload?: boolean
  
  /** Показывать детали ошибки */
  showDetails?: boolean
  
  /** Логировать ошибки в консоль */
  logErrors?: boolean
  
  /** Fallback компонент для отображения ошибки */
  fallback?: Component
}

export interface ErrorBoundaryEmits {
  /** Событие возникновения ошибки */
  error: [errorInfo: ErrorInfo]
  
  /** Событие перезагрузки */
  reload: []
  
  /** Успешное копирование деталей ошибки */
  'copy-success': []
  
  /** Ошибка при копировании деталей */
  'copy-error': []
}

export interface ErrorBoundarySlots {
  /** Основной контент */
  default: () => any
}

// Пропсы для fallback компонента
export interface FallbackComponentProps {
  /** Информация об ошибке */
  error: ErrorInfo | null
}

export interface FallbackComponentEmits {
  /** Событие перезагрузки из fallback компонента */
  reload: []
}

// Опции для useErrorBoundary композабла
export interface ErrorBoundaryOptions {
  /** Автоматическое логирование */
  autoLog?: boolean
  
  /** Максимальное количество попыток перезагрузки */
  maxRetries?: number
  
  /** Функция отправки ошибок на сервер */
  onError?: (errorInfo: ErrorInfo) => void | Promise<void>
  
  /** Кастомное форматирование ошибки */
  formatError?: (error: Error) => string
}