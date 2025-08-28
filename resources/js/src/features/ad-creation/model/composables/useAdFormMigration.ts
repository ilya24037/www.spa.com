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
    if (!value || value === null || value === undefined) return defaultValue
    
    if (Array.isArray(value)) return value
    
    if (typeof value === 'string') {
      // Защита от пустых строк и строк "null"
      if (value.trim() === '' || value === 'null') return defaultValue
      
      try {
        const parsed = JSON.parse(value)
        return Array.isArray(parsed) ? parsed : defaultValue
      } catch (e) {
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
      bikini_zone: oldData?.bikini_zone || '',
      appearance: oldData?.appearance || ''
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
      // КРИТИЧЕСКИ ВАЖНО: сохраняем ID!
      id: oldData.id,
      user_id: oldData.user_id,
      status: oldData.status || 'draft',
      category: oldData.category || 'relax',
      title: oldData.title || '',
      
      // Основная информация
      specialty: oldData.specialty || '',
      clients: migrateArrayField(oldData.clients, []),
      service_location: migrateArrayField(oldData.service_location, []),
      work_format: oldData.work_format || 'individual',
      service_provider: migrateArrayField(oldData.service_provider, ['women']),
      experience: oldData.experience || '',
      description: oldData.description || oldData.about || '',
      
      // КРИТИЧЕСКИ ВАЖНО: Дополнительные массивы, которые могут быть строками в БД
      features: migrateArrayField(oldData.features, []),
      additional_features: migrateArrayField(oldData.additional_features, []),
      
      // Услуги и расписание (используем универсальные функции)
      services: migrateJsonField(oldData.services, {}),
      schedule: migrateJsonField(oldData.schedule, {}),
      schedule_notes: oldData.schedule_notes || '', // ИСПРАВЛЕНИЕ: Добавлено недостающее поле schedule_notes
      prices: migrateJsonField(oldData.prices, {}),
      geo: migrateJsonField(oldData.geo, null),
      faq: migrateJsonField(oldData.faq, {}),
      
      // Медиа (используем универсальную миграцию массивов)
      photos: migrateArrayField(oldData.photos, []),
      video: migrateArrayField(oldData.video, []),
      
      // Boolean поля (нужно преобразовать 0/1 в boolean)
      online_booking: oldData.online_booking === 1 || oldData.online_booking === '1' || oldData.online_booking === true,
      is_starting_price: oldData.is_starting_price === 1 || oldData.is_starting_price === '1' || oldData.is_starting_price === true,
      
      // Объекты параметров и контактов
      parameters: migrateParameters(oldData),
      contacts: migrateContacts(oldData),
      
      // ДОПОЛНИТЕЛЬНЫЕ ПОЛЯ, которые могут отсутствовать
      phone: oldData.phone || '',
      address: oldData.address || '',
      price: oldData.price || null,
      price_unit: oldData.price_unit || 'hour',
      
      // ИСПРАВЛЕНИЕ: Поля акций и скидок (аналогично schedule_notes)
      new_client_discount: oldData.new_client_discount || '',
      gift: oldData.gift || '',
      discount: oldData.discount || '0'
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