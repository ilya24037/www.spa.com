/**
 * Композабл для работы с Toast уведомлениями
 * Объединенная версия старого и нового API с полной TypeScript поддержкой
 * 
 * Использование:
 * const toast = useToast()
 * toast.success('Сохранено!')          // новый API
 * toast.showSuccess('Операция выполнена') // старый API (совместимость)
 */

import { ref, readonly } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info' | 'loading'

export interface ToastItem {
  id: number
  title?: string
  message: string
  type: ToastType
  duration: number
  closable: boolean
  action?: ToastAction | null
  timestamp: number
  visible?: boolean
}

export interface ToastAction {
  label: string
  handler: () => void | Promise<void>
  class?: string
}

export interface ToastOptions {
  title?: string
  duration?: number
  closable?: boolean
  action?: ToastAction
}

// Глобальное состояние для уведомлений
const toasts = ref<ToastItem[]>([])
let toastId = 0

export function useToast() {
  /**
   * Добавить уведомление (базовый метод)
   */
  const addToast = (options: Partial<ToastItem>): number => {
    const toast: ToastItem = {
      id: ++toastId,
      title: options.title || '',
      message: options.message || '',
      type: options.type || 'info',
      duration: options.duration ?? 5000,
      closable: options.closable !== false,
      action: options.action || null,
      timestamp: Date.now(),
      visible: true
    }
    
    toasts.value.push(toast)
    
    // Автоматическое удаление
    if (toast.duration > 0) {
      setTimeout(() => {
        removeToast(toast.id)
      }, toast.duration)
    }
    
    return toast.id
  }
  
  /**
   * Удалить уведомление
   */
  const removeToast = (id: number): void => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index > -1) {
      toasts.value.splice(index, 1)
    }
  }

  /**
   * Показать уведомление (новый API)
   */
  const showToast = (
    type: ToastType,
    message: string, 
    options: ToastOptions = {}
  ): number => {
    return addToast({
      type,
      message,
      ...options
    })
  }

  /**
   * Скрыть конкретное уведомление
   */
  const hideToast = (id: number): void => {
    removeToast(id)
  }

  /**
   * Скрыть все уведомления
   */
  const hideAllToasts = (): void => {
    toasts.value.splice(0)
  }
  
  /**
   * Показать успешное уведомление (старый API)
   */
  const showSuccess = (message: string, title = 'Успешно', options: Partial<ToastOptions> = {}): number => {
    return addToast({
      title,
      message,
      type: 'success',
      ...options
    })
  }
  
  /**
   * Показать уведомление об ошибке (старый API)
   */
  const showError = (message: string, title = 'Ошибка', options: Partial<ToastOptions> = {}): number => {
    return addToast({
      title,
      message,
      type: 'error',
      duration: 7000, // Ошибки показываем дольше
      ...options
    })
  }
  
  /**
   * Показать предупреждение (старый API)
   */
  const showWarning = (message: string, title = 'Внимание', options: Partial<ToastOptions> = {}): number => {
    return addToast({
      title,
      message,
      type: 'warning',
      ...options
    })
  }
  
  /**
   * Показать информационное уведомление (старый API)
   */
  const showInfo = (message: string, title = 'Информация', options: Partial<ToastOptions> = {}): number => {
    return addToast({
      title,
      message,
      type: 'info',
      ...options
    })
  }
  
  /**
   * Показать уведомление с действием (старый API)
   */
  const showAction = (message: string, action: ToastAction, title = 'Действие', options: Partial<ToastOptions> = {}): number => {
    return addToast({
      title,
      message,
      type: 'info',
      action: {
        label: action.label || 'Действие',
        handler: action.handler || (() => {}),
        class: action.class || 'text-purple-600 hover:text-purple-700'
      },
      ...options
    })
  }
  
  /**
   * Очистить все уведомления (старый API)
   */
  const clearToasts = (): void => {
    toasts.value = []
  }
  
  /**
   * Показать уведомление загрузки (старый API)
   */
  const showLoading = (message = 'Загрузка...', title = ''): number => {
    return addToast({
      title,
      message,
      type: 'loading',
      duration: 0, // Не исчезает автоматически
      closable: false
    })
  }
  
  /**
   * Обновить уведомление (старый API)
   */
  const updateToast = (id: number, options: Partial<ToastItem>): void => {
    const toast = toasts.value.find(t => t.id === id)
    if (toast) {
      Object.assign(toast, options)
      
      // Если изменилась продолжительность, перезапускаем таймер
      if (options.duration !== undefined && options.duration > 0) {
        setTimeout(() => {
          removeToast(id)
        }, options.duration)
      }
    }
  }

  /**
   * Быстрые методы (новый API) 
   */
  const success = (message: string, options?: Omit<ToastOptions, 'type'>): number => 
    showToast('success', message, options)
    
  const error = (message: string, options?: Omit<ToastOptions, 'type'>): number => 
    showToast('error', message, { duration: 7000, ...options })
    
  const warning = (message: string, options?: Omit<ToastOptions, 'type'>): number => 
    showToast('warning', message, options)
    
  const info = (message: string, options?: Omit<ToastOptions, 'type'>): number => 
    showToast('info', message, options)

  /**
   * Заменители для alert(), confirm() и других JS методов
   */
  const alertSuccess = (message: string): number => success(message, { duration: 3000 })
  const alertError = (message: string): number => error(message, { duration: 5000 })
  const alertWarning = (message: string): number => warning(message, { duration: 4000 })
  const alertInfo = (message: string): number => info(message, { duration: 3000 })

  // Совместимость со старыми методами (другие названия)
  const show = (message: string, type: ToastType = 'info', options?: ToastOptions): number => 
    showToast(type, message, options)
    
  const remove = (id: number): void => removeToast(id)
  const clear = (): void => clearToasts()
  
  return {
    // Состояние
    toasts: readonly(toasts),
    
    // Базовые методы
    addToast,
    removeToast,
    
    // Новый API
    showToast,
    hideToast,
    hideAllToasts,
    success,
    error,
    warning,
    info,
    alertSuccess,
    alertError,
    alertWarning,
    alertInfo,
    
    // Старый API (для совместимости)
    showSuccess,
    showError, 
    showWarning,
    showInfo,
    showAction,
    showLoading,
    updateToast,
    clearToasts,
    
    // Другие названия (совместимость)
    show,
    remove,
    clear
  }
}

/**
 * Композабл для работы с промисами и Toast (старый API)
 */
export function useToastPromise() {
  const { showLoading, updateToast } = useToast()
  
  const promise = async <T>(
    promiseFn: () => Promise<T>,
    {
      loading = 'Загрузка...',
      success = 'Успешно!',
      error = 'Произошла ошибка'
    }: {
      loading?: string
      success?: string | ((result: T) => string)
      error?: string | ((err: unknown) => string)
    } = {}
  ): Promise<T> => {
    const toastId = showLoading(loading)
    
    try {
      const result = await promiseFn()
      updateToast(toastId, {
        type: 'success',
        message: typeof success === 'function' ? success(result) : success,
        duration: 3000
      })
      return result
    } catch (err: unknown) {
      updateToast(toastId, {
        type: 'error',
        message: typeof error === 'function' ? error(err) : error,
        duration: 5000
      })
      throw err
    }
  }
  
  return { promise }
}

// Экспорт глобального списка для компонента ToastContainer (совместимость)
export function useToastList() {
  return toasts
}