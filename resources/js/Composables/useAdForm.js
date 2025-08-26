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

  // Объект parameters для физических характеристик (объявляем ПЕРЕД form)
  const parameters = reactive({
    title: initialData.title || '',
    age: initialData.age || '',
    height: initialData.height || '',
    weight: initialData.weight || '',
    breast_size: initialData.breast_size || '',
    hair_color: initialData.hair_color || '',
    eye_color: initialData.eye_color || '',
    nationality: initialData.nationality || '',
    bikini_zone: initialData.bikini_zone || ''
  })

  // Объект contacts для контактной информации
  const contacts = reactive({
    phone: initialData.phone || '',
    contact_method: initialData.contact_method || 'whatsapp',
    whatsapp: initialData.whatsapp || '',
    telegram: initialData.telegram || ''
  })

  // Состояние формы
  const form = reactive({
    // Новые поля согласно Avito (title теперь в parameters)
    // title: initialData.title || '', // УБРАНО - теперь в parameters
    specialty: initialData.specialty || '',
    clients: initialData.clients || [],
    service_location: initialData.service_location || [],
    work_format: initialData.work_format || '',
    experience: initialData.experience || '',
    
    // Цены (обновленные)
    price: String(initialData.price || ''),
    price_unit: initialData.price_unit || 'session',
    is_starting_price: initialData.is_starting_price || false,
    
    // Физические параметры (теперь в объекте parameters)
    // age, height, weight, breast_size, hair_color, eye_color, nationality, bikini_zone - убраны
    
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
    travel_area: initialData.travel_area || null,
    
    // Контакты (теперь в объекте contacts)
    // phone, contact_method, whatsapp, telegram - убраны
    
    // Другие поля
    description: initialData.description || '',
    category: initialData.category || 'erotic',
    
    // Новые поля
    services: initialData.services || [],
    services_additional_info: initialData.services_additional_info || '',
    features: initialData.features || [],
    additional_features: initialData.additional_features || '',
    schedule: initialData.schedule || {},
    schedule_notes: initialData.schedule_notes || '',
    // whatsapp: initialData.whatsapp || '', // УБРАНО - теперь в contacts
    // telegram: initialData.telegram || '', // УБРАНО - теперь в contacts
    
    // Дополнительные поля для службы
    service_provider: initialData.service_provider || '',
    
    // Объект parameters для физических характеристик
    parameters: parameters,
    
    // Объект contacts для контактной информации
    contacts: contacts
  })

  // Состояние валидации
  const errors = ref({})
  const isSubmitting = ref(false)
  
  // Валидность формы
  const isValid = computed(() => {
    return Object.keys(errors.value).length === 0
  })

  /**
   * Валидировать всю форму
   */
  const validateForm = async () => {
    try {
      const formData = prepareFormData(form)
      const validationResult = await validateAdForm(formData)
      
      if (validationResult.isValid) {
        errors.value = {}
        return true
      } else {
        errors.value = validationResult.errors
        return false
      }
    } catch (error) {
      // Validation error
      return false
    }
  }

  /**
   * Валидировать отдельное поле
   */
  const validateField = async (fieldName) => {
    try {
      const fieldData = { [fieldName]: form[fieldName] }
      const validationResult = await validateAdForm(fieldData, { fieldsOnly: [fieldName] })
      
      if (validationResult.isValid) {
        delete errors.value[fieldName]
      } else {
        errors.value[fieldName] = validationResult.errors[fieldName]
      }
    } catch (error) {
      // Field validation error
    }
  }

  /**
   * Очистить ошибки
   */
  const clearErrors = () => {
    errors.value = {}
  }

  /**
   * Очистить ошибку поля
   */
  const clearFieldError = (fieldName) => {
    delete errors.value[fieldName]
  }

  /**
   * Отправить форму
   */
  const submitForm = async () => {
    try {
      isSubmitting.value = true
      
      // Валидируем форму
      const isFormValid = await validateForm()
      if (!isFormValid) {
        throw new Error('Форма содержит ошибки')
      }

      // Подготавливаем данные
      const formData = prepareFormData(form)

      // Отправляем
      let result
      if (isEditMode && adId) {
        result = await updateAd(adId, formData)
      } else {
        result = await createAd(formData)
      }

      // Вызываем колбэк успеха
      if (onSuccess) {
        onSuccess(result)
      }

      return result
    } catch (error) {
      // Form submission error
      
      // Вызываем колбэк ошибки
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
      const draftData = await loadDraftById(draftId)
      Object.assign(form, draftData)
      return draftData
    } catch (error) {
      // Draft loading error
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
      // Draft saving error
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
  }

  /**
   * Получить данные формы
   */
  const getFormData = () => {
    return { ...form }
  }

  // Автосохранение (если включено)
  let autosave = null
  if (autosaveEnabled) {
    try {
      autosave = useAutosave(form, {
        delay: 5000,
        onSave: saveDraftForm
      })
    } catch (error) {
      // Autosave unavailable
    }
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
