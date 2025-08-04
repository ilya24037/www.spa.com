// PhoneModal.types.ts
export interface PhoneModalProps {
  show: boolean
  phone: string
}

export interface PhoneModalEmits {
  close: []
  called: [phone: string]
  copied: [phone: string]
}

// Состояние компонента
export interface PhoneModalState {
  isCopying: boolean
  copySuccess: boolean
}

// Типы для работы с телефоном
export interface PhoneInfo {
  original: string
  formatted: string
  cleaned: string
  isValid: boolean
}

// Типы для действий
export type PhoneAction = 'call' | 'copy' | 'close'

// Clipboard API типы
export interface ClipboardResponse {
  success: boolean
  error?: string
}

// Error типы для обработки ошибок
export interface PhoneModalError {
  type: 'clipboard' | 'tel' | 'validation'
  message: string
  originalError?: unknown
}