// Main modal component export
export { default as Modal } from './Modal.vue'
export type { ModalProps, ModalSize, ModalVariant } from './Modal.vue'

// Legacy exports for backward compatibility
export { default as BaseModal } from './Modal.vue' // BaseModal теперь ссылается на Modal
// ConfirmModal экспортируется отдельно чтобы избежать циклической зависимости

// Modal composables export (если есть)
// export {
//   useModal,
//   useNamedModal,
//   useConfirm,
//   useAlert,
//   useModalStack
// } from './composables/useModal'