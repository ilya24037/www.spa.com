/**
 * Централизованное управление состоянием формы объявления
 * Pinia store для координации всех модулей формы (FSD архитектура)
 */

import { defineStore } from 'pinia'
import { ref, reactive, computed } from 'vue'
import { adApi } from '../../../api/adApi'

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
    travel_area: '',
    phone: '',
    contact_method: 'messages',
    whatsapp: '',
    telegram: '',
    
    // Способы оплаты
    payment_methods: [],
    
    // Медиа
    photos: [],
    video: null,
    
    // Метаданные
    category: '',
    ad_id: null,
    is_edit_mode: false
  })
  
  // Ошибки валидации
  const errors = reactive({})
  
  // Состояние загрузки
  const loading = ref(false)
  const saving = ref(false)
  
  // Настройки
  const config = reactive({
    category: '',
    categories: [],
    adId: null,
    autosaveEnabled: true,
    autosaveInterval: 30000 // 30 секунд
  })
  
  // === ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ===
  
  /**
   * Процент заполненности формы
   */
  const completionPercentage = computed(() => {
    const requiredFields = [
      'work_format', 'description', 'service_location', 
      'phone', 'contact_method', 'price'
    ]
    
    const filledRequiredFields = requiredFields.filter(field => {
      const value = formData[field]
      if (Array.isArray(value)) return value.length > 0
      if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0
      return value !== '' && value !== null && value !== undefined
    }).length
    
    const optionalFields = Object.keys(formData).filter(field => 
      !requiredFields.includes(field) && 
      field !== 'category' && 
      field !== 'ad_id' && 
      field !== 'is_edit_mode'
    )
    
    const filledOptionalFields = optionalFields.filter(field => {
      const value = formData[field]
      if (Array.isArray(value)) return value.length > 0
      if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0
      return value !== '' && value !== null && value !== undefined
    }).length
    
    // Обязательные поля весят больше
    const requiredWeight = 0.7
    const optionalWeight = 0.3
    
    const requiredScore = (filledRequiredFields / requiredFields.length) * requiredWeight
    const optionalScore = (filledOptionalFields / optionalFields.length) * optionalWeight
    
    return Math.round((requiredScore + optionalScore) * 100)
  })
  
  /**
   * Проверка готовности к публикации
   */
  const isReadyToPublish = computed(() => {
    const requiredFields = ['work_format', 'description', 'service_location', 'phone', 'price']
    return requiredFields.every(field => {
      const value = formData[field]
      if (Array.isArray(value)) return value.length > 0
      return value !== '' && value !== null && value !== undefined
    })
  })
  
  /**
   * Есть ли несохраненные изменения
   */
  const hasUnsavedChanges = computed(() => {
    // Логика определения несохраненных изменений
    return true // TODO: Реализовать сравнение с последним сохраненным состоянием
  })
  
  // === МЕТОДЫ ===
  
  /**
   * Инициализация формы
   */
  const initializeForm = (initialData = {}, options = {}) => {
    // Сбрасываем состояние
    Object.keys(errors).forEach(key => delete errors[key])
    loading.value = false
    saving.value = false
    
    // Устанавливаем конфигурацию
    Object.assign(config, options)
    
    // Заполняем форму данными
    Object.assign(formData, {
      category: options.category || '',
      ad_id: options.adId || null,
      is_edit_mode: !!options.adId,
      ...initialData
    })
    
    // Обрабатываем JSON поля
    if (typeof initialData.schedule === 'string') {
      try {
        formData.schedule = JSON.parse(initialData.schedule)
      } catch (e) {
        formData.schedule = {}
      }
    }
    
    if (typeof initialData.photos === 'string') {
      try {
        formData.photos = JSON.parse(initialData.photos)
      } catch (e) {
        formData.photos = []
      }
    }
  }
  
  /**
   * Обновление поля формы
   */
  const updateField = (fieldName, value) => {
    formData[fieldName] = value
    
    // Очищаем ошибку для этого поля
    if (errors[fieldName]) {
      delete errors[fieldName]
    }
  }
  
  /**
   * Установка ошибок валидации
   */
  const setErrors = (validationErrors) => {
    Object.keys(errors).forEach(key => delete errors[key])
    Object.assign(errors, validationErrors)
  }
  
  /**
   * Очистка ошибок
   */
  const clearErrors = () => {
    Object.keys(errors).forEach(key => delete errors[key])
  }
  
  /**
   * Сохранение черновика
   */
  const saveAsDraft = async () => {
    saving.value = true
    clearErrors()
    
    try {
      const preparedData = adApi.prepareFormData(formData)
      
      let response
      if (formData.is_edit_mode && formData.ad_id) {
        response = await adApi.updateDraft(formData.ad_id, preparedData)
      } else {
        response = await adApi.saveDraft(preparedData)
      }
      
      // Обновляем ID если это новое объявление
      if (response.data && response.data.id && !formData.ad_id) {
        formData.ad_id = response.data.id
        formData.is_edit_mode = true
      }
      
      return response
    } catch (error) {
      if (error.response && error.response.data && error.response.data.errors) {
        setErrors(error.response.data.errors)
      }
      throw error
    } finally {
      saving.value = false
    }
  }
  
  /**
   * Публикация объявления
   */
  const publishAd = async () => {
    saving.value = true
    clearErrors()
    
    try {
      const preparedData = adApi.prepareFormData(formData)
      
      let response
      if (formData.is_edit_mode && formData.ad_id) {
        response = await adApi.publishAd(formData.ad_id, preparedData)
      } else {
        response = await adApi.createAd(preparedData)
      }
      
      return response
    } catch (error) {
      if (error.response && error.response.data && error.response.data.errors) {
        setErrors(error.response.data.errors)
      }
      throw error
    } finally {
      saving.value = false
    }
  }
  
  /**
   * Загрузка фотографий
   */
  const uploadPhotos = async (files) => {
    try {
      const response = await adApi.uploadPhotos(files, formData.ad_id)
      
      // Обновляем массив фотографий
      if (response.data && response.data.photos) {
        formData.photos = response.data.photos
      }
      
      return response
    } catch (error) {
      throw error
    }
  }
  
  /**
   * Сброс формы
   */
  const resetForm = () => {
    Object.keys(formData).forEach(key => {
      if (Array.isArray(formData[key])) {
        formData[key] = []
      } else if (typeof formData[key] === 'object' && formData[key] !== null) {
        formData[key] = {}
      } else {
        formData[key] = ''
      }
    })
    
    clearErrors()
    loading.value = false
    saving.value = false
  }
  
  // Возвращаем публичный API
  return {
    // Состояние
    formData,
    errors,
    loading,
    saving,
    config,
    
    // Вычисляемые свойства
    completionPercentage,
    isReadyToPublish,
    hasUnsavedChanges,
    
    // Методы
    initializeForm,
    updateField,
    setErrors,
    clearErrors,
    saveAsDraft,
    publishAd,
    uploadPhotos,
    resetForm
  }
})