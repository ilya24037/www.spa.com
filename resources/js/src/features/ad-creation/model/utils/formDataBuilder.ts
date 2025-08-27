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
  
  return formData
}

// ✅ ОСНОВНАЯ ИНФОРМАЦИЯ
function appendBasicInfo(formData: FormData, form: AdForm): void {
  if (form.specialty) formData.append('specialty', form.specialty)
  if (form.work_format) formData.append('work_format', form.work_format)
  if (form.experience) formData.append('experience', form.experience)
  if (form.description) formData.append('description', form.description)
}

// ✅ МЕДИА ФАЙЛЫ
function appendMedia(formData: FormData, form: AdForm): void {
  // Фотографии
  if (form.photos && form.photos.length > 0) {
    form.photos.forEach((photo, index) => {
      if (photo instanceof File) {
        formData.append(`photos[${index}]`, photo)
      } else if (photo.url) {
        formData.append(`existing_photos[${index}]`, JSON.stringify(photo))
      }
    })
  }
  
  // Видео
  if (form.video && form.video.length > 0) {
    form.video.forEach((video, index) => {
      if (video instanceof File) {
        formData.append(`videos[${index}]`, video)
      } else if (video.url) {
        formData.append(`existing_videos[${index}]`, JSON.stringify(video))
      }
    })
  }
}

// ✅ ЦЕНЫ И УСЛУГИ
function appendPricesAndServices(formData: FormData, form: AdForm): void {
  // Цены
  if (form.prices && Object.keys(form.prices).length > 0) {
    formData.append('prices', JSON.stringify(form.prices))
  }
  
  // Услуги
  if (form.services && form.services.length > 0) {
    formData.append('services', JSON.stringify(form.services))
  }
  
  // Клиенты
  if (form.clients && form.clients.length > 0) {
    formData.append('clients', JSON.stringify(form.clients))
  }
}

// ✅ РАСПИСАНИЕ
function appendSchedule(formData: FormData, form: AdForm): void {
  if (form.schedule && Object.keys(form.schedule).length > 0) {
    formData.append('schedule', JSON.stringify(form.schedule))
  }
  
  if (form.schedule_notes) {
    formData.append('schedule_notes', form.schedule_notes)
  }
}

// ✅ КОНТАКТЫ
function appendContacts(formData: FormData, form: AdForm): void {
  if (form.phone) formData.append('phone', form.phone)
  if (form.whatsapp) formData.append('whatsapp', form.whatsapp)
  if (form.telegram) formData.append('telegram', form.telegram)
  if (form.vk) formData.append('vk', form.vk)
  if (form.instagram) formData.append('instagram', form.instagram)
}

// ✅ ЛОКАЦИЯ
function appendLocation(formData: FormData, form: AdForm): void {
  if (form.address) formData.append('address', form.address)
  
  if (form.geo) {
    formData.append('geo', JSON.stringify(form.geo))
  }
  
  if (form.radius !== null && form.radius !== undefined) {
    formData.append('radius', String(form.radius))
  }
  
  formData.append('is_remote', form.is_remote ? '1' : '0')
}

// ✅ ПАРАМЕТРЫ
function appendParameters(formData: FormData, form: AdForm): void {
  // Числовые параметры
  if (form.age) formData.append('age', String(form.age))
  if (form.height) formData.append('height', String(form.height))
  if (form.weight) formData.append('weight', String(form.weight))
  if (form.breast_size) formData.append('breast_size', String(form.breast_size))
  
  // Строковые параметры
  if (form.hair_color) formData.append('hair_color', form.hair_color)
  if (form.eye_color) formData.append('eye_color', form.eye_color)
  if (form.nationality) formData.append('nationality', form.nationality)
  if (form.appearance) formData.append('appearance', form.appearance)
}

// ✅ ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ
function appendAdditional(formData: FormData, form: AdForm): void {
  // Массивы
  if (form.additional_features && form.additional_features.length > 0) {
    formData.append('additional_features', JSON.stringify(form.additional_features))
  }
  
  // Числа (проверка на null и undefined)
  if (form.discount !== null && form.discount !== undefined) {
    formData.append('discount', String(form.discount))
  }
  if (form.new_client_discount !== null && form.new_client_discount !== undefined) {
    formData.append('new_client_discount', String(form.new_client_discount))
  }
  if (form.min_duration !== null && form.min_duration !== undefined) {
    formData.append('min_duration', String(form.min_duration))
  }
  if (form.contacts_per_hour !== null && form.contacts_per_hour !== undefined) {
    formData.append('contacts_per_hour', String(form.contacts_per_hour))
  }
  
  // Строки
  if (form.gift) formData.append('gift', form.gift)
  
  // Булевы
  formData.append('has_girlfriend', form.has_girlfriend ? '1' : '0')
}