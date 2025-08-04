/**
 * TypeScript типы для Toast компонента
 */

export type ToastType = 'success' | 'error' | 'warning' | 'info'

export type ToastPosition = 
  | 'top-left' 
  | 'top-center' 
  | 'top-right' 
  | 'bottom-left' 
  | 'bottom-center' 
  | 'bottom-right'

export interface ToastProps {
  /** Тип уведомления */
  type?: ToastType
  
  /** Заголовок (опционально) */
  title?: string
  
  /** Основное сообщение */
  message: string
  
  /** Длительность показа в мс (0 = не скрывать автоматически) */
  duration?: number
  
  /** Позиция на экране */
  position?: ToastPosition
  
  /** Показывать кнопку закрытия */
  closable?: boolean
}

export interface ToastEmits {
  /** Событие закрытия */
  close: []
}

export interface ToastMethods {
  /** Показать уведомление */
  show: () => void
  
  /** Скрыть уведомление */
  close: () => void
}

export interface ToastOptions {
  /** Тип уведомления */
  type?: ToastType
  
  /** Заголовок */
  title?: string
  
  /** Длительность */
  duration?: number
  
  /** Позиция */
  position?: ToastPosition
  
  /** Возможность закрытия */
  closable?: boolean
}