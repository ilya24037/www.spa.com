/**
 * TypeScript типы для MetroSelector компонента
 */

// Интерфейс для станции метро (расширяемый для будущих улучшений)
export interface MetroStation {
  name: string
  line?: string
  color?: string
}

// Props для MetroSelector компонента
export interface MetroSelectorProps {
  // Массив выбранных станций (v-model)
  modelValue: string[]
  // Массив доступных для выбора станций
  stations: string[]
}

// Emits для MetroSelector компонента
export interface MetroSelectorEmits {
  // Событие обновления v-model
  'update:modelValue': [value: string[]]
}

// Типы для composable useMetroData
export interface UseMetroDataReturn {
  // Отсортированный список всех станций московского метро
  moscowMetroStations: string[]
  // Функция поиска станций по запросу
  searchStations: (query: string) => string[]
  // Получить количество станций
  getStationsCount: () => number
  // Алиасы для удобства
  stations: string[]
  allStations: string[]
}

// Тип для поискового запроса
export type SearchQuery = string

// Тип для состояния модального окна
export type ModalState = boolean

// Массив станций (основной тип данных)
export type StationsList = string[]