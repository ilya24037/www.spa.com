import { computed, type Ref } from 'vue'
import type {
  DateBookingInfo,
  DateAvailabilityStatus,
  CalendarLegendItem
} from '../../../model/calendar.types'

/**
 * Composable для управления статусами бронирования и их отображением
 */
export function useBookingStatus(
  bookingData: Ref<Record<string, DateBookingInfo>>
) {
  
  // Получение статуса бронирования для даты
  const getBookingStatus = (dateString: string): DateAvailabilityStatus => {
    const bookingInfo = bookingData.value[dateString]
    
    if (!bookingInfo) return 'available'
    
    const { totalSlots, bookedSlots } = bookingInfo
    const availablePercent = ((totalSlots - bookedSlots) / totalSlots) * 100
    
    if (availablePercent === 0) return 'full'
    if (availablePercent <= 30) return 'full'
    if (availablePercent <= 70) return 'busy'
    return 'available'
  }

  // Получение процента заполненности для индикатора
  const getBookingPercent = (dateString: string): number => {
    const bookingInfo = bookingData.value[dateString]
    
    if (!bookingInfo || bookingInfo.totalSlots === 0) return 0
    
    const { totalSlots, bookedSlots } = bookingInfo
    return Math.round((bookedSlots / totalSlots) * 100)
  }

  // Получение количества доступных слотов
  const getAvailableSlots = (dateString: string): number => {
    const bookingInfo = bookingData.value[dateString]
    
    if (!bookingInfo) return 0
    
    return Math.max(0, bookingInfo.totalSlots - bookingInfo.bookedSlots)
  }

  // Получение общего количества слотов
  const getTotalSlots = (dateString: string): number => {
    const bookingInfo = bookingData.value[dateString]
    return bookingInfo?.totalSlots || 0
  }

  // Получение количества забронированных слотов
  const getBookedSlots = (dateString: string): number => {
    const bookingInfo = bookingData.value[dateString]
    return bookingInfo?.bookedSlots || 0
  }

  // Проверка полной загруженности даты
  const isDateFullyBooked = (dateString: string): boolean => {
    const status = getBookingStatus(dateString)
    return status === 'full'
  }

  // Проверка частичной загруженности даты
  const isDateBusy = (dateString: string): boolean => {
    const status = getBookingStatus(dateString)
    return status === 'busy'
  }

  // Проверка доступности даты для бронирования
  const isDateBookable = (dateString: string): boolean => {
    const status = getBookingStatus(dateString)
    return status === 'available' || status === 'busy'
  }

  // Получение CSS классов для статуса
  const getStatusClasses = (status: DateAvailabilityStatus): string => {
    const classes = {
      available: 'calendar-day--available',
      busy: 'calendar-day--busy', 
      full: 'calendar-day--full',
      unavailable: 'calendar-day--unavailable'
    }
    
    return classes[status] || ''
  }

  // Получение цвета индикатора для статуса
  const getStatusColor = (status: DateAvailabilityStatus): string => {
    const colors = {
      available: '#10B981', // green-500
      busy: '#F59E0B',      // amber-500  
      full: '#EF4444',      // red-500
      unavailable: '#6B7280' // gray-500
    }
    
    return colors[status] || colors.unavailable
  }

  // Получение описания статуса для accessibility
  const getStatusDescription = (status: DateAvailabilityStatus, dateString: string): string => {
    const availableSlots = getAvailableSlots(dateString)
    const totalSlots = getTotalSlots(dateString)
    
    switch (status) {
      case 'available':
        return `Много свободных мест (${availableSlots} из ${totalSlots})`
      case 'busy':
        return `Есть свободные места (${availableSlots} из ${totalSlots})`
      case 'full':
        return 'Все места заняты'
      case 'unavailable':
        return 'Дата недоступна для бронирования'
      default:
        return 'Статус неизвестен'
    }
  }

  // Получение короткого описания статуса
  const getStatusShortDescription = (status: DateAvailabilityStatus): string => {
    const descriptions = {
      available: 'Свободно',
      busy: 'Частично занято',
      full: 'Занято',
      unavailable: 'Недоступно'
    }
    
    return descriptions[status] || 'Неизвестно'
  }

  // Конфигурация легенды календаря
  const legendItems = computed<CalendarLegendItem[]>(() => [
    {
      id: 'available',
      color: getStatusColor('available'),
      label: 'Много свободных слотов',
      status: 'available'
    },
    {
      id: 'busy', 
      color: getStatusColor('busy'),
      label: 'Есть свободные слоты',
      status: 'busy'
    },
    {
      id: 'full',
      color: getStatusColor('full'),
      label: 'Почти все занято',
      status: 'full'
    }
  ])

  // Статистика по всем датам
  const bookingStatistics = computed(() => {
    const dates = Object.keys(bookingData.value)
    const stats = {
      total: dates.length,
      available: 0,
      busy: 0,
      full: 0,
      unavailable: 0,
      totalSlots: 0,
      bookedSlots: 0,
      averageOccupancy: 0
    }

    dates.forEach(dateString => {
      const status = getBookingStatus(dateString)
      const info = bookingData.value[dateString]
      
      stats[status]++
      if (info) stats.totalSlots += info.totalSlots
      if (info) stats.bookedSlots += info.bookedSlots
    })

    if (stats.totalSlots > 0) {
      stats.averageOccupancy = Math.round((stats.bookedSlots / stats.totalSlots) * 100)
    }

    return stats
  })

  // Получение рекомендуемых дат (с наибольшим количеством свободных мест)
  const getRecommendedDates = (count: number = 5): string[] => {
    const dates = Object.keys(bookingData.value)
    
    return dates
      .filter(dateString => isDateBookable(dateString))
      .sort((a, b) => {
        const slotsA = getAvailableSlots(a)
        const slotsB = getAvailableSlots(b)
        return slotsB - slotsA // Сортируем по убыванию свободных мест
      })
      .slice(0, count)
  }

  // Поиск альтернативных дат при полной загруженности
  const findAlternativeDates = (requestedDate: string, count: number = 3): string[] => {
    const requestedDateObj = new Date(requestedDate)
    const alternatives: { date: string; distance: number }[] = []
    
    Object.keys(bookingData.value).forEach(dateString => {
      if (dateString === requestedDate || !isDateBookable(dateString)) return
      
      const date = new Date(dateString)
      const distance = Math.abs(date.getTime() - requestedDateObj.getTime())
      
      alternatives.push({ date: dateString, distance })
    })
    
    return alternatives
      .sort((a, b) => a.distance - b.distance)
      .slice(0, count)
      .map(item => item.date)
  }

  return {
    // Основные методы
    getBookingStatus,
    getBookingPercent,
    getAvailableSlots,
    getTotalSlots,
    getBookedSlots,
    
    // Проверки состояния
    isDateFullyBooked,
    isDateBusy,
    isDateBookable,
    
    // Отображение
    getStatusClasses,
    getStatusColor,
    getStatusDescription,
    getStatusShortDescription,
    
    // Computed свойства
    legendItems,
    bookingStatistics,
    
    // Рекомендации
    getRecommendedDates,
    findAlternativeDates
  }
}