/**
 * Композабл для валидации полей формы
 * Предоставляет унифицированную систему валидации
 */

import { ref, computed, watch } from 'vue'

export function useFieldValidation() {
  const errors = ref({})
  const touchedFields = ref(new Set())

  /**
   * Правила валидации
   */
  const validationRules = {
    required: (value, message = 'Поле обязательно для заполнения') => {
      if (Array.isArray(value)) {
        return value.length > 0 ? null : message
      }
      if (typeof value === 'object' && value !== null) {
        return Object.keys(value).length > 0 ? null : message
      }
      return value && value.toString().trim() ? null : message
    },

    minLength: (value, min, message) => {
      if (!value) return null
      const actualMessage = message || `Минимальная длина: ${min} символов`
      return value.toString().length >= min ? null : actualMessage
    },

    maxLength: (value, max, message) => {
      if (!value) return null
      const actualMessage = message || `Максимальная длина: ${max} символов`
      return value.toString().length <= max ? null : actualMessage
    },

    min: (value, min, message) => {
      if (!value) return null
      const actualMessage = message || `Минимальное значение: ${min}`
      return Number(value) >= min ? null : actualMessage
    },

    max: (value, max, message) => {
      if (!value) return null
      const actualMessage = message || `Максимальное значение: ${max}`
      return Number(value) <= max ? null : actualMessage
    },

    email: (value, message = 'Введите корректный email') => {
      if (!value) return null
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      return emailRegex.test(value) ? null : message
    },

    phone: (value, message = 'Введите корректный номер телефона') => {
      if (!value) return null
      const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/
      return phoneRegex.test(value.replace(/[\s\-\(\)]/g, '')) ? null : message
    },

    url: (value, message = 'Введите корректный URL') => {
      if (!value) return null
      try {
        new URL(value)
        return null
      } catch {
        return message
      }
    },

    custom: (value, validator, message = 'Значение не соответствует требованиям') => {
      if (!value) return null
      return validator(value) ? null : message
    }
  }

  /**
   * Валидация одного поля
   */
  function validateField(fieldName, value, rules = []) {
    const fieldErrors = []

    rules.forEach(rule => {
      let error = null

      if (typeof rule === 'string') {
        // Простое правило: 'required', 'email' и т.д.
        error = validationRules[rule]?.(value)
      } else if (typeof rule === 'object') {
        // Правило с параметрами: { type: 'minLength', value: 5, message: 'Минимум 5 символов' }
        const { type, value: ruleValue, message } = rule
        error = validationRules[type]?.(value, ruleValue, message)
      } else if (typeof rule === 'function') {
        // Кастомная функция валидации
        error = rule(value)
      }

      if (error) {
        fieldErrors.push(error)
      }
    })

    // Обновляем ошибки для поля
    if (fieldErrors.length > 0) {
      errors.value[fieldName] = fieldErrors[0] // Показываем только первую ошибку
    } else {
      delete errors.value[fieldName]
    }

    return fieldErrors.length === 0
  }

  /**
   * Валидация всех полей
   */
  function validateAll(fieldsConfig) {
    let isValid = true
    
    Object.keys(fieldsConfig).forEach(fieldName => {
      const { value, rules } = fieldsConfig[fieldName]
      const fieldValid = validateField(fieldName, value, rules)
      if (!fieldValid) {
        isValid = false
      }
    })

    return isValid
  }

  /**
   * Пометить поле как затронутое
   */
  function touchField(fieldName) {
    touchedFields.value.add(fieldName)
  }

  /**
   * Очистить ошибки поля
   */
  function clearFieldError(fieldName) {
    delete errors.value[fieldName]
  }

  /**
   * Очистить все ошибки
   */
  function clearAllErrors() {
    errors.value = {}
  }

  /**
   * Проверить, есть ли ошибка у поля
   */
  function hasFieldError(fieldName) {
    return Boolean(errors.value[fieldName])
  }

  /**
   * Получить ошибку поля
   */
  function getFieldError(fieldName) {
    return errors.value[fieldName] || null
  }

  /**
   * Проверить, была ли форма затронута
   */
  function isFieldTouched(fieldName) {
    return touchedFields.value.has(fieldName)
  }

  /**
   * Количество ошибок
   */
  const errorCount = computed(() => {
    return Object.keys(errors.value).length
  })

  /**
   * Есть ли ошибки
   */
  const hasErrors = computed(() => {
    return errorCount.value > 0
  })

  /**
   * Список всех ошибок
   */
  const allErrors = computed(() => {
    return Object.values(errors.value)
  })

  /**
   * Создание реактивного валидатора поля
   */
  function createFieldValidator(fieldName, rules) {
    return {
      validate: (value) => validateField(fieldName, value, rules),
      touch: () => touchField(fieldName),
      clear: () => clearFieldError(fieldName),
      hasError: computed(() => hasFieldError(fieldName)),
      error: computed(() => getFieldError(fieldName)),
      isTouched: computed(() => isFieldTouched(fieldName))
    }
  }

  return {
    // Состояние
    errors,
    touchedFields,
    errorCount,
    hasErrors,
    allErrors,

    // Методы
    validateField,
    validateAll,
    touchField,
    clearFieldError,
    clearAllErrors,
    hasFieldError,
    getFieldError,
    isFieldTouched,
    createFieldValidator,

    // Правила (для расширения)
    validationRules
  }
}