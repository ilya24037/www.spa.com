import { ref, reactive, computed } from 'vue'
import type { Ref } from 'vue'
import type { AdForm } from '../types'

// ✅ УПРОЩЕННАЯ ИНИЦИАЛИЗАЦИЯ (без парсинга - миграция в adFormModel.ts)
const getValue = (saved: any, initial: any, field: string, defaultValue: any): any => {
  return saved?.[field] ?? initial?.[field] ?? defaultValue
}

/**
 * Composable для управления состоянием формы объявления
 * ОПТИМИЗИРОВАННАЯ ВЕРСИЯ: сокращен с 264 до ~120 строк
 */
export function useAdFormState(props: any) {
  // ✅ УПРОЩЕННАЯ ЛОГИКА РАБОТЫ С localStorage
  const getStorageData = (): any => {
    const isNewAd = !props.adId && !props.initialData?.id
    if (isNewAd) {
      localStorage.removeItem('adFormData')
      return null
    }
    
    try {
      const saved = localStorage.getItem('adFormData')
      return saved ? JSON.parse(saved) : null
    } catch (e) {
      localStorage.removeItem('adFormData')
      return null
    }
  }
  
  const savedFormData = getStorageData()
  const initialData = props.initialData || {}
  
  // ✅ КОМПАКТНАЯ ИНИЦИАЛИЗАЦИЯ ФОРМЫ (универсальная функция getValue)
  const g = (field: string, def: any) => getValue(savedFormData, initialData, field, def)
  
  const form = reactive<AdForm>({
    // Системные поля
    id: g('id', null),
    user_id: g('user_id', null),
    status: g('status', 'draft'),
    category: g('category', 'relax'),
    title: g('title', ''),
    
    // Основные поля
    specialty: g('specialty', ''), work_format: g('work_format', 'individual'),
    experience: g('experience', ''), description: g('description', ''),
    
    // Массивы (автопарсинг JSON)
    clients: g('clients', []), service_location: g('service_location', []),
    service_provider: g('service_provider', ['women']), features: g('features', []),
    photos: g('photos', []), video: g('video', []), custom_travel_areas: g('custom_travel_areas', []),
    
    // Объекты (прямая передача из props - миграция происходит в adFormModel.ts)
    services: g('services', {}), schedule: g('schedule', {}), prices: g('prices', {}),
    geo: g('geo', null), faq: g('faq', {}),
    parameters: g('parameters', {}),
    contacts: g('contacts', {}),
    
    // Остальные поля
    services_additional_info: g('services_additional_info', ''), additional_features: g('additional_features', ''),
    schedule_notes: g('schedule_notes', ''), online_booking: g('online_booking', false),
    price: g('price', null), price_unit: g('price_unit', 'hour'), is_starting_price: g('is_starting_price', false),
    new_client_discount: g('new_client_discount', ''), gift: g('gift', ''),
    address: g('address', ''), travel_area: g('travel_area', ''), travel_radius: g('travel_radius', ''),
    travel_price: g('travel_price', null), travel_price_type: g('travel_price_type', ''),
    min_duration: g('min_duration', null), contacts_per_hour: g('contacts_per_hour', null),
    discount: g('discount', null), has_girlfriend: g('has_girlfriend', false),
    
    // Верификация
    verification_photo: g('verification_photo', null), verification_video: g('verification_video', null),
    verification_status: g('verification_status', ''), verification_comment: g('verification_comment', null),
    verification_expires_at: g('verification_expires_at', null)
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
  
  // ✅ УПРОЩЕННЫЕ МЕТОДЫ УПРАВЛЕНИЯ СОСТОЯНИЕМ
  const resetForm = () => {
    const defaultForm = {
      id: null, user_id: null, title: '', category: 'relax', description: '',
      status: 'draft', photos: [], video: [], prices: {}, services: [],
      clients: [], schedule: {}, phone: '', address: '', geo: null
    }
    Object.assign(form, defaultForm)
    errors.value = {}
    generalError.value = null
    isDirty.value = false
  }
  
  const setFormData = (data: Partial<AdForm>) => {
    // КРИТИЧЕСКИ ВАЖНО: сохраняем ID для корректного обновления
    if (data.id !== undefined) {
      form.id = data.id
    }
    // Обновляем остальные поля
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