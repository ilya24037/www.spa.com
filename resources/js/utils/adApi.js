/**
 * API функции для работы с объявлениями
 * Выносим API логику из компонентов
 */

import { router } from '@inertiajs/vue3'

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
export const saveDraft = async (formData) => {
  return new Promise((resolve, reject) => {
    router.post('/ads/draft', formData, {
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
 * Загрузить фотографии для объявления
 */
export const uploadAdPhotos = async (adId, files) => {
  const formData = new FormData()
  files.forEach((file, index) => {
    formData.append(`photos[${index}]`, file)
  })
  
  return new Promise((resolve, reject) => {
    router.post(`/ads/${adId}/photos`, formData, {
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
 * Автосохранение черновика
 */
export const autosaveDraft = async (formData) => {
  try {
    // Отправляем данные без показа ошибок пользователю
    await saveDraft(formData)
    return true
  } catch (error) {
    console.warn('Автосохранение не удалось:', error)
    return false
  }
}

/**
 * Подготовить данные формы для отправки
 */
export const prepareFormData = (form) => {
  return {
    title: form.title || '',
    specialty: form.specialty || '',
    clients: Array.isArray(form.clients) ? form.clients : [],
    service_location: Array.isArray(form.service_location) ? form.service_location : [],
    work_format: form.work_format || '',
    service_provider: Array.isArray(form.service_provider) ? form.service_provider : [],
    experience: form.experience || '',
    description: form.description || '',
    price: form.price || '',
    price_unit: form.price_unit || 'service',
    is_starting_price: Array.isArray(form.is_starting_price) ? form.is_starting_price : [],
    discount: form.discount || '',
    gift: form.gift || '',
    address: form.address || '',
    travel_area: form.travel_area || '',
    phone: form.phone || '',
    contact_method: form.contact_method || 'messages'
  }
} 