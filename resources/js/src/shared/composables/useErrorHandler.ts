import { ref } from 'vue'
import { useToast } from './useToast'

export interface ErrorDetails {
  message: string
  code?: string | number
  field?: string
  details?: Record<string, any>
}

export interface ValidationErrors {
  [field: string]: string[]
}

/**
 * Composable для централизованной обработки ошибок
 * 
 * Использование:
 * const { handleError, clearError, error } = useErrorHandler()
 * 
 * try {
 *   await api.call()
 * } catch (e) {
 *   handleError(e)
 * }
 */
export function useErrorHandler() {
  const error = ref<ErrorDetails | null>(null)
  const validationErrors = ref<ValidationErrors>({})
  const toast = useToast()
  
  const handleError = (err: unknown, showToast = true) => {
    console.error('[ErrorHandler]', err)
    
    // Обработка различных типов ошибок
    if (err instanceof Error) {
      error.value = {
        message: err.message,
        details: { stack: err.stack }
      }
      
      if (showToast) {
        toast.error(err.message)
      }
    } 
    // Обработка ошибок от Laravel API
    else if (typeof err === 'object' && err !== null) {
      const apiError = err as any
      
      // Validation errors от Laravel
      if (apiError.errors) {
        validationErrors.value = apiError.errors
        error.value = {
          message: apiError.message || 'Ошибка валидации',
          code: apiError.status || 422,
          details: apiError.errors
        }
        
        if (showToast) {
          const firstError = Object.values(apiError.errors).flat()[0]
          toast.error(firstError as string || 'Проверьте правильность заполнения формы')
        }
      }
      // Обычная ошибка API
      else {
        error.value = {
          message: apiError.message || 'Произошла ошибка',
          code: apiError.status || apiError.code,
          details: apiError
        }
        
        if (showToast) {
          toast.error(apiError.message || 'Произошла ошибка')
        }
      }
    }
    // Строковая ошибка
    else if (typeof err === 'string') {
      error.value = { message: err }
      
      if (showToast) {
        toast.error(err)
      }
    }
    // Неизвестная ошибка
    else {
      error.value = { message: 'Произошла неизвестная ошибка' }
      
      if (showToast) {
        toast.error('Произошла неизвестная ошибка')
      }
    }
    
    // Логирование в продакшене (можно отправить в Sentry)
    if (import.meta.env.PROD) {
      // TODO: Отправка в Sentry или другой сервис логирования
      console.error('Production error:', error.value)
    }
  }
  
  const clearError = () => {
    error.value = null
    validationErrors.value = {}
  }
  
  const hasValidationError = (field: string): boolean => {
    return field in validationErrors.value
  }
  
  const getValidationError = (field: string): string | null => {
    return validationErrors.value[field]?.[0] || null
  }
  
  return {
    error,
    validationErrors,
    handleError,
    clearError,
    hasValidationError,
    getValidationError
  }
}