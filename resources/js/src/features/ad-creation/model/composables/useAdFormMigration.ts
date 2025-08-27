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
    
    // Основная информация (соответствует новому интерфейсу)
    migrated.specialty = oldData.specialty || ''
    migrated.clients = oldData.clients || []
    migrated.service_location = oldData.service_location || []
    migrated.work_format = oldData.work_format || 'individual'
    migrated.service_provider = oldData.service_provider || ['women']
    migrated.experience = oldData.experience || ''
    migrated.description = oldData.description || oldData.about || ''
    
    // Услуги и возможности
    migrated.services = oldData.services || {}
    migrated.services_additional_info = oldData.services_additional_info || ''
    migrated.features = oldData.features || []
    migrated.additional_features = oldData.additional_features || ''
    
    // Расписание и бронирование
    migrated.schedule = oldData.schedule || {}
    migrated.schedule_notes = oldData.schedule_notes || ''
    migrated.online_booking = oldData.online_booking || false
    
    // Цены
    migrated.price = oldData.price || null
    migrated.price_unit = oldData.price_unit || 'hour'
    migrated.is_starting_price = oldData.is_starting_price || false
    migrated.prices = oldData.prices || {}
    
    // Параметры (объект как в оригинале)
    migrated.parameters = migrateParameters(oldData)
    
    // Скидки и подарки
    migrated.new_client_discount = oldData.new_client_discount || ''
    migrated.gift = oldData.gift || ''
    
    // Медиа - миграция старых форматов
    migrated.photos = migratePhotos(oldData)
    migrated.video = migrateVideos(oldData)
    
    // Геолокация и путешествия
    migrated.geo = migrateGeo(oldData)
    migrated.address = oldData.address || oldData.location || ''
    migrated.travel_area = oldData.travel_area || ''
    migrated.custom_travel_areas = oldData.custom_travel_areas || []
    migrated.travel_radius = oldData.travel_radius || ''
    migrated.travel_price = oldData.travel_price || null
    migrated.travel_price_type = oldData.travel_price_type || ''
    
    // Контакты (объект как в оригинале)
    migrated.contacts = migrateContacts(oldData)
    
    // FAQ
    migrated.faq = oldData.faq || {}
    
    // Поля верификации
    migrated.verification_photo = oldData.verification_photo || null
    migrated.verification_video = oldData.verification_video || null
    migrated.verification_status = oldData.verification_status || ''
    migrated.verification_comment = oldData.verification_comment || null
    migrated.verification_expires_at = oldData.verification_expires_at || null
    
    return migrated
  }
  
  // ✅ МИГРАЦИЯ ПАРАМЕТРОВ
  const migrateParameters = (oldData: any) => {
    if (oldData?.parameters && typeof oldData.parameters === 'object') {
      return oldData.parameters
    }
    
    return {
      title: oldData?.title || '',
      age: oldData?.age || '',
      height: oldData?.height || '',
      weight: oldData?.weight || '',
      breast_size: oldData?.breast_size || '',
      hair_color: oldData?.hair_color || '',
      eye_color: oldData?.eye_color || '',
      nationality: oldData?.nationality || '',
      bikini_zone: oldData?.bikini_zone || ''
    }
  }
  
  // ✅ МИГРАЦИЯ КОНТАКТОВ
  const migrateContacts = (oldData: any) => {
    // Если уже в новом формате с объектом contacts
    if (oldData?.contacts && typeof oldData.contacts === 'object') {
      return oldData.contacts
    }
    
    // Мигрируем из старого формата (отдельные поля)
    return {
      phone: oldData?.phone || oldData?.contact_phone || '',
      contact_method: oldData?.contact_method || 'any',
      whatsapp: oldData?.whatsapp || '',
      telegram: oldData?.telegram || ''
    }
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