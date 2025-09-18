import { ref, computed } from 'vue'

export interface SectionState {
  [key: string]: boolean
}

export interface SectionConfig {
  key: string
  title: string
  required: boolean
  fields: string[]
}

/**
 * Composable для управления секциями формы
 */
export function useFormSections(sections: SectionConfig[], formData: any) {
  // Вспомогательная функция для получения вложенных значений
  const getNestedValue = (obj: any, path: string): any => {
    return path.split('.').reduce((current, key) => current?.[key], obj)
  }
  
  // Функция проверки заполненности секции при инициализации
  const checkInitialSectionFilled = (section: SectionConfig): boolean => {
    const sectionKey = section.key
    
    // Специальная логика для параметров (объект parameters)
    if (sectionKey === 'parameters') {
      const params = formData.parameters
      if (params && typeof params === 'object') {
        // Проверяем хотя бы одно заполненное поле
        return !!(params.title || params.age || params.height || params.weight ||
                  params.breast_size || params.hair_color || params.eye_color || params.nationality)
      }
      return false
    }
    
    // Специальная логика для контактов (объект contacts)
    if (sectionKey === 'contacts') {
      const contacts = formData.contacts
      if (contacts && typeof contacts === 'object') {
        // Проверяем хотя бы одно заполненное поле
        return !!(contacts.phone || contacts.whatsapp || contacts.telegram || 
                  (contacts.contact_method && contacts.contact_method !== 'any'))
      }
      return false
    }
    
    // Специальная логика для услуг и комфорта (они используют общие данные)
    if (sectionKey === 'services' || sectionKey === 'comfort') {
      const services = formData.services
      if (services && typeof services === 'object') {
        // Для секции Комфорт проверяем только категории с is_amenity
        // Для секции Услуги проверяем категории без is_amenity
        // Но поскольку у нас нет доступа к конфигу здесь, проверяем все
        for (const categoryServices of Object.values(services)) {
          if (categoryServices && typeof categoryServices === 'object') {
            for (const service of Object.values(categoryServices) as any[]) {
              if (service?.enabled || service?.comment) return true
            }
          }
        }
      }
      return false
    }
    
    // Специальная логика для удобств (старый код, если останется)
    if (sectionKey === 'amenities') {
      const amenities = formData.amenities
      if (amenities && typeof amenities === 'object') {
        for (const categoryAmenities of Object.values(amenities)) {
          if (categoryAmenities && typeof categoryAmenities === 'object') {
            for (const amenity of Object.values(categoryAmenities) as any[]) {
              if (amenity?.enabled) return true
            }
          }
        }
      }
      return false
    }

    // Специальная логика для графика работы
    if (sectionKey === 'schedule') {
      const schedule = formData.schedule
      // Пустой объект {} или null считаем как отсутствие данных
      if (!schedule || (typeof schedule === 'object' && Object.keys(schedule).length === 0)) {
        return false
      }
      // Проверяем есть ли реальные данные в расписании
      for (const daySchedule of Object.values(schedule)) {
        if (daySchedule && typeof daySchedule === 'object') {
          const day = daySchedule as any
          if (day.enabled || day.start || day.end) {
            return true // Есть данные хотя бы для одного дня
          }
        }
      }
      return false
    }

    // Для остальных секций проверяем поля
    return section.fields.some(field => {
      const value = getNestedValue(formData, field)
      return value !== null && value !== undefined && value !== '' &&
             (Array.isArray(value) ? value.length > 0 :
              typeof value === 'object' ? Object.keys(value).length > 0 : true)
    })
  }
  
  // Состояние секций (открыт/закрыт)
  // Инициализируем с учетом заполненных данных
  const sectionsState = ref<SectionState>(
    sections.reduce((acc, section) => {
      // Секция открыта если она обязательная ИЛИ в ней есть заполненные данные
      acc[section.key] = section.required || checkInitialSectionFilled(section)
      return acc
    }, {} as SectionState)
  )

  // Методы управления секциями
  const toggleSection = (sectionKey: string) => {
    sectionsState.value[sectionKey] = !sectionsState.value[sectionKey]
  }

  const expandAll = () => {
    sections.forEach(section => {
      sectionsState.value[section.key] = true
    })
  }

  const collapseAll = () => {
    sections.forEach(section => {
      sectionsState.value[section.key] = false
    })
  }

  const expandRequired = () => {
    sections.forEach(section => {
      sectionsState.value[section.key] = section.required
    })
  }

  // Проверка заполненности секции
  const checkSectionFilled = (sectionKey: string): boolean => {
    const section = sections.find(s => s.key === sectionKey)
    if (!section) return false

    // Специальная логика для параметров (объект parameters)
    if (sectionKey === 'parameters') {
      const params = formData.parameters
      if (params && typeof params === 'object') {
        // Проверяем основные поля
        return !!(params.title && params.age && params.height)
      }
      return false
    }

    // Специальная логика для контактов (объект contacts)
    if (sectionKey === 'contacts') {
      const contacts = formData.contacts
      if (contacts && typeof contacts === 'object') {
        // Проверяем обязательное поле телефон
        return !!contacts.phone
      }
      return false
    }

    // Специальная логика для услуг
    if (sectionKey === 'services') {
      const services = formData.services
      if (services && typeof services === 'object') {
        for (const categoryServices of Object.values(services)) {
          if (categoryServices && typeof categoryServices === 'object') {
            for (const service of Object.values(categoryServices) as any[]) {
              if (service?.enabled) return true
            }
          }
        }
      }
      return false
    }

    // Специальная логика для удобств
    if (sectionKey === 'amenities') {
      const amenities = formData.amenities
      if (amenities && typeof amenities === 'object') {
        for (const categoryAmenities of Object.values(amenities)) {
          if (categoryAmenities && typeof categoryAmenities === 'object') {
            for (const amenity of Object.values(categoryAmenities) as any[]) {
              if (amenity?.enabled) return true
            }
          }
        }
      }
      return false
    }

    return section.fields.some(field => {
      const value = getNestedValue(formData, field)
      return value !== null && value !== undefined && value !== '' && 
             (Array.isArray(value) ? value.length > 0 : true)
    })
  }

  // Подсчет заполненных полей в секции
  const getFilledCount = (sectionKey: string): number => {
    const section = sections.find(s => s.key === sectionKey)
    if (!section) return 0

    // Специальная логика для параметров (объект parameters)
    if (sectionKey === 'parameters') {
      let count = 0
      const params = formData.parameters
      if (params && typeof params === 'object') {
        if (params.title) count++
        if (params.age) count++
        if (params.height) count++
        if (params.weight) count++
        if (params.breast_size) count++
        if (params.hair_color) count++
        if (params.eye_color) count++
        if (params.nationality) count++
      }
      return count
    }

    // Специальная логика для контактов (объект contacts)
    if (sectionKey === 'contacts') {
      let count = 0
      const contacts = formData.contacts
      if (contacts && typeof contacts === 'object') {
        if (contacts.phone) count++
        if (contacts.whatsapp) count++
        if (contacts.telegram) count++
        if (contacts.contact_method && contacts.contact_method !== 'any') count++
      }
      return count
    }

    // Специальная логика для услуг
    if (sectionKey === 'services') {
      let count = 0
      const services = formData.services
      if (services && typeof services === 'object') {
        Object.values(services).forEach((categoryServices: any) => {
          if (categoryServices && typeof categoryServices === 'object') {
            Object.values(categoryServices).forEach((service: any) => {
              if (service?.enabled) count++
            })
          }
        })
      }
      return count
    }

    // Специальная логика для удобств
    if (sectionKey === 'amenities') {
      let count = 0
      const amenities = formData.amenities
      if (amenities && typeof amenities === 'object') {
        Object.values(amenities).forEach((categoryAmenities: any) => {
          if (categoryAmenities && typeof categoryAmenities === 'object') {
            Object.values(categoryAmenities).forEach((amenity: any) => {
              if (amenity?.enabled) count++
            })
          }
        })
      }
      return count
    }

    return section.fields.filter(field => {
      const value = getNestedValue(formData, field)
      return value !== null && value !== undefined && value !== '' && 
             (Array.isArray(value) ? value.length > 0 : true)
    }).length
  }

  // Общий прогресс формы
  const formProgress = computed(() => {
    const totalFields = sections.reduce((acc, section) => acc + section.fields.length, 0)
    const filledFields = sections.reduce((acc, section) => acc + getFilledCount(section.key), 0)
    
    return totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0
  })

  return {
    sectionsState,
    toggleSection,
    expandAll,
    collapseAll,
    expandRequired,
    checkSectionFilled,
    getFilledCount,
    formProgress
  }
}
