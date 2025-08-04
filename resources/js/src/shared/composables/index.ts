/**
 * Экспорт всех базовых composables
 * 
 * Импорт в компонентах:
 * import { useToast, useModal, useLoadingState } from '@/src/shared/composables'
 */

// Управление состояниями
export { useLoadingState } from './useLoadingState'
export type { LoadingState } from './useLoadingState'

// Уведомления (замена alert)
export { useToast, useToastList } from './useToast'
export type { Toast, ToastType } from './useToast'

// Модальные окна (замена alert/confirm)
export { useModal } from './useModal'
export type { ModalOptions } from './useModal'

// Обработка ошибок
export { useErrorHandler } from './useErrorHandler'
export type { ErrorDetails, ValidationErrors } from './useErrorHandler'

// Асинхронные действия
export { useAsyncAction } from './useAsyncAction'
export type { AsyncActionOptions } from './useAsyncAction'

// Управление формами
export { useForm } from './useForm'
export type { FormField, FormOptions } from './useForm'

// Пагинация
export { usePagination } from './usePagination'
export type { PaginationOptions, PaginationMeta } from './usePagination'

// Debounce и Throttle
export { useDebounce, useDebounceFn, useThrottleFn } from './useDebounce'

// Хранилище
export { useLocalStorage, useSessionStorage } from './useLocalStorage'

// Утилиты
export { useId } from './useId'