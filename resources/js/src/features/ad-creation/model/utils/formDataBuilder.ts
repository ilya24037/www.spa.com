import type { AdForm } from '../types'

/**
 * Утилита для построения FormData из формы объявления
 * УПРОЩЕННАЯ ВЕРСИЯ: согласно плану, без лишних утилит
 */

// ✅ ОСНОВНАЯ ФУНКЦИЯ ПОСТРОЕНИЯ
export function buildFormData(form: AdForm, isPublishing = false): FormData {
  const formData = new FormData()
  
  // ID объявления (важно для обновления)
  if (form.id) {
    formData.append('id', String(form.id))
    formData.append('_method', 'PUT')
  }
  
  // Статус
  formData.append('status', isPublishing ? 'active' : 'draft')
  
  // Основная информация
  appendBasicInfo(formData, form)
  
  // Медиа файлы
  appendMedia(formData, form)
  
  // Цены и услуги
  appendPricesAndServices(formData, form)
  
  // Расписание
  appendSchedule(formData, form)
  
  // Контакты
  appendContacts(formData, form)
  
  // Локация
  appendLocation(formData, form)
  
  // Параметры
  appendParameters(formData, form)
  
  // Дополнительная информация
  appendAdditional(formData, form)
  
  // ФИНАЛЬНАЯ ОТЛАДКА
  const keys = Array.from(formData.keys())
  console.log('🔍 FormData ФИНАЛЬНЫЕ КЛЮЧИ:', keys)
  console.log('🔍 Общее количество полей:', keys.length)
  
  // Показываем несколько примеров значений
  keys.slice(0, 5).forEach(key => {
    console.log(`🔍 ${key}:`, formData.get(key))
  })
  
  return formData
}

// ✅ ОСНОВНАЯ ИНФОРМАЦИЯ (ИСПРАВЛЕНО: отправляем все поля включая пустые)
function appendBasicInfo(formData: FormData, form: AdForm): void {
  // КРИТИЧЕСКИ ВАЖНО: отправляем все поля включая пустые для черновиков
  formData.append('specialty', form.specialty || '')
  formData.append('work_format', form.work_format || 'individual')
  formData.append('experience', form.experience || '')
  formData.append('description', form.description || '')
  formData.append('title', form.title || '')
  formData.append('category', form.category || 'relax')
}

