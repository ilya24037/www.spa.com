/**
 * Централизованное управление состоянием формы объявления
 * Pinia store для координации всех модулей формы (FSD архитектура)
 * 
 * Интегрированный функционал:
 * - Управление состоянием формы
 * - Валидация полей
 * - Отправка и обновление объявлений
 * - Автосохранение черновиков
 * - Загрузка данных
 */

import { defineStore } from 'pinia'
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { adApi } from '../../../api/adApi'
import { logger } from '@/src/shared/utils/logger'

export const useAdFormStore = defineStore('adForm', () => {
    // === СОСТОЯНИЕ ===
  
    // Данные формы (интегрированные из useAdForm композабла)
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

  // === ИНТЕГРИРОВАННЫЙ ФУНКЦИОНАЛ ИЗ useAdForm ===
  
  // Дополнительное состояние для валидации и отправки
  const isSubmitting = ref(false)
  const autosaveEnabled = ref(true)
  const autosaveTimer = ref(null)
  
  // === ВАЛИДАЦИЯ ===
  
  /**
   * Простая валидация формы (базовая проверка обязательных полей)
   */
  const validateForm = () => {
    const newErrors = {}
    
    // Обязательные поля
    const requiredFields = {
      'work_format': 'Выберите формат работы',
      'description': 'Введите описание',
      'service_location': 'Укажите место оказания услуг',
      'phone': 'Укажите телефон',
      'price': 'Укажите цену'
    }
    
    Object.entries(requiredFields).forEach(([field, message]) => {
      const value = formData[field]
      
      if (!value || 
          (Array.isArray(value) && value.length === 0) ||
          (typeof value === 'string' && value.trim() === '') ||
          (typeof value === 'object' && Object.keys(value).length === 0)) {
        newErrors[field] = message
      }
    })
    
    // Специальная валидация цены
    if (formData.price && isNaN(Number(formData.price))) {
      newErrors.price = 'Цена должна быть числом'
    }
    
    // Специальная валидация телефона
    if (formData.phone && !/^[\+]?[1-9][\d]{0,15}$/.test(formData.phone.replace(/[\s\-\(\)]/g, ''))) {
      newErrors.phone = 'Введите корректный номер телефона'
    }
    
    errors.value = newErrors
    return Object.keys(newErrors).length === 0
  }

  /**
   * Валидация отдельного поля
   */
  const validateField = (fieldName) => {
    const fieldErrors = { ...errors.value }
    
    const value = formData[fieldName]
    
    // Удаляем старую ошибку
    delete fieldErrors[fieldName]
    
    // Проверяем обязательные поля
    const requiredFields = ['work_format', 'description', 'service_location', 'phone', 'price']
    
    if (requiredFields.includes(fieldName)) {
      if (!value || 
          (Array.isArray(value) && value.length === 0) ||
          (typeof value === 'string' && value.trim() === '') ||
          (typeof value === 'object' && Object.keys(value).length === 0)) {
        
        const messages = {
          'work_format': 'Выберите формат работы',
          'description': 'Введите описание',
          'service_location': 'Укажите место оказания услуг',
          'phone': 'Укажите телефон',
          'price': 'Укажите цену'
        }
        
        fieldErrors[fieldName] = messages[fieldName]
      }
    }
    
    // Специальные валидации
    if (fieldName === 'price' && value && isNaN(Number(value))) {
      fieldErrors.price = 'Цена должна быть числом'
    }
    
    if (fieldName === 'phone' && value && !/^[\+]?[1-9][\d]{0,15}$/.test(value.replace(/[\s\-\(\)]/g, ''))) {
      fieldErrors.phone = 'Введите корректный номер телефона'
    }
    
    errors.value = fieldErrors
    return !fieldErrors[fieldName]
  }

  /**
   * Вычисляемое свойство - форма валидна
   */
  const isValid = computed(() => Object.keys(errors.value).length === 0)

  // === ОТПРАВКА И СОХРАНЕНИЕ ===

  /**
   * Отправка формы (создание или обновление объявления)
   */
  const submitForm = async (options = {}) => {
    if (isSubmitting.value) {
      logger.warn('Form submission already in progress', null, 'AdFormStore')
      return
    }

    const { 
      onSuccess = null, 
      onError = null,
      isEditMode = formData.is_edit_mode || false,
      adId = formData.ad_id || null
    } = options

    // Валидация перед отправкой
    if (!validateForm()) {
      const errorMessage = 'Заполните все обязательные поля'
      logger.warn('Form validation failed on submit', { errors: errors.value }, 'AdFormStore')
      
      if (onError) onError(new Error(errorMessage))
      return Promise.reject(new Error(errorMessage))
    }

    isSubmitting.value = true
    loading.value = true

    try {
      logger.info(`Submitting ad form - ${isEditMode ? 'update' : 'create'}`, { adId }, 'AdFormStore')

      // Подготавливаем данные для отправки
      const submitData = adApi.prepareFormData(formData)

      let result
      if (isEditMode && adId) {
        result = await adApi.updateAd(adId, submitData)
        logger.info('Ad updated successfully', { adId, result }, 'AdFormStore')
      } else {
        result = await adApi.createAd(submitData)
        logger.info('Ad created successfully', { result }, 'AdFormStore')
      }

      // Очищаем ошибки при успешной отправке
      clearErrors()

      // Обновляем состояние после успешной отправки
      if (result.data) {
        formData.ad_id = result.data.id
        formData.is_edit_mode = true
      }

      // Вызываем коллбек успеха
      if (onSuccess) {
        onSuccess(result)
      }

      return result
    } catch (error) {
      logger.error('Ad form submission failed', error, 'AdFormStore')

      // Обрабатываем ошибки валидации с сервера
      if (error.response?.data?.errors) {
        errors.value = { ...errors.value, ...error.response.data.errors }
      }

      // Вызываем коллбек ошибки
      if (onError) {
        onError(error)
      }

      throw error
    } finally {
      isSubmitting.value = false
      loading.value = false
    }
  }

  /**
   * Сохранение черновика
   */
  const saveDraft = async () => {
    if (saving.value) return

    saving.value = true

    try {
      logger.debug('Saving draft', null, 'AdFormStore')

      const draftData = adApi.prepareFormData(formData)
      const result = await adApi.saveDraft(draftData)

      // Обновляем ID черновика если получен новый
      if (result.data?.id && !formData.ad_id) {
        formData.ad_id = result.data.id
      }

      logger.debug('Draft saved successfully', { draftId: result.data?.id }, 'AdFormStore')
      
      return result
    } catch (error) {
      logger.error('Failed to save draft', error, 'AdFormStore')
      throw error
    } finally {
      saving.value = false
    }
  }

  /**
   * Загрузка черновика по ID
   */
  const loadDraft = async (draftId) => {
    loading.value = true

    try {
      logger.info('Loading draft', { draftId }, 'AdFormStore')

      const draft = await adApi.loadDraftById(draftId)
      
      // Обновляем форму данными из черновика
      Object.assign(formData, draft)

      // Обеспечиваем правильные типы данных
      if (formData.price !== undefined && formData.price !== null) {
        formData.price = String(formData.price)
      }

      // Парсим JSON поля если они пришли как строки
      if (typeof formData.schedule === 'string') {
        try {
          formData.schedule = JSON.parse(formData.schedule) || {}
        } catch (e) {
          formData.schedule = {}
          logger.warn('Failed to parse schedule JSON', e, 'AdFormStore')
        }
      } else if (!formData.schedule) {
        formData.schedule = {}
      }

      if (typeof formData.photos === 'string') {
        try {
          formData.photos = JSON.parse(formData.photos) || []
        } catch (e) {
          formData.photos = []
          logger.warn('Failed to parse photos JSON', e, 'AdFormStore')
        }
      } else if (!Array.isArray(formData.photos)) {
        formData.photos = []
      }

      // Устанавливаем режим редактирования
      formData.ad_id = draftId
      formData.is_edit_mode = true

      logger.info('Draft loaded successfully', { draftId }, 'AdFormStore')
      
      return draft
    } catch (error) {
      logger.error('Failed to load draft', error, 'AdFormStore')
      throw error
    } finally {
      loading.value = false
    }
  }

  // === АВТОСОХРАНЕНИЕ ===

  /**
   * Включение автосохранения
   */
  const enableAutosave = (interval = 30000) => {
    autosaveEnabled.value = true
    
    // Очищаем предыдущий таймер
    if (autosaveTimer.value) {
      clearInterval(autosaveTimer.value)
    }

    // Устанавливаем новый таймер
    autosaveTimer.value = setInterval(async () => {
      if (hasUnsavedChanges.value && !saving.value && !loading.value) {
        try {
          await saveDraft()
          logger.debug('Autosave completed', null, 'AdFormStore')
        } catch (error) {
          logger.warn('Autosave failed', error, 'AdFormStore')
        }
      }
    }, interval)

    logger.info('Autosave enabled', { interval }, 'AdFormStore')
  }

  /**
   * Отключение автосохранения
   */
  const disableAutosave = () => {
    autosaveEnabled.value = false
    
    if (autosaveTimer.value) {
      clearInterval(autosaveTimer.value)
      autosaveTimer.value = null
    }

    logger.info('Autosave disabled', null, 'AdFormStore')
  }

  // === ДОПОЛНИТЕЛЬНЫЕ УТИЛИТЫ ===



  /**
   * Проверка изменений в форме
   */
  const isDirty = computed(() => hasUnsavedChanges.value)

  // Автоматически включаем автосохранение при создании store
  enableAutosave()

  // === ПУБЛИЧНЫЙ API ===
  
  // Возвращаем публичный API
  return {
    // === ОСНОВНОЕ СОСТОЯНИЕ ===
    formData,
    errors,
    loading,
    saving,
    config,
    isSubmitting,
    autosaveEnabled,
    
    // === ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ===
    completionPercentage,
    isReadyToPublish,
    hasUnsavedChanges,
    isValid,
    isDirty,
    
    // === ОСНОВНЫЕ МЕТОДЫ (ОРИГИНАЛЬНЫЕ) ===
    initializeForm,
    updateField,
    setErrors,
    clearErrors,
    saveAsDraft,
    publishAd,
    uploadPhotos,
    
    // === ИНТЕГРИРОВАННЫЕ МЕТОДЫ ИЗ useAdForm ===
    // Валидация
    validateForm,
    validateField,
    
    // Отправка и сохранение  
    submitForm,
    saveDraft,
    loadDraft,
    
    // Автосохранение
    enableAutosave,
    disableAutosave,
    
    // Утилиты
    resetForm
  }
})