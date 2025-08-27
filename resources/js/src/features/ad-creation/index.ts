// Экспорт публичного API feature ad-creation
export { default as AdForm } from './ui/AdForm.vue'
export { useAdFormModel } from './model/adFormModel'
export { adApi } from './api/adApi'
export type { AdForm, AdForm as AdFormData } from './model/types'