import { ref, type Ref } from 'vue'
import BaseModal from '../BaseModal.vue'
import ConfirmModal from '../ConfirmModal.vue'

interface ModalState {
  isOpen: Ref<boolean>
  open: () => void
  close: () => void
  toggle: () => void
}

/**
 * Composable для управления состоянием модального окна
 */
export function useModal(initialState = false): ModalState {
  const isOpen = ref(initialState)

  const open = () => {
    isOpen.value = true
  }

  const close = () => {
    isOpen.value = false
  }

  const toggle = () => {
    isOpen.value = !isOpen.value
  }

  return {
    isOpen,
    open,
    close,
    toggle
  }
}

// Глобальное хранилище для модальных окон
const globalModals = new Map<string, ModalState>()

/**
 * Composable для управления именованными модальными окнами
 */
export function useNamedModal(name: string): ModalState {
  if (!globalModals.has(name)) {
    globalModals.set(name, useModal())
  }
  
  return globalModals.get(name)!
}

interface ConfirmOptions {
  title?: string
  message?: string
  description?: string
  variant?: 'info' | 'warning' | 'danger' | 'success'
  confirmText?: string
  cancelText?: string
  requiresConfirmation?: boolean
  confirmationText?: string
}

interface ConfirmResult {
  confirmed: boolean
}

/**
 * Программный вызов модального окна подтверждения
 */
export function useConfirm() {
  const confirm = (options: ConfirmOptions = {}): Promise<ConfirmResult> => {
    return new Promise((resolve) => {
      // Создаем временный компонент модального окна
      const modalId = `confirm-${Date.now()}`
      
      // Создаем элемент для монтирования
      const mountElement = document.createElement('div')
      mountElement.id = modalId
      document.body.appendChild(mountElement)
      
      // Используем статический импорт ConfirmModal
      import('vue').then(({ createApp }) => {
        const app = createApp(ConfirmModal, {
          modelValue: true,
          title: options.title || 'Подтверждение',
          message: options.message || 'Вы уверены?',
          description: options.description,
          variant: options.variant || 'info',
          confirmText: options.confirmText || 'Да',
          cancelText: options.cancelText || 'Отмена',
          requiresConfirmation: options.requiresConfirmation || false,
          confirmationText: options.confirmationText || '',
          persistent: true,
          onConfirm: () => {
            cleanup()
            resolve({ confirmed: true })
          },
          onCancel: () => {
            cleanup()
            resolve({ confirmed: false })
          },
          onClose: () => {
            cleanup()
            resolve({ confirmed: false })
          },
          'onUpdate:modelValue': (value: boolean) => {
            if (!value) {
              cleanup()
              resolve({ confirmed: false })
            }
          }
        })
        
        const cleanup = () => {
          app.unmount()
          if (mountElement.parentNode) {
            mountElement.parentNode.removeChild(mountElement)
          }
        }
        
        app.mount(mountElement)
      })
    })
  }
  
  return { confirm }
}

interface AlertOptions {
  title?: string
  message?: string
  variant?: 'info' | 'warning' | 'danger' | 'success'
  buttonText?: string
}

/**
 * Программный вызов модального окна уведомления
 */
export function useAlert() {
  const alert = (options: AlertOptions = {}): Promise<void> => {
    return new Promise((resolve) => {
      const modalId = `alert-${Date.now()}`
      
      const mountElement = document.createElement('div')
      mountElement.id = modalId
      document.body.appendChild(mountElement)
      
      // Используем статический импорт BaseModal
      import('vue').then(({ createApp, h }) => {
        const app = createApp({
          render() {
            return h(BaseModal, {
              modelValue: true,
              title: options.title || 'Уведомление',
              variant: options.variant || 'info',
              size: 'sm',
              showFooter: true,
              showConfirmButton: true,
              confirmText: options.buttonText || 'ОК',
              persistent: true,
              onConfirm: () => {
                cleanup()
                resolve()
              },
              onClose: () => {
                cleanup()
                resolve()
              },
              'onUpdate:modelValue': (value: boolean) => {
                if (!value) {
                  cleanup()
                  resolve()
                }
              }
            }, {
              default: () => h('p', { class: 'text-gray-700' }, options.message || 'Уведомление')
            })
          }
        })
        
        const cleanup = () => {
          app.unmount()
          if (mountElement.parentNode) {
            mountElement.parentNode.removeChild(mountElement)
          }
        }
        
        app.mount(mountElement)
      })
    })
  }
  
  return { alert }
}

/**
 * Stack для управления несколькими модальными окнами
 */
class ModalStack {
  private stack: string[] = []
  private zIndexBase = 1000
  
  push(modalId: string): number {
    this.stack.push(modalId)
    return this.zIndexBase + this.stack.length
  }
  
  remove(modalId: string): void {
    const index = this.stack.indexOf(modalId)
    if (index > -1) {
      this.stack.splice(index, 1)
    }
  }
  
  getZIndex(modalId: string): number {
    const index = this.stack.indexOf(modalId)
    return index > -1 ? this.zIndexBase + index + 1 : this.zIndexBase
  }
  
  isEmpty(): boolean {
    return this.stack.length === 0
  }
  
  getTopModal(): string | undefined {
    return this.stack.length > 0 ? this.stack[this.stack.length - 1] : undefined
  }
}

const modalStack = new ModalStack()

/**
 * Composable для управления стеком модальных окон
 */
export function useModalStack() {
  const registerModal = (modalId: string): number => {
    return modalStack.push(modalId)
  }
  
  const unregisterModal = (modalId: string): void => {
    modalStack.remove(modalId)
  }
  
  const getZIndex = (modalId: string): number => {
    return modalStack.getZIndex(modalId)
  }
  
  const isTopModal = (modalId: string): boolean => {
    return modalStack.getTopModal() === modalId
  }
  
  return {
    registerModal,
    unregisterModal,
    getZIndex,
    isTopModal
  }
}