/**
 * Barrel export для всех map-связанных импортов
 * Обеспечивает обратную совместимость после миграции
 */

// Основной компонент карты
export { default } from './YandexMap.vue'
export type { MapMarker } from './YandexMap.vue'

// Composables для обратной совместимости
export { useMapWithMasters } from '@/src/features/map/composables/useMapWithMasters'