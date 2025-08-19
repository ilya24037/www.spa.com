import { ref, reactive, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/authStore'

// Типы данных формы
export interface AdFormData {
  title: string
  specialty: string
  clients: string[]
  service_location: string[]
  work_format: string
  service_provider: string[]
  experience: string
  description: string
  services: any
  services_additional_info: string
  features: string[]
  additional_features: string
  schedule: any
  schedule_notes: string
  online_booking: boolean
  price: number | null
  price_unit: string
  is_starting_price: boolean
  prices?: {
    apartments_express?: number | null
    apartments_1h?: number | null
    apartments_2h?: number | null
    apartments_night?: number | null
    outcall_1h?: number | null
    outcall_2h?: number | null
    outcall_night?: number | null
    taxi_included?: boolean
  }
  main_service_name: string
  main_service_price: number | null
  main_service_price_unit: string
  additional_services: Array<{
    name: string
    price: number
    unit: string
  }>
  age: string | number
  height: string
  weight: string
  breast_size: string
  hair_color: string
  eye_color: string
  nationality: string
  new_client_discount: string
  gift: string
  photos: any[]
  video: any[]
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
  
  // Попытка восстановить данные из localStorage ТОЛЬКО для новых объявлений
  let savedFormData = null
  const isNewAd = !props.adId && !props.initialData?.id
  
  if (isNewAd) {
    try {
      const saved = localStorage.getItem('adFormData')
      if (saved) {
        savedFormData = JSON.parse(saved)
      }
    } catch (e) {
      // Молча игнорируем ошибку восстановления
    }
  } else {
    // Для существующих объявлений очищаем localStorage чтобы не было конфликтов
    localStorage.removeItem('adFormData')
  }
  
  // Состояние формы
  const form = reactive<AdFormData>({
    title: savedFormData?.title || props.initialData?.title || '',
    specialty: savedFormData?.specialty || props.initialData?.specialty || '',
    clients: savedFormData?.clients || props.initialData?.clients || [],
    service_location: savedFormData?.service_location || props.initialData?.service_location || ['У заказчика дома'],
    work_format: savedFormData?.work_format || props.initialData?.work_format || 'individual',
    service_provider: savedFormData?.service_provider || props.initialData?.service_provider || ['women'],
    experience: savedFormData?.experience || props.initialData?.experience || '',
    description: savedFormData?.description || props.initialData?.description || '',
    services: savedFormData?.services || props.initialData?.services || {},
    services_additional_info: savedFormData?.services_additional_info || props.initialData?.services_additional_info || '',
    features: savedFormData?.features || props.initialData?.features || [],
    additional_features: savedFormData?.additional_features || props.initialData?.additional_features || '',
    schedule: savedFormData?.schedule || props.initialData?.schedule || {},
    schedule_notes: savedFormData?.schedule_notes || props.initialData?.schedule_notes || '',
    online_booking: savedFormData?.online_booking || props.initialData?.online_booking || false,
    price: savedFormData?.price || props.initialData?.price || null,
    price_unit: savedFormData?.price_unit || props.initialData?.price_unit || 'hour',
    is_starting_price: savedFormData?.is_starting_price || props.initialData?.is_starting_price || false,
    prices: (() => {
      // Сначала проверяем сохраненные данные
      if (savedFormData?.prices) {
        return savedFormData.prices
      }
      // Если prices есть в initialData
      if (props.initialData?.prices) {
        // Если это строка - парсим JSON
        if (typeof props.initialData.prices === 'string') {
          try {
            return JSON.parse(props.initialData.prices)
          } catch (e) {
            // Молча игнорируем ошибку парсинга
          }
        }
        // Если это уже объект - используем как есть
        return props.initialData.prices
      }
      // По умолчанию
      return {
        apartments_express: null,
        apartments_1h: null,
        apartments_2h: null,
        apartments_night: null,
        outcall_1h: null,
        outcall_2h: null,
        outcall_night: null,
        taxi_included: false
      }
    })(),
    main_service_name: props.initialData?.main_service_name || '',
    main_service_price: props.initialData?.main_service_price || null,
    main_service_price_unit: props.initialData?.main_service_price_unit || 'hour',
    additional_services: props.initialData?.additional_services || [],
    age: props.initialData?.age || '',
    height: props.initialData?.height || '',
    weight: props.initialData?.weight || '',
    breast_size: props.initialData?.breast_size || '',
    hair_color: props.initialData?.hair_color || '',
    eye_color: props.initialData?.eye_color || '',
    nationality: props.initialData?.nationality || '',
    new_client_discount: props.initialData?.new_client_discount || '',
    gift: props.initialData?.gift || '',
    photos: props.initialData?.photos || [],
    video: props.initialData?.video || [],
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
  
  // Режим редактирования - упрощенная проверка
  const isEditMode = computed(() => {
    // Преобразуем в число и проверяем что больше 0
    const id = Number(props.adId)
    console.log('isEditMode check:', {
      propsAdId: props.adId,
      propsAdIdType: typeof props.adId,
      numberValue: id,
      isNaN: isNaN(id),
      isGreaterThanZero: id > 0,
      result: !isNaN(id) && id > 0
    })
    return !isNaN(id) && id > 0
  })
  
  // Watcher для синхронизации адреса из geo в отдельное поле address
  watch(() => form.geo, (newGeo) => {
    if (typeof newGeo === 'string' && newGeo) {
      try {
        const geoData = JSON.parse(newGeo)
        if (geoData.address) {
          form.address = geoData.address
        }
      } catch (e) {
        // Молча игнорируем ошибку парсинга
      }
    } else if (typeof newGeo === 'object' && newGeo && newGeo.address) {
      form.address = newGeo.address
    }
  }, { deep: true, immediate: true })

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
    
    // Подготовка данных для отправки
    const submitData = {
      ...form,
      category: props.category
    }
    
    // Если это редактирование существующего объявления
    if (isEditMode.value) {
      // Для черновиков используем PUT на /draft/{id}
      if (props.initialData?.status === 'draft') {
        router.put(`/draft/${props.adId}`, submitData, {
          preserveScroll: true,
          onSuccess: () => {
            emit('success')
          },
          onError: (errorResponse: any) => {
            // Обработка ошибки
            errors.value = errorResponse
          },
          onFinish: () => {
            saving.value = false
          }
        })
      } else {
        // Для обычных объявлений используем PUT на /ads/{id}
        router.put(`/ads/${props.adId}`, submitData, {
          preserveScroll: true,
          onSuccess: () => {
            // Для активных объявлений переходим к списку активных
            if (props.initialData?.status === 'active') {
              router.visit('/profile/items/active/all')
            } else {
              emit('success')
            }
          },
          onError: (errorResponse: any) => {
            // Обработка ошибки
            errors.value = errorResponse
          },
          onFinish: () => {
            saving.value = false
          }
        })
      }
    } else {
      // Создание нового объявления
      router.post('/additem', submitData, {
        preserveScroll: true,
        onError: (errorResponse: any) => {
          // Обработка ошибки
          errors.value = errorResponse
        },
        onFinish: () => {
          saving.value = false
        }
      })
    }
  }

  // Сохранение черновика (как в старой версии из Backup)
  const handleSaveDraft = async () => {
    // Временная отладка для диагностики
    console.log('📝 Сохранение черновика:', {
      isEditMode: isEditMode.value,
      adId: props.adId,
      adIdType: typeof props.adId,
      status: props.initialData?.status,
      hasInitialData: !!props.initialData,
      initialDataId: props.initialData?.id
    })
    
    saving.value = true
    
    // Создаем FormData для отправки файлов
    const formData = new FormData()
    
    // ВАЖНО: Если редактируем существующий черновик, передаем его ID
    // Проверяем все возможные источники ID
    let adId = null
    
    if (props.adId && Number(props.adId) > 0) {
      adId = Number(props.adId)
      console.log('✅ ID найден в props.adId:', adId)
    } else if (props.initialData?.id && Number(props.initialData.id) > 0) {
      adId = Number(props.initialData.id)
      console.log('✅ ID найден в props.initialData.id:', adId)
    }
    
    if (adId && adId > 0) {
      formData.append('ad_id', String(adId))
      formData.append('id', String(adId)) // Дублируем для надежности
      console.log('✅ Добавлен ad_id в FormData:', adId)
    } else {
      console.log('❌ ID не найден или не валидный. props.adId:', props.adId, 'initialData.id:', props.initialData?.id)
    }
    
    // Добавляем все обычные поля
    formData.append('category', props.category || '')
    formData.append('title', form.title || '')
    formData.append('specialty', form.specialty || '')
    formData.append('work_format', form.work_format || '')
    formData.append('experience', form.experience || '')
    formData.append('description', form.description || '')
    formData.append('services_additional_info', form.services_additional_info || '')
    formData.append('additional_features', form.additional_features || '')
    formData.append('schedule_notes', form.schedule_notes || '')
    formData.append('price', form.price?.toString() || '')
    formData.append('price_unit', form.price_unit || '')
    formData.append('is_starting_price', form.is_starting_price ? '1' : '0')
    
    // Добавляем новые поля ценообразования
    if (form.prices) {
      formData.append('prices[apartments_express]', form.prices.apartments_express?.toString() || '')
      formData.append('prices[apartments_1h]', form.prices.apartments_1h?.toString() || '')
      formData.append('prices[apartments_2h]', form.prices.apartments_2h?.toString() || '')
      formData.append('prices[apartments_night]', form.prices.apartments_night?.toString() || '')
      formData.append('prices[outcall_1h]', form.prices.outcall_1h?.toString() || '')
      formData.append('prices[outcall_2h]', form.prices.outcall_2h?.toString() || '')
      formData.append('prices[outcall_night]', form.prices.outcall_night?.toString() || '')
      formData.append('prices[taxi_included]', form.prices.taxi_included ? '1' : '0')
    }
    formData.append('main_service_name', form.main_service_name || '')
    formData.append('main_service_price', form.main_service_price?.toString() || '')
    formData.append('main_service_price_unit', form.main_service_price_unit || '')
    formData.append('age', form.age?.toString() || '')
    formData.append('height', form.height || '')
    formData.append('weight', form.weight || '')
    formData.append('breast_size', form.breast_size || '')
    formData.append('hair_color', form.hair_color || '')
    formData.append('eye_color', form.eye_color || '')
    formData.append('nationality', form.nationality || '')
    formData.append('new_client_discount', form.new_client_discount || '')
    formData.append('gift', form.gift || '')
    formData.append('address', form.address || '')
    formData.append('travel_area', form.travel_area || '')
    formData.append('travel_radius', form.travel_radius?.toString() || '')
    formData.append('travel_price', form.travel_price?.toString() || '')
    formData.append('travel_price_type', form.travel_price_type || '')
    formData.append('phone', form.phone || '')
    formData.append('contact_method', form.contact_method || '')
    formData.append('whatsapp', form.whatsapp || '')
    formData.append('telegram', form.telegram || '')
    
    // Добавляем массивы как JSON
    if (form.clients) formData.append('clients', JSON.stringify(form.clients))
    if (form.service_location) formData.append('service_location', JSON.stringify(form.service_location))
    if (form.service_provider) formData.append('service_provider', JSON.stringify(form.service_provider))
    if (form.services) formData.append('services', JSON.stringify(form.services))
    if (form.features) formData.append('features', JSON.stringify(form.features))
    if (form.schedule) formData.append('schedule', JSON.stringify(form.schedule))
    if (form.additional_services) formData.append('additional_services', JSON.stringify(form.additional_services))
    if (form.media_settings) formData.append('media_settings', JSON.stringify(form.media_settings))
    if (form.geo) formData.append('geo', JSON.stringify(form.geo))
    if (form.custom_travel_areas) formData.append('custom_travel_areas', JSON.stringify(form.custom_travel_areas))
    
    // Обрабатываем фотографии
    if (form.photos && form.photos.length > 0) {
      form.photos.forEach((photo: any, index: number) => {
        if (photo instanceof File) {
          formData.append(`photos[${index}]`, photo)
        } else if (typeof photo === 'string') {
          formData.append(`photos[${index}]`, photo)
        }
      })
    } else {
      // Если фото нет, отправляем пустой массив
      formData.append('photos', '[]')
    }
    
    // Обрабатываем видео
    if (form.video && form.video.length > 0) {
      form.video.forEach((video: any, index: number) => {
        if (video instanceof File) {
          formData.append(`video[${index}]`, video)
        } else if (typeof video === 'string') {
          formData.append(`video[${index}]`, video)
        }
      })
    } else {
      // Если видео нет, отправляем пустой массив
      formData.append('video', '[]')
    }
    
    // ВСЕГДА используем POST /draft для черновиков
    // Контроллер сам определит - создавать новый или обновить существующий
    router.post('/draft', formData as any, {
      preserveScroll: true,
      forceFormData: true,
      onStart: () => {
        // Debug log removed
      },
      onSuccess: (page) => {
        // Debug log removed
      },
      onError: (errors) => {
        // Обработка ошибки
      },
      onFinish: () => {
        // Debug log removed
        saving.value = false
      }
    })
  }

  // Публикация объявления
  const handlePublish = async () => {
    if (!authStore.isAuthenticated) {
      router.visit('/login')
      return
    }
    
    if (!validateForm()) {
      return
    }
    
    saving.value = true
    
    const publishData = {
      ...form,
      category: props.category
    }
    
    // Отправляем на публикацию через Inertia
    router.post('/ads/publish', publishData, {
      preserveScroll: true,
      onError: (errorResponse: any) => {
        // Обработка ошибки
        errors.value = errorResponse
      },
      onFinish: () => {
        saving.value = false
      }
    })
  }


  // Отмена редактирования и возврат к списку
  const handleCancel = () => {
    emit('cancel')
  }

  // Автосохранение в localStorage для новых объявлений
  if (isNewAd) {
    watch(form, (newValue) => {
      try {
        localStorage.setItem('adFormData', JSON.stringify(newValue))
      } catch (e) {
        // Молча игнорируем ошибку сохранения
      }
    }, { deep: true })
  }

  return {
    form,
    errors,
    saving,
    isEditMode,
    handleSubmit,
    handleSaveDraft,
    handlePublish,
    handleCancel
  }
}