/**
 * API функции для работы с объявлениями
 * Выносим API логику из компонентов
 */

import { router } from '@inertiajs/vue3'

/**
 * Получение CSRF токена
 */
const getCsrfToken = async () => {
  try {
    const response = await fetch('/csrf-token')
    const data = await response.json()
    return data.token
  } catch (error) {
    console.error('Ошибка получения CSRF токена:', error)
    // Fallback - пытаемся получить из meta тега
    const metaToken = document.querySelector('meta[name="csrf-token"]')
    return metaToken ? metaToken.getAttribute('content') : ''
  }
}

/**
 * Создать новое объявление
 */
export const createAd = async (formData) => {
  return new Promise((resolve, reject) => {
    router.post('/ads', formData, {
      onSuccess: (page) => {
        resolve(page)
      },
      onError: (errors) => {
        reject(errors)
      }
    })
  })
}

/**
 * Обновить объявление
 */
export const updateAd = async (adId, formData) => {
  return new Promise((resolve, reject) => {
    router.put(`/ads/${adId}`, formData, {
      onSuccess: (page) => {
        resolve(page)
      },
      onError: (errors) => {
        reject(errors)
      }
    })
  })
}

/**
 * Сохранить черновик объявления
 */
export const saveDraft = async (formData, draftId = null) => {
  console.log('saveDraft called with formData:', formData)
  console.log('saveDraft - formData.photos:', formData.photos)
  console.log('saveDraft - formData.photos length:', formData.photos ? formData.photos.length : 0)
  console.log('saveDraft - typeof formData:', typeof formData)
  console.log('saveDraft - formData keys:', Object.keys(formData))
  
  // Проверяем что photos действительно есть в данных
  const dataToSend = {
    ...formData,
    photos: formData.photos || []
  }
  console.log('saveDraft - dataToSend has photos:', 'photos' in dataToSend)
  console.log('saveDraft - dataToSend.photos is:', dataToSend.photos)
  
  // Используем обычный fetch вместо Inertia router для отладки
  try {
    const url = draftId ? `/draft/${draftId}` : '/ads/draft'
    const method = draftId ? 'PUT' : 'POST'
    
    console.log(`Sending ${method} request to ${url}`)
    console.log('Data being sent:', JSON.stringify(dataToSend, null, 2))
    
    const response = await fetch(url, {
      method: method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(dataToSend)
    })
    
    if (!response.ok) {
      const error = await response.json()
      console.error('Server error:', error)
      throw new Error(error.message || 'Ошибка сохранения черновика')
    }
    
    const result = await response.json()
    console.log('Draft saved successfully:', result)
    
    // Редирект на страницу черновиков после успешного сохранения
    if (result.redirect || result.success) {
      const redirectUrl = result.redirect || '/my-ads?tab=drafts'
      console.log('Redirecting to:', redirectUrl)
      router.visit(redirectUrl)
    }
    
    return result
  } catch (error) {
    console.error('Ошибка при сохранении черновика:', error)
    throw error
  }
}

/**
 * Загрузить черновик объявления по ID
 */
export const loadDraftById = async (draftId) => {
  try {
    const response = await fetch(`/ads/${draftId}/data`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      }
    })
    
    if (response.ok) {
      return await response.json()
    } else {
      throw new Error('Ошибка при загрузке черновика')
    }
  } catch (error) {
    console.error('Ошибка при загрузке черновика:', error)
    throw error
  }
}

/**
 * Получить объявление по ID
 */
export const getAd = async (adId) => {
  return new Promise((resolve, reject) => {
    router.get(`/ads/${adId}`, {}, {
      onSuccess: (page) => {
        resolve(page.props.ad)
      },
      onError: (errors) => {
        reject(errors)
      }
    })
  })
}

/**
 * Удалить объявление
 */
