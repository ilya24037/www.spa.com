import { defineStore } from 'pinia'
import { ref, computed, type Ref, type ComputedRef } from 'vue'
import dayjs from 'dayjs'

// TypeScript интерфейсы
interface Booking {
  id: number
  masterId: number
  serviceId?: number
  date: string
  time: string
  clientName: string
  clientPhone: string
  clientEmail?: string
  notes?: string
  totalPrice: number
  duration: number
  status: 'pending' | 'confirmed' | 'completed' | 'cancelled'
  [key: string]: any
}

interface BookingForm {
  masterId: number | null
  serviceId: number | null
  date: string | null
  time: string | null
  clientName: string
  clientPhone: string
  clientEmail: string
  notes: string
  totalPrice: number
  duration: number
}

interface TimeSlot {
  time: string
  available: boolean
  price?: number
  [key: string]: any
}

export const useBookingStore = defineStore('booking', () => {
  // Состояние
  const bookings = ref<Ref<Booking[]>>([])
  const currentBooking = ref<Ref<Booking | null>>(null)
  const loading = ref<Ref<boolean>>(false)
  const error = ref<Ref<string | null>>(null)

  // Состояние формы бронирования
  const bookingForm = ref<Ref<BookingForm>>({
    masterId: null,
    serviceId: null,
    date: null,
    time: null,
    clientName: '',
    clientPhone: '',
    clientEmail: '',
    notes: '',
    totalPrice: 0,
    duration: 0
  })

  // Состояние календаря
  const masterSchedule = ref<Ref<any[]>>([])
  const availableTimeSlots = ref<Ref<TimeSlot[]>>([])
  const selectedDate = ref<Ref<string | null>>(null)
  const selectedTime = ref<Ref<string | null>>(null)

  // Геттеры
  const formattedDateTime = computed(() => {
    if (!bookingForm?.value.date || !bookingForm?.value.time) return null
    return `${bookingForm?.value.date} ${bookingForm?.value.time}`
  })

  const isFormValid = computed(() => {
    return bookingForm?.value.masterId &&
           bookingForm?.value.date &&
           bookingForm?.value.time &&
           bookingForm?.value.clientName?.trim() &&
           bookingForm?.value.clientPhone?.trim()
  })

  const upcomingBookings = computed(() => {
    const now = dayjs()
    return bookings?.value.filter(booking => 
      dayjs(booking?.date + ' ' + booking?.time).isAfter(now) && 
      ['confirmed', 'pending'].includes(booking?.status)
    )
  })

  const pastBookings = computed(() => {
    const now = dayjs()
    return bookings?.value.filter(booking => 
      dayjs(booking?.date + ' ' + booking?.time).isBefore(now) || 
      booking?.status === 'completed'
    )
  })

  // Действия (Actions)
  
  /**
   * Инициализация формы бронирования
   */
  const initializeBookingForm = (master: any, service: any = null) => {
    bookingForm?.value = {
      masterId: master?.id,
      serviceId: service?.id || null,
      date: null,
      time: null,
      clientName: '',
      clientPhone: '',
      clientEmail: '',
      notes: '',
      totalPrice: service?.price || 0,
      duration: service?.duration || 60
    }
    
    error?.value = null
  }

  /**
   * Обновление даты и времени
   */
  const updateDateTime = (date: any, time: any) => {
    bookingForm?.value.date = date
    bookingForm?.value.time = time
    selectedDate?.value = date
    selectedTime?.value = time
  }

  /**
   * Обновление данных клиента
   */
  const updateClientData = (clientData: any) => {
    Object?.assign(bookingForm?.value, {
      clientName: clientData?.name,
      clientPhone: clientData?.phone,
      clientEmail: clientData?.email || '',
      notes: clientData?.notes || ''
    })
  }

  /**
   * Загрузка расписания мастера
   */
  const loadMasterSchedule = async (masterId: number, startDate: string, endDate: string) => {
    loading?.value = true
    error?.value = null

    try {
      // Имитация API вызова
      await new Promise(resolve => setTimeout(resolve, 500))
      
      // Генерируем расписание на 30 дней
      const schedule = []
      let currentDate = dayjs(startDate)
      const end = dayjs(endDate)
      
      while (currentDate?.isBefore(end) || currentDate?.isSame(end, 'day')) {
        // Пропускаем выходные (суббота, воскресенье)
        if (![0, 6].includes(currentDate?.day())) {
          schedule?.push({
            date: currentDate?.format('YYYY-MM-DD'),
            available: Math.random() > 0.2, // 80% дней доступны
            timeSlots: generateTimeSlots(currentDate)
          })
        }
        currentDate = currentDate?.add(1, 'day')
      }
      
      masterSchedule?.value = schedule
      return schedule

    } catch (err) {
      error?.value = 'Ошибка загрузки расписания мастера'
      throw err
    } finally {
      loading?.value = false
    }
  }

  /**
   * Загрузка доступных временных слотов для даты
   */
  const loadTimeSlots = async (masterId: number, date: string) => {
    loading?.value = true
    
    try {
      // Имитация API вызова
      await new Promise(resolve => setTimeout(resolve, 300))
      
      const slots = generateTimeSlots(dayjs(date))
      availableTimeSlots?.value = slots
      return slots

    } catch (err) {
      error?.value = 'Ошибка загрузки временных слотов'
      throw err
    } finally {
      loading?.value = false
    }
  }

  /**
   * Создание бронирования
   */
  const createBooking = async () => {
    if (!isFormValid?.value) {
      throw new Error('Форма заполнена некорректно')
    }

    loading?.value = true
    error?.value = null

    try {
      // Подготовка данных для API
      const bookingData = {
        master_id: bookingForm?.value.masterId,
        service_id: bookingForm?.value.serviceId,
        start_time: `${bookingForm?.value.date} ${bookingForm?.value.time}:00`,
        duration_minutes: bookingForm?.value.duration,
        total_price: bookingForm?.value.totalPrice,
        client_name: bookingForm?.value.clientName,
        client_phone: bookingForm?.value.clientPhone,
        client_email: bookingForm?.value.clientEmail || null,
        notes: bookingForm?.value.notes || null,
        status: 'pending'
      }

      // Имитация API вызова
      await new Promise((resolve, reject) => {
        setTimeout(() => {
          if (Math.random() > 0.1) { // 90% успеха
            resolve(undefined)
          } else {
            reject(new Error('Время уже занято другим клиентом'))
          }
        }, 1500)
      })

      // Создаем объект бронирования
      const newBooking = {
        id: Date?.now(),
        bookingNumber: `BK-${Date?.now().toString().slice(-6)}`,
        ...bookingData,
        startTime: bookingData?.start_time,
        endTime: dayjs(bookingData?.start_time).add(bookingData?.duration_minutes, 'minutes').format('YYYY-MM-DD HH:mm:ss'),
        createdAt: dayjs().format('YYYY-MM-DD HH:mm:ss')
      }

      // Добавляем в список бронирований
      bookings?.value.unshift(newBooking)
      currentBooking?.value = newBooking

      // Очищаем форму
      resetBookingForm()

      return newBooking

    } catch (err) {
      error?.value = (err as Error).message || 'Ошибка создания бронирования'
      throw err
    } finally {
      loading?.value = false
    }
  }

  /**
   * Загрузка списка бронирований пользователя
   */
  const loadUserBookings = async (userId: number) => {
    loading?.value = true
    error?.value = null

    try {
      // Имитация API вызова
      await new Promise(resolve => setTimeout(resolve, 800))
      
      // Генерируем тестовые бронирования
      const mockBookings = generateMockBookings(userId)
      bookings?.value = mockBookings
      
      return mockBookings

    } catch (err) {
      error?.value = 'Ошибка загрузки бронирований'
      throw err
    } finally {
      loading?.value = false
    }
  }

  /**
   * Отмена бронирования
   */
  const cancelBooking = async (bookingId: number, reason: string | null = null) => {
    loading?.value = true
    error?.value = null

    try {
      // Имитация API вызова
      await new Promise(resolve => setTimeout(resolve, 500))

      // Обновляем статус в локальном состоянии
      const bookingIndex = bookings?.value.findIndex(b => b?.id === bookingId)
      if (bookingIndex !== -1) {
        bookings?.value[bookingIndex].status = 'cancelled'
        bookings?.value[bookingIndex].cancellationReason = reason
        bookings?.value[bookingIndex].cancelledAt = dayjs().format('YYYY-MM-DD HH:mm:ss')
      }

      return true

    } catch (err) {
      error?.value = 'Ошибка отмены бронирования'
      throw err
    } finally {
      loading?.value = false
    }
  }

  /**
   * Сброс формы бронирования
   */
  const resetBookingForm = () => {
    bookingForm?.value = {
      masterId: null,
      serviceId: null,
      date: null,
      time: null,
      clientName: '',
      clientPhone: '',
      clientEmail: '',
      notes: '',
      totalPrice: 0,
      duration: 0
    }
    
    selectedDate?.value = null
    selectedTime?.value = null
    availableTimeSlots?.value = []
    error?.value = null
  }

  /**
   * Очистка состояния
   */
  const clearState = () => {
    bookings?.value = []
    currentBooking?.value = null
    masterSchedule?.value = []
    resetBookingForm()
  }

  return {
    // Состояние
    bookings,
    currentBooking,
    loading,
    error,
    bookingForm,
    masterSchedule,
    availableTimeSlots,
    selectedDate,
    selectedTime,

    // Геттеры
    formattedDateTime,
    isFormValid,
    upcomingBookings,
    pastBookings,

    // Действия
    initializeBookingForm,
    updateDateTime,
    updateClientData,
    loadMasterSchedule,
    loadTimeSlots,
    createBooking,
    loadUserBookings,
    cancelBooking,
    resetBookingForm,
    clearState
  }
})

