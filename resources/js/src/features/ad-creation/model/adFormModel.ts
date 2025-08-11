import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/authStore'

// Типы данных формы
export interface AdFormData {
  title: string
  specialty: string
  clients: string[]
  service_location: string[]
  work_format: string
  service_provider: string
  experience: string
  description: string
  services: any[]
  services_additional_info: string
  features: string[]
  additional_features: string
  schedule: any
  schedule_notes: string
  price: number | null
  price_unit: string
  is_starting_price: boolean
  main_service_name: string
  main_service_price: number | null
  main_service_price_unit: string
  additional_services: Array<{
    name: string
    price: number
    unit: string
  }>
  height: string
  weight: string
  hair_color: string
  eye_color: string
  nationality: string
  new_client_discount: string
  gift: string
  photos: any[]
  videos: any[]
  media_settings: string[]
  geo: any
  address: string
  travel_area: string
  custom_travel_areas: string[]
  travel_radius: string | number
  travel_price: number | null
  travel_price_type: string
  phone: string
  contact_method: string
  whatsapp: string
  telegram: string
}

// Композабл для управления формой
export function useAdFormModel(props: any, emit: any) {
  const authStore = useAuthStore()
  
  // Состояние формы
  const form = reactive<AdFormData>({
    title: props.initialData?.title || '',
    specialty: props.initialData?.specialty || '',
    clients: props.initialData?.clients || [],
    service_location: props.initialData?.service_location || ['У заказчика дома'],
    work_format: props.initialData?.work_format || 'individual',
    service_provider: props.initialData?.service_provider || 'woman',
    experience: props.initialData?.experience || '',
    description: props.initialData?.description || '',
    services: props.initialData?.services || [],
    services_additional_info: props.initialData?.services_additional_info || '',
    features: props.initialData?.features || [],
    additional_features: props.initialData?.additional_features || '',
    schedule: props.initialData?.schedule || {},
    schedule_notes: props.initialData?.schedule_notes || '',
    price: props.initialData?.price || null,
    price_unit: props.initialData?.price_unit || 'hour',
    is_starting_price: props.initialData?.is_starting_price || false,
    main_service_name: props.initialData?.main_service_name || '',
    main_service_price: props.initialData?.main_service_price || null,
    main_service_price_unit: props.initialData?.main_service_price_unit || 'hour',
    additional_services: props.initialData?.additional_services || [],
    height: props.initialData?.height || '',
    weight: props.initialData?.weight || '',
    hair_color: props.initialData?.hair_color || '',
    eye_color: props.initialData?.eye_color || '',
    nationality: props.initialData?.nationality || '',
    new_client_discount: props.initialData?.new_client_discount || '',
    gift: props.initialData?.gift || '',
    photos: props.initialData?.photos || [],
    videos: props.initialData?.videos || [],
    media_settings: props.initialData?.media_settings || ['show_photos_in_gallery'],
    geo: props.initialData?.geo || {},
    address: props.initialData?.address || '',
    travel_area: props.initialData?.travel_area || 'no_travel',
    custom_travel_areas: props.initialData?.custom_travel_areas || [],
    travel_radius: props.initialData?.travel_radius || '',
    travel_price: props.initialData?.travel_price || null,
    travel_price_type: props.initialData?.travel_price_type || 'free',
    phone: props.initialData?.phone || '',
    contact_method: props.initialData?.contact_method || 'phone',
    whatsapp: props.initialData?.whatsapp || '',
    telegram: props.initialData?.telegram || ''
  })

  // Ошибки валидации
  const errors = ref<Record<string, string[]>>({})
  
  // Состояние сохранения
  const saving = ref(false)

  // Валидация формы
  const validateForm = (): boolean => {
    const newErrors: Record<string, string[]> = {}
    
    if (!form.title) {
      newErrors.title = ['Название объявления обязательно']
    }
    
    if (!form.specialty) {
      newErrors.specialty = ['Специализация обязательна']
    }
    
    if (!form.price || form.price <= 0) {
      newErrors.price = ['Укажите корректную цену']
    }
    
    if (!form.phone) {
      newErrors.phone = ['Телефон обязателен']
    }
    
    if (!form.geo?.city) {
      newErrors['geo.city'] = ['Выберите город']
    }
    
    errors.value = newErrors
    return Object.keys(newErrors).length === 0
  }

  // Обработка отправки формы
  const handleSubmit = async () => {
    if (!validateForm()) {
      return
    }
    
    saving.value = true
    
    try {
      // Подготовка данных для отправки
      const formData = new FormData()
      
      // Добавляем все поля формы
      Object.entries(form).forEach(([key, value]) => {
        if (value !== null && value !== undefined) {
          if (Array.isArray(value)) {
            value.forEach((item, index) => {
              if (typeof item === 'object' && item.file) {
                formData.append(`${key}[${index}]`, item.file)
              } else {
                formData.append(`${key}[]`, item)
              }
            })
          } else if (typeof value === 'object') {
            formData.append(key, JSON.stringify(value))
          } else {
            formData.append(key, String(value))
          }
        }
      })
      
      // Добавляем категорию
      formData.append('category', props.category)
      
      // Отправка на сервер
      router.post('/api/ads', formData, {
        onSuccess: (response: any) => {
          emit('success', response)
        },
        onError: (errors: any) => {
          console.error('Ошибка создания объявления:', errors)
          errors.value = errors
        },
        onFinish: () => {
          saving.value = false
        }
      })
    } catch (error) {
      console.error('Ошибка при отправке формы:', error)
      saving.value = false
    }
  }

  // Сохранение черновика
  const handleSaveDraft = async () => {
    saving.value = true
    
    try {
      const draftData = {
        ...form,
        category: props.category,
        status: 'draft'
      }
      
      router.post('/api/ads/draft', draftData, {
        onSuccess: () => {
          // Draft saved successfully
        },
        onError: (errors: any) => {
          // Error saving draft
        },
        onFinish: () => {
          saving.value = false
        }
      })
    } catch (error) {
      console.error('Ошибка при сохранении черновика:', error)
      saving.value = false
    }
  }

  // Публикация объявления
  const handlePublish = async () => {
    if (!authStore.isAuthenticated) {
      router.visit('/login')
      return
    }
    
    await handleSubmit()
  }

  return {
    form,
    errors,
    saving,
    handleSubmit,
    handleSaveDraft,
    handlePublish
  }
}