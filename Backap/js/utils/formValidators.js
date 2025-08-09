/**
 * Валидаторы для форм объявлений
 * Выносим логику валидации из компонентов
 */

/**
 * Валидация заголовка объявления
 */
export const validateTitle = (title) => {
  if (!title || title.trim().length === 0) {
    return 'Название объявления обязательно'
  }
  
  if (title.length < 10) {
    return 'Название должно содержать минимум 10 символов'
  }
  
  if (title.length > 255) {
    return 'Название не должно превышать 255 символов'
  }
  
  return null
}

/**
 * Валидация описания
 */
export const validateDescription = (description) => {
  if (!description || description.trim().length === 0) {
    return 'Описание обязательно'
  }
  
  if (description.length < 50) {
    return 'Описание должно содержать минимум 50 символов'
  }
  
  if (description.length > 4000) {
    return 'Описание не должно превышать 4000 символов'
  }
  
  return null
}

/**
 * Валидация цены
 */
export const validatePrice = (price) => {
  if (!price || price === '' || price === null || price === undefined) {
    return 'Цена обязательна'
  }
  
  const numPrice = Number(price)
  
  if (isNaN(numPrice)) {
    return 'Цена должна быть числом'
  }
  
  if (numPrice < 0) {
    return 'Цена не может быть отрицательной'
  }
  
  if (numPrice > 1000000) {
    return 'Цена не может превышать 1 000 000 ₽'
  }
  
  return null
}

/**
 * Валидация телефона
 */
export const validatePhone = (phone) => {
  if (!phone || phone.trim().length === 0) {
    return 'Телефон обязателен'
  }
  
  // Простая валидация российского номера
  const phoneRegex = /^(\+7|7|8)?[\s\-]?\(?[489][0-9]{2}\)?[\s\-]?[0-9]{3}[\s\-]?[0-9]{2}[\s\-]?[0-9]{2}$/
  
  if (!phoneRegex.test(phone.replace(/\s/g, ''))) {
    return 'Неверный формат телефона'
  }
  
  return null
}

/**
 * Валидация адреса
 */
export const validateAddress = (address) => {
  if (!address || address.trim().length === 0) {
    return 'Адрес обязателен'
  }
  
  if (address.length < 10) {
    return 'Адрес должен содержать минимум 10 символов'
  }
  
  if (address.length > 500) {
    return 'Адрес не должен превышать 500 символов'
  }
  
  return null
}

/**
 * Валидация обязательного поля
 */
export const validateRequired = (value, fieldName) => {
  if (!value || (Array.isArray(value) && value.length === 0)) {
    return `${fieldName} обязательно`
  }
  return null
}

/**
 * Валидация массива (минимум один элемент)
 */
export const validateArrayMinLength = (array, minLength, fieldName) => {
  if (!Array.isArray(array) || array.length < minLength) {
    return `Выберите минимум ${minLength} вариант(а) для ${fieldName}`
  }
  return null
}

/**
 * Валидация скидки
 */
export const validateDiscount = (discount) => {
  if (!discount || discount === '') {
    return null // Скидка необязательна
  }
  
  const numDiscount = Number(discount)
  
  if (isNaN(numDiscount)) {
    return 'Скидка должна быть числом'
  }
  
  if (numDiscount < 0) {
    return 'Скидка не может быть отрицательной'
  }
  
  if (numDiscount > 100) {
    return 'Скидка не может превышать 100%'
  }
  
  return null
}

/**
 * Валидация всей формы объявления
 */
export const validateAdForm = (form) => {
  const errors = {}
  
  // Валидация заголовка
  const titleError = validateTitle(form.title)
  if (titleError) errors.title = titleError
  
  // Валидация специальности
  const specialtyError = validateRequired(form.specialty, 'Специальность')
  if (specialtyError) errors.specialty = specialtyError
  
  // Валидация места оказания услуги
  const locationError = validateArrayMinLength(form.service_location, 1, 'Место оказания услуги')
  if (locationError) errors.service_location = locationError
  
  // Валидация формата работы
  const workFormatError = validateRequired(form.work_format, 'Формат работы')
  if (workFormatError) errors.work_format = workFormatError
  
  // Валидация опыта
  const experienceError = validateRequired(form.experience, 'Опыт работы')
  if (experienceError) errors.experience = experienceError
  
  // Валидация описания
  const descriptionError = validateDescription(form.description)
  if (descriptionError) errors.description = descriptionError
  
  // Валидация цены
  const priceError = validatePrice(form.price)
  if (priceError) errors.price = priceError
  
  // Валидация скидки
  const discountError = validateDiscount(form.discount)
  if (discountError) errors.discount = discountError
  
  // Валидация адреса
  const addressError = validateAddress(form.address)
  if (addressError) errors.address = addressError
  
  // Валидация района выезда
  const travelAreaError = validateRequired(form.travel_area, 'Район выезда')
  if (travelAreaError) errors.travel_area = travelAreaError
  
  // Валидация телефона
  const phoneError = validatePhone(form.phone)
  if (phoneError) errors.phone = phoneError
  
  // Валидация способа связи
  const contactMethodError = validateRequired(form.contact_method, 'Способ связи')
  if (contactMethodError) errors.contact_method = contactMethodError
  
  return {
    errors,
    isValid: Object.keys(errors).length === 0
  }
} 