export const deleteAd = async (adId) => {
  return new Promise((resolve, reject) => {
    router.delete(`/ads/${adId}`, {
      onSuccess: (page) => {
        resolve(page)
      },
      onError: (errors) => {
        reject(errors)
      }
    })
  })
}

/**
 * Переключить статус объявления
 */
export const toggleAdStatus = async (adId, status) => {
  return new Promise((resolve, reject) => {
    router.patch(`/ads/${adId}/status`, { status }, {
      onSuccess: (page) => {
        resolve(page)
      },
      onError: (errors) => {
        reject(errors)
      }
    })
  })
}

/**
 * Дублировать объявление
 */
export const duplicateAd = async (adId) => {
  return new Promise((resolve, reject) => {
    router.post(`/ads/${adId}/duplicate`, {}, {
      onSuccess: (page) => {
        resolve(page)
      },
      onError: (errors) => {
        reject(errors)
      }
    })
  })
}

/**
 * Получить список объявлений пользователя
 */
export const getUserAds = async (filters = {}) => {
  return new Promise((resolve, reject) => {
    router.get('/ads', filters, {
      onSuccess: (page) => {
        resolve(page.props.ads)
      },
      onError: (errors) => {
        reject(errors)
      }
    })
  })
}

/**
 * Загрузить фотографии для объявления через API
 */
export const uploadAdPhotos = async (adId, files) => {
  const formData = new FormData()
  files.forEach((file, index) => {
    formData.append(`photos[${index}]`, file)
  })
  
  try {
    const response = await fetch(`/ads/${adId}/media/photos`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: formData
    })
    
    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.message || 'Ошибка загрузки фото')
    }
    
    return await response.json()
  } catch (error) {
    console.error('Ошибка загрузки фото:', error)
    throw error
  }
}

/**
 * Удалить фотографию объявления
 */
export const deleteAdPhoto = async (adId, photoId) => {
  return new Promise((resolve, reject) => {
    router.delete(`/ads/${adId}/photos/${photoId}`, {
      onSuccess: (page) => {
        resolve(page)
      },
      onError: (errors) => {
        reject(errors)
      }
    })
  })
}

/**
 * Автосохранение черновика (без редиректа)
 */
export const autosaveDraft = async (formData) => {
  console.log('autosaveDraft called with formData:', formData)
  console.log('autosaveDraft - formData.photos:', formData.photos)
  
  try {
    // Подготавливаем данные для черновика
    const preparedData = prepareFormData(formData)
    
    console.log('autosaveDraft - preparedData.photos:', preparedData.photos)
    console.log('autosaveDraft - sending to server:', JSON.stringify(preparedData))
    
    // Используем обычный fetch для автосохранения
    const response = await fetch('/ads/draft', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify(preparedData)
    })
    
    if (response.ok) {
      return true
    } else {
      throw new Error('Ошибка сохранения')
    }
  } catch (error) {
    console.warn('Автосохранение не удалось:', error)
    return false
  }
}

/**
 * Подготовить данные формы для отправки
 */
