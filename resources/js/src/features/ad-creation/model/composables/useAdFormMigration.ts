import type { AdForm } from '../types'

/**
 * Composable для миграции старых данных формы
 * KISS: Простое преобразование данных без сложной логики
 */
export function useAdFormMigration() {
  
  // ✅ МИГРАЦИЯ СТАРЫХ ДАННЫХ К НОВОМУ ФОРМАТУ
  const migrateOldData = (oldData: any): Partial<AdForm> => {
    if (!oldData) return {}
    
    const migrated: Partial<AdForm> = {}
    
    // Основная информация
    migrated.id = oldData.id || oldData.ad_id || null
    migrated.user_id = oldData.user_id || null
    migrated.title = oldData.title || oldData.name || ''
    migrated.category = oldData.category || 'relax'
    migrated.description = oldData.description || oldData.about || ''
    migrated.status = oldData.status || 'draft'
    
    // Медиа - миграция старых форматов
    migrated.photos = migratePhotos(oldData)
    migrated.video = migrateVideos(oldData)
    
    // Цены и услуги
    migrated.prices = oldData.prices || oldData.pricing || {}
    migrated.services = migrateServices(oldData)
    migrated.clients = oldData.clients || []
    
    // Расписание
    migrated.schedule = oldData.schedule || {}
    migrated.schedule_notes = oldData.schedule_notes || ''
    
    // Контакты
    migrated.phone = oldData.phone || oldData.contact_phone || ''
    migrated.whatsapp = oldData.whatsapp || ''
    migrated.telegram = oldData.telegram || ''
    migrated.vk = oldData.vk || oldData.vkontakte || ''
    migrated.instagram = oldData.instagram || oldData.insta || ''
    
    // Локация
    migrated.address = oldData.address || oldData.location || ''
    migrated.geo = migrateGeo(oldData)
    migrated.radius = oldData.radius || null
    migrated.is_remote = oldData.is_remote || false
    
    // Параметры
    migrated.age = oldData.age || null
    migrated.height = oldData.height || null
    migrated.weight = oldData.weight || null
    migrated.breast_size = oldData.breast_size || oldData.breast || null
    migrated.hair_color = oldData.hair_color || ''
    migrated.eye_color = oldData.eye_color || ''
    migrated.nationality = oldData.nationality || ''
    migrated.appearance = oldData.appearance || ''
    
    // Дополнительно
    migrated.additional_features = oldData.additional_features || oldData.features || []
    migrated.discount = oldData.discount || null
    migrated.gift = oldData.gift || ''
    migrated.new_client_discount = oldData.new_client_discount || null
    migrated.has_girlfriend = oldData.has_girlfriend || false
    migrated.min_duration = oldData.min_duration || null
    migrated.contacts_per_hour = oldData.contacts_per_hour || null
    migrated.experience = oldData.experience || null
    migrated.work_format = oldData.work_format || ''
    migrated.specialty = oldData.specialty || ''
    
    return migrated
  }
  
  // ✅ МИГРАЦИЯ ФОТОГРАФИЙ
  const migratePhotos = (oldData: any): any[] => {
    // Проверяем разные возможные форматы
    if (oldData.photos && Array.isArray(oldData.photos)) {
      return oldData.photos
    }
    
    if (oldData.images && Array.isArray(oldData.images)) {
      return oldData.images.map((img: any) => ({
        id: img.id,
        url: img.url || img.path,
        is_verification: img.is_verification || false,
        order: img.order || 0
      }))
    }
    
    if (oldData.gallery && Array.isArray(oldData.gallery)) {
      return oldData.gallery
    }
    
    return []
  }
  
  // ✅ МИГРАЦИЯ ВИДЕО
  const migrateVideos = (oldData: any): any[] => {
    if (oldData.video && Array.isArray(oldData.video)) {
      return oldData.video
    }
    
    if (oldData.videos && Array.isArray(oldData.videos)) {
      return oldData.videos
    }
    
    return []
  }
  
  // ✅ МИГРАЦИЯ УСЛУГ
  const migrateServices = (oldData: any): string[] => {
    if (oldData.services && Array.isArray(oldData.services)) {
      return oldData.services
    }
    
    // Старый формат с объектами услуг
    if (oldData.services && typeof oldData.services === 'object') {
      return Object.keys(oldData.services).filter(key => oldData.services[key])
    }
    
    return []
  }
  
  // ✅ МИГРАЦИЯ ГЕОЛОКАЦИИ
  const migrateGeo = (oldData: any): { lat: number; lng: number } | null => {
    if (oldData.geo) {
      return oldData.geo
    }
    
    if (oldData.latitude && oldData.longitude) {
      return {
        lat: Number(oldData.latitude),
        lng: Number(oldData.longitude)
      }
    }
    
    if (oldData.coordinates) {
      return {
        lat: oldData.coordinates.lat || oldData.coordinates[0],
        lng: oldData.coordinates.lng || oldData.coordinates[1]
      }
    }
    
    return null
  }
  
  return {
    migrateOldData
  }
}