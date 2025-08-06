import { ref, computed, watch, readonly } from 'vue'
import type {
  CalendarState,
  CalendarNavigation,
  CalendarDay,
  DateBookingInfo,
  DateAvailabilityStatus,
  BookingCalendarProps
} from '../../../model/calendar.types'

/**
 * Основной composable для управления календарем бронирования
 */
export function useCalendar(props: BookingCalendarProps) {
  // Состояние календаря
  const state = ref<CalendarState>({
    currentMonth: new Date().getMonth(),
    currentYear: new Date().getFullYear(),
    selectedDate: null,
    hoveredDate: null,
    isLoading: false
  })

  // Константы для локализации
  const monthNames = [
    'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
    'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
  ]

  const weekDays = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс']

  // Навигация календаря
  const navigation = computed<CalendarNavigation>(() => {
    const currentDate = new Date(state.value.currentYear, state.value.currentMonth, 1)
    const minDate = _props.minDate ? new Date(_props.minDate) : new Date()
    const maxDate = _props.maxDate ? new Date(_props.maxDate) : new Date(Date.now() + 365 * 24 * 60 * 60 * 1000)

    const canGoPrevious = currentDate > new Date(minDate.getFullYear(), minDate.getMonth(), 1)
    const canGoNext = currentDate < new Date(maxDate.getFullYear(), maxDate.getMonth(), 1)

    return {
      canGoPrevious,
      canGoNext,
      currentMonthName: monthNames[state.value.currentMonth],
      currentYear: state.value.currentYear,
      currentMonth: state.value.currentMonth
    }
  })

  // Вычисляем дни для отображения в календаре
  const calendarDays = computed<CalendarDay[]>(() => {
    const days: CalendarDay[] = []
    const year = state.value.currentYear
    const month = state.value.currentMonth

    // Первый день месяца
    const firstDay = new Date(year, month, 1)
    const lastDay = new Date(year, month + 1, 0)
    
    // Первый день недели (понедельник = 0)
    let firstDayOfWeek = firstDay.getDay()
    firstDayOfWeek = firstDayOfWeek === 0 ? 6 : firstDayOfWeek - 1

    // Добавляем дни из предыдущего месяца
    const prevMonth = new Date(year, month - 1, 0)
    for (let i = firstDayOfWeek - 1; i >= 0; i--) {
      const date = new Date(year, month - 1, prevMonth.getDate() - i)
      days.push(createCalendarDay(date, false))
    }

    // Добавляем дни текущего месяца
    for (let day = 1; day <= lastDay.getDate(); day++) {
      const date = new Date(year, month, day)
      days.push(createCalendarDay(date, true))
    }

    // Добавляем дни из следующего месяца до 42 дней (6 недель)
    const remainingDays = 42 - days.length
    for (let day = 1; day <= remainingDays; day++) {
      const date = new Date(year, month + 1, day)
      days.push(createCalendarDay(date, false))
    }

    return days
  })

  // Создает объект дня календаря
  const createCalendarDay = (date: Date, isCurrentMonth: boolean): CalendarDay => {
    const dateString = formatDateString(date)
    const today = new Date()
    const bookingInfo = _props.bookingData?.[dateString] || null
    const isAvailable = checkDateAvailability(dateString)

    return {
      date,
      dateString,
      day: date.getDate(),
      isToday: isSameDate(date, today),
      isSelected: state.value.selectedDate === dateString,
      isHovered: state.value.hoveredDate === dateString,
      isAvailable,
      isPast: date < new Date(today.getFullYear(), today.getMonth(), today.getDate()),
      isWeekend: date.getDay() === 0 || date.getDay() === 6,
      isCurrentMonth,
      bookingInfo,
      status: getDateStatus(dateString, isAvailable, bookingInfo)
    }
  }

  // Форматирование даты в строку
  const formatDateString = (date: Date): string => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
  }

  // Проверка одинаковости дат
  const isSameDate = (date1: Date, date2: Date): boolean => {
    return formatDateString(date1) === formatDateString(date2)
  }

  // Проверка доступности даты
  const checkDateAvailability = (dateString: string): boolean => {
    const date = new Date(dateString)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    
    // Проверяем прошедшие даты
    if (date < today) return false
    
    // Если список доступных дат не задан, считаем все будущие даты доступными
    if (!_props.availableDates || _props.availableDates.length === 0) {
      return true
    }
    
    // Проверяем наличие в списке доступных дат
    return _props.availableDates.includes(dateString)
  }

  // Получение статуса даты
  const getDateStatus = (
    _dateString: string, 
    isAvailable: boolean, 
    bookingInfo: DateBookingInfo | null
  ): DateAvailabilityStatus => {
    if (!isAvailable) return 'unavailable'
    if (!bookingInfo) return 'available'

    const { totalSlots, bookedSlots } = bookingInfo
    const availablePercent = ((totalSlots - bookedSlots) / totalSlots) * 100

    if (availablePercent > 70) return 'available'
    if (availablePercent > 30) return 'busy'
    return 'full'
  }

  // Методы навигации
  const goToPreviousMonth = () => {
    if (!navigation.value.canGoPrevious) return

    if (state.value.currentMonth === 0) {
      state.value.currentMonth = 11
      state.value.currentYear--
    } else {
      state.value.currentMonth--
    }
  }

  const goToNextMonth = () => {
    if (!navigation.value.canGoNext) return

    if (state.value.currentMonth === 11) {
      state.value.currentMonth = 0
      state.value.currentYear++
    } else {
      state.value.currentMonth++
    }
  }

  const goToMonth = (year: number, month: number) => {
    state.value.currentYear = year
    state.value.currentMonth = month
  }

  const goToToday = () => {
    const today = new Date()
    goToMonth(today.getFullYear(), today.getMonth())
  }

  // Выбор даты
  const selectDate = (dateString: string) => {
    const day = calendarDays.value.find(d => d.dateString === dateString)
    
    if (!day || !day.isAvailable) return

    state.value.selectedDate = dateString
  }

  // Hover события
  const setHoveredDate = (dateString: string | null) => {
    state.value.hoveredDate = dateString
  }

  // Инициализация календаря
  const initializeCalendar = () => {
    if (_props.modelValue) {
      const selectedDate = new Date(_props.modelValue)
      state.value.selectedDate = formatDateString(selectedDate)
      goToMonth(selectedDate.getFullYear(), selectedDate.getMonth())
    }
  }

  // Отслеживание изменений modelValue
  watch(() => _props.modelValue, (newValue) => {
    if (newValue) {
      const date = new Date(newValue)
      const dateString = formatDateString(date)
      
      if (state.value.selectedDate !== dateString) {
        state.value.selectedDate = dateString
        goToMonth(date.getFullYear(), date.getMonth())
      }
    } else {
      state.value.selectedDate = null
    }
  }, { immediate: true })

  // Возвращаем интерфейс
  return {
    // Состояние
    state: readonly(state),
    
    // Вычисляемые свойства
    navigation,
    calendarDays,
    monthNames,
    weekDays,
    
    // Методы навигации
    goToPreviousMonth,
    goToNextMonth,
    goToMonth,
    goToToday,
    
    // Методы выбора
    selectDate,
    setHoveredDate,
    
    // Утилиты
    formatDateString,
    checkDateAvailability,
    getDateStatus,
    
    // Инициализация
    initializeCalendar
  }
}