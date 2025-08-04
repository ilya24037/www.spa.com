import { ref, computed } from 'vue'

export interface ModalOptions {
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
  type?: 'info' | 'warning' | 'error' | 'success'
  onConfirm?: () => void | Promise<void>
  onCancel?: () => void
}

/**
 * Composable для управления модальными окнами
 * Заменяет alert() и confirm() на красивые модалки
 * 
 * Использование:
 * const modal = useModal()
 * 
 * // Простое информационное окно (замена alert)
 * modal.info('Телефон будет доступен после записи')
 * 
 * // Подтверждение действия (замена confirm)
 * const confirmed = await modal.confirm('Удалить объявление?')
 * if (confirmed) { ... }
 */
export function useModal() {
  const isOpen = ref(false)
  const options = ref<ModalOptions>({})
  const resolvePromise = ref<((value: boolean) => void) | null>(null)
  
  const open = (modalOptions: ModalOptions = {}) => {
    options.value = {
      confirmText: 'OK',
      cancelText: 'Отмена',
      type: 'info',
      ...modalOptions
    }
    isOpen.value = true
    
    return new Promise<boolean>((resolve) => {
      resolvePromise.value = resolve
    })
  }
  
  const close = (confirmed = false) => {
    isOpen.value = false
    
    if (resolvePromise.value) {
      resolvePromise.value(confirmed)
      resolvePromise.value = null
    }
    
    // Вызов callback если есть
    if (confirmed && options.value.onConfirm) {
      options.value.onConfirm()
    } else if (!confirmed && options.value.onCancel) {
      options.value.onCancel()
    }
  }
  
  const confirm = async (message: string, title = 'Подтверждение') => {
    return open({
      title,
      message,
      type: 'warning',
      confirmText: 'Да',
      cancelText: 'Отмена'
    })
  }
  
  const info = (message: string, title = 'Информация') => {
    return open({
      title,
      message,
      type: 'info',
      confirmText: 'Понятно',
      cancelText: undefined // Скрыть кнопку отмены
    })
  }
  
  const error = (message: string, title = 'Ошибка') => {
    return open({
      title,
      message,
      type: 'error',
      confirmText: 'OK',
      cancelText: undefined
    })
  }
  
  const success = (message: string, title = 'Успешно') => {
    return open({
      title,
      message,
      type: 'success',
      confirmText: 'OK',
      cancelText: undefined
    })
  }
  
  const warning = (message: string, title = 'Внимание') => {
    return open({
      title,
      message,
      type: 'warning',
      confirmText: 'OK',
      cancelText: undefined
    })
  }
  
  return {
    isOpen: computed(() => isOpen.value),
    options: computed(() => options.value),
    open,
    close,
    confirm,
    info,
    error,
    success,
    warning
  }
}