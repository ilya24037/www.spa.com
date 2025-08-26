import { ref, reactive, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/authStore'

// Функция миграции параметров для обратной совместимости
const migrateParameters = (data: any): any => {
  // Если уже в новом формате с объектом parameters
  if (data?.parameters && typeof data.parameters === 'object') {
    return data.parameters
  }
  
  // Мигрируем из старого формата (отдельные поля)
  return {
    title: data?.title || '',
    age: data?.age || '',
    height: data?.height || '',
    weight: data?.weight || '',
    breast_size: data?.breast_size || '',
    hair_color: data?.hair_color || '',
    eye_color: data?.eye_color || '',
    nationality: data?.nationality || ''
  }
}

// Функция миграции контактов для обратной совместимости
const migrateContacts = (data: any): any => {
  // Если уже в новом формате с объектом contacts
  if (data?.contacts && typeof data.contacts === 'object') {
    return data.contacts
  }
  
  // Мигрируем из старого формата (отдельные поля)
  return {
    phone: data?.phone || '',
    contact_method: data?.contact_method || 'any',  // ✅ Исправлено на 'any' как было изначально
    whatsapp: data?.whatsapp || '',
    telegram: data?.telegram || ''
  }
}

// Типы данных формы
export interface AdFormData {
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
  // Объединяем параметры в единый объект
  parameters: {
    title: string
    age: string | number
    height: string
    weight: string
    breast_size: string
    hair_color: string
    eye_color: string
    nationality: string
  }
  new_client_discount: string
  gift: string
  photos: any[]
  video: any[]

  geo: any
  address: string
  travel_area: string
  custom_travel_areas: string[]
  travel_radius: string | number
  travel_price: number | null
  travel_price_type: string
  // Объединяем контакты в единый объект
  contacts: {
    phone: string
    contact_method: string
    whatsapp: string
    telegram: string
  }
  faq?: Record<string, any> // FAQ ответы
  
  // Поля верификации
  verification_photo: string | null
  verification_video: string | null
  verification_status: string
  verification_comment: string | null
  verification_expires_at: string | null
}

// Композабл для управления формой
export function useAdFormModel(props: any, emit: any) {
  const authStore = useAuthStore()
  
  // НЕ восстанавливаем данные из localStorage для новых объявлений
  // Это предотвращает появление старых данных в новой форме
  let savedFormData = null
  const isNewAd = !props.adId && !props.initialData?.id
  
  // ВАЖНО: Очищаем localStorage при создании нового объявления
  if (isNewAd) {
    // Очищаем старые данные, чтобы форма была пустой
    localStorage.removeItem('adFormData')
    // localStorage очищен для нового объявления
  }
  
  // Состояние формы
  const form = reactive<AdFormData>({
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
    services: (() => {
      if (savedFormData?.services) return savedFormData.services
      if (!props.initialData?.services) return {}
      if (typeof props.initialData.services === 'object' && !Array.isArray(props.initialData.services)) {
        return props.initialData.services
      }
      if (typeof props.initialData.services === 'string') {
        try {
          return JSON.parse(props.initialData.services)
        } catch (e) {
          return {}
        }
      }
      return {}
    })(),
    services_additional_info: savedFormData?.services_additional_info || props.initialData?.services_additional_info || '',
    features: (() => {
      if (savedFormData?.features) return savedFormData.features
      if (!props.initialData?.features) return []
      if (Array.isArray(props.initialData.features)) return props.initialData.features
      if (typeof props.initialData.features === 'string') {
        try {
          const parsed = JSON.parse(props.initialData.features)
          return Array.isArray(parsed) ? parsed : []
        } catch (e) {
          return []
        }
      }
      return []
    })(),
    additional_features: savedFormData?.additional_features || props.initialData?.additional_features || '',
    schedule: (() => {
      // Сначала проверяем сохраненные данные
      if (savedFormData?.schedule) {
        console.log('📅 adFormModel: Используем schedule из savedFormData', savedFormData.schedule)
        return savedFormData.schedule
      }
      // Если schedule есть в initialData
      if (props.initialData?.schedule) {
        console.log('📅 adFormModel: initialData.schedule найден:', {
          value: props.initialData.schedule,
          type: typeof props.initialData.schedule
        })
        // Если это строка - парсим JSON
        if (typeof props.initialData.schedule === 'string') {
          try {
            const parsed = JSON.parse(props.initialData.schedule)
            console.log('📅 adFormModel: schedule успешно распарсен из JSON:', parsed)
            return parsed
          } catch (e) {
            console.warn('❌ Ошибка парсинга schedule:', e)
            return {}
          }
        }
        // Если это уже объект - используем как есть
        console.log('📅 adFormModel: schedule уже объект, используем как есть:', props.initialData.schedule)
        return props.initialData.schedule
      }
      // По умолчанию
      console.log('📅 adFormModel: schedule не найден, используем пустой объект')
      return {}
    })(),
    schedule_notes: (() => {
      // Сначала проверяем сохраненные данные
      if (savedFormData?.schedule_notes) {
        console.log('📅 adFormModel: Используем schedule_notes из savedFormData:', savedFormData.schedule_notes)
        return savedFormData.schedule_notes
      }
      // Если schedule_notes есть в initialData
      if (props.initialData?.schedule_notes) {
        console.log('📅 adFormModel: initialData.schedule_notes найден:', {
          value: props.initialData.schedule_notes,
          type: typeof props.initialData.schedule_notes
        })
        return props.initialData.schedule_notes
      }
      // По умолчанию
      console.log('📅 adFormModel: schedule_notes не найден, используем пустую строку')
      return ''
    })(),
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
        outcall_express: null,
        outcall_1h: null,
        outcall_2h: null,
        outcall_night: null,
        taxi_included: false,
        outcall_apartment: true,
        outcall_hotel: false,
        outcall_house: false,
        outcall_sauna: false,
        outcall_office: false
      }
    })(),
    // Используем функцию миграции для обратной совместимости
    parameters: migrateParameters(savedFormData || props.initialData),
    new_client_discount: props.initialData?.new_client_discount || '',
    gift: props.initialData?.gift || '',
    photos: (() => {
      if (!props.initialData?.photos) return []
      if (Array.isArray(props.initialData.photos)) return props.initialData.photos
      if (typeof props.initialData.photos === 'string') {
        try {
          const parsed = JSON.parse(props.initialData.photos)
          return Array.isArray(parsed) ? parsed : []
        } catch (e) {
          return []
        }
      }
      return []
    })(),
    video: (() => {
      if (!props.initialData?.video) return []
      if (Array.isArray(props.initialData.video)) return props.initialData.video
      if (typeof props.initialData.video === 'string') {
        try {
          const parsed = JSON.parse(props.initialData.video)
          return Array.isArray(parsed) ? parsed : []
        } catch (e) {
          return []
        }
      }
      return []
    })(),

    geo: props.initialData?.geo || {},
    address: props.initialData?.address || '',
    travel_area: props.initialData?.travel_area || 'no_travel',
    custom_travel_areas: props.initialData?.custom_travel_areas || [],
    travel_radius: props.initialData?.travel_radius || '',
    travel_price: props.initialData?.travel_price || null,
    travel_price_type: props.initialData?.travel_price_type || 'free',
    // Используем функцию миграции для обратной совместимости
    contacts: migrateContacts(savedFormData || props.initialData),
    faq: (() => {
      if (props.initialData?.faq) {
        if (typeof props.initialData.faq === 'string') {
          try {
            return JSON.parse(props.initialData.faq)
          } catch (e) {
            return {}
          }
        }
        return props.initialData.faq
      }
      return {}
    })(),
    
    // Поля верификации
    verification_photo: props.initialData?.verification_photo || null,
    verification_video: props.initialData?.verification_video || null,
    verification_status: props.initialData?.verification_status || 'none',
    verification_comment: props.initialData?.verification_comment || null,
    verification_expires_at: props.initialData?.verification_expires_at || null
  })

  // Ошибки валидации
  const errors = ref<Record<string, string[]>>({})
  
  // Состояние сохранения
  const saving = ref(false)
  
  // Режим редактирования - учитываем оба источника ID
  const isEditMode = computed(() => {
    // Проверяем оба источника ID
    const idFromProps = Number(props.adId)
    const idFromData = Number(props.initialData?.id)
    
    const hasValidPropsId = !isNaN(idFromProps) && idFromProps > 0
    const hasValidDataId = !isNaN(idFromData) && idFromData > 0
    
    // Проверяем оба источника ID для определения режима редактирования
    return hasValidPropsId || hasValidDataId
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
    
    console.log('🔍 validateForm: Проверяем поля формы', {
      'parameters.title': form.parameters.title,
      'specialty': form.specialty,
      'price': form.price,
      'contacts.phone': form.contacts.phone,
      'geo.city': form.geo?.city,
      'geo': form.geo
    })
    
    if (!form.parameters.title) {
      newErrors['parameters.title'] = ['Название объявления обязательно']
      console.log('❌ validateForm: Название объявления пустое')
    }
    
    // specialty теперь необязательно - убрали валидацию
    
    if (!form.price || form.price <= 0) {
      newErrors.price = ['Укажите корректную цену']
      console.log('❌ validateForm: Цена некорректная:', form.price)
    }
    
    if (!form.contacts.phone) {
      newErrors['contacts.phone'] = ['Телефон обязателен']
      console.log('❌ validateForm: Телефон пустой')
    }
    
    if (!form.geo?.city) {
      newErrors['geo.city'] = ['Выберите город']
      console.log('❌ validateForm: Город не выбран')
    }
    
    console.log('🔍 validateForm: Результат валидации', {
      errors: newErrors,
      errorsCount: Object.keys(newErrors).length,
      isValid: Object.keys(newErrors).length === 0
    })
    
    errors.value = newErrors
    return Object.keys(newErrors).length === 0
  }

  // Обработка отправки формы
  const handleSubmit = async () => {
    console.log('🔵 adFormModel: КНОПКА "СОХРАНИТЬ ИЗМЕНЕНИЯ" НАЖАТА', {
      isEditMode: isEditMode.value,
      adId: props.adId,
      initialDataId: props.initialData?.id,
      initialDataStatus: props.initialData?.status,
      formData: {
        title: form.parameters.title,
        specialty: form.specialty,
        service_provider: form.service_provider,
        clients: form.clients
      }
    })
    
    // ВРЕМЕННО: отключаем валидацию для активных объявлений
    if (props.initialData?.status !== 'active' && !validateForm()) {
      console.log('❌ adFormModel: Валидация не прошла')
      return
    }
    
    if (props.initialData?.status === 'active') {
      console.log('✅ adFormModel: Пропускаем валидацию для активного объявления')
    }
    
    console.log('✅ adFormModel: Валидация прошла успешно')
    saving.value = true
    
    // Подготовка данных для отправки - ИСПРАВЛЯЕМ СТРУКТУРУ
    const submitData = {
      ...form,
      // ✅ Извлекаем поля из parameters объекта для backend совместимости
      title: form.parameters.title,
      age: form.parameters.age,
      height: form.parameters.height,
      weight: form.parameters.weight,
      breast_size: form.parameters.breast_size,
      hair_color: form.parameters.hair_color,
      eye_color: form.parameters.eye_color,
      nationality: form.parameters.nationality,
      // ✅ Извлекаем поля из contacts объекта для backend совместимости  
      phone: form.contacts.phone,
      contact_method: form.contacts.contact_method,
      whatsapp: form.contacts.whatsapp,
      telegram: form.contacts.telegram,
      // ✅ Исправляем is_starting_price - backend ждет array, а не boolean
      is_starting_price: form.is_starting_price ? ['true'] : [],
      category: props.category
    }
    
    console.log('📤 adFormModel: Подготовлены данные для отправки', {
      submitDataKeys: Object.keys(submitData),
      title: submitData.title,
      phone: submitData.phone,
      is_starting_price: submitData.is_starting_price,
      service_provider: submitData.service_provider,
      clients: submitData.clients,
      contacts: submitData.contacts
    })
    
    
    // Если это редактирование существующего объявления
    if (isEditMode.value) {
      // Определяем ID для редактирования (та же логика что и в handleSaveDraft)
      let editId = null
      if (props.adId && Number(props.adId) > 0) {
        editId = Number(props.adId)
      } else if (props.initialData?.id && Number(props.initialData.id) > 0) {
        editId = Number(props.initialData.id)
      }
      
      // Для черновиков используем PUT на /draft/{id}
      if (props.initialData?.status === 'draft') {
        console.log('🟡 adFormModel: Отправляем PUT запрос для ЧЕРНОВИКА', {
          url: `/draft/${editId}`,
          editId: editId,
          submitDataKeys: Object.keys(submitData)
        })
        
        router.put(`/draft/${editId}`, submitData, {
          preserveScroll: true,
          onSuccess: () => {
            console.log('✅ adFormModel: Черновик успешно обновлен')
            emit('success')
          },
          onError: (errorResponse: any) => {
            console.log('❌ adFormModel: Ошибка обновления черновика', errorResponse)
            errors.value = errorResponse
          },
          onFinish: () => {
            console.log('🏁 adFormModel: Черновик - запрос завершен')
            saving.value = false
          }
        })
      } else {
        console.log('🟢 adFormModel: Отправляем PUT запрос для АКТИВНОГО ОБЪЯВЛЕНИЯ', {
          url: `/ads/${editId}`,
          editId: editId,
          submitDataKeys: Object.keys(submitData),
          service_provider: submitData.service_provider,
          clients: submitData.clients
        })
        
        // Для обычных объявлений используем PUT на /ads/{id}
        router.put(`/ads/${editId}`, submitData, {
          preserveScroll: true,
          onSuccess: () => {
            // ✅ Позволяем Backend сделать redirect (как у черновиков)
            console.log('🟢 adFormModel: Активное объявление успешно обновлено, Backend сделает redirect')
            // Не делаем router.visit() - Backend сам перенаправит
            emit('success')
          },
          onError: (errorResponse: any) => {
            console.log('❌ adFormModel: Ошибка обновления активного объявления', errorResponse)
            errors.value = errorResponse
          },
          onFinish: () => {
            console.log('🏁 adFormModel: Активное объявление - запрос завершен')
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

  // Сохранение черновика с корректной логикой PUT/POST
  const handleSaveDraft = async () => {
    try {
      saving.value = true
    
    // Создаем FormData для отправки файлов
    const formData = new FormData()
    
    // Определяем ID для редактирования существующего черновика
    // Приоритет: props.adId > props.initialData.id
    let adId = null
    
    if (props.adId && Number(props.adId) > 0) {
      adId = Number(props.adId)
    } else if (props.initialData?.id && Number(props.initialData.id) > 0) {
      adId = Number(props.initialData.id)
    }
    
    
    // Добавляем все обычные поля
    formData.append('category', props.category || '')
    formData.append('title', form.parameters.title || '')
    formData.append('specialty', form.specialty || '')
    formData.append('work_format', form.work_format || '')
    formData.append('experience', form.experience || '')
    // Важно: всегда отправляем description как строку, даже если пустая
    formData.append('description', form.description || '')
    formData.append('services_additional_info', form.services_additional_info || '')
    formData.append('additional_features', form.additional_features || '')
    formData.append('schedule_notes', form.schedule_notes || '')
    console.log('📅 adFormModel: Добавляем schedule_notes в FormData', {
      schedule_notes: form.schedule_notes,
      schedule_notesType: typeof form.schedule_notes
    })
    formData.append('online_booking', form.online_booking ? '1' : '0')
    console.log('📅 adFormModel: Добавляем online_booking в FormData', {
      online_booking: form.online_booking,
      online_bookingType: typeof form.online_booking
    })
    formData.append('price', form.price?.toString() || '')
    formData.append('price_unit', form.price_unit || '')
    formData.append('is_starting_price', form.is_starting_price ? '1' : '0')
    
    // Добавляем новые поля ценообразования
    if (form.prices) {
      formData.append('prices[apartments_express]', form.prices.apartments_express?.toString() || '')
      formData.append('prices[apartments_1h]', form.prices.apartments_1h?.toString() || '')
      formData.append('prices[apartments_2h]', form.prices.apartments_2h?.toString() || '')
      formData.append('prices[apartments_night]', form.prices.apartments_night?.toString() || '')
      formData.append('prices[outcall_express]', form.prices.outcall_express?.toString() || '')
      formData.append('prices[outcall_1h]', form.prices.outcall_1h?.toString() || '')
      formData.append('prices[outcall_2h]', form.prices.outcall_2h?.toString() || '')
      formData.append('prices[outcall_night]', form.prices.outcall_night?.toString() || '')
      formData.append('prices[taxi_included]', form.prices.taxi_included ? '1' : '0')
      // Места выезда
      formData.append('prices[outcall_apartment]', form.prices.outcall_apartment ? '1' : '0')
      formData.append('prices[outcall_hotel]', form.prices.outcall_hotel ? '1' : '0')
      formData.append('prices[outcall_house]', form.prices.outcall_house ? '1' : '0')
      formData.append('prices[outcall_sauna]', form.prices.outcall_sauna ? '1' : '0')
      formData.append('prices[outcall_office]', form.prices.outcall_office ? '1' : '0')
    }
    
    // Добавляем параметры (из объекта parameters для обратной совместимости с backend)
    formData.append('age', form.parameters.age?.toString() || '')
    formData.append('height', form.parameters.height || '')
    formData.append('weight', form.parameters.weight || '')
    formData.append('breast_size', form.parameters.breast_size || '')
    formData.append('hair_color', form.parameters.hair_color || '')
    formData.append('eye_color', form.parameters.eye_color || '')
    formData.append('nationality', form.parameters.nationality || '')
    formData.append('new_client_discount', form.new_client_discount || '')
    formData.append('gift', form.gift || '')
    formData.append('address', form.address || '')
    formData.append('travel_area', form.travel_area || '')
    formData.append('travel_radius', form.travel_radius?.toString() || '')
    formData.append('travel_price', form.travel_price?.toString() || '')
    formData.append('travel_price_type', form.travel_price_type || '')
    // Добавляем контактные данные из объекта contacts для обратной совместимости с backend
    formData.append('phone', form.contacts.phone || '')
    formData.append('contact_method', form.contacts.contact_method || '')
    formData.append('whatsapp', form.contacts.whatsapp || '')
    formData.append('telegram', form.contacts.telegram || '')
    
    // Добавляем массивы как JSON
    try {
      if (form.clients) formData.append('clients', JSON.stringify(form.clients))
      if (form.service_location) formData.append('service_location', JSON.stringify(form.service_location))
      if (form.service_provider) formData.append('service_provider', JSON.stringify(form.service_provider))
      if (form.services) formData.append('services', JSON.stringify(form.services))
      if (form.features) formData.append('features', JSON.stringify(form.features))
      if (form.schedule) {
        console.log('📅 adFormModel: Добавляем schedule в FormData', {
          schedule: form.schedule,
          scheduleType: typeof form.schedule,
          scheduleStringified: JSON.stringify(form.schedule)
        })
        formData.append('schedule', JSON.stringify(form.schedule))
      } else {
        console.log('📅 adFormModel: form.schedule пустой или undefined', {
          schedule: form.schedule,
          scheduleType: typeof form.schedule
        })
      }

      // Проверяем, не является ли geo уже строкой
      if (form.geo) {
        if (typeof form.geo === 'string') {
          // Если уже строка, отправляем как есть
          formData.append('geo', form.geo)
        } else {
          // Если объект, сериализуем
          formData.append('geo', JSON.stringify(form.geo))
        }
      }
      if (form.custom_travel_areas) formData.append('custom_travel_areas', JSON.stringify(form.custom_travel_areas))
      
      // Добавляем FAQ данные
      if (form.faq && Object.keys(form.faq).length > 0) {
        formData.append('faq', JSON.stringify(form.faq))
      }
    } catch (jsonError) {
      // Молча игнорируем ошибку JSON
    }
    
    // KISS: Всегда отправляем полный массив фотографий
    // Подготовка photos для отправки
    
    console.log('📸 adFormModel: НАЧИНАЕМ ПОДГОТОВКУ PHOTOS', {
      formPhotos: form.photos,
      photosLength: form.photos?.length,
      photosType: typeof form.photos,
      isArray: Array.isArray(form.photos),
      photosDetailed: form.photos?.map((p, i) => ({
        index: i,
        type: typeof p,
        isFile: p instanceof File,
        hasUrl: p?.url,
        hasPreview: p?.preview,
        id: p?.id
      }))
    })
    
    if (form.photos && Array.isArray(form.photos)) {
      console.log('✅ adFormModel: form.photos является массивом, начинаем итерацию')
      // Всегда отправляем массив photos, даже если пустой
      form.photos.forEach((photo: any, index: number) => {
        console.log(`📸 adFormModel: Обрабатываем фото ${index}`, {
          photo: photo,
          type: typeof photo,
          isFile: photo instanceof File,
          hasUrl: photo?.url,
          hasPreview: photo?.preview,
          id: photo?.id
        })
        
        if (photo instanceof File) {
          console.log(`✅ adFormModel: Фото ${index} является File, добавляем в FormData`)
          formData.append(`photos[${index}]`, photo)
        } else if (typeof photo === 'string' && photo !== '') {
          console.log(`✅ adFormModel: Фото ${index} является строкой, добавляем в FormData:`, photo)
          formData.append(`photos[${index}]`, photo)
        } else if (typeof photo === 'object' && photo !== null) {
          const value = photo.url || photo.preview || ''
          console.log(`📸 adFormModel: Фото ${index} является объектом, извлекаем value:`, value)
          if (value) {
            console.log(`✅ adFormModel: Фото ${index} добавляем объект в FormData`)
            formData.append(`photos[${index}]`, value)
          } else {
            console.log(`❌ adFormModel: Фото ${index} объект без url/preview, пропускаем`)
          }
        } else {
          console.log(`❌ adFormModel: Фото ${index} неизвестный тип, пропускаем`)
        }
      })
      
      // Если массив пустой, явно отправляем пустой массив
      if (form.photos.length === 0) {
        console.log('❌ adFormModel: Массив photos пуст, отправляем []')
        formData.append('photos', '[]')
      } else {
        console.log('✅ adFormModel: Добавили фото в FormData, количество:', form.photos.length)
      }
    } else {
      // Если photos не инициализирован, отправляем пустой массив
      console.log('❌ adFormModel: form.photos НЕ массив, отправляем []')
      formData.append('photos', '[]')
    }
    
    // Обрабатываем видео (аналогично photos)
    if (form.video && Array.isArray(form.video)) {
      console.log('🎥 adFormModel: Обрабатываем видео:', {
        videoCount: form.video.length,
        videoData: form.video
      })
      
      // Всегда отправляем массив video, даже если пустой
      form.video.forEach((video: any, index: number) => {
        console.log(`🎥 adFormModel: Обрабатываем видео ${index}:`, {
          video,
          isFile: video instanceof File,
          hasFile: video?.file instanceof File,
          hasUrl: !!video?.url,
          videoType: typeof video
        })
        
        if (video instanceof File) {
          // Прямой File объект
          formData.append(`video[${index}]`, video)
          console.log(`🎥 adFormModel: Добавлен File для видео ${index}`)
        } else if (video?.file instanceof File) {
          // Video объект с File полем (основной случай)
          formData.append(`video[${index}]`, video.file)
          console.log(`🎥 adFormModel: Добавлен video.file для видео ${index}`)
        } else if (typeof video === 'string' && video !== '') {
          // Строковые URL
          formData.append(`video[${index}]`, video)
          console.log(`🎥 adFormModel: Добавлен URL для видео ${index}`)
        } else if (typeof video === 'object' && video !== null) {
          // Объект без File
          const value = video.url || video.preview || ''
          if (value) {
            formData.append(`video[${index}]`, value)
            console.log(`🎥 adFormModel: Добавлен value для видео ${index}:`, value)
          } else {
            formData.append(`video[${index}]`, JSON.stringify(video))
            console.log(`🎥 adFormModel: Добавлен JSON для видео ${index}`)
          }
        }
      })
      
      // Если массив пустой, явно отправляем пустой массив
      if (form.video.length === 0) {
        formData.append('video', '[]')
      }
    } else {
      // Если video не инициализирован, отправляем пустой массив
      formData.append('video', '[]')
    }
    
    // KISS: Простая логика отправки
    if (adId && adId > 0) {
      // Обновление существующего черновика
      
      // Проверяем, есть ли файлы (фото или видео) 
      // ИСПРАВЛЕНИЕ: видео может быть как File, так и объект с file полем
      const hasPhotoFiles = form.photos?.some((p: any) => p instanceof File)
      const hasVideoFiles = form.video?.some((v: any) => 
        v instanceof File || (v && typeof v === 'object' && (v.file instanceof File || v.url))
      )
      const hasFiles = hasPhotoFiles || hasVideoFiles
      
      if (hasFiles) {
        // Если есть файлы - используем FormData с POST и _method=PUT
        formData.append('_method', 'PUT')
        
        console.log('🚀 adFormModel: ОТПРАВЛЯЕМ ЗАПРОС (с файлами) PUT /draft/' + adId, {
          adId: adId,
          hasPhotoFiles: hasPhotoFiles,
          hasVideoFiles: hasVideoFiles,
          formDataEntries: Array.from((formData as any).entries()).filter(([key]) => key.startsWith('photos')),
          method: 'POST с _method=PUT'
        })
        
        router.post(`/draft/${adId}`, formData as any, {
          preserveScroll: true,
          forceFormData: true,
          onSuccess: () => {
            saving.value = false
          },
          onError: (errors) => {
            saving.value = false
          },
          onFinish: () => {
            saving.value = false
          }
        })
      } else {
        // Если файлов нет - используем обычный PUT с JSON
        
        console.log('🚀 adFormModel: ОТПРАВЛЯЕМ ЗАПРОС (без файлов) PUT /draft/' + adId, {
          adId: adId,
          hasPhotoFiles: hasPhotoFiles,
          hasVideoFiles: hasVideoFiles,
          method: 'PUT с JSON'
        })
        
        // Правильная конвертация FormData в объект с поддержкой индексированных массивов
        const plainData: any = {}
        
        console.log('🔧 adFormModel: Начинаем конвертацию FormData в plainData')
        
        formData.forEach((value, key) => {
          console.log(`🔧 adFormModel: Обрабатываем ключ "${key}" = "${value}"`)
          
          // Пропускаем пустые значения
          if (value === '' || value === undefined) return
          
          // Проверяем, это индексированный массив (например photos[0], photos[1])
          const indexMatch = key.match(/^(\w+)\[(\d+)\]$/)
          if (indexMatch) {
            const arrayName = indexMatch[1] // 'photos'
            const arrayIndex = parseInt(indexMatch[2], 10) // 0, 1, 2...
            
            console.log(`🔧 adFormModel: Найден индексированный массив: ${arrayName}[${arrayIndex}]`)
            
            // Создаем массив если его еще нет
            if (!plainData[arrayName]) {
              plainData[arrayName] = []
            }
            
            // Добавляем значение в нужную позицию массива
            plainData[arrayName][arrayIndex] = value
            
            console.log(`✅ adFormModel: Добавили в ${arrayName}[${arrayIndex}], текущий размер массива:`, plainData[arrayName].length)
            return
          }
          
          // Парсим JSON строки
          if (typeof value === 'string' && (value.startsWith('[') || value.startsWith('{'))) {
            try {
              plainData[key] = JSON.parse(value)
              console.log(`✅ adFormModel: JSON распарсен для ключа "${key}"`)
            } catch {
              plainData[key] = value
              console.log(`⚠️ adFormModel: Ошибка парсинга JSON для ключа "${key}", оставляем как строку`)
            }
          } else {
            plainData[key] = value
            console.log(`✅ adFormModel: Обычное значение для ключа "${key}"`)
          }
        })
        
        console.log('📤 adFormModel: plainData для PUT запроса:', {
          photos: plainData.photos,
          photosType: typeof plainData.photos,
          allKeys: Object.keys(plainData)
        })
        
        router.put(`/draft/${adId}`, plainData, {
          preserveScroll: true,
          onSuccess: () => {
            saving.value = false
          },
          onError: (errors) => {
            saving.value = false
          },
          onFinish: () => {
            saving.value = false
          }
        })
      }
    } else {
      // Создание нового черновика
      console.log('🚀 adFormModel: СОЗДАЕМ НОВЫЙ ЧЕРНОВИК POST /draft', {
        hasPhotoFiles: form.photos?.some((p: any) => p instanceof File),
        photosCount: form.photos?.length || 0,
        formDataPhotos: Array.from((formData as any).entries()).filter(([key]) => key.startsWith('photos')),
        method: 'POST FormData'
      })
      
      router.post('/draft', formData as any, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
          saving.value = false
        },
        onError: (errors) => {
          saving.value = false
        },
        onFinish: () => {
          saving.value = false
        }
      })
    }
    } catch (error) {
      saving.value = false
    }
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

  // Автосохранение в localStorage ТОЛЬКО для существующих черновиков
  // НЕ сохраняем для новых объявлений, чтобы избежать конфликтов
  if (!isNewAd && props.initialData?.status === 'draft') {
    watch(form, (newValue) => {
      try {
        // Сохраняем с ID черновика в ключе, чтобы разделить данные разных черновиков
        const storageKey = `adFormData_draft_${props.adId || props.initialData?.id}`
        localStorage.setItem(storageKey, JSON.stringify(newValue))
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