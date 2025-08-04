// Shared Slice - common FSD components

// === LAYOUTS ===
export * from './layouts/components'
export { default as MainLayout } from './layouts/MainLayout'
export * from './layouts/ProfileLayout'

// === UI COMPONENTS ===

// Atoms
export * from './ui/atoms'

// Molecules  
export * from './ui/molecules'

// Organisms
export * from './ui/organisms'

// Frequently used specific components
export { StarRating } from './ui/molecules/StarRating'
export { Toast } from './ui/molecules/Toast'
export { Breadcrumbs } from './ui/molecules/Breadcrumbs'
export { BackButton } from './ui/molecules/BackButton'
export { Modal } from './ui/organisms/Modal'
export { ConfirmModal } from './ui/organisms/ConfirmModal'

// Layout specific components
export { default as ProfileSidebar } from './layouts/ProfileLayout/ProfileSidebar.vue'