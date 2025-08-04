import { ref, reactive } from 'vue'

export type ToastType = 'success' | 'error' | 'info' | 'warning'

export interface Toast {
  id: string
  message: string
  type: ToastType
  duration?: number
  action?: {
    label: string
    handler: () => void
  }
}

interface ToastOptions {
  duration?: number
  action?: Toast['action']
}

// Глобальное хранилище для toast уведомлений
const toasts = ref<Toast[]>([])

/**
 * Composable для показа toast уведомлений
 * Заменяет alert() на красивые уведомления
 * 
 * Использование:
 * const toast = useToast()
 * toast.success('Успешно сохранено!')
 * toast.error('Произошла ошибка')
 */
export function useToast() {
  const show = (message: string, type: ToastType = 'info', options?: ToastOptions) => {
    const id = `toast-${Date.now()}-${Math.random()}`
    const duration = options?.duration ?? 5000
    
    const toast: Toast = {
      id,
      message,
      type,
      duration,
      action: options?.action
    }
    
    toasts.value.push(toast)
    
    // Автоудаление через заданное время
    if (duration > 0) {
      setTimeout(() => {
        remove(id)
      }, duration)
    }
    
    return id
  }
  
  const remove = (id: string) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }
  
  const clear = () => {
    toasts.value = []
  }
  
  return {
    // Методы для показа
    success: (message: string, options?: ToastOptions) => show(message, 'success', options),
    error: (message: string, options?: ToastOptions) => show(message, 'error', options),
    info: (message: string, options?: ToastOptions) => show(message, 'info', options),
    warning: (message: string, options?: ToastOptions) => show(message, 'warning', options),
    
    // Управление
    show,
    remove,
    clear,
    
    // Доступ к списку для рендеринга
    toasts
  }
}

// Экспорт глобального списка для компонента ToastContainer
export function useToastList() {
  return toasts
}