// Вспомогательные функции

/**
 * Генерация временных слотов для даты
 */
function generateTimeSlots(date: any) {
  const slots = []
  const startHour = 9
  const endHour = 21
  const interval = 60 // минут

  for (let hour = startHour; hour < endHour; hour++) {
    const timeString = `${hour?.toString().padStart(2, '0')}:00`
    
    // Случайно делаем некоторые слоты недоступными
    const available = Math.random() > 0.3
    
    slots?.push({
      time: timeString,
      available: available,
      datetime: date?.format('YYYY-MM-DD') + ' ' + timeString
    })
  }

  return slots
}

/**
 * Генерация тестовых бронирований
 */
function generateMockBookings(userId: any) {
  const bookings = []
  const statuses = ['pending', 'confirmed', 'completed', 'cancelled']
  
  for (let i = 0; i < 5; i++) {
    const startTime = dayjs().add(i - 2, 'days').hour(10 + i).minute(0).second(0)
    
    bookings?.push({
      id: 1000 + i,
      bookingNumber: `BK-${(1000 + i).toString().slice(-6)}`,
      masterId: 1,
      serviceId: 1,
      startTime: startTime?.format('YYYY-MM-DD HH:mm:ss'),
      endTime: startTime?.add(60, 'minutes').format('YYYY-MM-DD HH:mm:ss'),
      durationMinutes: 60,
      totalPrice: 3000 + (i * 500),
      clientName: 'Иван Петров',
      clientPhone: '+7 (999) 123-45-67',
      clientEmail: 'ivan@example?.com',
      status: statuses[i % statuses?.length],
      notes: i % 2 === 0 ? 'Дополнительные пожелания' : null,
      createdAt: dayjs().subtract(i, 'days').format('YYYY-MM-DD HH:mm:ss')
    })
  }

  return bookings
}