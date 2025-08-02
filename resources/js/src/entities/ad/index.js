// 🏗️ Entities/Ad - Сущность объявлений
// Экспорты для Feature-Sliced Design архитектуры

// === UI КОМПОНЕНТЫ ===

// AdCard - карточки объявлений
export {
  AdCard,
  AdCardList,
  AdCardListItem
} from './ui/AdCard'

// AdStatus - статусы объявлений  
export {
  AdStatus,
  AdStatusBadge,
  AdStatusFilter
} from './ui/AdStatus'

// AdForm - форма создания/редактирования
export {
  AdForm,
  AdFormBasicInfo,
  AdFormPersonalInfo,
  AdFormCommercialInfo,
  AdFormLocationInfo,
  AdFormMediaInfo,
  AdFormActionButton,
  useAdFormStore
} from './ui/AdForm'

// === MODEL СЛОЙ ===

// Stores
export { useAdStore } from './model/adStore'

// Композаблы
export { useAd, useAdList } from './model/useAd'

// Типы и константы
export {
  AD_STATUSES,
  AD_STATUS_LABELS,
  AD_STATUS_COLORS,
  PRICE_UNITS,
  PRICE_UNIT_LABELS,
  WORK_FORMATS,
  WORK_FORMAT_LABELS,
  CLIENT_TYPES,
  CLIENT_TYPE_LABELS,
  CONTACT_METHODS,
  CONTACT_METHOD_LABELS,
  PAYMENT_METHODS,
  PAYMENT_METHOD_LABELS,
  SORT_OPTIONS,
  SORT_LABELS,
  VALIDATION_RULES,
  DEFAULT_AD_FORM,
  getStatusClasses,
  isPublicStatus,
  isEditableStatus,
  isDeletableStatus
} from './model/adTypes'

// === API СЛОЙ ===

// API для работы с объявлениями
export { adApi } from './api/adApi'