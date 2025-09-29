/**
 * Динамические импорты модальных компонентов для программного использования
 * Отделены от статических импортов чтобы избежать конфликтов в Vite
 */

// Динамические импорты для useModal composables
// BaseModal и ConfirmModal используются статически в шаблонах
export const importAlertModal = () => import('../AlertModal.vue')

// Типы для динамических импортов
export type AlertModalComponent = Awaited<ReturnType<typeof importAlertModal>>['default']
