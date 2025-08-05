// ErrorState.types.ts - Типы для компонента отображения ошибок

/**
 * Типы ошибок
 */
export type ErrorType = 
  | 'network'        // Проблемы с сетью
  | 'server'         // Серверная ошибка (500+)
  | 'not_found'      // Ресурс не найден (404)
  | 'validation'     // Ошибка валидации (422)
  | 'permission'     // Нет доступа (403)
  | 'auth'           // Не авторизован (401)
  | 'timeout'        // Превышено время ожидания
  | 'data_load'      // Ошибка загрузки данных
  | 'payment'        // Ошибка оплаты
  | 'booking'        // Ошибка бронирования
  | 'upload'         // Ошибка загрузки файла
  | 'generic'        // Общая ошибка

/**
 * Размеры компонента ошибки
 */
export type ErrorSize = 'small' | 'medium' | 'large' | 'full'

/**
 * Варианты отображения
 */
export type ErrorVariant = 'inline' | 'card' | 'modal' | 'page'

/**
 * Информация об ошибке
 */
export interface ErrorInfo {
  type: ErrorType
  message: string
  details?: string
  code?: number | string
  originalError?: unknown
  timestamp?: Date
  requestId?: string
  helpUrl?: string
}

/**
 * Действие для исправления ошибки
 */
export interface ErrorAction {
  label: string
  action: () => void | Promise<void>
  variant?: 'primary' | 'secondary' | 'danger'
  icon?: string
  loading?: boolean
}

/**
 * Props компонента ErrorState
 */
export interface ErrorStateProps {
  error: ErrorInfo | null
  size?: ErrorSize
  variant?: ErrorVariant
  showDetails?: boolean
  showIcon?: boolean
  showActions?: boolean
  retryable?: boolean
  dismissible?: boolean
  customIcon?: string
  customTitle?: string
  customMessage?: string
  actions?: ErrorAction[]
  class?: string
}

/**
 * Emits компонента ErrorState
 */
export interface ErrorStateEmits {
  (e: 'retry'): void
  (e: 'dismiss'): void
  (e: 'action', action: ErrorAction): void
  (e: 'report', error: ErrorInfo): void
}

/**
 * Конфигурация для типа ошибки
 */
export interface ErrorTypeConfig {
  icon: string
  iconColor: string
  title: string
  defaultMessage: string
  defaultActions?: Omit<ErrorAction, 'action'>[]
  showDetails: boolean
  reportable: boolean
}

/**
 * Карта конфигураций для типов ошибок
 */
export type ErrorTypeConfigs = Record<ErrorType, ErrorTypeConfig>

/**
 * Состояние компонента
 */
export interface ErrorStateState {
  isRetrying: boolean
  isDismissed: boolean
  showFullDetails: boolean
  reportSent: boolean
}

/**
 * Контекст ошибки для аналитики
 */
export interface ErrorContext {
  component?: string
  action?: string
  userId?: number | string
  sessionId?: string
  url?: string
  userAgent?: string
  additionalData?: Record<string, any>
}

/**
 * Опции для useErrorHandler composable
 */
export interface UseErrorHandlerOptions {
  autoReport?: boolean
  showToast?: boolean
  logToConsole?: boolean
  context?: ErrorContext
  onError?: (error: ErrorInfo) => void
  onRetry?: () => Promise<void>
  onDismiss?: () => void
}

/**
 * Возвращаемое значение useErrorHandler
 */
export interface UseErrorHandlerReturn {
  error: import("vue").Ref<ErrorInfo | null>
  isRetrying: import("vue").Ref<boolean>
  isDismissed: import("vue").Ref<boolean>
  handleError: (error: unknown, type?: ErrorType) => void
  clearError: () => void
  retryOperation: () => Promise<void>
  dismissError: () => void
  reportError: (error: ErrorInfo) => Promise<void>
}

/**
 * Статистика ошибок
 */
export interface ErrorStatistics {
  totalErrors: number
  errorsByType: Record<ErrorType, number>
  errorsByCode: Record<string, number>
  recentErrors: ErrorInfo[]
  criticalErrors: ErrorInfo[]
}

