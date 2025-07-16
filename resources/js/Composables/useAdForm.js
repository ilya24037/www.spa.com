/**
 * Composable для управления формой объявления
 * Объединяет валидацию, отправку и автосохранение
 */

import { ref, reactive, computed } from 'vue'
import { validateAdForm } from '@/utils/formValidators'
import { createAd, updateAd, prepareFormData } from '@/utils/adApi'
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
    // Основные детали
    title: initialData.title || '',
    specialty: initialData.specialty || '',
    clients: initialData.clients || [],
    service_location: initialData.service_location || [],
    work_format: initialData.work_format || '',
    service_provider: initialData.service_provider || [],
    experience: initialData.experience || '',
    
    // Описание
    description: initialData.description || '',
    
    // Цены
    price: initialData.price || '',
    price_unit: initialData.price_unit || 'service',
    is_starting_price: initialData.is_starting_price || [],
    
    // Акции
    discount: initialData.discount || '',
    gift: initialData.gift || '',
    promo_code: initialData.promo_code || '',
    has_special_offers: initialData.has_special_offers || [],
    special_offers_description: initialData.special_offers_description || '',
    
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
    
    // Прайс-лист
    main_service_name: initialData.main_service_name || '',
    main_service_price: initialData.main_service_price || '',
    main_service_price_unit: initialData.main_service_price_unit || 'service',
    additional_services: initialData.additional_services || [],
    show_price_list: initialData.show_price_list || [],
    show_prices_public: initialData.show_prices_public || [],
    negotiable_prices: initialData.negotiable_prices || [],
    currency: initialData.currency || 'RUB',
    
    // Образование
    education_level: initialData.education_level || '',
    university: initialData.university || '',
    specialization: initialData.specialization || '',
    graduation_year: initialData.graduation_year || '',
    courses: initialData.courses || [],
    has_certificates: initialData.has_certificates || [],
    certificate_photos: initialData.certificate_photos || [],
    experience_years: initialData.experience_years || '',
    work_history: initialData.work_history || '',
    
    // Контакты
    phone: initialData.phone || '',
    contact_method: initialData.contact_method || 'messages'
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
      return false
    }

    try {
      isSubmitting.value = true
      
      const formData = prepareFormData(form)
      
      let result
      if (isEditMode && adId) {
        result = await updateAd(adId, formData)
      } else {
        result = await createAd(formData)
      }

      // Остановить автосохранение после успешной отправки
      autosave.stopAutosave()
      autosave.reset()

      if (onSuccess) {
        onSuccess(result)
      }

      return true
    } catch (error) {
      if (error && typeof error === 'object') {
        errors.value = error
      }

      if (onError) {
        onError(error)
      }

      return false
    } finally {
      isSubmitting.value = false
    }
  }

  /**
   * Сохранить и выйти (черновик)
   */
  const saveAndExit = async () => {
    await autosave.forceSave()
    
    // Перейти на страницу профиля
    window.location.href = '/profile'
  }

  /**
   * Сбросить форму
   */
  const resetForm = () => {
    Object.keys(form).forEach(key => {
      if (Array.isArray(form[key])) {
        form[key] = []
      } else {
        form[key] = ''
      }
    })
    
    form.price_unit = 'service'
    form.contact_method = 'messages'
    
    clearErrors()
    autosave.reset()
  }

  /**
   * Заполнить форму данными
   */
  const fillForm = (data) => {
    Object.keys(form).forEach(key => {
      if (data[key] !== undefined) {
        form[key] = data[key]
      }
    })
    
    autosave.reset()
  }

  /**
   * Получить данные формы
   */
  const getFormData = () => {
    return prepareFormData(form)
  }

  /**
   * Проверить, есть ли несохраненные изменения
   */
  const hasUnsavedChanges = computed(() => {
    return autosave.hasUnsavedChanges.value
  })

  return {
    // Данные формы
    form,
    errors,
    isValid,
    isSubmitting,
    
    // Автосохранение
    isSaving: autosave.isSaving,
    lastSaved: autosave.lastSaved,
    hasUnsavedChanges,
    
    // Методы валидации
    validateForm,
    validateField,
    clearErrors,
    clearFieldError,
    
    // Методы отправки
    submitForm,
    saveAndExit,
    
    // Методы управления
    resetForm,
    fillForm,
    getFormData,
    
    // Автосохранение
    startAutosave: autosave.startAutosave,
    stopAutosave: autosave.stopAutosave,
    forceSave: autosave.forceSave
  }
} 