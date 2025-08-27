import type { AdForm } from '../types'

/**
 * Composable для миграции старых данных формы
 * ОПТИМИЗИРОВАННЫЙ: 100 строк согласно плану
 */
export function useAdFormMigration() {
  
  // ✅ УНИВЕРСАЛЬНАЯ МИГРАЦИЯ JSON ПОЛЕЙ
  const migrateJsonField = (value: any, defaultValue: any = {}): any => {
    if (!value) return defaultValue
    
    if (typeof value === 'string') {
      try {
        return JSON.parse(value)
      } catch {
        return defaultValue
      }
    }
    
    return value
  }
  
  // ✅ УНИВЕРСАЛЬНАЯ МИГРАЦИЯ МАССИВОВ
  const migrateArrayField = (value: any, defaultValue: any[] = []): any[] => {
    if (!value) return defaultValue
    
    if (Array.isArray(value)) return value
    
    if (typeof value === 'string') {
      try {
        const parsed = JSON.parse(value)
        return Array.isArray(parsed) ? parsed : defaultValue
      } catch {
        return defaultValue
      }
    }
    
    return defaultValue
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
    if (oldData?.contacts && typeof oldData.contacts === 'object') {
      return oldData.contacts
    }
    
    return {
      phone: oldData?.phone || oldData?.contact_phone || '',
      contact_method: oldData?.contact_method || 'any',
      whatsapp: oldData?.whatsapp || '',
      telegram: oldData?.telegram || ''
    }
  }
  
  // ✅ ОСНОВНАЯ ФУНКЦИЯ МИГРАЦИИ
  const migrateOldData = (oldData: any): Partial<AdForm> => {
    if (!oldData) return {}
    
    return {
      // Основная информация
      specialty: oldData.specialty || '',
      clients: migrateArrayField(oldData.clients, []),
      service_location: migrateArrayField(oldData.service_location, []),
      work_format: oldData.work_format || 'individual',
      service_provider: migrateArrayField(oldData.service_provider, ['women']),
      experience: oldData.experience || '',
      description: oldData.description || oldData.about || '',
      
      // Услуги и расписание (используем универсальные функции)
      services: migrateJsonField(oldData.services, {}),
      schedule: migrateJsonField(oldData.schedule, {}),
      prices: migrateJsonField(oldData.prices, {}),
      geo: migrateJsonField(oldData.geo, null),
      faq: migrateJsonField(oldData.faq, {}),
      
      // Медиа (используем универсальную миграцию массивов)
      photos: migrateArrayField(oldData.photos, []),
      video: migrateArrayField(oldData.video, []),
      
      // Объекты параметров и контактов
      parameters: migrateParameters(oldData),
      contacts: migrateContacts(oldData)
    }
  }
  
  return {
    migrateOldData,
    migrateParameters,
    migrateContacts,
    migrateJsonField,
    migrateArrayField
  }
}