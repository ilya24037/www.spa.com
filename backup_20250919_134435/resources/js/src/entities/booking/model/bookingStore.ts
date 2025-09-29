import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import dayjs from 'dayjs'
import { bookingApi } from '../api/bookingApi.js'
import { logger } from '@/src/shared/utils/logger'

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
  created_at?: string
  updated_at?: string
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
  booked?: boolean
  bookedBy?: string
}

export const useBookingStore = defineStore('booking', () => {
  // Состояние
  const bookings = ref<Booking[]>([])
  const currentBooking = ref<Booking | null>(null)
  const loading = ref<boolean>(false)
  const error = ref<string | null>(null)

  // Состояние формы бронирования
  const bookingForm = ref<BookingForm>({
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
  interface ScheduleDay {
    date: string
    available: boolean
    timeSlots: TimeSlot[]
  }
  
  const masterSchedule = ref<ScheduleDay[]>([])
  const availableTimeSlots = ref<TimeSlot[]>([])
  const selectedDate = ref<string | null>(null)
  const selectedTime = ref<string | null>(null)

  // Геттеры
  const formattedDateTime = computed(() => {
    if (!bookingForm.value.date || !bookingForm.value.time) return null
    return `${bookingForm.value.date} ${bookingForm.value.time}`
  })

  const isFormValid = computed(() => {
    return bookingForm.value.masterId &&
           bookingForm.value.date &&
           bookingForm.value.time &&
           bookingForm.value.clientName?.trim() &&
           bookingForm.value.clientPhone?.trim()
  })

  const upcomingBookings = computed(() => {
    const now = dayjs()
    return bookings.value.filter(booking => 
      dayjs(booking?.date + ' ' + booking?.time).isAfter(now) && 
      ['confirmed', 'pending'].includes(booking?.status)
    )
  })

  const pastBookings = computed(() => {
    const now = dayjs()
    return bookings.value.filter(booking => 
      dayjs(booking?.date + ' ' + booking?.time).isBefore(now) || 
      booking?.status === 'completed'
    )
  })

  // Действия (Actions)
  
  /**
   * Инициализация формы бронирования
   */
  interface Master {
    id: number
    name?: string
  }
  
  interface Service {
    id: number
    price?: number
    duration?: number
  }
  
  const initializeBookingForm = (master: Master, service: Service | null = null) => {
    bookingForm.value = {
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
    
    error.value = null
  }

  /**
   * Обновление даты и времени
   */
  const updateDateTime = (date: string, time: string) => {
    bookingForm.value.date = date
    bookingForm.value.time = time
    selectedDate.value = date
    selectedTime.value = time
  }

  /**
   * Обновление данных клиента
   */
  interface ClientData {
    name?: string
    phone?: string
    email?: string
    notes?: string
  }
  
  const updateClientData = (clientData: ClientData) => {
    Object.assign(bookingForm.value, {
      clientName: clientData?.name,
      clientPhone: clientData?.phone,
      clientEmail: clientData?.email || '',
      notes: clientData?.notes || ''
    })
  }

  /**
   * Загрузка расписания мастера
   */
  const loadMasterSchedule = async (_masterId: number, startDate: string, endDate: string) => {
    loading.value = true
    error.value = null

    try {
      // Имитация API вызова - в будущем использовать _masterId
      await new Promise(resolve => setTimeout(resolve, 500))
      
      // Генерируем расписание на 30 дней
      const schedule = []
      let currentDate = dayjs(startDate)
      const end = dayjs(endDate)
      
      while (currentDate.isBefore(end) || currentDate.isSame(end, 'day')) {
        // Пропускаем выходные (суббота, воскресенье)
        if (![0, 6].includes(currentDate.day())) {
          schedule.push({
            date: currentDate.format('YYYY-MM-DD'),
            available: Math.random() > 0.2, // 80% дней доступны
            timeSlots: generateTimeSlots(currentDate)
          })
        }
        currentDate = currentDate.add(1, 'day')
      }
      
      masterSchedule.value = schedule
      return schedule

    } catch (err) {
      error.value = 'Ошибка загрузки расписания мастера'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Загрузка доступных временных слотов для даты
   */
  const loadTimeSlots = async (_masterId: number, date: string) => {
    loading.value = true
    
    try {
      // Имитация API вызова - в будущем использовать _masterId
      await new Promise(resolve => setTimeout(resolve, 300))
      
      const slots = generateTimeSlots(dayjs(date))
      availableTimeSlots.value = slots
      return slots

    } catch (err) {
      error.value = 'Ошибка загрузки временных слотов'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Создание бронирования
   */
  interface BookingApiData {
    master_id?: number
    masterId?: number
    service_id?: number
    serviceId?: number
    start_time?: string
    date?: string
    time?: string
    duration_minutes?: number
    duration?: number
    total_price?: number
    totalPrice?: number
    client_name?: string
    clientName?: string
    client_phone?: string
    clientPhone?: string
    client_email?: string | null
    clientEmail?: string | null
    notes?: string | null
  }
  
  const createBooking = async (bookingData?: BookingApiData) => {
    const formData = bookingData || bookingForm.value
    
    if (!bookingData && !isFormValid.value) {
      throw new Error('Форма заполнена некорректно')
    }

    loading.value = true
    error.value = null

    try {
      // Подготовка данных для API
      const apiFormData = formData as BookingApiData
      const apiData = {
        masterId: apiFormData.masterId || apiFormData.master_id || 0,
        serviceId: apiFormData.serviceId || apiFormData.service_id,
        startTime: apiFormData.start_time || `${apiFormData.date} ${apiFormData.time}:00`,
        duration: apiFormData.duration || apiFormData.duration_minutes || 60,
        totalPrice: apiFormData.totalPrice || apiFormData.total_price || 0,
        clientName: apiFormData.clientName || apiFormData.client_name || '',
        clientPhone: apiFormData.clientPhone || apiFormData.client_phone || '',
        clientEmail: apiFormData.clientEmail || apiFormData.client_email || null,
        notes: apiFormData.notes || null
      }

      // Реальный API вызов
      const response = await bookingApi.createBooking(apiData)
      
      if (!response.success) {
        throw new Error(response.error || 'Ошибка создания бронирования')
      }

      // Используем данные из ответа API
      const booking = response.booking || response.data
      const [dateStr, timeStr] = apiData.startTime.split(' ')
      
      const newBooking: Booking = {
        id: booking.id || Date.now(),
        masterId: apiData.masterId || 0,
        serviceId: apiData.serviceId || undefined,
        date: dateStr || '',
        time: timeStr ? timeStr.substring(0, 5) : '', // убираем секунды
        clientName: apiData.clientName,
        clientPhone: apiData.clientPhone,
        clientEmail: apiData.clientEmail || undefined,
        notes: apiData.notes || undefined,
        totalPrice: apiData.totalPrice,
        duration: apiData.duration,
        status: booking.status || 'pending'
      }

      // Добавляем в список бронирований
      bookings.value.unshift(newBooking)
      currentBooking.value = newBooking

      // Очищаем форму
      resetBookingForm()

      return newBooking

    } catch (err) {
      error.value = (err as Error).message || 'Ошибка создания бронирования'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Загрузка списка бронирований пользователя
   */
  const loadUserBookings = async (userId: number) => {
    loading.value = true
    error.value = null

    try {
      // Имитация API вызова
      await new Promise(resolve => setTimeout(resolve, 800))
      
      // Генерируем тестовые бронирования
      const mockBookings = generateMockBookings(userId)
      bookings.value = mockBookings
      
      return mockBookings

    } catch (err) {
      error.value = 'Ошибка загрузки бронирований'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Отмена бронирования
   */
  const cancelBooking = async (bookingId: number, reason: string | null = null) => {
    loading.value = true
    error.value = null

    try {
      // Имитация API вызова
      await new Promise(resolve => setTimeout(resolve, 500))

      // Обновляем статус в локальном состоянии
      const bookingIndex = bookings.value.findIndex(b => b?.id === bookingId)
      if (bookingIndex !== -1 && bookings.value[bookingIndex]) {
        const booking = bookings.value[bookingIndex]
        booking.status = 'cancelled'
        // Дополнительная информация об отмене сохраняется в логах
        logger.info('Booking cancelled', {
          bookingId,
          reason,
          cancelledAt: dayjs().format('YYYY-MM-DD HH:mm:ss')
        }, 'BookingStore')
      }

      return true

    } catch (err) {
      error.value = 'Ошибка отмены бронирования'
      throw err
    } finally {
      loading.value = false
    }
  }

  /**
   * Сброс формы бронирования
   */
  const resetBookingForm = () => {
    bookingForm.value = {
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
    
    selectedDate.value = null
    selectedTime.value = null
    availableTimeSlots.value = []
    error.value = null
  }

  /**
   * Очистка состояния
   */
  const clearState = () => {
    bookings.value = []
    currentBooking.value = null
    masterSchedule.value = []
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
  // const interval = 60 // минут - для будущего использования

  for (let hour = startHour; hour < endHour; hour++) {
    const timeString = `${hour.toString().padStart(2, '0')}:00`
    
    // Случайно делаем некоторые слоты недоступными
    const available = Math.random() > 0.3
    
    slots.push({
      time: timeString,
      available: available,
      datetime: date.format('YYYY-MM-DD') + ' ' + timeString
    })
  }

  return slots
}

/**
 * Генерация тестовых бронирований
 */
function generateMockBookings(_userId?: any): Booking[] {
  const bookings: Booking[] = []
  const statuses: Array<'pending' | 'confirmed' | 'completed' | 'cancelled'> = ['pending', 'confirmed', 'completed', 'cancelled']
  
  for (let i = 0; i < 5; i++) {
    const startTime = dayjs().add(i - 2, 'days').hour(10 + i).minute(0).second(0)
    const dateStr = startTime.format('YYYY-MM-DD')
    const timeStr = startTime.format('HH:mm:ss')
    
    bookings.push({
      id: 1000 + i,
      masterId: 1,
      serviceId: 1,
      date: dateStr,
      time: timeStr,
      clientName: 'Иван Петров',
      clientPhone: '+7 (999) 123-45-67',
      clientEmail: 'ivan@example.com',
      notes: i % 2 === 0 ? 'Дополнительные пожелания' : undefined,
      totalPrice: 3000 + (i * 500),
      duration: 60,
      status: statuses[i % statuses.length] || 'pending'
    })
  }

  return bookings
}