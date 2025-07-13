import { computed, ref } from 'vue'

export function useFormProgress(formData, sections = []) {
  /**
   * Проверяет, заполнено ли поле
   */
  const isFieldFilled = (value) => {
    if (value === null || value === undefined) return false
    if (typeof value === 'string') return value.trim().length > 0
    if (Array.isArray(value)) return value.length > 0
    if (typeof value === 'object') return Object.keys(value).length > 0
    if (typeof value === 'boolean') return true
    if (typeof value === 'number') return !isNaN(value) && value > 0
    return !!value
  }

  /**
   * Вычисляет прогресс для секции
   */
  const calculateSectionProgress = (section) => {
    if (!section.fields || section.fields.length === 0) return 0

    const filledFields = section.fields.filter(fieldKey => {
      const value = formData[fieldKey]
      return isFieldFilled(value)
    }).length

    return Math.round((filledFields / section.fields.length) * 100)
  }

  /**
   * Проверяет, завершена ли секция
   */
  const isSectionCompleted = (section) => {
    if (!section.requiredFields) return calculateSectionProgress(section) >= 70
    
    return section.requiredFields.every(fieldKey => {
      const value = formData[fieldKey]
      return isFieldFilled(value)
    })
  }

  /**
   * Прогресс по секциям
   */
  const sectionsProgress = computed(() => {
    return sections.map(section => ({
      ...section,
      progress: calculateSectionProgress(section),
      completed: isSectionCompleted(section)
    }))
  })

  /**
   * Общий прогресс формы
   */
  const overallProgress = computed(() => {
    if (sections.length === 0) return 0

    const totalProgress = sectionsProgress.value.reduce((sum, section) => {
      return sum + section.progress
    }, 0)

    return Math.round(totalProgress / sections.length)
  })

  /**
   * Количество завершённых секций
   */
  const completedSections = computed(() => {
    return sectionsProgress.value.filter(section => section.completed).length
  })

  /**
   * Следующая незавершённая секция
   */
  const nextSection = computed(() => {
    return sectionsProgress.value.find(section => !section.completed)
  })

  /**
   * Является ли форма готовой к отправке
   */
  const isFormReady = computed(() => {
    return overallProgress.value >= 80 && completedSections.value >= Math.ceil(sections.length * 0.6)
  })

  /**
   * Получает детальную информацию о прогрессе
   */
  const getProgressDetails = () => {
    return {
      overall: overallProgress.value,
      sections: sectionsProgress.value,
      completed: completedSections.value,
      total: sections.length,
      ready: isFormReady.value,
      next: nextSection.value
    }
  }

  /**
   * Валидирует обязательные поля
   */
  const validateRequired = () => {
    const errors = {}
    
    sections.forEach(section => {
      if (section.requiredFields) {
        section.requiredFields.forEach(fieldKey => {
          const value = formData[fieldKey]
          if (!isFieldFilled(value)) {
            errors[fieldKey] = `Поле "${section.name}" обязательно для заполнения`
          }
        })
      }
    })

    return {
      valid: Object.keys(errors).length === 0,
      errors
    }
  }

  /**
   * Получает рекомендации по заполнению
   */
  const getRecommendations = () => {
    const recommendations = []

    sectionsProgress.value.forEach(section => {
      if (!section.completed) {
        recommendations.push({
          section: section.name,
          message: `Завершите раздел "${section.name}" (${section.progress}% готово)`,
          priority: section.progress < 30 ? 'high' : section.progress < 70 ? 'medium' : 'low'
        })
      }
    })

    // Сортируем по приоритету
    return recommendations.sort((a, b) => {
      const priorityOrder = { high: 3, medium: 2, low: 1 }
      return priorityOrder[b.priority] - priorityOrder[a.priority]
    })
  }

  /**
   * Получает статистику заполнения
   */
  const getStats = () => {
    const allFields = sections.flatMap(s => s.fields || [])
    const filledFields = allFields.filter(fieldKey => isFieldFilled(formData[fieldKey]))

    return {
      totalFields: allFields.length,
      filledFields: filledFields.length,
      emptyFields: allFields.length - filledFields.length,
      fillRate: allFields.length > 0 ? Math.round((filledFields.length / allFields.length) * 100) : 0
    }
  }

  return {
    // Вычисляемые свойства
    sectionsProgress,
    overallProgress,
    completedSections,
    nextSection,
    isFormReady,

    // Методы
    calculateSectionProgress,
    isSectionCompleted,
    getProgressDetails,
    validateRequired,
    getRecommendations,
    getStats,
    isFieldFilled
  }
}

// Предустановленные конфигурации секций
export const massageFormSections = [
  {
    name: 'Личная информация',
    icon: '👤',
    fields: ['display_name', 'age', 'experience_years', 'salon_name'],
    requiredFields: ['display_name'],
    weight: 1
  },
  {
    name: 'Описание',
    icon: '📝', 
    fields: ['description'],
    requiredFields: ['description'],
    weight: 2
  },
  {
    name: 'Местоположение',
    icon: '📍',
    fields: ['city', 'district', 'address'],
    requiredFields: ['city'],
    weight: 1.5
  },
  {
    name: 'Контакты',
    icon: '📞',
    fields: ['phone', 'whatsapp', 'telegram', 'show_phone'],
    requiredFields: ['phone'],
    weight: 2
  },
  {
    name: 'Цены',
    icon: '💰',
    fields: ['price_from', 'price_to'],
    requiredFields: ['price_from'],
    weight: 1.5
  },
  {
    name: 'Услуги',
    icon: '🔧',
    fields: ['services'],
    requiredFields: ['services'],
    weight: 2
  },
  {
    name: 'Фотографии',
    icon: '📸',
    fields: ['photos'],
    requiredFields: [],
    weight: 1.5
  }
]