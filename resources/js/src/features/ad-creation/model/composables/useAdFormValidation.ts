import type { AdForm, ValidationRule } from '../types'
import type { Ref } from 'vue'

/**
 * Composable для валидации формы объявления
 * KISS: Простая валидация без сложной логики
 */
export function useAdFormValidation() {
  
  // ✅ ПРАВИЛА ВАЛИДАЦИИ ДЛЯ ПУБЛИКАЦИИ
  const publicationRules: ValidationRule[] = [
    // Основная информация
    { field: 'title', rules: ['required'], message: 'Укажите заголовок объявления' },
    { field: 'category', rules: ['required'], message: 'Выберите категорию' },
    { field: 'description', rules: ['required'], message: 'Добавьте описание' },
    
    // Медиа
    { field: 'photos', rules: ['required'], message: 'Добавьте хотя бы одно фото' },
    
    // Контакты  
    { field: 'phone', rules: ['required', 'phone'], message: 'Укажите номер телефона' },
    
    // Локация
    { field: 'address', rules: ['required'], message: 'Укажите адрес' },
    
    // Услуги и клиенты
    { field: 'services', rules: ['required'], message: 'Выберите хотя бы одну услугу' },
    { field: 'clients', rules: ['required'], message: 'Укажите для кого услуги' }
  ]
  
  // ✅ ПРАВИЛА ВАЛИДАЦИИ ДЛЯ ЧЕРНОВИКА
  const draftRules: ValidationRule[] = [
    { field: 'title', rules: ['required'], message: 'Укажите заголовок для черновика' }
  ]
  
  // ✅ ОСНОВНАЯ ФУНКЦИЯ ВАЛИДАЦИИ
  const validateForm = (form: AdForm, isPublishing = false): Record<string, string[]> => {
    const errors: Record<string, string[]> = {}
    const rules = isPublishing ? publicationRules : draftRules
    
    for (const rule of rules) {
      const fieldValue = getFieldValue(form, rule.field)
      const fieldErrors = validateField(fieldValue, rule)
      
      if (fieldErrors.length > 0) {
        errors[rule.field] = fieldErrors
      }
    }
    
    return errors
  }
  
  // ✅ ВАЛИДАЦИЯ ОТДЕЛЬНОГО ПОЛЯ
  const validateField = (value: any, rule: ValidationRule): string[] => {
    const errors: string[] = []
    
    for (const ruleName of rule.rules) {
      switch (ruleName) {
        case 'required':
          if (!validateRequired(value)) {
            errors.push(rule.message || `Поле ${rule.field} обязательно`)
          }
          break
          
        case 'numeric':
          if (value && !validateNumeric(value)) {
            errors.push(rule.message || 'Значение должно быть числом')
          }
          break
          
        case 'min':
          if (value && rule.min && !validateMin(value, rule.min)) {
            errors.push(rule.message || `Минимальное значение: ${rule.min}`)
          }
          break
          
        case 'max':
          if (value && rule.max && !validateMax(value, rule.max)) {
            errors.push(rule.message || `Максимальное значение: ${rule.max}`)
          }
          break
          
        case 'email':
          if (value && !validateEmail(value)) {
            errors.push(rule.message || 'Неверный формат email')
          }
          break
          
        case 'phone':
          if (value && !validatePhone(value)) {
            errors.push(rule.message || 'Неверный формат телефона')
          }
          break
      }
    }
    
    return errors
  }
  
  // ✅ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ВАЛИДАЦИИ
  const validateRequired = (value: any): boolean => {
    if (value === null || value === undefined || value === '') {
      return false
    }
    
    if (Array.isArray(value)) {
      return value.length > 0
    }
    
    if (typeof value === 'object') {
      return Object.keys(value).length > 0
    }
    
    return true
  }
  
  const validateNumeric = (value: any): boolean => {
    return !isNaN(Number(value))
  }
  
  const validateMin = (value: any, min: number): boolean => {
    const num = Number(value)
    return !isNaN(num) && num >= min
  }
  
  const validateMax = (value: any, max: number): boolean => {
    const num = Number(value)
    return !isNaN(num) && num <= max
  }
  
  const validateEmail = (value: string): boolean => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(value)
  }
  
  const validatePhone = (value: string): boolean => {
    const phoneRegex = /^[\d\s\-\+\(\)]+$/
    const cleaned = value.replace(/\D/g, '')
    return phoneRegex.test(value) && cleaned.length >= 10
  }
  
  // ✅ ПОЛУЧЕНИЕ ЗНАЧЕНИЯ ПОЛЯ ПО ПУТИ
  const getFieldValue = (form: AdForm, path: string): any => {
    return path.split('.').reduce((obj: any, key) => obj?.[key], form)
  }
  
  // ✅ ПРОВЕРКА НАЛИЧИЯ ОШИБОК
  const hasErrors = (errors: Record<string, string[]>): boolean => {
    return Object.keys(errors).length > 0
  }
  
  // ✅ ПОЛУЧЕНИЕ ПЕРВОЙ ОШИБКИ
  const getFirstError = (errors: Record<string, string[]>): string | null => {
    const firstField = Object.keys(errors)[0]
    return firstField ? errors[firstField][0] : null
  }
  
  // ✅ ОЧИСТКА ОШИБКИ ПОЛЯ
  const clearFieldError = (errors: Record<string, string[]>, field: string): void => {
    delete errors[field]
  }
  
  return {
    validateForm,
    validateField,
    hasErrors,
    getFirstError,
    clearFieldError,
    publicationRules,
    draftRules
  }
}