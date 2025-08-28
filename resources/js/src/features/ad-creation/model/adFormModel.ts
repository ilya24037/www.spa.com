/**
 * РЕФАКТОРЕННЫЙ adFormModel.ts
 * KISS принцип: модульная архитектура, 300 строк вместо 1185
 * Вся логика разнесена по специализированным composables
 */
import { computed, watch, onMounted } from 'vue'
import type { Ref } from 'vue'
import { useToast } from '@/src/shared/composables/useToast'

// Импорт модульных composables
import { useAdFormState } from './composables/useAdFormState'
import { useAdFormValidation } from './composables/useAdFormValidation'
import { useAdFormSubmission } from './composables/useAdFormSubmission'
import { useAdFormMigration } from './composables/useAdFormMigration'
import type { AdForm } from './types'

// ✅ СТРОГАЯ ТИПИЗАЦИЯ ДЛЯ PROPS И EMIT (ЭКСПОРТ ДЛЯ ПЕРЕИСПОЛЬЗОВАНИЯ)
export interface AdFormProps {
  adId?: number
  initialData?: Partial<AdForm>
  mode?: 'create' | 'edit' | 'draft'
}

export interface AdFormEmits {
  (event: 'cancel'): void
  (event: 'success', data: AdForm): void
  (event: 'error', error: string): void
}

/**
 * Основной composable для управления формой объявления
 * Объединяет все модули и предоставляет единый API
 * 
 * @param props - props компонента с типизацией AdFormProps
 * @param emit - emit функция с типизацией AdFormEmits
 */
export function useAdFormModel(props: AdFormProps, emit: AdFormEmits) {
  const toast = useToast()
  
  // ✅ МИГРАЦИЯ ДАННЫХ (согласно плану строки 760-783)
  const { migrateOldData } = useAdFormMigration()
  
  // Мигрируем данные если они есть
  const migratedProps = {
    ...props,
    initialData: props.initialData ? migrateOldData(props.initialData) : undefined
  }
  
  // ✅ ПОДКЛЮЧЕНИЕ МОДУЛЕЙ (с мигрированными данными)
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
  } = useAdFormState(migratedProps)
  
  const {
    validateForm,
    validateField,
    hasErrors,
    getFirstError,
    clearFieldError,
    handleValidationErrors,
    clearFieldHighlight,
    clearAllFieldHighlights,
    // Refs для полей
    titleInputRef,
    priceInputRef,
    phoneInputRef,
    citySelectRef,
    clientsSectionRef
  } = useAdFormValidation()
  
  const {
    saveDraft,
    publishAd,
    loadAd,
    autosaveDraft,
    navigateAfterSave,
    handleSubmissionError
  } = useAdFormSubmission()
  
  
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
    
    // КРИТИЧЕСКИ ВАЖНО: Если есть initialData с нужным ID, используем их вместо загрузки
    if (props.initialData?.id === adId) {
      console.log('🔍 DEBUG initializeForm: Using props.initialData instead of loading')
      console.log('🔍 DEBUG props.initialData.id:', props.initialData.id)
      
      // Мигрируем данные из props
      const migrated = migrateOldData(props.initialData)
      console.log('🔍 DEBUG initializeForm after migration from props:', {
        'migrated.id': migrated.id,
        'originalId': props.initialData.id
      })
      
      setFormData(migrated)
      return
    }
    
    isLoading.value = true
    clearErrors()
    
    try {
      const result = await loadAd(adId)
      
      console.log('🔍 DEBUG initializeForm loadAd result:', {
        'success': result.success,
        'result.data.id': result.data?.id,
        'adId requested': adId
      })
      
      if (result.success && result.data) {
        // Мигрируем старые данные если нужно
        const migrated = migrateOldData(result.data)
        console.log('🔍 DEBUG initializeForm after migration:', {
          'migrated.id': migrated.id,
          'originalId': result.data.id
        })
        // КРИТИЧЕСКИ ВАЖНО: сохраняем ID для обновлений
        setFormData(migrated)
      } else {
        toast.error(result.message || 'Ошибка загрузки объявления')
      }
    } catch (error) {
      toast.error('Не удалось загрузить объявление')
    } finally {
      isLoading.value = false
    }
  }
  
  // ✅ СОХРАНЕНИЕ ЧЕРНОВИКА
  const handleSaveDraft = async () => {
    if (isSaving.value) return
    
    console.log('🔍 DEBUG handleSaveDraft START:', {
      'form.id BEFORE': form.id,
      'props.adId': props.adId,
      'props.initialData?.id': props.initialData?.id
    })
    
    // Валидация для черновика (теперь пустая - без обязательных полей)
    const validationErrors = validateForm(form, false)
    
    if (hasErrors(validationErrors)) {
      setErrors(validationErrors)
      handleValidationErrors(validationErrors)
      const firstError = getFirstError(validationErrors)
      if (firstError) {
        toast.error(firstError)
      }
      return
    }
    
    isSaving.value = true
    clearErrors()
    
    try {
      console.log('🔍 DEBUG before saveDraft:', {
        'form.id': form.id,
        'typeof form.id': typeof form.id
      })
      
      const result = await saveDraft(form)
      
      console.log('🔍 DEBUG saveDraft result:', {
        'success': result.success,
        'result.data?.id': result.data?.id,
        'form.id AFTER response': form.id
      })
      
      if (result.success) {
        toast.success(result.message || 'Черновик сохранен')
        
        if (result.data) {
          // КРИТИЧЕСКИ ВАЖНО: обновляем ID формы для последующих обновлений
          if (result.data.id && !form.id) {
            console.log('🔍 DEBUG updating form.id:', {
              'OLD form.id': form.id,
              'NEW form.id': result.data.id
            })
            form.id = result.data.id
          }
          // Мигрируем данные перед обновлением формы
          const migratedData = migrateOldData(result.data)
          // Обновляем все данные формы
          setFormData(migratedData)
          
          console.log('🔍 DEBUG after setFormData:', {
            'form.id FINAL': form.id
          })
          
          // После сохранения черновика перенаправляем в личный кабинет
          navigateAfterSave(result.data.id, true)
        }
      } else {
        if (result.errors) {
          setErrors(result.errors)
          handleValidationErrors(result.errors)
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
      handleValidationErrors(validationErrors)
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
          handleValidationErrors(result.errors)
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
  
  
  // ✅ ХУКИ ЖИЗНЕННОГО ЦИКЛА
  onMounted(() => {
    // Используем props.adId или initialData.id для инициализации
    const adId = props.adId || props.initialData?.id
    console.log('🔍 DEBUG onMounted:', {
      'props.adId': props.adId,
      'props.initialData?.id': props.initialData?.id,
      'final adId': adId,
      'form.id at mount': form.id
    })
    if (adId) {
      initializeForm(adId)
    }
  })
  
  // ✅ WATCHERS
  // Следим за изменением props.adId
  watch(() => props.adId, (newId) => {
    if (newId) {
      initializeForm(newId)
    } else if (!props.initialData?.id) {
      // Сбрасываем только если нет initialData
      resetForm()
    }
  })
  
  // Следим за изменением initialData
  watch(() => props.initialData, (newData) => {
    if (newData?.id && !props.adId) {
      initializeForm(newData.id)
    }
  }, { deep: true })
  
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
    
    // Refs для полей (из useAdFormValidation)
    titleInputRef,
    priceInputRef,
    phoneInputRef,
    citySelectRef,
    clientsSectionRef
  }
}