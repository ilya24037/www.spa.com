// 🎯 Feature MastersFilter - Фильтрация мастеров для главной страницы
// Feature-Sliced Design экспорты

// === UI КОМПОНЕНТЫ ===

// Основная панель фильтров
export { FilterPanel } from './ui'

// Отдельные компоненты фильтров (для кастомизации)
export {
  FilterSearch,
  FilterPrice,
  FilterLocation,
  FilterCategory,
  FilterRating,
  FilterAdditional
} from './ui'

// === MODEL СЛОЙ ===

// Store для управления фильтрами
export { useMastersFilterStore } from './model/mastersFilterStore'