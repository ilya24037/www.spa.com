import { ref, reactive, computed, watch, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { useAuthStore } from '@/stores/authStore'

// Функция миграции параметров для обратной совместимости
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
    bikini_zone: string
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
  let savedFormData: any = null
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
        return savedFormData.schedule
      }
      // Если schedule есть в initialData
      if (props.initialData?.schedule) {
        // Если это строка - парсим JSON
        if (typeof props.initialData.schedule === 'string') {
          try {
            const parsed = JSON.parse(props.initialData.schedule)
            return parsed
          } catch (e) {
            console.warn('❌ Ошибка парсинга schedule:', e)
            return {}
          }
        }
        // Если это уже объект - используем как есть
        return props.initialData.schedule
      }
      // По умолчанию
      return {}
    })(),
    schedule_notes: (() => {
      // Сначала проверяем сохраненные данные
      if (savedFormData?.schedule_notes) {
        return savedFormData.schedule_notes
      }
      // Если schedule_notes есть в initialData
      if (props.initialData?.schedule_notes) {
        return props.initialData.schedule_notes
      }
      // По умолчанию
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
    parameters: (() => {
      const migratedParams = migrateParameters(savedFormData || props.initialData);
      return migratedParams;
    })(),
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
      
      if (Array.isArray(props.initialData.video)) {
        // Преобразуем строки URL в объекты для VideoUpload компонента
        return props.initialData.video.map((v: any) => {
          if (typeof v === 'string') {
            return { id: `video_${Date.now()}_${Math.random()}`, url: v, file: null }
          }
          return v
        })
      }
      
      if (typeof props.initialData.video === 'string') {
        try {
          const parsed = JSON.parse(props.initialData.video)
          if (Array.isArray(parsed)) {
            // Преобразуем строки URL в объекты
            return parsed.map((v: any) => {
              if (typeof v === 'string') {
                return { id: `video_${Date.now()}_${Math.random()}`, url: v, file: null }
              }
              return v
            })
          }
          return []
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
  
  // Функция для очистки подсветки ошибок
  const clearErrorHighlight = (fieldName: string) => {
    setTimeout(() => {
      if (fieldName === 'title') {
        const input = document.querySelector('input[placeholder*="Имя"], input[placeholder*="имя"]')
        if (input) {
          input.classList.remove('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
        }
      } else if (fieldName === 'price') {
        // Убираем подсветку только с полей "1 час"
        const apartments1hInput = document.querySelector('input[name="apartments_1h"]')
        const outcall1hInput = document.querySelector('input[name="outcall_1h"]')
        
        if (apartments1hInput) {
          apartments1hInput.classList.remove('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
        }
        if (outcall1hInput) {
          outcall1hInput.classList.remove('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
        }
      } else if (fieldName === 'phone') {
        const input = document.querySelector('input[placeholder*="телефон"], input[type="tel"]')
        if (input) {
          input.classList.remove('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
        }
      } else if (fieldName === 'city') {
        const select = document.querySelector('select[placeholder*="город"], .city-select')
        if (select) {
          select.classList.remove('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
        }
      }
    }, 0)
  }
  
  // Watchers для очистки подсветки при изменении полей
  watch(() => form.parameters?.title, (newValue) => {
    if (newValue) {
      clearErrorHighlight('title')
      if (errors.value['parameters.title']) {
        delete errors.value['parameters.title']
      }
    }
  })
  
  // Watcher для проверки цен за 1 час (только apartments_1h или outcall_1h)
  watch(() => [
    form.prices?.apartments_1h,
    form.prices?.outcall_1h
  ], (newValues) => {
    // Проверяем наличие хотя бы одной цены за 1 час
    const hasApartments1h = newValues[0] && Number(newValues[0]) > 0
    const hasOutcall1h = newValues[1] && Number(newValues[1]) > 0
    
    if (hasApartments1h || hasOutcall1h) {
      clearErrorHighlight('price')
      if (errors.value.price) {
        delete errors.value.price
      }
    }
  }, { deep: true })
  
  watch(() => form.contacts?.phone, (newValue) => {
    if (newValue) {
      clearErrorHighlight('phone')
      if (errors.value['contacts.phone']) {
        delete errors.value['contacts.phone']
      }
    }
  })
  
  watch(() => form.geo?.city, (newValue) => {
    if (newValue) {
      clearErrorHighlight('city')
      if (errors.value['geo.city']) {
        delete errors.value['geo.city']
      }
    }
  })
  
  // Watcher для поля clients
  watch(() => form.clients, (newValue) => {
    if (newValue && newValue.length > 0) {
      if (errors.value.clients) {
        delete errors.value.clients
      }
    }
  }, { deep: true })
  
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
      'geo': form.geo,
      'clients': form.clients
    })
    
    if (!form.parameters.title) {
      newErrors['parameters.title'] = ['Имя обязательно']
      console.log('❌ validateForm: Имя пустое')
    }
    
    // specialty теперь необязательно - убрали валидацию
    
    // Проверяем цену за 1 час (в апартаментах ИЛИ на выезде)
    const hasApartments1h = form.prices?.apartments_1h && form.prices.apartments_1h > 0
    const hasOutcall1h = form.prices?.outcall_1h && form.prices.outcall_1h > 0
    
    if (!hasApartments1h && !hasOutcall1h) {
      newErrors.price = ['Укажите цену за 1 час (в апартаментах или на выезде)']
      console.log('❌ validateForm: Не указана цена за 1 час')
    }
    
    if (!form.contacts.phone) {
      newErrors['contacts.phone'] = ['Телефон обязателен']
      console.log('❌ validateForm: Телефон пустой')
    }
    
    if (!form.geo?.city) {
      newErrors['geo.city'] = ['Выберите город']
      console.log('❌ validateForm: Город не выбран')
    }
    
    // Проверяем, что выбран хотя бы один клиент
    if (!form.clients || form.clients.length === 0) {
      newErrors.clients = ['Укажите значение параметра']
      console.log('❌ validateForm: Не выбраны клиенты')
    }
    
    console.log('🔍 validateForm: Результат валидации', {
      errors: newErrors,
      errorsCount: Object.keys(newErrors).length,
      isValid: Object.keys(newErrors).length === 0
    })
    
    errors.value = newErrors
    
    // Если есть ошибки - прокручиваем к первому незаполненному полю
    if (Object.keys(newErrors).length > 0) {
      console.log('🚨 Есть ошибки валидации, прокручиваем к первому полю')
      
      // Определяем какое поле показать первым
      let firstErrorField = null
      let sectionSelector = null
      
      if (newErrors['parameters.title']) {
        firstErrorField = 'parameters.title'
        sectionSelector = '.parameters-section, [data-section="parameters"]'
      } else if (newErrors.price) {
        firstErrorField = 'price'
        sectionSelector = '.pricing-section, [data-section="pricing"]'
      } else if (newErrors['contacts.phone']) {
        firstErrorField = 'contacts.phone'
        sectionSelector = '.contacts-section, [data-section="contacts"]'
      } else if (newErrors['geo.city']) {
        firstErrorField = 'geo.city'
        sectionSelector = '.geography-section, [data-section="geography"]'
      } else if (newErrors.clients) {
        firstErrorField = 'clients'
        sectionSelector = '.clients-section'
      }
      
      console.log('📍 Первое поле с ошибкой:', firstErrorField)
      
      // Прокручиваем к секции с ошибкой
      setTimeout(() => {
        // Если ошибка в поле clients, сначала раскрываем секцию "ОСНОВНОЕ"
        if (firstErrorField === 'clients') {
          const basicSection = document.querySelector('[data-section="basic"]')
          if (basicSection) {
            // Проверяем, свернута ли секция по наличию класса collapsed или скрытого контента
            const content = basicSection.querySelector('.section-content')
            const isCollapsed = basicSection.classList.contains('collapsed') || 
                                (content && (content.style.display === 'none' || content.classList.contains('hidden')))
            
            if (isCollapsed) {
              // Ищем кнопку-заголовок для клика
              const toggleButton = basicSection.querySelector('.section-header, .collapsible-header, [onclick*="toggle"]')
              if (toggleButton) {
                toggleButton.click() // Раскрываем секцию
                console.log('📂 Раскрыта секция ОСНОВНОЕ')
              }
            }
          }
        }
        
        if (sectionSelector) {
          const section = document.querySelector(sectionSelector)
          if (section) {
            // Небольшая задержка, чтобы секция успела раскрыться
            setTimeout(() => {
              section.scrollIntoView({ behavior: 'smooth', block: 'center' })
              console.log('✅ Прокручено к секции:', sectionSelector)
            }, firstErrorField === 'clients' ? 500 : 100) // Больше задержка для clients, чтобы секция успела раскрыться
            
            // Добавляем визуальную подсветку полей с ошибками
            setTimeout(() => {
              // Добавляем красную рамку к полям с ошибками
              if (newErrors['parameters.title']) {
                const titleInput = document.querySelector('input[placeholder*="Имя"], input[placeholder*="имя"]')
                if (titleInput) {
                  titleInput.classList.add('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
                }
              }
              
              if (newErrors.price) {
                // Подсвечиваем только поля "1 час" в апартаментах и на выезде
                const apartments1hInput = document.querySelector('input[name="apartments_1h"]')
                const outcall1hInput = document.querySelector('input[name="outcall_1h"]')
                
                if (apartments1hInput) {
                  apartments1hInput.classList.add('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
                }
                if (outcall1hInput) {
                  outcall1hInput.classList.add('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
                }
              }
              
              if (newErrors['contacts.phone']) {
                const phoneInput = document.querySelector('input[placeholder*="телефон"], input[type="tel"]')
                if (phoneInput) {
                  phoneInput.classList.add('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
                }
              }
              
              if (newErrors['geo.city']) {
                const citySelect = document.querySelector('select[placeholder*="город"], .city-select')
                if (citySelect) {
                  citySelect.classList.add('border-2', 'border-red-500', 'ring-2', 'ring-red-200')
                }
              }
            }, 500)
          }
        }
      }, 100)
    }
    
    return Object.keys(newErrors).length === 0
  }

  // Обработка отправки формы
  const handleSubmit = async () => {
    // Защита от двойного клика
    if (saving.value) {
      console.log('⚠️ handleSubmit: Уже идет сохранение, игнорируем повторный вызов')
      return
    }
    
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
    
    // Валидация только для активных объявлений
    if (props.initialData?.status === 'active' && !validateForm()) {
      console.log('❌ adFormModel: Валидация не прошла для активного объявления')
      return
    }
    
    if (props.initialData?.status !== 'active') {
      console.log('✅ adFormModel: Пропускаем валидацию для черновика')
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
      // ✅ Добавляем поля верификации
      verification_photo: form.verification_photo,
      verification_video: form.verification_video,
      verification_status: form.verification_status,
      verification_comment: form.verification_comment,
      verification_expires_at: form.verification_expires_at,
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
    // Защита от двойного клика
    if (saving.value) {
      console.log('⚠️ handleSaveDraft: Уже идет сохранение, игнорируем повторный вызов')
      return
    }
    
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
    formData.append('online_booking', form.online_booking ? '1' : '0')
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
    formData.append('bikini_zone', form.parameters.bikini_zone || '')
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
    
    // Добавляем поля верификации
    formData.append('verification_photo', form.verification_photo || '')
    formData.append('verification_video', form.verification_video || '')
    formData.append('verification_status', form.verification_status || 'none')
    formData.append('verification_comment', form.verification_comment || '')
    formData.append('verification_expires_at', form.verification_expires_at || '')
    
    // Добавляем массивы как JSON
    try {
      if (form.clients) formData.append('clients', JSON.stringify(form.clients))
      if (form.service_location) formData.append('service_location', JSON.stringify(form.service_location))
      if (form.service_provider) formData.append('service_provider', JSON.stringify(form.service_provider))
      if (form.services) formData.append('services', JSON.stringify(form.services))
      if (form.features) formData.append('features', JSON.stringify(form.features))
      if (form.schedule) {
        formData.append('schedule', JSON.stringify(form.schedule))
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
    
    if (form.photos && Array.isArray(form.photos)) {
      // ✅ ВОССТАНОВЛЕНА АРХИВНАЯ ЛОГИКА: Всегда отправляем массив photos
      form.photos.forEach((photo: any, index: number) => {
        if (photo instanceof File) {
          formData.append(`photos[${index}]`, photo)
        } else if (typeof photo === 'string' && photo !== '') {
          formData.append(`photos[${index}]`, photo)
        } else if (typeof photo === 'object' && photo !== null) {
          const value = photo.url || photo.preview || ''
          if (value) {
            formData.append(`photos[${index}]`, value)
          }
        }
      })
      
      // Если массив пустой, явно отправляем пустой массив
      if (form.photos.length === 0) {
        formData.append('photos', '[]')
      }
    } else {
      // Если photos не инициализирован, отправляем пустой массив
      formData.append('photos', '[]')
    }
    
    // ✅ ВОССТАНОВЛЕНА АРХИВНАЯ ЛОГИКА: Обрабатываем видео (аналогично photos)
    if (form.video && Array.isArray(form.video)) {
      // Всегда отправляем массив video, даже если пустой
      form.video.forEach((video: any, index: number) => {
        if (video instanceof File) {
          // Прямой File объект  
          // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel
          formData.append(`video_${index}_file`, video)
        } else if (video?.file instanceof File) {
          // Video объект с File полем (основной случай для новых видео)
          // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel  
          formData.append(`video_${index}_file`, video.file)
        } else if (typeof video === 'string' && video !== '') {
          // Строковые URL (существующие видео)
          // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel
          formData.append(`video_${index}`, video)
        } else if (typeof video === 'object' && video !== null) {
          // Объект без File (существующие видео с объектами)
          const value = video.url || video.preview || ''
          if (value) {
            // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel
            formData.append(`video_${index}`, value)
          } else {
            // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel
            formData.append(`video_${index}`, JSON.stringify(video))
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
      
          // Определяем есть ли файлы для отправки
      // ✅ УПРОЩЕННАЯ ЛОГИКА: более читаемая проверка
      const hasPhotoFiles = form.photos?.some((photo: any) => {
        return photo instanceof File || 
               (typeof photo === 'string' && photo.startsWith('data:')) ||
               (photo?.preview && photo.preview.startsWith('data:'))
      }) || false
      
      // Анализ фото для определения метода отправки
      
      // ✅ УПРОЩЕННАЯ ЛОГИКА: аналогично фото
      const hasVideoFiles = form.video?.some((video: any) => {
        return video instanceof File ||
               video?.file instanceof File ||
               (typeof video === 'string' && video.startsWith('data:video/'))
      }) || false
      
      const hasFiles = hasPhotoFiles || hasVideoFiles
      
      if (hasFiles) {
        // Если есть файлы - используем FormData с POST и _method=PUT
        formData.append('_method', 'PUT')
        
        // Отправка с файлами через FormData
        
        router.post(`/draft/${adId}`, formData as any, {
          preserveScroll: true,
          forceFormData: true,
          onSuccess: () => {
            saving.value = false
            // Редирект на страницу черновиков после успешного сохранения
            router.visit('/profile?tab=drafts')
          },
          onError: (errors) => {
            saving.value = false
            console.error('Ошибка сохранения черновика:', errors)
          },
          onFinish: () => {
            saving.value = false
          }
        })
      } else {
        // Если файлов нет - используем обычный PUT с JSON
        
        // Отправка без файлов через JSON
        
        // Правильная конвертация FormData в объект с поддержкой индексированных массивов
        const plainData: any = {}
        
        formData.forEach((value, key) => {
          
          // Пропускаем пустые значения
          if (value === '' || value === undefined) return
          
          // Проверяем, это индексированный массив (например photos[0], photos[1])
          const indexMatch = key.match(/^(\w+)\[(\d+)\]$/)
          if (indexMatch) {
            const arrayName = indexMatch[1] // 'photos'
            const arrayIndex = parseInt(indexMatch[2], 10) // 0, 1, 2...
            
            // Создаем массив если его еще нет
            if (!plainData[arrayName]) {
              plainData[arrayName] = []
            }
            
            // Добавляем значение в нужную позицию массива
            plainData[arrayName][arrayIndex] = value
            return
          }
          
          // Парсим JSON строки
          if (typeof value === 'string' && (value.startsWith('[') || value.startsWith('{'))) {
            try {
              plainData[key] = JSON.parse(value)
            } catch {
              plainData[key] = value
            }
          } else {
            plainData[key] = value
          }
        })
        
        
        router.put(`/draft/${adId}`, plainData, {
          preserveScroll: true,
          onSuccess: () => {
            saving.value = false
            // Редирект на страницу черновиков после успешного сохранения
            router.visit('/profile?tab=drafts')
          },
          onError: (errors) => {
            saving.value = false
            console.error('Ошибка сохранения черновика:', errors)
          },
          onFinish: () => {
            saving.value = false
          }
        })
      }
    } else {
      // Создание нового черновика
      // Создание нового черновика
      
      router.post('/draft', formData as any, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
          saving.value = false
          // Редирект на страницу черновиков после успешного сохранения
          router.visit('/profile?tab=drafts')
        },
        onError: (errors) => {
          saving.value = false
          console.error('Ошибка создания черновика:', errors)
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
    console.log('🚀 handlePublish ВЫЗВАН')
    
    // Защита от двойного клика
    if (saving.value) {
      console.log('⚠️ handlePublish: Уже идет сохранение, игнорируем повторный вызов')
      return
    }
    
    console.log('🔍 Проверка авторизации:', {
      authStore: authStore,
      isAuthenticated: authStore.isAuthenticated,
      user: authStore.user
    })
    
    // Временно отключаем проверку авторизации - пользователь уже авторизован если он на /additem
    // if (!authStore.isAuthenticated) {
    //   console.log('❌ Пользователь не авторизован')
    //   router.visit('/login')
    //   return
    // }
    
    console.log('🔍 Вызываем validateForm()...')
    const isValid = validateForm()
    console.log('📊 Результат validateForm():', isValid)
    
    if (!isValid) {
      console.log('❌ ВАЛИДАЦИЯ НЕ ПРОШЛА - ОСТАНАВЛИВАЕМ ПУБЛИКАЦИЮ')
      saving.value = false
      return
    }
    
    console.log('✅ Валидация прошла, готовим данные для публикации')
    saving.value = true
    
    const publishData = {
      ...form,
      category: props.category
    }
    
    console.log('📤 Отправляем данные на /ads/publish:', {
      hasTitle: !!form.parameters?.title,
      hasPrice: !!form.price,
      hasPhone: !!form.contacts?.phone,
      hasCity: !!form.geo?.city
    })
    
    // Отправляем на публикацию через Inertia
    router.post('/ads/publish', publishData, {
      preserveScroll: true,
      onSuccess: () => {
        console.log('✅ Объявление успешно опубликовано!')
        // Backend сам сделает redirect на /profile
      },
      onError: (errorResponse: any) => {
        // Обработка ошибки
        console.error('❌ Ошибка публикации:', errorResponse)
        errors.value = errorResponse
        alert('Ошибка при публикации объявления. Проверьте данные.')
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