/**
 * РЕФАКТОРЕННЫЙ adFormModel.ts
 * KISS принцип: модульная архитектура, 300 строк вместо 1185
 * Вся логика разнесена по специализированным composables
 */
import { ref, computed, watch, onMounted, nextTick } from 'vue'
import type { Ref } from 'vue'
import { useRoute } from 'vue-router'
import { useToast } from '@/src/shared/composables/useToast'

// Импорт модульных composables
import { useAdFormState } from './composables/useAdFormState'
import { useAdFormValidation } from './composables/useAdFormValidation'
import { useAdFormSubmission } from './composables/useAdFormSubmission'
import { useAdFormMigration } from './composables/useAdFormMigration'

/**
 * Основной composable для управления формой объявления
 * Объединяет все модули и предоставляет единый API
 * 
 * @param props - props компонента (должны содержать adId, initialData)
 * @param emit - emit функция компонента  
 */
export function useAdFormModel(props: any, emit: any) {
  const route = useRoute()
  const toast = useToast()
  
  // ✅ ПОДКЛЮЧЕНИЕ МОДУЛЕЙ
  const {
    form,
    isLoading,
    isSaving,
    isPublishing,
    errors,
    generalError,
    isDirty,
    isEditMode,
    isDraftMode,
    resetForm,
    setFormData,
    clearErrors,
    setErrors,
    setGeneralError,
    markAsDirty
  } = useAdFormState(props)
  
  const {
    validateForm,
    validateField,
    hasErrors,
    getFirstError,
    clearFieldError
  } = useAdFormValidation()
  
  const {
    saveDraft,
    publishAd,
    loadAd,
    autosaveDraft,
    navigateAfterSave,
    handleSubmissionError
  } = useAdFormSubmission()
  
  const {
    migrateOldData
  } = useAdFormMigration()
  
  // ✅ REFS ДЛЯ ПРОКРУТКИ (вместо querySelector)
  const firstErrorRef = ref<HTMLElement | null>(null)
  const formRef = ref<HTMLFormElement | null>(null)
  
  // ✅ ВЫЧИСЛЯЕМЫЕ СВОЙСТВА
  const canSave = computed(() => {
    return !isSaving.value && !isPublishing.value && isDirty.value
  })
  
  const hasValidationErrors = computed(() => {
    return hasErrors(errors.value)
  })
  
  // ✅ ИНИЦИАЛИЗАЦИЯ ФОРМЫ
  const initializeForm = async (adId?: number) => {
    if (!adId) {
      resetForm()
      return
    }
    
    isLoading.value = true
    clearErrors()
    
    try {
      const result = await loadAd(adId)
      
      if (result.success && result.data) {
        // Мигрируем старые данные если нужно
        const migrated = migrateOldData(result.data)
        setFormData(migrated)
      } else {
        toast.error(result.message || 'Ошибка загрузки объявления')
      }
    } catch (error) {
      console.error('Ошибка инициализации:', error)
      toast.error('Не удалось загрузить объявление')
    } finally {
      isLoading.value = false
    }
  }
  
  // ✅ СОХРАНЕНИЕ ЧЕРНОВИКА
  const handleSaveDraft = async () => {
    if (isSaving.value) return
    
    // Валидация для черновика (только базовая)
    const validationErrors = validateForm(form, false)
    
    if (hasErrors(validationErrors)) {
      setErrors(validationErrors)
      const firstError = getFirstError(validationErrors)
      if (firstError) {
        toast.error(firstError)
      }
      return
    }
    
    isSaving.value = true
    clearErrors()
    
    try {
      const result = await saveDraft(form)
      
      if (result.success) {
        toast.success(result.message || 'Черновик сохранен')
        
        if (result.data) {
          setFormData(result.data)
          if (!isEditMode.value && result.data.id) {
            navigateAfterSave(result.data.id, true)
          }
        }
      } else {
        if (result.errors) {
          setErrors(result.errors)
          scrollToFirstError()
        }
        toast.error(result.message || 'Ошибка сохранения')
      }
    } catch (error) {
      const submissionErrors = handleSubmissionError(error)
      setErrors(submissionErrors)
      toast.error('Произошла ошибка при сохранении')
    } finally {
      isSaving.value = false
    }
  }
  
  // ✅ ПУБЛИКАЦИЯ ОБЪЯВЛЕНИЯ
  const handlePublish = async () => {
    if (isPublishing.value) return
    
    // Полная валидация для публикации
    const validationErrors = validateForm(form, true)
    
    if (hasErrors(validationErrors)) {
      setErrors(validationErrors)
      scrollToFirstError()
      toast.error('Заполните все обязательные поля')
      return
    }
    
    isPublishing.value = true
    clearErrors()
    
    try {
      const result = await publishAd(form)
      
      if (result.success) {
        toast.success(result.message || 'Объявление опубликовано')
        
        if (result.data) {
          setFormData(result.data)
          navigateAfterSave(result.data.id, false)
        }
      } else {
        if (result.errors) {
          setErrors(result.errors)
          scrollToFirstError()
        }
        toast.error(result.message || 'Ошибка публикации')
      }
    } catch (error) {
      const submissionErrors = handleSubmissionError(error)
      setErrors(submissionErrors)
      toast.error('Произошла ошибка при публикации')
    } finally {
      isPublishing.value = false
    }
  }
  
  // ✅ ПРОКРУТКА К ПЕРВОЙ ОШИБКЕ (через refs)
  const scrollToFirstError = async () => {
    await nextTick()
    
    // Если есть ref на элемент с ошибкой
    if (firstErrorRef.value) {
      firstErrorRef.value.scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      })
      return
    }
    
    // Иначе ищем первое поле с ошибкой в форме
    if (formRef.value) {
      const firstErrorField = formRef.value.querySelector('.has-error')
      if (firstErrorField) {
        firstErrorField.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        })
      }
    }
  }
  
  // ✅ ОБРАБОТКА ИЗМЕНЕНИЙ ПОЛЕЙ
  const handleFieldChange = (field: string, value: any) => {
    // Обновляем значение в форме
    const keys = field.split('.')
    let obj: any = form
    
    for (let i = 0; i < keys.length - 1; i++) {
      obj = obj[keys[i]]
    }
    
    obj[keys[keys.length - 1]] = value
    
    // Очищаем ошибку этого поля
    clearFieldError(errors.value, field)
    
    // Помечаем форму как измененную
    markAsDirty()
    
    // Автосохранение для черновиков
    if (isDraftMode.value && form.title) {
      autosaveDraft(form)
    }
  }
  
  // ✅ УСТАНОВКА REFS
  const setFirstErrorRef = (el: HTMLElement | null) => {
    firstErrorRef.value = el
  }
  
  const setFormRef = (el: HTMLFormElement | null) => {
    formRef.value = el
  }
  
  // ✅ ХУКИ ЖИЗНЕННОГО ЦИКЛА
  onMounted(() => {
    const adId = route.params.id ? Number(route.params.id) : null
    if (adId) {
      initializeForm(adId)
    }
  })
  
  // ✅ WATCHERS
  watch(() => route.params.id, (newId) => {
    if (newId) {
      initializeForm(Number(newId))
    } else {
      resetForm()
    }
  })
  
  // ✅ ОБРАБОТКА ОТПРАВКИ ФОРМЫ (alias для handlePublish)
  const handleSubmit = async () => {
    return handlePublish()
  }
  
  // ✅ ОБРАБОТКА ОТМЕНЫ
  const handleCancel = () => {
    emit('cancel')
    clearErrors()
  }
  
  // ✅ ПУБЛИЧНЫЙ API (совместимость с AdForm.vue)
  return {
    // Состояние (точные имена из AdForm.vue)
    form,
    errors,
    generalError,
    isLoading,
    saving: isSaving,  // ✅ АЛИАС для совместимости
    isPublishing,
    isDirty,
    isEditMode,
    isDraftMode,
    canSave,
    hasValidationErrors,
    
    // Методы (точные имена из AdForm.vue)
    initializeForm,
    handleSubmit,      // ✅ ДОБАВЛЕН для совместимости
    handleSaveDraft,
    handlePublish,
    handleCancel,      // ✅ ДОБАВЛЕН для совместимости
    handleFieldChange,
    clearErrors,
    resetForm,
    scrollToFirstError,
    
    // Refs для AdForm.vue
    setFirstErrorRef,
    setFormRef
  }
}