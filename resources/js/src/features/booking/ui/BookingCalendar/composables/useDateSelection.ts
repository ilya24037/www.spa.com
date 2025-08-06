import { computed, type Ref } from 'vue'
import type {
  AvailableDateItem,
  DateBookingInfo,
  AvailableDatesOptions
} from '../../../model/calendar.types'

/**
 * Composable для управления выбором дат и доступными датами
 */
export function useDateSelection(
  availableDates: Ref<string[]>,
  bookingData: Ref<Record<string, DateBookingInfo>>,
  selectedDate: Ref<string | null>
) {
  
  // Получение следующих доступных дат для мобильного списка
  const getNextAvailableDates = (options: AvailableDatesOptions = {}): AvailableDateItem[] => {
    const {
      maxDates = 5,
      startDate = new Date(),
      endDate = new Date(Date.now() + 60 * 24 * 60 * 60 * 1000), // 60 дней вперед
      excludeWeekends = false,
      onlyFuture = true
    } = options

    const dates: AvailableDateItem[] = []
    const today = new Date()
    today.setHours(0, 0, 0, 0)

    let currentDate = new Date(startDate)
    currentDate.setHours(0, 0, 0, 0)

    while (dates.length < maxDates && currentDate <= endDate) {
      const dateString = formatDateString(currentDate)
      
      // Пропускаем прошедшие даты если onlyFuture = true
      if (onlyFuture && currentDate < today) {
        currentDate.setDate(currentDate.getDate() + 1)
        continue
      }

      // Пропускаем выходные если excludeWeekends = true
      if (excludeWeekends && isWeekend(currentDate)) {
        currentDate.setDate(currentDate.getDate() + 1)
        continue
      }

      // Проверяем доступность даты
      if (isDateAvailable(dateString)) {
        dates.push({
          dateString,
          displayText: formatDateForDisplay(currentDate),
          availableSlots: getAvailableSlotsText(dateString),
          isSelected: selectedDate.value === dateString,
          date: new Date(currentDate)
        })
      }

      currentDate.setDate(currentDate.getDate() + 1)
    }

    return dates
  }

  // Проверка доступности даты
  const isDateAvailable = (dateString: string): boolean => {
    // Если список доступных дат пуст, считаем все даты доступными
    if (availableDates.value.length === 0) return true
    
    return availableDates.value.includes(dateString)
  }

  // Проверка является ли дата выходным днем
  const isWeekend = (date: Date): boolean => {
    const dayOfWeek = date.getDay()
    return dayOfWeek === 0 || dayOfWeek === 6
  }

  // Форматирование даты в строку YYYY-MM-DD
  const formatDateString = (date: Date): string => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  // Форматирование даты для отображения пользователю
  const formatDateForDisplay = (date: Date): string => {
    const today = new Date()
    const tomorrow = new Date(today)
    tomorrow.setDate(tomorrow.getDate() + 1)

    if (isSameDate(date, today)) {
      return 'Сегодня'
    } else if (isSameDate(date, tomorrow)) {
      return 'Завтра'
    }

    // Для других дат показываем день недели и дату
    const weekDayNames = [
      'воскресенье', 'понедельник', 'вторник', 'среда',
      'четверг', 'пятница', 'суббота'
    ]
    
    const monthNames = [
      'января', 'февраля', 'марта', 'апреля', 'мая', 'июня',
      'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'
    ]

    const weekDay = weekDayNames[date.getDay()]
    const day = date.getDate()
    const month = monthNames[date.getMonth()]

    return `${weekDay}, ${day} ${month}`
  }

  // Проверка одинаковости дат
  const isSameDate = (date1: Date, date2: Date): boolean => {
    return formatDateString(date1) === formatDateString(date2)
  }

  // Получение текста количества доступных слотов
  const getAvailableSlotsText = (dateString: string): string => {
    const bookingInfo = bookingData.value[dateString]
    
    if (!bookingInfo) {
      return '10+ свободных слотов' // Дефолтное значение
    }

    const available = bookingInfo.totalSlots - bookingInfo.bookedSlots
    
    if (available === 0) {
      return 'Все занято'
    } else if (available === 1) {
      return '1 свободный слот'
    } else if (available < 5) {
      return `${available} свободных слота`
    } else if (available < 10) {
      return `${available} свободных слотов`
    } else {
      return '10+ свободных слотов'
    }
  }

  // Поиск ближайшей доступной даты
  const findNearestAvailableDate = (fromDate: Date = new Date()): string | null => {
    const maxDaysToSearch = 90 // Ищем в пределах 3 месяцев
    let currentDate = new Date(fromDate)
    currentDate.setHours(0, 0, 0, 0)

    for (let i = 0; i < maxDaysToSearch; i++) {
      const dateString = formatDateString(currentDate)
      
      if (isDateAvailable(dateString)) {
        return dateString
      }
      
      currentDate.setDate(currentDate.getDate() + 1)
    }

    return null
  }

  // Получение дат в диапазоне
  const getDatesInRange = (startDate: Date, endDate: Date): string[] => {
    const dates: string[] = []
    const currentDate = new Date(startDate)

    while (currentDate <= endDate) {
      dates.push(formatDateString(currentDate))
      currentDate.setDate(currentDate.getDate() + 1)
    }

    return dates
  }

  // Фильтрация доступных дат по критериям
  const filterAvailableDates = (criteria: {
    excludeWeekends?: boolean
    excludePast?: boolean
    minDate?: Date
    maxDate?: Date
    onlyWithSlots?: boolean
  } = {}) => {
    const {
      excludeWeekends = false,
      excludePast = true,
      minDate,
      maxDate,
      onlyWithSlots = false
    } = criteria

    const today = new Date()
    today.setHours(0, 0, 0, 0)

    return availableDates.value.filter(dateString => {
      const date = new Date(dateString)

      // Исключаем прошедшие даты
      if (excludePast && date < today) return false

      // Исключаем выходные
      if (excludeWeekends && isWeekend(date)) return false

      // Проверяем минимальную дату
      if (minDate && date < minDate) return false

      // Проверяем максимальную дату
      if (maxDate && date > maxDate) return false

      // Проверяем наличие свободных слотов
      if (onlyWithSlots) {
        const bookingInfo = bookingData.value[dateString]
        if (bookingInfo && bookingInfo.availableSlots === 0) return false
      }

      return true
    })
  }

  // Computed свойства
  const nextAvailableDates = computed(() => getNextAvailableDates())

  const hasAvailableDates = computed(() => {
    return availableDates.value.length > 0
  })

  const nearestAvailableDate = computed(() => {
    return findNearestAvailableDate()
  })

  return {
    // Computed
    nextAvailableDates,
    hasAvailableDates,
    nearestAvailableDate,

    // Methods
    getNextAvailableDates,
    isDateAvailable,
    formatDateString,
    formatDateForDisplay,
    getAvailableSlotsText,
    findNearestAvailableDate,
    getDatesInRange,
    filterAvailableDates,
    isWeekend,
    isSameDate
  }
}