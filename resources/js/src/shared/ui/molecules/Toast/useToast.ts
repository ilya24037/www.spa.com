/**
 * Композабл для работы с Toast компонентом
 * 
 * Использование:
 * const { showToast, hideToast } = useToast()
 * showToast('success', 'Сохранено!', { duration: 3000 })
 */

import { ref, readonly } from 'vue'
import type { ToastType, ToastOptions } from './Toast.types'

interface ToastItem {
  id: string
  type: ToastType
  title?: string
  message: string
  duration: number
  position: ToastOptions['position']
  closable: boolean
  visible: boolean
}

// Глобальное состояние для всех Toast уведомлений
const toasts = ref<ToastItem[]>([])
let toastCounter = 0

export function useToast() {
  /**
   * Показать Toast уведомление
   */
  const showToast = (
    type: ToastType,
    message: string, 
    options: ToastOptions = {}
  ): string => {
    const id = `toast-${++toastCounter}`
    
    const toast: ToastItem = {
      id,
      type,
      title: options.title,
      message,
      duration: options.duration ?? 4000,
      position: options.position ?? 'top-right',
      closable: options.closable ?? true,
      visible: true
    }
    
    toasts.value.push(toast)
    
    // Авто-удаление
    if (toast.duration > 0) {
      setTimeout(() => {
        hideToast(id)
      }, toast.duration)
    }
    
    return id
  }

  /**
   * Скрыть конкретное уведомление
   */
  const hideToast = (id: string): void => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value[index].visible = false
      
      // Удаляем после анимации
      setTimeout(() => {
        toasts.value.splice(index, 1)
      }, 300)
    }
  }

  /**
   * Скрыть все уведомления
   */
  const hideAllToasts = (): void => {
    toasts.value.forEach(toast => {
      toast.visible = false
    })
    
    setTimeout(() => {
      toasts.value.splice(0)
    }, 300)
  }

  /**
   * Специализированные методы для быстрого использования
   */
  const success = (message: string, options?: Omit<ToastOptions, 'type'>) => 
    showToast('success', message, options)
    
  const error = (message: string, options?: Omit<ToastOptions, 'type'>) => 
    showToast('error', message, options)
    
  const warning = (message: string, options?: Omit<ToastOptions, 'type'>) => 
    showToast('warning', message, options)
    
  const info = (message: string, options?: Omit<ToastOptions, 'type'>) => 
    showToast('info', message, options)

  /**
   * Заменители для alert(), confirm() и других JS методов
   */
  const alertSuccess = (message: string) => success(message, { duration: 3000 })
  const alertError = (message: string) => error(message, { duration: 5000 })
  const alertWarning = (message: string) => warning(message, { duration: 4000 })
  const alertInfo = (message: string) => info(message, { duration: 3000 })

  return {
    // Состояние
    toasts: readonly(toasts),
    
    // Основные методы
    showToast,
    hideToast,
    hideAllToasts,
    
    // Быстрые методы
    success,
    error,
    warning,
    info,
    
    // Заменители alert()
    alertSuccess,
    alertError,
    alertWarning,
    alertInfo
  }
}