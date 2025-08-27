import { ref, reactive, computed } from 'vue'
import type { Ref } from 'vue'
import { useRoute } from 'vue-router'
import type { AdForm } from '../types'

// Функции миграции для обратной совместимости (из оригинала)
const migrateParameters = (data: any): any => {
  if (data?.parameters && typeof data.parameters === 'object') {
    return data.parameters
  }
  
  const migrated = {
    title: data?.title || '',
    age: data?.age || '',
    height: data?.height || '',
    weight: data?.weight || '',
    breast_size: data?.breast_size || '',
    hair_color: data?.hair_color || '',
    eye_color: data?.eye_color || '',
    nationality: data?.nationality || '',
    bikini_zone: data?.bikini_zone || ''
  };
  
  return migrated;
}

const migrateContacts = (data: any): any => {
  // Если уже в новом формате с объектом contacts
  if (data?.contacts && typeof data.contacts === 'object') {
    return data.contacts
  }
  
  // Мигрируем из старого формата (отдельные поля)
  return {
    phone: data?.phone || '',
    contact_method: data?.contact_method || 'any',
    whatsapp: data?.whatsapp || '',
    telegram: data?.telegram || ''
  }
}

/**
 * Composable для управления состоянием формы объявления
 * KISS: Только состояние, никакой логики валидации или отправки
 */
export function useAdFormState(props: any) {
  const route = useRoute()
  
  // Логика работы с localStorage (из оригинала)
  let savedFormData: any = null
  const isNewAd = !props.adId && !props.initialData?.id
  
  // ВАЖНО: Очищаем localStorage при создании нового объявления
  if (isNewAd) {
    localStorage.removeItem('adFormData')
  } else {
    try {
      const saved = localStorage.getItem('adFormData')
      savedFormData = saved ? JSON.parse(saved) : null
    } catch (e) {
      console.warn('Ошибка чтения localStorage:', e)
      localStorage.removeItem('adFormData')
    }
  }
  
  // ✅ ОСНОВНОЕ СОСТОЯНИЕ с правильной инициализацией
  const form = reactive<AdForm>({
    // Основная информация
    specialty: savedFormData?.specialty || props.initialData?.specialty || '',
    
    clients: (() => {
      if (savedFormData?.clients) return savedFormData.clients
      if (!props.initialData?.clients) return []
      if (Array.isArray(props.initialData.clients)) return props.initialData.clients
      if (typeof props.initialData.clients === 'string') {
        try {
          const parsed = JSON.parse(props.initialData.clients)
          return Array.isArray(parsed) ? parsed : []
        } catch (e) {
          return []
        }
      }
      return []
    })(),
    
    service_location: savedFormData?.service_location || props.initialData?.service_location || [],
    work_format: savedFormData?.work_format || props.initialData?.work_format || 'individual',
    
    service_provider: (() => {
      if (savedFormData?.service_provider) return savedFormData.service_provider
      if (!props.initialData?.service_provider) return ['women']
      if (Array.isArray(props.initialData.service_provider)) return props.initialData.service_provider
      if (typeof props.initialData.service_provider === 'string') {
        try {
          const parsed = JSON.parse(props.initialData.service_provider)
          return Array.isArray(parsed) ? parsed : ['women']
        } catch (e) {
          return ['women']
        }
      }
      return ['women']
    })(),
    
    experience: savedFormData?.experience || props.initialData?.experience || '',
    description: savedFormData?.description || props.initialData?.description || '',
    
    // Услуги и возможности
    services: savedFormData?.services || props.initialData?.services || {},
    services_additional_info: savedFormData?.services_additional_info || props.initialData?.services_additional_info || '',
    features: savedFormData?.features || props.initialData?.features || [],
    additional_features: savedFormData?.additional_features || props.initialData?.additional_features || '',
    
    // Расписание
    schedule: savedFormData?.schedule || props.initialData?.schedule || {},
    schedule_notes: savedFormData?.schedule_notes || props.initialData?.schedule_notes || '',
    online_booking: savedFormData?.online_booking || props.initialData?.online_booking || false,
    
    // Цены
    price: savedFormData?.price || props.initialData?.price || null,
    price_unit: savedFormData?.price_unit || props.initialData?.price_unit || 'hour',
    is_starting_price: savedFormData?.is_starting_price || props.initialData?.is_starting_price || false,
    prices: savedFormData?.prices || props.initialData?.prices || {},
    
    // Параметры (объект)
    parameters: migrateParameters(savedFormData || props.initialData || {}),
    
    // Скидки и подарки
    new_client_discount: savedFormData?.new_client_discount || props.initialData?.new_client_discount || '',
    gift: savedFormData?.gift || props.initialData?.gift || '',
    
    // Медиа
    photos: savedFormData?.photos || props.initialData?.photos || [],
    video: savedFormData?.video || props.initialData?.video || [],
    
    // Геолокация
    geo: savedFormData?.geo || props.initialData?.geo || null,
    address: savedFormData?.address || props.initialData?.address || '',
    travel_area: savedFormData?.travel_area || props.initialData?.travel_area || '',
    custom_travel_areas: savedFormData?.custom_travel_areas || props.initialData?.custom_travel_areas || [],
    travel_radius: savedFormData?.travel_radius || props.initialData?.travel_radius || '',
    travel_price: savedFormData?.travel_price || props.initialData?.travel_price || null,
    travel_price_type: savedFormData?.travel_price_type || props.initialData?.travel_price_type || '',
    
    // Контакты (объект)
    contacts: migrateContacts(savedFormData || props.initialData || {}),
    
    // FAQ
    faq: savedFormData?.faq || props.initialData?.faq || {},
    
    // Верификация
    verification_photo: savedFormData?.verification_photo || props.initialData?.verification_photo || null,
    verification_video: savedFormData?.verification_video || props.initialData?.verification_video || null,
    verification_status: savedFormData?.verification_status || props.initialData?.verification_status || '',
    verification_comment: savedFormData?.verification_comment || props.initialData?.verification_comment || null,
    verification_expires_at: savedFormData?.verification_expires_at || props.initialData?.verification_expires_at || null
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