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
  // Состояние секций (открыт/закрыт)
  const sectionsState = ref<SectionState>(
    sections.reduce((acc, section) => {
      acc[section.key] = section.required // Обязательные секции открыты по умолчанию
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

  // Вспомогательная функция для получения вложенных значений
  const getNestedValue = (obj: any, path: string): any => {
    return path.split('.').reduce((current, key) => current?.[key], obj)
  }

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
