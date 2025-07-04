/**
 * Склонение слов в зависимости от числа
 * @param {number} number - число
 * @param {string[]} words - массив слов [один, два, пять]
 * @returns {string}
 */
export function pluralize(number, words) {
    const cases = [2, 0, 1, 1, 1, 2]
    const index = (number % 100 > 4 && number % 100 < 20) 
        ? 2 
        : cases[(number % 10 < 5) ? number % 10 : 5]
    return words[index]
}

/**
 * Форматирование телефона
 * @param {string} phone - телефон
 * @returns {string}
 */
export function formatPhone(phone) {
    if (!phone) return ''
    
    // Удаляем все нецифровые символы
    const cleaned = phone.replace(/\D/g, '')
    
    // Форматируем в зависимости от длины
    if (cleaned.length === 11 && cleaned[0] === '7') {
        // Российский формат: 7 XXX XXX XX XX
        return `+7 ${cleaned.slice(1, 4)} ${cleaned.slice(4, 7)} ${cleaned.slice(7, 9)} ${cleaned.slice(9, 11)}`
    } else if (cleaned.length === 10) {
        // Без кода страны: XXX XXX XX XX
        return `${cleaned.slice(0, 3)} ${cleaned.slice(3, 6)} ${cleaned.slice(6, 8)} ${cleaned.slice(8, 10)}`
    }
    
    return phone
}

/**
 * Форматирование даты
 * @param {string} date - дата
 * @param {string} format - формат (short, long, relative)
 * @returns {string}
 */
export function formatDate(date, format = 'short') {
    if (!date) return ''
    
    const d = new Date(date)
    const now = new Date()
    const diffTime = now - d
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))
    
    switch (format) {
        case 'relative':
            if (diffDays === 0) return 'Сегодня'
            if (diffDays === 1) return 'Вчера'
            if (diffDays < 7) return `${diffDays} ${pluralize(diffDays, ['день', 'дня', 'дней'])} назад`
            if (diffDays < 30) return `${Math.floor(diffDays / 7)} ${pluralize(Math.floor(diffDays / 7), ['неделю', 'недели', 'недель'])} назад`
            if (diffDays < 365) return `${Math.floor(diffDays / 30)} ${pluralize(Math.floor(diffDays / 30), ['месяц', 'месяца', 'месяцев'])} назад`
            break
            
        case 'long':
            return d.toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
            
        case 'short':
        default:
            return d.toLocaleDateString('ru-RU', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            })
    }
}

/**
 * Форматирование цены
 * @param {number} price - цена
 * @param {string} currency - валюта
 * @returns {string}
 */
export function formatPrice(price, currency = '₽') {
    if (!price) return '0'
    return new Intl.NumberFormat('ru-RU').format(price) + ' ' + currency
}

/**
 * Обрезка текста
 * @param {string} text - текст
 * @param {number} length - максимальная длина
 * @param {string} suffix - окончание
 * @returns {string}
 */
export function truncate(text, length = 100, suffix = '...') {
    if (!text || text.length <= length) return text
    return text.substring(0, length).trim() + suffix
}

/**
 * Генерация инициалов
 * @param {string} name - имя
 * @returns {string}
 */
export function getInitials(name) {
    if (!name) return ''
    
    const parts = name.split(' ')
    if (parts.length >= 2) {
        return parts[0][0] + parts[1][0]
    }
    return name.substring(0, 2).toUpperCase()
}

/**
 * Проверка мобильного устройства
 * @returns {boolean}
 */
export function isMobile() {
    return window.innerWidth < 768
}

/**
 * Дебаунс функции
 * @param {Function} func - функция
 * @param {number} wait - задержка в мс
 * @returns {Function}
 */
export function debounce(func, wait = 300) {
    let timeout
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout)
            func(...args)
        }
        clearTimeout(timeout)
        timeout = setTimeout(later, wait)
    }
}

/**
 * Копирование в буфер обмена
 * @param {string} text - текст
 * @returns {Promise<boolean>}
 */
export async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text)
        return true
    } catch (err) {
        console.error('Ошибка копирования:', err)
        return false
    }
}

/**
 * Валидация email
 * @param {string} email
 * @returns {boolean}
 */
export function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return re.test(email)
}

/**
 * Валидация телефона
 * @param {string} phone
 * @returns {boolean}
 */
export function isValidPhone(phone) {
    const cleaned = phone.replace(/\D/g, '')
    return cleaned.length === 10 || cleaned.length === 11
}

/**
 * Генерация случайного ID
 * @param {number} length
 * @returns {string}
 */
export function generateId(length = 8) {
    return Math.random().toString(36).substring(2, length + 2)
}

/**
 * Получение расстояния между координатами
 * @param {number} lat1
 * @param {number} lon1
 * @param {number} lat2
 * @param {number} lon2
 * @returns {number} расстояние в км
 */
export function getDistance(lat1, lon1, lat2, lon2) {
    const R = 6371 // Радиус Земли в км
    const dLat = (lat2 - lat1) * Math.PI / 180
    const dLon = (lon2 - lon1) * Math.PI / 180
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon/2) * Math.sin(dLon/2)
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))
    return R * c
}

/**
 * Форматирование расстояния
 * @param {number} distance - расстояние в км
 * @returns {string}
 */
export function formatDistance(distance) {
    if (distance < 1) {
        return `${Math.round(distance * 1000)} м`
    } else if (distance < 10) {
        return `${distance.toFixed(1)} км`
    } else {
        return `${Math.round(distance)} км`
    }
}