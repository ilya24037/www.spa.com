// 🏗️ Entities/Master - Сущность мастеров
// Экспорты для Feature-Sliced Design архитектуры

// === UI КОМПОНЕНТЫ ===

// MasterCard - карточки мастеров
export {
  MasterCard,
  MasterCardList,
  MasterCardListItem
} from './ui/MasterCard'

// MasterGallery - галерея фотографий
export {
  MasterGallery,
  MasterGalleryModal
} from './ui/MasterGallery'

// MasterInfo - информация о мастере
export {
  MasterInfo,
  MasterParameters
} from './ui/MasterInfo'

// MasterServices - услуги мастера
export {
  MasterServices
} from './ui/MasterServices'

// MasterReviews - отзывы мастера
export {
  MasterReviews
} from './ui/MasterReviews'

// MasterContact - контактная информация
export {
  MasterContact
} from './ui/MasterContact'

// === MODEL СЛОЙ ===

// Stores
export { useMasterStore } from './model/masterStore'

// Композаблы
export { useMaster, useMasterList } from './model/useMaster'

// === API СЛОЙ ===

// API для работы с мастерами
export { masterApi } from './api/masterApi'