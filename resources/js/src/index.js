/**
 * 🚀 Feature-Sliced Design (FSD) - Главный экспорт
 * Централизованный доступ ко всем слоям архитектуры
 * 
 * Структура FSD:
 * 📁 shared - Переиспользуемые компоненты (UI, layouts, utils)
 * 📁 entities - Бизнес-сущности (Master, Ad, Booking)
 * 📁 features - Функциональности (Filters, Map, Gallery)
 * 📁 widgets - Составные блоки страниц (MastersCatalog, ProfileDashboard)
 * 📁 pages - Страницы приложения (обновлены для использования FSD)
 * 
 * @see https://feature-sliced.design/
 */

// 🔧 Shared - Переиспользуемые компоненты
export * from './shared'

// 🏗️ Entities - Бизнес-сущности  
export * from './entities/master'
export * from './entities/ad'
export * from './entities/booking'

// ⚡ Features - Функциональности
export * from './features/masters-filter'
export * from './features/map'
export * from './features/gallery'
export * from './features/profile-navigation'

// 🧩 Widgets - Составные блоки
export * from './widgets/masters-catalog'
export * from './widgets/master-profile'
export * from './widgets/profile-dashboard'

/**
 * 📋 Примеры использования:
 * 
 * // В страницах
 * import { MastersCatalog } from '@/src/widgets/masters-catalog'
 * import { MasterProfile } from '@/src/widgets/master-profile'
 * import { ProfileDashboard } from '@/src/widgets/profile-dashboard'
 * 
 * // Отдельные компоненты
 * import { MasterCard, AdCard } from '@/src/entities/master'
 * import { FilterPanel } from '@/src/features/masters-filter'
 * import { UniversalMap } from '@/src/features/map'
 * import { Breadcrumbs, BackButton } from '@/src/shared'
 * 
 * // Или через главный экспорт
 * import { 
 *   MastersCatalog, 
 *   MasterCard, 
 *   FilterPanel,
 *   Breadcrumbs 
 * } from '@/src'
 */