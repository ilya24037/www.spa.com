import { ref, reactive, computed } from 'vue'
import type { Ref } from 'vue'
import { useRoute } from 'vue-router'
import type { AdForm } from '../types'

/**
 * Composable для управления состоянием формы объявления
 * KISS: Только состояние, никакой логики валидации или отправки
 */
export function useAdFormState() {
  const route = useRoute()
  
  // ✅ ОСНОВНОЕ СОСТОЯНИЕ
  const form = reactive<AdForm>({
    // Основная информация
    id: null,
    user_id: null,
    title: '',
    category: 'relax',
    description: '',
    status: 'draft',
    
    // Медиа
    photos: [],
    video: [],
    
    // Цены и услуги
    prices: {},
    services: [],
    clients: [],
    
    // Расписание
    schedule: {},
    schedule_notes: '',
    
    // Контакты
    phone: '',
    whatsapp: '',
    telegram: '',
    vk: '',
    instagram: '',
    
    // Локация
    address: '',
    geo: null,
    radius: null,
    is_remote: false,
    
    // Параметры
    age: null,
    height: null,
    weight: null,
    breast_size: null,
    hair_color: '',
    eye_color: '',
    nationality: '',
    appearance: '',
    
    // Дополнительно
    additional_features: [],
    discount: null,
    gift: '',
    new_client_discount: null,
    has_girlfriend: false,
    min_duration: null,
    contacts_per_hour: null,
    experience: null,
    work_format: '',
    specialty: ''
  })

  // ✅ СОСТОЯНИЯ ЗАГРУЗКИ
  const isLoading = ref(false)
  const isSaving = ref(false)
  const isPublishing = ref(false)
  
  // ✅ ОШИБКИ
  const errors = ref<Record<string, string[]>>({})
  const generalError = ref<string | null>(null)
  
  // ✅ ДОПОЛНИТЕЛЬНЫЕ ФЛАГИ
  const isDirty = ref(false)
  const isEditMode = computed(() => !!form.id)
  const isDraftMode = computed(() => form.status === 'draft')
  
  // ✅ МЕТОДЫ УПРАВЛЕНИЯ СОСТОЯНИЕМ
  const resetForm = () => {
    Object.assign(form, {
      id: null,
      user_id: null,
      title: '',
      category: 'relax',
      description: '',
      status: 'draft',
      photos: [],
      video: [],
      prices: {},
      services: [],
      clients: [],
      schedule: {},
      schedule_notes: '',
      phone: '',
      whatsapp: '',
      telegram: '',
      vk: '',
      instagram: '',
      address: '',
      geo: null,
      radius: null,
      is_remote: false,
      age: null,
      height: null,
      weight: null,
      breast_size: null,
      hair_color: '',
      eye_color: '',
      nationality: '',
      appearance: '',
      additional_features: [],
      discount: null,
      gift: '',
      new_client_discount: null,
      has_girlfriend: false,
      min_duration: null,
      contacts_per_hour: null,
      experience: null,
      work_format: '',
      specialty: ''
    })
    
    errors.value = {}
    generalError.value = null
    isDirty.value = false
  }
  
  const setFormData = (data: Partial<AdForm>) => {
    Object.assign(form, data)
    isDirty.value = false
  }
  
  const clearErrors = () => {
    errors.value = {}
    generalError.value = null
  }
  
  const setErrors = (newErrors: Record<string, string[]>) => {
    errors.value = newErrors
  }
  
  const setGeneralError = (error: string | null) => {
    generalError.value = error
  }
  
  const markAsDirty = () => {
    isDirty.value = true
  }
  
  return {
    // Состояние
    form,
    isLoading,
    isSaving,
    isPublishing,
    errors,
    generalError,
    isDirty,
    isEditMode,
    isDraftMode,
    
    // Методы
    resetForm,
    setFormData,
    clearErrors,
    setErrors,
    setGeneralError,
    markAsDirty
  }
}