// Main modal components export
export { default as BaseModal } from './BaseModal.vue'
export { default as ConfirmModal } from './ConfirmModal.vue'
export { default as AlertModal } from './AlertModal.vue'

// Modal composables export
export {
  useModal,
  useNamedModal,
  useConfirm,
  useAlert,
  useModalStack
} from './composables/useModal'

// TypeScript types for modal system
export interface ModalProps {
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  variant?: 'primary' | 'danger' | 'warning' | 'success'
  centered?: boolean
  fullscreen?: boolean
  closable?: boolean
  closeOnBackdrop?: boolean
  closeOnEscape?: boolean
  showHeader?: boolean
  showFooter?: boolean
  persistent?: boolean
  zIndex?: number
}

export interface ConfirmModalProps extends Omit<ModalProps, 'variant'> {
  message?: string
  description?: string
  variant?: 'info' | 'warning' | 'danger' | 'success'
  confirmText?: string
  cancelText?: string
  requiresConfirmation?: boolean
  confirmationText?: string
  confirmationLabel?: string
  confirmationPlaceholder?: string
}

export interface AlertModalProps extends Omit<ModalProps, 'variant'> {
  message?: string
  variant?: 'info' | 'warning' | 'danger' | 'success'
  buttonText?: string
}

export interface ModalEmits {
  'update:modelValue': [value: boolean]
  open: []
  close: []
  confirm?: []
  cancel?: []
  backdrop: []
  escape: []
}

// Utility types for programmatic modals
export interface ConfirmOptions {
  title?: string
  message?: string
  description?: string
  variant?: 'info' | 'warning' | 'danger' | 'success'
  confirmText?: string
  cancelText?: string
  requiresConfirmation?: boolean
  confirmationText?: string
}

export interface AlertOptions {
  title?: string
  message?: string
  variant?: 'info' | 'warning' | 'danger' | 'success'
  buttonText?: string
}

export interface ConfirmResult {
  confirmed: boolean
}

// Re-export utility functions
export { generateUniqueId } from '@/src/shared/lib/utils'