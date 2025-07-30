/**
 * Базовый композабл для модулей формы
 * Предоставляет общую логику для всех модулей
 */

import { ref, watch, computed } from 'vue'

export function useFormModule(props, emit, options = {}) {
  const {
    trackChanges = true,
    validateOnChange = false,
    debounceMs = 300
  } = options

  // Отслеживание изменений
  const hasChanges = ref(false)
  const originalData = ref(null)

  // Сохранение оригинальных данных при инициализации
  if (trackChanges && !originalData.value) {
    originalData.value = JSON.stringify(extractFormData(props))
  }

  /**
   * Извлечение данных формы из props
   */
  function extractFormData(data) {
    const formData = {}
    Object.keys(data).forEach(key => {
      if (key !== 'errors' && key !== 'loading') {
        formData[key] = data[key]
      }
    })
    return formData
  }

  /**
   * Создание локального состояния из props
   */
  function createLocalState(propsList) {
    const localState = {}
    propsList.forEach(propName => {
      localState[propName] = ref(props[propName])
    })
    return localState
  }

  /**
   * Настройка отслеживания изменений props
   */
  function watchProps(propsList, localState) {
    propsList.forEach(propName => {
      watch(() => props[propName], (newValue) => {
        localState[propName].value = newValue
      }, { deep: true })
    })
  }

  /**
   * Создание функций обновления
   */
  function createUpdateFunctions(propsList, localState) {
    const updateFunctions = {}
    
    propsList.forEach(propName => {
      const updateFunctionName = `update${propName.charAt(0).toUpperCase() + propName.slice(1)}`
      
      updateFunctions[updateFunctionName] = () => {
        emit(`update:${propName}`, localState[propName].value)
        
        if (trackChanges) {
          checkForChanges()
        }
      }
    })
    
    return updateFunctions
  }

  /**
   * Проверка наличия изменений
   */
  function checkForChanges() {
    const currentData = JSON.stringify(extractFormData(props))
    hasChanges.value = currentData !== originalData.value
  }

  /**
   * Debounced версия функции
   */
  function debounce(func, wait) {
    let timeout
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout)
        func(...args)
      }
      clearTimeout(timeout)
      timeout = setTimeout(later, wait)
    }
  }

  /**
   * Валидация поля
   */
  function validateField(fieldName, value, rules = {}) {
    const errors = []
    
    // Обязательное поле
    if (rules.required && (!value || (Array.isArray(value) && value.length === 0))) {
      errors.push(`Поле "${fieldName}" обязательно для заполнения`)
    }
    
    // Минимальная длина
    if (rules.minLength && value && value.length < rules.minLength) {
      errors.push(`Минимальная длина: ${rules.minLength} символов`)
    }
    
    // Максимальная длина
    if (rules.maxLength && value && value.length > rules.maxLength) {
      errors.push(`Максимальная длина: ${rules.maxLength} символов`)
    }
    
    // Диапазон чисел
    if (rules.min && Number(value) < rules.min) {
      errors.push(`Минимальное значение: ${rules.min}`)
    }
    
    if (rules.max && Number(value) > rules.max) {
      errors.push(`Максимальное значение: ${rules.max}`)
    }
    
    return errors
  }

  /**
   * Вычисление процента заполнения модуля
   */
  const completionPercentage = computed(() => {
    if (!trackChanges) return 0
    
    const formData = extractFormData(props)
    const totalFields = Object.keys(formData).length
    
    if (totalFields === 0) return 0
    
    const filledFields = Object.values(formData).filter(value => {
      if (Array.isArray(value)) return value.length > 0
      if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0
      return value !== '' && value !== null && value !== undefined
    }).length
    
    return Math.round((filledFields / totalFields) * 100)
  })

  /**
   * Сброс формы к изначальному состоянию
   */
  function resetForm() {
    if (originalData.value) {
      const original = JSON.parse(originalData.value)
      Object.keys(original).forEach(key => {
        emit(`update:${key}`, original[key])
      })
      hasChanges.value = false
    }
  }

  /**
   * Проверка валидности модуля
   */
  const isValid = computed(() => {
    return !props.errors || Object.keys(props.errors).length === 0
  })

  return {
    // Состояние
    hasChanges,
    isValid,
    completionPercentage,
    
    // Методы
    createLocalState,
    watchProps,
    createUpdateFunctions,
    debounce,
    validateField,
    resetForm,
    extractFormData,
    checkForChanges
  }
}