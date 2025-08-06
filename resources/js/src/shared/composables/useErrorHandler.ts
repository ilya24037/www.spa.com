import { logger } from '@/src/shared/lib/logger'

import { ref } from 'vue'
import { useToast } from './useToast'
import type {
  ErrorInfo,
  ErrorType
} from '../ui/molecules/ErrorState/ErrorState.types'

export interface ValidationErrors {
  [field: string]: string[]
}

/**
 * Composable для централизованной обработки ошибок
 * Полностью совместим с ErrorState компонентом
 */
export function useErrorHandler(showToast = true) {
  const error = ref<ErrorInfo | null>(null)
  const validationErrors = ref<ValidationErrors>({})
  const isRetrying = ref<boolean>(false)
  const toast = showToast ? useToast() : null
  
  /**
   * Определяет тип ошибки на основе кода или сообщения
   */
  const detectErrorType = (err: unknown): ErrorType => {
    if (!err) return 'generic'

    if (typeof err === 'object' && err !== null) {
      const errorObj = err as any
      
      // HTTP статус коды
      if (errorObj.status || errorObj.code) {
        const code = errorObj.status || errorObj.code
        
        if (code === 401) return 'auth'
        if (code === 403) return 'permission'
        if (code === 404) return 'not_found'
        if (code === 422) return 'validation'
        if (code === 408 || code === 'ETIMEDOUT') return 'timeout'
        if (code >= 500) return 'server'
      }

      // Проверяем по сообщению
      const message = errorObj.message?.toLowerCase() || ''
      
      if (message.includes('network') || message.includes('fetch')) return 'network'
      if (message.includes('timeout')) return 'timeout'
      if (message.includes('validation') || message.includes('invalid')) return 'validation'
      if (message.includes('permission') || message.includes('forbidden')) return 'permission'
      if (message.includes('unauthorized') || message.includes('auth')) return 'auth'
      if (message.includes('not found')) return 'not_found'
      if (message.includes('payment')) return 'payment'
      if (message.includes('booking')) return 'booking'
      if (message.includes('upload') || message.includes('file')) return 'upload'
      if (message.includes('server')) return 'server'
    }

    return 'generic'
  }

  /**
   * Извлекает сообщение об ошибке
   */
  const extractErrorMessage = (err: unknown): string => {
    if (!err) return 'Произошла неизвестная ошибка'
    if (typeof err === 'string') return err

    if (typeof err === 'object' && err !== null) {
      const errorObj = err as any
      
      if (errorObj.message) return errorObj.message
      if (errorObj.error?.message) return errorObj.error.message
      if (errorObj.data?.message) return errorObj.data.message
      if (errorObj.response?.data?.message) return errorObj.response.data.message
      if (errorObj.statusText) return errorObj.statusText
    }

    return 'Произошла ошибка при выполнении операции'
  }

  /**
   * Извлекает детали ошибки
   */
  const extractErrorDetails = (err: unknown): string | undefined => {
    if (!err || typeof err !== 'object') return undefined

    const errorObj = err as any
    const details: string[] = []
    
    if (errorObj.details) {
      details.push(errorObj.details)
    }
    
    if (errorObj.response?.data?.errors) {
      const errors = errorObj.response.data.errors
      if (typeof errors === 'object') {
        // Сохраняем в validationErrors для доступа через getValidationError
        validationErrors.value = errors
        
        Object.entries(errors).forEach(([field, messages]) => {
          if (Array.isArray(messages)) {
            details.push(`${field}: ${messages.join(', ')}`)
          }
        })
      }
    } else if (errorObj.errors) {
      // Laravel validation errors
      validationErrors.value = errorObj.errors
      Object.entries(errorObj.errors).forEach(([field, messages]) => {
        if (Array.isArray(messages)) {
          details.push(`${field}: ${messages.join(', ')}`)
        }
      })
    }
    
    return details.length > 0 ? details.join('\n') : undefined
  }

  /**
   * Извлекает код ошибки
   */
  const extractErrorCode = (err: unknown): string | number | undefined => {
    if (!err || typeof err !== 'object') return undefined

    const errorObj = err as any
    
    return errorObj.code || 
           errorObj.status || 
           errorObj.response?.status || 
           errorObj.statusCode || 
           undefined
  }

  /**
   * Основной метод обработки ошибок
   */
  const handleError = (err: unknown, type?: ErrorType): void => {
    logger.error('[ErrorHandler]', err)
    
    const errorInfo: ErrorInfo = {
      type: type || detectErrorType(err),
      message: extractErrorMessage(err),
      details: extractErrorDetails(err),
      code: extractErrorCode(err),
      originalError: err,
      timestamp: new Date(),
      requestId: `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
    }

    // Сохраняем ошибку
    error.value = errorInfo

    // Показываем toast если нужно
    if (toast && showToast) {
      toast.error(errorInfo.message)
    }

    // Логирование в продакшене
    if (import.meta.env.PROD) {
      logger.error('Production error:', errorInfo)
      // TODO: Отправка в Sentry или другой сервис
    }
  }
  
  /**
   * Очищает ошибку
   */
  const clearError = (): void => {
    error.value = null
    validationErrors.value = {}
    isRetrying.value = false
  }
  
  /**
   * Проверяет наличие ошибки валидации для поля
   */
  const hasValidationError = (field: string): boolean => {
    return field in validationErrors.value
  }
  
  /**
   * Получает текст ошибки валидации для поля
   */
  const getValidationError = (field: string): string | null => {
    return validationErrors.value[field]?.[0] || null
  }

  /**
   * Повторяет операцию с обработкой ошибок
   */
  const retryOperation = async (operation: () => Promise<void>): Promise<void> => {
    try {
      isRetrying.value = true
      clearError()
      await operation()
    } catch (retryError) {
      handleError(retryError)
    } finally {
      isRetrying.value = false
    }
  }
  
  return {
    // Состояние
    error,
    validationErrors,
    isRetrying,
    
    // Методы
    handleError,
    clearError,
    hasValidationError,
    getValidationError,
    retryOperation
  }
}