// Типы для интеграции с Vue
import type { Ref } from 'vue'

// Экспорт дефолтной конфигурации
export const DEFAULT_ERROR_CONFIGS: ErrorTypeConfigs = {
  network: {
    icon: 'wifi-off',
    iconColor: 'text-orange-500',
    title: 'Проблема с подключением',
    defaultMessage: 'Проверьте интернет-соединение и попробуйте снова',
    defaultActions: [{ label: 'Повторить', variant: 'primary' }],
    showDetails: false,
    reportable: false
  },
  server: {
    icon: 'server-crash',
    iconColor: 'text-red-500',
    title: 'Ошибка сервера',
    defaultMessage: 'Произошла ошибка на сервере. Мы уже работаем над решением',
    defaultActions: [{ label: 'Обновить страницу', variant: 'primary' }],
    showDetails: true,
    reportable: true
  },
  not_found: {
    icon: 'search-off',
    iconColor: 'text-gray-500',
    title: 'Не найдено',
    defaultMessage: 'Запрашиваемый ресурс не найден',
    defaultActions: [{ label: 'На главную', variant: 'primary' }],
    showDetails: false,
    reportable: false
  },
  validation: {
    icon: 'alert-triangle',
    iconColor: 'text-yellow-500',
    title: 'Ошибка валидации',
    defaultMessage: 'Проверьте правильность введенных данных',
    defaultActions: [{ label: 'Исправить', variant: 'primary' }],
    showDetails: true,
    reportable: false
  },
  permission: {
    icon: 'lock',
    iconColor: 'text-red-500',
    title: 'Доступ запрещен',
    defaultMessage: 'У вас нет прав для выполнения этого действия',
    defaultActions: [{ label: 'Запросить доступ', variant: 'secondary' }],
    showDetails: false,
    reportable: false
  },
  auth: {
    icon: 'user-x',
    iconColor: 'text-blue-500',
    title: 'Требуется авторизация',
    defaultMessage: 'Войдите в систему для продолжения',
    defaultActions: [{ label: 'Войти', variant: 'primary' }],
    showDetails: false,
    reportable: false
  },
  timeout: {
    icon: 'clock-alert',
    iconColor: 'text-orange-500',
    title: 'Превышено время ожидания',
    defaultMessage: 'Операция заняла слишком много времени',
    defaultActions: [{ label: 'Повторить', variant: 'primary' }],
    showDetails: false,
    reportable: true
  },
  data_load: {
    icon: 'database-x',
    iconColor: 'text-purple-500',
    title: 'Ошибка загрузки данных',
    defaultMessage: 'Не удалось загрузить необходимые данные',
    defaultActions: [{ label: 'Перезагрузить', variant: 'primary' }],
    showDetails: true,
    reportable: true
  },
  payment: {
    icon: 'credit-card-off',
    iconColor: 'text-red-600',
    title: 'Ошибка оплаты',
    defaultMessage: 'Не удалось обработать платеж',
    defaultActions: [
      { label: 'Попробовать снова', variant: 'primary' },
      { label: 'Другой способ', variant: 'secondary' }
    ],
    showDetails: true,
    reportable: true
  },
  booking: {
    icon: 'calendar-x',
    iconColor: 'text-indigo-500',
    title: 'Ошибка бронирования',
    defaultMessage: 'Не удалось создать бронирование',
    defaultActions: [{ label: 'Попробовать снова', variant: 'primary' }],
    showDetails: true,
    reportable: true
  },
  upload: {
    icon: 'upload-cloud-off',
    iconColor: 'text-gray-600',
    title: 'Ошибка загрузки файла',
    defaultMessage: 'Не удалось загрузить файл',
    defaultActions: [{ label: 'Выбрать другой файл', variant: 'primary' }],
    showDetails: true,
    reportable: false
  },
  generic: {
    icon: 'alert-circle',
    iconColor: 'text-gray-500',
    title: 'Произошла ошибка',
    defaultMessage: 'Что-то пошло не так. Попробуйте позже',
    defaultActions: [{ label: 'Повторить', variant: 'primary' }],
    showDetails: true,
    reportable: true
  }
}