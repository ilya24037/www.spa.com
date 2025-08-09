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
    work_format: initialData.work_format || '',
    experience: initialData.experience || '',
    
    // Цены (обновленные)
    price: String(initialData.price || ''),
    price_unit: initialData.price_unit || 'session',
    is_starting_price: initialData.is_starting_price || false,
    
    // Физические параметры
    age: initialData.age || '',
    height: initialData.height || '',
    weight: initialData.weight || '',
    breast_size: initialData.breast_size || '',
    hair_color: initialData.hair_color || '',
    eye_color: initialData.eye_color || '',
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
    travel_area: initialData.travel_area || null,
    
    // Контакты
    phone: initialData.phone || '',
    contact_method: initialData.contact_method || 'whatsapp',
    
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
    whatsapp: initialData.whatsapp || '',
    telegram: initialData.telegram || '',
    
    // Дополнительные поля для службы
    service_provider: initialData.service_provider || ''
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
      console.error('Ошибка валидации:', error)
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
      console.error(`Ошибка валидации поля ${fieldName}:`, error)
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
      console.error('Ошибка при отправке формы:', error)
      
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
      console.warn('Автосохранение недоступно:', error)
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