// ✅ МЕДИА ФАЙЛЫ (ВОССТАНОВЛЕНА АРХИВНАЯ ЛОГИКА)
function appendMedia(formData: FormData, form: AdForm): void {
  // ✅ ВОССТАНОВЛЕННАЯ АРХИВНАЯ ЛОГИКА: Всегда отправляем массив photos
  if (form.photos && Array.isArray(form.photos)) {
    console.log('✅ formDataBuilder: form.photos является массивом, начинаем итерацию')
    form.photos.forEach((photo: any, index: number) => {
      console.log(`📸 formDataBuilder: Обрабатываем фото ${index}`, {
        photo: photo,
        type: typeof photo,
        isFile: photo instanceof File,
        hasUrl: photo?.url,
        hasPreview: photo?.preview,
        id: photo?.id
      })
      
      if (photo instanceof File) {
        console.log(`✅ formDataBuilder: Фото ${index} является File, добавляем в FormData`)
        formData.append(`photos[${index}]`, photo)
      } else if (typeof photo === 'string' && photo !== '') {
        console.log(`✅ formDataBuilder: Фото ${index} является строкой, добавляем в FormData:`, photo)
        formData.append(`photos[${index}]`, photo)
      } else if (typeof photo === 'object' && photo !== null) {
        const value = photo.url || photo.preview || ''
        console.log(`📸 formDataBuilder: Фото ${index} является объектом, извлекаем value:`, value)
        if (value) {
          console.log(`✅ formDataBuilder: Фото ${index} добавляем объект в FormData`)
          formData.append(`photos[${index}]`, value)
        } else {
          console.log(`❌ formDataBuilder: Фото ${index} объект без url/preview, пропускаем`)
        }
      } else {
        console.log(`❌ formDataBuilder: Фото ${index} неизвестный тип, пропускаем`)
      }
    })
    
    // Если массив пустой, явно отправляем пустой массив
    if (form.photos.length === 0) {
      console.log('❌ formDataBuilder: Массив photos пуст, отправляем []')
      formData.append('photos', '[]')
    } else {
      console.log('✅ formDataBuilder: Добавили фото в FormData, количество:', form.photos.length)
    }
  } else {
    // Если photos не инициализирован, отправляем пустой массив
    console.log('❌ formDataBuilder: photos не является массивом, отправляем []')
    formData.append('photos', '[]')
  }
  
  // ✅ ВОССТАНОВЛЕННАЯ АРХИВНАЯ ЛОГИКА: Обрабатываем видео (аналогично photos)
  if (form.video && Array.isArray(form.video)) {
    console.log('🎥 formDataBuilder: Обрабатываем видео:', {
      videoCount: form.video.length,
      videoData: form.video
    })
    
    // Всегда отправляем массив video, даже если пустой
    form.video.forEach((video: any, index: number) => {
      console.log(`🎥 formDataBuilder: Обрабатываем видео ${index}:`, {
        video,
        isFile: video instanceof File,
        hasFile: video?.file instanceof File,
        hasUrl: !!video?.url,
        videoType: typeof video
      })
      
      if (video instanceof File) {
        // Прямой File объект  
        // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel
        formData.append(`video_${index}_file`, video)
        console.log(`🎥 formDataBuilder: Добавлен File для видео ${index}`)
      } else if (video?.file instanceof File) {
        // Video объект с File полем (основной случай для новых видео)
        // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel  
        formData.append(`video_${index}_file`, video.file)
        console.log(`🎥 formDataBuilder: Добавлен video.file для видео ${index}`)
      } else if (typeof video === 'string' && video !== '') {
        // Строковые URL (существующие видео)
        // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel
        formData.append(`video_${index}`, video)
        console.log(`🎥 formDataBuilder: Добавлен URL для видео ${index}`)
      } else if (typeof video === 'object' && video !== null) {
        // Объект без File (существующие видео с объектами)
        const value = video.url || video.preview || ''
        if (value) {
          // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel
          formData.append(`video_${index}`, value)
          console.log(`🎥 formDataBuilder: Добавлен value для видео ${index}:`, value)
        } else {
          // ВАЖНО: используем подчеркивание вместо точки для совместимости с Laravel
          formData.append(`video_${index}`, JSON.stringify(video))
          console.log(`🎥 formDataBuilder: Добавлен JSON для видео ${index}`)
        }
      }
    })
    
    // Если массив пустой, явно отправляем пустой массив
    if (form.video.length === 0) {
      console.log('❌ formDataBuilder: Массив video пуст, отправляем []')
      formData.append('video', '[]')
    } else {
      console.log('✅ formDataBuilder: Добавили видео в FormData, количество:', form.video.length)
    }
  } else {
    // Если video не инициализирован, отправляем пустой массив
    console.log('❌ formDataBuilder: form.video НЕ массив, отправляем []')
    formData.append('video', '[]')
  }
}

// ✅ ЦЕНЫ И УСЛУГИ (ВОССТАНОВЛЕНА АРХИВНАЯ ЛОГИКА)
function appendPricesAndServices(formData: FormData, form: AdForm): void {
  // ✅ АРХИТЕКТУРНО ПРАВИЛЬНОЕ РЕШЕНИЕ (после миграции 2025_08_28):
  // Цены отправляются в prices, места выезда - в geo через GeoSection
  console.log('💰 formDataBuilder: Обрабатываем цены:', form.prices)
  
  if (form.prices) {
    // ✅ ТОЛЬКО ЦЕНЫ (после миграции 2025_08_28)
    // Места выезда теперь хранятся в geo и отправляются через GeoSection
    formData.append('prices[apartments_express]', form.prices.apartments_express?.toString() || '')
    formData.append('prices[apartments_1h]', form.prices.apartments_1h?.toString() || '')
    formData.append('prices[apartments_2h]', form.prices.apartments_2h?.toString() || '')
    formData.append('prices[apartments_night]', form.prices.apartments_night?.toString() || '')
    formData.append('prices[outcall_express]', form.prices.outcall_express?.toString() || '')
    formData.append('prices[outcall_1h]', form.prices.outcall_1h?.toString() || '')
    formData.append('prices[outcall_2h]', form.prices.outcall_2h?.toString() || '')
    formData.append('prices[outcall_night]', form.prices.outcall_night?.toString() || '')
    
    console.log('✅ formDataBuilder: Добавлены все поля цен и мест выезда')
  } else {
    console.log('❌ formDataBuilder: form.prices не определен')
  }
  
  // Услуги - всегда отправляем
  formData.append('services', JSON.stringify(form.services || []))
  
  // Клиенты - всегда отправляем
  formData.append('clients', JSON.stringify(form.clients || []))
  
  // Поставщик услуг
  formData.append('service_provider', JSON.stringify(form.service_provider || ['women']))
  
  // Дополнительные фичи
  formData.append('features', JSON.stringify(form.features || []))
}