export const prepareFormData = (form) => {
  console.log('prepareFormData - input form:', form)
  console.log('prepareFormData - form.photos:', form.photos)
  
  // Дополнительная отладка для фото
  if (form.photos && Array.isArray(form.photos)) {
    console.log('prepareFormData - photos count:', form.photos.length)
    form.photos.forEach((photo, index) => {
      console.log(`Photo ${index}:`, {
        hasId: !!photo.id,
        hasFile: !!photo.file,
        hasPreview: !!photo.preview,
        fileName: photo.name,
        size: photo.size
      })
    })
  }
  
  const data = {
    title: form.title || '',
    specialty: form.specialty || '',
    clients: Array.isArray(form.clients) ? form.clients : [],
    service_location: Array.isArray(form.service_location) ? form.service_location : [],
    outcall_locations: Array.isArray(form.outcall_locations) ? form.outcall_locations : [],
    taxi_option: form.taxi_option || '',
    work_format: form.work_format || '',
    has_girlfriend: form.has_girlfriend || false,
    service_provider: Array.isArray(form.service_provider) ? form.service_provider : [],
    experience: form.experience || '',
    features: form.features || {},
    additional_features: form.additional_features || '',
    description: form.description || '',
    price: form.price || '',
    price_unit: form.price_unit || 'service',
    is_starting_price: Array.isArray(form.is_starting_price) ? form.is_starting_price : [],
    pricing_data: form.pricing_data || {},
    contacts_per_hour: form.contacts_per_hour || '',
    
    // Детальные цены
    express_price: form.express_price || '',
    price_per_hour: form.price_per_hour || '',
    outcall_price: form.outcall_price || '',
    price_two_hours: form.price_two_hours || '',
    price_night: form.price_night || '',
    min_duration: form.min_duration || '',

    discount: form.discount || '',
    new_client_discount: form.new_client_discount || '',
    gift: form.gift || '',
    address: form.address || '',
    travel_area: form.travel_area || '',
    geo: form.geo || {},
    phone: form.phone || '',
    contact_method: form.contact_method || 'messages',
    whatsapp: form.whatsapp || '',
    telegram: form.telegram || '',
    
    // Физические параметры
    age: form.age || '',
    height: form.height || '',
    weight: form.weight || '',
    breast_size: form.breast_size || '',
    hair_color: form.hair_color || '',
    eye_color: form.eye_color || '',
    appearance: form.appearance || '',
    nationality: form.nationality || '',
    
    // Услуги
    services: form.services || {},
    services_additional_info: form.services_additional_info || '',
    
    // График работы
    schedule: form.schedule || {},
    schedule_notes: form.schedule_notes || '',
    
    // Медиа - отправляем метаданные с preview
    photos: Array.isArray(form.photos) ? form.photos.map(photo => ({
      id: photo.id,
      // Отправляем preview только если он не слишком большой (меньше 1MB)
      preview: photo.preview && photo.preview.length < 1024 * 1024 ? photo.preview : null,
      name: photo.name,
      size: photo.size,
      rotation: photo.rotation || 0,
      // Флаг что это новое фото
      isNew: !photo.serverPath
      // НЕ отправляем photo.file - это объект File, который не сериализуется в JSON
    })) : [],
    // Видео - отправляем полную информацию (без файла)
    video: form.video ? {
      id: form.video.id,
      filename: form.video.filename,
      url: form.video.url || form.video.preview,
      size: form.video.size,
      name: form.video.name,
      serverPath: form.video.serverPath
    } : null
  }
  
  // Для черновика оставляем все поля, даже пустые
  // Это позволит сохранить черновик даже с полностью пустой формой
  
  console.log('prepareFormData - output data:', data)
  console.log('prepareFormData - data.photos:', data.photos)
  console.log('prepareFormData - data.photos length:', data.photos ? data.photos.length : 0)
  console.log('prepareFormData - data.photos JSON:', JSON.stringify(data.photos))
  
  // Детальная проверка первого фото
  if (data.photos && data.photos.length > 0) {
    console.log('prepareFormData - first photo details:', {
      id: data.photos[0].id,
      hasPreview: !!data.photos[0].preview,
      previewLength: data.photos[0].preview ? data.photos[0].preview.length : 0,
      name: data.photos[0].name,
      size: data.photos[0].size,
      rotation: data.photos[0].rotation
    })
  }
  
  return data
} 

/**
 * Опубликовать объявление (с валидацией)
 */
export async function publishAd(formData) {
  try {
    const csrfToken = await getCsrfToken()
    const response = await fetch('/ads/publish', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(formData)
    })

    if (!response.ok) {
      const error = await response.json()
      throw {
        response: {
          data: error,
          status: response.status
        }
      }
    }

    return await response.json()
  } catch (error) {
    console.error('Ошибка при публикации объявления:', error)
    throw error
  }
} 