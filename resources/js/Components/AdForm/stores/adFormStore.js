/**
 * Централизованное управление состоянием формы объявления
 * Pinia store для координации всех модулей формы
 */

import { defineStore } from 'pinia'
import { ref, reactive, computed } from 'vue'
import { useFieldValidation } from '../composables/useFieldValidation'
import { prepareFormData, saveDraft, publishAd } from '@/utils/adApi'

export const useAdFormStore = defineStore('adForm', () => {
  // === СОСТОЯНИЕ ===
  
  // Данные формы (зеркало useAdForm композабла)
  const formData = reactive({
    // Базовая информация
    work_format: '',
    has_girlfriend: false,
    service_provider: [],
    clients: [],
    description: '',
    
    // Персональная информация
    age: '',
    height: '',
    weight: '',
    breast_size: '',
    hair_color: '',
    eye_color: '',
    appearance: '',
    nationality: '',
    features: {},
    additional_features: '',
    experience: '',
    
    // Коммерческая информация
    price: '',
    price_unit: 'service',
    is_starting_price: false,
    pricing_data: {},
    contacts_per_hour: '',
    new_client_discount: '',
    gift: '',
    services: {},
    services_additional_info: '',
    schedule: {},
    schedule_notes: '',
    
    // Детальные цены
    express_price: '',
    price_per_hour: '',
    outcall_price: '',
    price_two_hours: '',
    price_night: '',
    
    // Прайс-лист услуг
    main_service_name: '',
    main_service_price: '',
    main_service_price_unit: 'час',
    additional_services: [],
    
    // Локация и контакты
    service_location: [],
    outcall_locations: [],
    address: '',
    taxi_option: '',
    geo: {},
    phone: '',
    contact_method: 'messages',
    whatsapp: '',
    telegram: '',
    
    // Способы оплаты
    payment_methods: [],
    
    // Медиа
    photos: [],
    video: null,
    
    // Мета-информация
    category: '',
    specialty: ''
  })

  // Состояние формы
  const isLoading = ref(false)
  const isSaving = ref(false)
  const hasUnsavedChanges = ref(false)
  const lastSavedAt = ref(null)
  
  // ID объявления (для редактирования)
  const adId = ref(null)
  const isEditMode = computed(() => Boolean(adId.value))

  // Прогресс заполнения
  const currentStep = ref(1)
  const totalSteps = ref(5)
  
  // Валидация
  const validation = useFieldValidation()

  // === COMPUTED ===
  
  /**
   * Процент заполнения формы
   */
  const completionPercentage = computed(() => {
    const totalFields = Object.keys(formData).filter(key => 
      !['category', 'specialty'].includes(key)
    ).length
    
    const filledFields = Object.values(formData).filter(value => {
      if (Array.isArray(value)) return value.length > 0
      if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0
      return value !== '' && value !== null && value !== undefined
    }).length
    
    return totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0
  })

  /**
   * Группированные данные по модулям
   */
  const moduleData = computed(() => ({
    basicInfo: {
      work_format: formData.work_format,
      has_girlfriend: formData.has_girlfriend,
      service_provider: formData.service_provider,
      clients: formData.clients,
      description: formData.description
    },
    personalInfo: {
      age: formData.age,
      height: formData.height,
      weight: formData.weight,
      breast_size: formData.breast_size,
      hair_color: formData.hair_color,
      eye_color: formData.eye_color,
      appearance: formData.appearance,
      nationality: formData.nationality,
      features: formData.features,
      additional_features: formData.additional_features,
      experience: formData.experience,
      education_level: formData.education_level
    },
    businessInfo: {
      price: formData.price,
      price_unit: formData.price_unit,
      is_starting_price: formData.is_starting_price,
      pricing_data: formData.pricing_data,
      contacts_per_hour: formData.contacts_per_hour,
      new_client_discount: formData.new_client_discount,
      gift: formData.gift,
      services: formData.services,
      services_additional_info: formData.services_additional_info,
      schedule: formData.schedule,
      schedule_notes: formData.schedule_notes
    },
    locationInfo: {
      service_location: formData.service_location,
      outcall_locations: formData.outcall_locations,
      address: formData.address,
      taxi_option: formData.taxi_option,
      geo: formData.geo,
      phone: formData.phone,
      contact_method: formData.contact_method,
      whatsapp: formData.whatsapp,
      telegram: formData.telegram
    },
    mediaInfo: {
      photos: formData.photos,
      video: formData.video
    }
  }))

  /**
   * Процент заполнения по модулям
   */
  const moduleCompletionPercentage = computed(() => {
    const result = {}
    
    Object.keys(moduleData.value).forEach(moduleName => {
      const module = moduleData.value[moduleName]
      const totalFields = Object.keys(module).length
      
      const filledFields = Object.values(module).filter(value => {
        if (Array.isArray(value)) return value.length > 0
        if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0
        return value !== '' && value !== null && value !== undefined
      }).length
      
      result[moduleName] = totalFields > 0 ? Math.round((filledFields / totalFields) * 100) : 0
    })
    
    return result
  })

  /**
   * Готовность к публикации
   */
  const canPublish = computed(() => {
    const requiredFields = [
      'work_format', 'service_provider', 'clients', 'description',
      'experience', 'price', 'service_location', 'phone'
    ]
    
    return requiredFields.every(field => {
      const value = formData[field]
      if (Array.isArray(value)) return value.length > 0
      return value !== '' && value !== null && value !== undefined
    }) && !validation.hasErrors.value
  })

  // === МЕТОДЫ ===

  /**
   * Инициализация формы
   */
  function initializeForm(initialData = {}, options = {}) {
    // Сброс состояния
    isLoading.value = true
    validation.clearAllErrors()
    
    // Заполнение данных с безопасными значениями
    Object.keys(formData).forEach(key => {
      if (initialData[key] !== undefined && initialData[key] !== null) {
        // Особая обработка для массивов
        if (Array.isArray(formData[key])) {
          formData[key] = Array.isArray(initialData[key]) ? initialData[key] : []
        }
        // Особая обработка для объектов
        else if (typeof formData[key] === 'object' && formData[key] !== null) {
          // Специальная обработка для JSON полей которые могут прийти как строки
          if (typeof initialData[key] === 'string' && initialData[key].trim().startsWith('{')) {
            try {
              formData[key] = JSON.parse(initialData[key])
            } catch (e) {
              formData[key] = formData[key] || {}
            }
          } else {
            formData[key] = (typeof initialData[key] === 'object' && initialData[key] !== null) 
              ? initialData[key] 
              : (formData[key] || {})
          }
        }
        // Остальные типы
        else {
          formData[key] = initialData[key]
        }
      }
      // Специальная обработка для строковых полей (цены, текст) - даже если null, конвертируем в строку
      else if (typeof formData[key] === 'string') {
        formData[key] = String(initialData[key] || '')
      }
    })
    
      // Дополнительная обработка для полей цены - ensure они строки
  const priceFields = ['price_per_hour', 'outcall_price', 'express_price', 'price_two_hours', 'price_night', 'min_duration']
  priceFields.forEach(field => {
    if (formData[field] !== undefined) {
      formData[field] = String(formData[field] || '')
    }
  })
  

    
    // Настройка опций
    if (options.adId) {
      adId.value = options.adId
    }
    
    if (options.category) {
      formData.category = options.category
    }
    
    hasUnsavedChanges.value = false
    isLoading.value = false
  }

  /**
   * Обновление поля
   */
  function updateField(fieldName, value) {
    // Безопасное значение для массивов
    let safeValue = value
    if (Array.isArray(formData[fieldName]) && !Array.isArray(value)) {
      safeValue = value === null || value === undefined ? [] : value
    }
    
    if (formData[fieldName] !== safeValue) {
      formData[fieldName] = safeValue
      hasUnsavedChanges.value = true
      
      // Валидация поля при изменении
      validateField(fieldName, safeValue)
    }
  }

  /**
   * Обновление модуля целиком
   */
  function updateModule(moduleName, moduleData) {
    const module = moduleData[moduleName]
    if (module) {
      Object.keys(module).forEach(key => {
        updateField(key, module[key])
      })
    }
  }

  /**
   * Валидация поля
   */
  function validateField(fieldName, value) {
    // Правила валидации для разных полей
    const fieldRules = {
      description: ['required', { type: 'minLength', value: 50 }],
      phone: ['required', 'phone'],
      price: ['required', { type: 'min', value: 1 }],
      age: [{ type: 'min', value: 18 }, { type: 'max', value: 65 }],
      height: [{ type: 'min', value: 140 }, { type: 'max', value: 200 }],
      weight: [{ type: 'min', value: 40 }, { type: 'max', value: 150 }]
    }
    
    const rules = fieldRules[fieldName] || []
    return validation.validateField(fieldName, value, rules)
  }

  /**
   * Валидация всей формы
   */
  function validateForm() {
    const fieldsToValidate = {
      description: { value: formData.description, rules: ['required', { type: 'minLength', value: 50 }] },
      phone: { value: formData.phone, rules: ['required', 'phone'] },
      price: { value: formData.price, rules: ['required', { type: 'min', value: 1 }] },
      work_format: { value: formData.work_format, rules: ['required'] },
      service_provider: { value: formData.service_provider, rules: ['required'] },
      clients: { value: formData.clients, rules: ['required'] },
      service_location: { value: formData.service_location, rules: ['required'] },
      experience: { value: formData.experience, rules: ['required'] }
    }
    
    return validation.validateAll(fieldsToValidate)
  }

  /**
   * Сохранение черновика
   */
  async function saveAsDraft() {
    if (isSaving.value) return false
    
    isSaving.value = true
    
    try {
      const preparedData = prepareFormData(formData)
      
      // Передаем ID черновика если редактируем существующий
      const response = await saveDraft(preparedData, adId.value)
      
      // Если это новый черновик - сохраняем его ID
      if (response && response.id && !adId.value) {
        adId.value = response.id
      }
      
      hasUnsavedChanges.value = false
      lastSavedAt.value = new Date()
      
      return response
    } catch (error) {
      console.error('Ошибка сохранения черновика:', error)
      throw error
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Публикация объявления
   */
  async function publish() {
    if (isSaving.value) return false
    
    // Валидация перед публикацией
    if (!validateForm()) {
      throw new Error('Форма содержит ошибки')
    }
    
    if (!canPublish.value) {
      throw new Error('Не все обязательные поля заполнены')
    }
    
    isSaving.value = true
    
    try {
      const preparedData = prepareFormData(formData)
      const response = await publishAd(preparedData)
      
      hasUnsavedChanges.value = false
      lastSavedAt.value = new Date()
      
      return response
    } catch (error) {
      console.error('Ошибка публикации объявления:', error)
      throw error
    } finally {
      isSaving.value = false
    }
  }

  /**
   * Сброс формы
   */
  function resetForm() {
    Object.keys(formData).forEach(key => {
      if (Array.isArray(formData[key])) {
        formData[key] = []
      } else if (typeof formData[key] === 'object' && formData[key] !== null) {
        formData[key] = {}
      } else if (typeof formData[key] === 'boolean') {
        formData[key] = false
      } else {
        formData[key] = ''
      }
    })
    
    validation.clearAllErrors()
    hasUnsavedChanges.value = false
    lastSavedAt.value = null
    currentStep.value = 1
  }

  /**
   * Переход к следующему шагу
   */
  function nextStep() {
    if (currentStep.value < totalSteps.value) {
      currentStep.value++
    }
  }

  /**
   * Переход к предыдущему шагу
   */
  function previousStep() {
    if (currentStep.value > 1) {
      currentStep.value--
    }
  }

  /**
   * Переход к конкретному шагу
   */
  function goToStep(step) {
    if (step >= 1 && step <= totalSteps.value) {
      currentStep.value = step
    }
  }

  return {
    // Состояние
    formData,
    isLoading,
    isSaving,
    hasUnsavedChanges,
    lastSavedAt,
    adId,
    isEditMode,
    currentStep,
    totalSteps,
    
    // Computed
    completionPercentage,
    moduleData,
    moduleCompletionPercentage,
    canPublish,
    
    // Валидация
    errors: validation.errors,
    hasErrors: validation.hasErrors,
    
    // Методы
    initializeForm,
    updateField,
    updateModule,
    validateField,
    validateForm,
    saveAsDraft,
    publish,
    resetForm,
    nextStep,
    previousStep,
    goToStep
  }
})