// ✅ РАСПИСАНИЕ (ИСПРАВЛЕНО: отправляем все поля включая пустые)
function appendSchedule(formData: FormData, form: AdForm): void {
  // Всегда отправляем расписание
  formData.append('schedule', JSON.stringify(form.schedule || {}))
  
  // Всегда отправляем заметки к расписанию
  formData.append('schedule_notes', form.schedule_notes || '')
}

// ✅ КОНТАКТЫ (ИСПРАВЛЕНО: берем из form.contacts как в типах AdForm)
function appendContacts(formData: FormData, form: AdForm): void {
  // КРИТИЧЕСКИ ВАЖНО: контакты находятся в form.contacts согласно типам AdForm
  if (form.contacts) {
    formData.append('phone', form.contacts.phone || '')
    formData.append('whatsapp', form.contacts.whatsapp || '')
    formData.append('telegram', form.contacts.telegram || '')
    formData.append('contact_method', form.contacts.contact_method || '')
  }
  
  // vk и instagram могут быть на верхнем уровне (проверить типы)
  formData.append('vk', form.vk || '')
  formData.append('instagram', form.instagram || '')
}

// ✅ ЛОКАЦИЯ (ИСПРАВЛЕНО: отправляем все поля включая пустые)
function appendLocation(formData: FormData, form: AdForm): void {
  formData.append('address', form.address || '')
  
  // Всегда отправляем geo данные
  formData.append('geo', JSON.stringify(form.geo || {}))
  
  // Отправляем radius даже если 0 (проверяем что поле существует)
  if (form.radius !== undefined) {
    formData.append('radius', String(form.radius))
  } else {
    formData.append('radius', '0')
  }
  
  // Boolean поля всегда отправляем (проверяем что поле существует)
  formData.append('is_remote', form.is_remote ? '1' : '0')
}

// ✅ ПАРАМЕТРЫ (ИСПРАВЛЕНО: берем из form.parameters как в оригинале)
function appendParameters(formData: FormData, form: AdForm): void {
  // КРИТИЧЕСКИ ВАЖНО: параметры находятся в form.parameters согласно типам AdForm
  if (form.parameters) {
    // Числовые параметры - отправляем как отдельные поля для БД
    formData.append('age', String(form.parameters.age || ''))
    formData.append('height', String(form.parameters.height || ''))
    formData.append('weight', String(form.parameters.weight || ''))
    formData.append('breast_size', String(form.parameters.breast_size || ''))
    
    // Строковые параметры - отправляем как отдельные поля для БД
    formData.append('hair_color', form.parameters.hair_color || '')
    formData.append('eye_color', form.parameters.eye_color || '')
    formData.append('nationality', form.parameters.nationality || '')
    formData.append('bikini_zone', form.parameters.bikini_zone || '')
    formData.append('appearance', form.parameters.appearance || '') // appearance из parameters
    
    // title из parameters (имя мастера)
    if (form.parameters.title) {
      formData.append('title', form.parameters.title)
    }
  }
  
  // НЕ отправляем parameters как JSON - БД ожидает отдельные колонки!
}

// ✅ ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ (ИСПРАВЛЕНО: отправляем все поля включая пустые)
function appendAdditional(formData: FormData, form: AdForm): void {
  // Массивы - всегда отправляем
  formData.append('additional_features', JSON.stringify(form.additional_features || []))
  
  // Числа - отправляем даже если 0
  formData.append('discount', String(form.discount ?? 0))
  formData.append('new_client_discount', String(form.new_client_discount ?? 0))
  formData.append('min_duration', String(form.min_duration ?? 0))
  formData.append('contacts_per_hour', String(form.contacts_per_hour ?? 0))
  
  // Строки - отправляем даже если пустые
  formData.append('gift', form.gift || '')
  
  // Boolean поля - всегда отправляем
  formData.append('has_girlfriend', form.has_girlfriend ? '1' : '0')
  formData.append('online_booking', form.online_booking ? '1' : '0')
  formData.append('is_starting_price', form.is_starting_price ? '1' : '0')
  
  // FAQ данные
  if (form.faq && typeof form.faq === 'object') {
    formData.append('faq', JSON.stringify(form.faq))
  }
}