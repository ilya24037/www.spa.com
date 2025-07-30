/**
 * Composable для управления формой объявления
 * Объединяет валидацию, отправку и автосохранение
 */

import { ref, reactive, computed } from 'vue'
import { validateAdForm } from '@/utils/formValidators'
import { createAd, updateAd, saveDraft, prepareFormData, loadDraftById } from '@/utils/adApi'
import { useAutosave } from '@/Composables/useAutosave'

export function useAdForm(initialData = {}, options = {}) {
  const {
    isEditMode = false,
    adId = null,
    autosaveEnabled = true,
    onSuccess = null,
    onError = null
  } = options

  // Состояние формы
  const form = reactive({
    // Новые поля согласно Avito
    title: initialData.title || '',
    specialty: initialData.specialty || '',
    clients: initialData.clients || [],
            service_location: initialData.service_location || [],
        outcall_locations: initialData.outcall_locations || [],
        taxi_option: initialData.taxi_option || '',
        work_format: initialData.work_format || '',
        has_girlfriend: initialData.has_girlfriend || false,
    experience: initialData.experience || '',
    education_level: initialData.education_level || '',
    features: initialData.features || {},
    additional_features: initialData.additional_features || '',
    
    // Цены (обновленные)
            price: String(initialData.price || ''),
        price_unit: initialData.price_unit || 'session',
        is_starting_price: initialData.is_starting_price || false,
                pricing_data: initialData.pricing_data || {},
        contacts_per_hour: initialData.contacts_per_hour || '',

        // Физические параметры
    age: initialData.age || '',
    height: initialData.height || '',
    weight: initialData.weight || '',
    breast_size: initialData.breast_size || '',
    hair_color: initialData.hair_color || '',
    eye_color: initialData.eye_color || '',
    appearance: initialData.appearance || '',
    nationality: initialData.nationality || '',
    
    // Акции (обновленные)
    new_client_discount: initialData.new_client_discount || '',
    gift: initialData.gift || '',
    
    // Медиа
    photos: initialData.photos || [],
    video: initialData.video || null,
    show_photos_in_gallery: initialData.show_photos_in_gallery || [],
    allow_download_photos: initialData.allow_download_photos || [],
    watermark_photos: initialData.watermark_photos || [],
    
    // География
    address: initialData.address || null,
    travel_area: initialData.travel_area || '',
    custom_travel_areas: initialData.custom_travel_areas || [],
    travel_radius: initialData.travel_radius || '',
    travel_fee: initialData.travel_fee || '',
    travel_time: initialData.travel_time || '',
    
    // Описание
    description: initialData.description || '',
    
    // Контакты
    phone: initialData.phone || '',
    contact_method: initialData.contact_method || 'messages',
    
    // Расписание
    schedule: initialData.schedule || {},
    
    // Категория
    category: initialData.category || '',
    
    // Прочие поля (для совместимости)
    service_provider: initialData.service_provider || [],
    discount: initialData.discount || '',
    promo_code: initialData.promo_code || '',
    has_special_offers: initialData.has_special_offers || [],
    special_offers_description: initialData.special_offers_description || '',
    main_service_name: initialData.main_service_name || '',
    main_service_price: initialData.main_service_price || '',
    main_service_price_unit: initialData.main_service_price_unit || 'service',
    additional_services: initialData.additional_services || [],
    show_price_list: initialData.show_price_list || [],
    show_prices_public: initialData.show_prices_public || [],
    negotiable_prices: initialData.negotiable_prices || [],
    currency: initialData.currency || 'RUB',
    education_level: initialData.education_level || '',
    university: initialData.university || '',
    specialization: initialData.specialization || '',
    graduation_year: initialData.graduation_year || '',
    courses: initialData.courses || [],
    has_certificates: initialData.has_certificates || [],
    certificate_photos: initialData.certificate_photos || [],
    experience_years: initialData.experience_years || '',
    work_history: initialData.work_history || '',
    
    // Модульные услуги (новая архитектура)
    services: initialData.services || {},
    services_additional_info: initialData.services_additional_info || '',
    
    // Особенности мастера (новая архитектура)
    features: initialData.features || {},
    medical_certificate: initialData.medical_certificate || '',
    works_during_period: initialData.works_during_period || '',
    additional_features: initialData.additional_features || '',
    
    // График работы (новая архитектура)
    schedule: initialData.schedule || {},
    schedule_notes: initialData.schedule_notes || ''
  })

  // Состояние валидации
  const errors = ref({})
  const isSubmitting = ref(false)
  const isValid = computed(() => Object.keys(errors.value).length === 0)

  // Автосохранение
  const autosave = useAutosave(ref(form), {
    enabled: autosaveEnabled,
    onSave: (timestamp) => {
      console.log('Автосохранение выполнено:', timestamp)
    },
    onError: (error) => {
      console.warn('Ошибка автосохранения:', error)
    }
  })

  /**
   * Валидировать форму
   */
  const validateForm = () => {
    const validation = validateAdForm(form)
    errors.value = validation.errors
    return validation.isValid
  }

  /**
   * Валидировать отдельное поле
   */
  const validateField = (fieldName) => {
    const validation = validateAdForm(form)
    
    if (validation.errors[fieldName]) {
      errors.value[fieldName] = validation.errors[fieldName]
    } else {
      delete errors.value[fieldName]
    }
    
    return !validation.errors[fieldName]
  }

  /**
   * Очистить ошибки
   */
  const clearErrors = () => {
    errors.value = {}
  }

  /**
   * Очистить ошибку конкретного поля
   */
  const clearFieldError = (fieldName) => {
    if (errors.value[fieldName]) {
      delete errors.value[fieldName]
    }
  }

  /**
   * Отправить форму
   */
  const submitForm = async () => {
    if (isSubmitting.value) return

    // Валидация
    if (!validateForm()) {
      console.warn('Форма содержит ошибки:', errors.value)
      return Promise.reject(new Error('Validation failed'))
    }

    isSubmitting.value = true

    try {
      // Подготавливаем данные для отправки
      const formData = prepareFormData(form)
      
      let result
      if (isEditMode && adId) {
        result = await updateAd(adId, formData)
      } else {
        result = await createAd(formData)
      }

      // Очищаем ошибки при успешной отправке
      clearErrors()

      // Вызываем коллбек успеха
      if (onSuccess) {
        onSuccess(result)
      }

      return result
    } catch (error) {
      console.error('Ошибка при отправке формы:', error)
      
      // Обрабатываем ошибки валидации с сервера
      if (error.response?.data?.errors) {
        errors.value = error.response.data.errors
      }

      // Вызываем коллбек ошибки
      if (onError) {
        onError(error)
      }

      throw error
    } finally {
      isSubmitting.value = false
    }
  }

  /**
   * Загрузить черновик
   */
  const loadDraft = async (draftId) => {
    try {
      const draft = await loadDraftById(draftId)
      
      // Обновляем форму данными из черновика
      Object.assign(form, draft)
      
      // Убеждаемся, что price всегда строка
      if (form.price !== undefined && form.price !== null) {
        form.price = String(form.price)
      }
      
      // Убеждаемся, что schedule всегда объект
      if (typeof form.schedule === 'string') {
        try {
          form.schedule = JSON.parse(form.schedule) || {}
        } catch (e) {
          form.schedule = {}
        }
      } else if (!form.schedule) {
        form.schedule = {}
      }
      
      return draft
    } catch (error) {
      console.error('Ошибка при загрузке черновика:', error)
      throw error
    }
  }

  /**
   * Сохранить черновик
   */
  const saveDraftForm = async () => {
    try {
      const formData = prepareFormData(form)
      const result = await saveDraft(formData)
      return result
    } catch (error) {
      console.error('Ошибка при сохранении черновика:', error)
      throw error
    }
  }

  /**
   * Сбросить форму
   */
  const resetForm = () => {
    Object.keys(form).forEach(key => {
      if (Array.isArray(form[key])) {
        form[key] = []
      } else if (typeof form[key] === 'object' && form[key] !== null) {
        form[key] = {}
      } else if (typeof form[key] === 'boolean') {
        form[key] = false
      } else {
        form[key] = ''
      }
    })
    clearErrors()
  }

  /**
   * Установить данные формы
   */
  const setFormData = (data) => {
    Object.assign(form, data)
    
    // Убеждаемся, что price всегда строка
    if (form.price !== undefined && form.price !== null) {
      form.price = String(form.price)
    }
    
    // Убеждаемся, что schedule всегда объект
    if (typeof form.schedule === 'string') {
      try {
        form.schedule = JSON.parse(form.schedule) || {}
      } catch (e) {
        form.schedule = {}
      }
    } else if (!form.schedule) {
      form.schedule = {}
    }
  }

  /**
   * Получить данные формы
   */
  const getFormData = () => {
    return { ...form }
  }

  return {
    // Состояние
    form,
    errors,
    isSubmitting,
    isValid,
    
    // Методы
    validateForm,
    validateField,
    clearErrors,
    clearFieldError,
    handleSubmit: submitForm,
    loadDraft,
    saveDraft: saveDraftForm,
    resetForm,
    setFormData,
    getFormData,
    
    // Автосохранение
    autosave
  }
} 