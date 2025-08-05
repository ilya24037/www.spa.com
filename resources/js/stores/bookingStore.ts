import { defineStore } from 'pinia'
import { ref, computed, type Ref } from 'vue'
import axios, { type AxiosResponse } from 'axios'

// =================== TYPES ===================
export interface BookingData {
  masterId: number
  serviceId: number
  date: string
  time: string
  locationType: 'home' | 'salon'
  clientName: string
  clientPhone: string
  clientEmail?: string
  address?: string
  addressDetails?: string
  comment?: string
  paymentMethod: 'cash' | 'card'
}

export interface BookingResult {
  id: number
  status: 'pending' | 'confirmed' | 'cancelled' | 'completed'
  master_profile_id: number
  service_id: number
  booking_date: string
  booking_time: string
  service_location: 'home' | 'salon'
  client_name: string
  client_phone: string
  client_email?: string
  address?: string
  client_comment?: string
  payment_method: 'cash' | 'card'
  payment_status?: 'pending' | 'paid' | 'failed'
  total_price?: number
  created_at: string
  updated_at: string
  cancelled_at?: string
  confirmed_at?: string
  paid_at?: string
}

export interface TimeSlot {
  time: string
  available: boolean
  price?: number
}

export interface BookingSlots {
  [date: string]: TimeSlot[]
}

interface BookingState {
  masterId: number | null
  serviceId: number | null
  date: string | null
  time: string | null
  locationType: 'home' | 'salon'
  clientName: string
  clientPhone: string
  clientEmail: string
  address: string
  comment: string
  paymentMethod: 'cash' | 'card'
}

export interface BookingStats {
  total: number
  pending: number
  confirmed: number
  completed: number
  cancelled: number
}

interface ApiError {
  response?: {
    status?: number
    data?: {
      message?: string
      errors?: Record<string, string[]>
    }
  }
}

// =================== STORE ===================
export const useBookingStore = defineStore('booking', () => {
  
  // =================== СОСТОЯНИЕ ===================
  
  const bookings: import("vue").Ref<BookingResult[]> = ref([])
  const currentBooking: import("vue").Ref<BookingState> = ref({
    masterId: null,
    serviceId: null,
    date: null,
    time: null,
    locationType: 'home', // 'home' или 'salon'
    clientName: '',
    clientPhone: '',
    clientEmail: '',
    address: '',
    comment: '',
    paymentMethod: 'cash'
  })
  
  const availableSlots: import("vue").Ref<BookingSlots> = ref({})
  const isLoading: import("vue").Ref<boolean> = ref(false)
  const error: import("vue").Ref<string | null> = ref(null)
  const lastBooking: import("vue").Ref<BookingResult | null> = ref(null)
  
  // =================== ВЫЧИСЛЯЕМЫЕ ===================
  
  const isFormValid = computed((): boolean => {
    return Boolean(
      currentBooking.value.masterId &&
      currentBooking.value.serviceId &&
      currentBooking.value.date &&
      currentBooking.value.time &&
      currentBooking.value.clientName &&
      currentBooking.value.clientPhone
    )
  })
  
  const totalBookings = computed((): number => bookings.value.length)
  
  const pendingBookings = computed((): BookingResult[] => 
    bookings.value.filter(b => b.status === 'pending')
  )
  
  const confirmedBookings = computed((): BookingResult[] => 
    bookings.value.filter(b => b.status === 'confirmed')
  )
  
  // =================== ДЕЙСТВИЯ ===================
  
  // 📋 Получить список бронирований
  async function fetchBookings(): Promise<BookingResult[]> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const response: AxiosResponse<{ data?: BookingResult[] } | BookingResult[]> = await axios.get('/api/bookings', {
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`,
          'Accept': 'application/json'
        }
      })
      
      bookings.value = response.data.data || response.data as BookingResult[]
      
      return bookings.value
    } catch (err: unknown) {
      const error = err as ApiError
      
      if (error.response?.status === 401) {
        setError('Необходимо войти в систему')
      } else {
        setError('Не удалось загрузить бронирования')
      }
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // 📅 Получить доступные слоты
  async function fetchAvailableSlots(
    masterId: number, 
    serviceId: number, 
    date: string | null = null
  ): Promise<BookingSlots> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const params: Record<string, string | number> = {
        master_profile_id: masterId,
        service_id: serviceId
      }
      
      if (date) {
        params.date = date
      }
      
      const response: AxiosResponse<{ slots: TimeSlot[] | BookingSlots }> = await axios.get('/api/bookings/available-slots', {
        params,
        headers: {
          'Accept': 'application/json'
        }
      })
      
      if (date) {
        // Слоты для конкретной даты
        availableSlots.value[date] = response.data.slots as TimeSlot[]
      } else {
        // Слоты на несколько дней
        availableSlots.value = response.data.slots as BookingSlots
      }
      
      return availableSlots.value
    } catch (err: unknown) {
      const error = err as ApiError
      setError('Не удалось получить доступные слоты')
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // 📝 Создать новое бронирование (ОБНОВЛЕННЫЙ МЕТОД)
  async function createBooking(bookingData: BookingData): Promise<BookingResult> {
    
    isLoading.value = true
    error.value = null
    
    try {
      // Подготавливаем данные в правильном формате
      const dataToSend = {
        master_profile_id: bookingData.masterId,
        service_id: bookingData.serviceId,
        booking_date: bookingData.date,
        booking_time: bookingData.time,
        service_location: bookingData.locationType,
        client_name: bookingData.clientName,
        client_phone: bookingData.clientPhone,
        client_email: bookingData.clientEmail,
        address: bookingData.address,
        address_details: bookingData.addressDetails,
        client_comment: bookingData.comment,
        payment_method: bookingData.paymentMethod
      }
      
      // Отправляем на новый API endpoint
      const response: AxiosResponse<{ booking: BookingResult }> = await axios.post('/api/bookings', dataToSend, {
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })
      
      // Сохраняем результат
      lastBooking.value = response.data.booking
      
      // Добавляем в локальный список
      bookings.value.unshift(response.data.booking)
      
      // Очищаем форму
      resetCurrentBooking()
      
      return response.data.booking
    } catch (err: unknown) {
      const error = err as ApiError
      
      // Обработка ошибок валидации
      if (error.response?.status === 422) {
        const validationErrors = error.response.data?.errors || {}
        const errorMessage = Object.values(validationErrors).flat().join(', ') || error.response.data?.message
        setError(errorMessage || 'Ошибка валидации')
        throw validationErrors
      }
      
      // Обработка других ошибок
      if (error.response?.status === 401) {
        setError('Необходимо войти в систему')
      } else {
        setError(error.response?.data?.message || 'Не удалось создать бронирование')
      }
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // ❌ Отменить бронирование
  async function cancelBooking(bookingId: number, reason: string | null = null): Promise<any> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const response: AxiosResponse<any> = await axios.post(`/api/bookings/${bookingId}/cancel`, 
        { reason },
        {
          headers: {
            'Authorization': `Bearer ${getAuthToken()}`,
            'Accept': 'application/json'
          }
        }
      )
      
      // Обновляем статус в локальном списке
      const booking = bookings.value.find(b => b.id === bookingId)
      if (booking) {
        booking.status = 'cancelled'
        booking.cancelled_at = new Date().toISOString()
      }
      
      return response.data
    } catch (err: unknown) {
      const error = err as ApiError
      setError(error.response?.data?.message || 'Не удалось отменить бронирование')
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // ✅ Подтвердить бронирование (для мастеров)
  async function confirmBooking(bookingId: number): Promise<any> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const response: AxiosResponse<any> = await axios.post(`/api/bookings/${bookingId}/confirm`, {}, {
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`,
          'Accept': 'application/json'
        }
      })
      
      // Обновляем статус в локальном списке
      const booking = bookings.value.find(b => b.id === bookingId)
      if (booking) {
        booking.status = 'confirmed'
        booking.confirmed_at = new Date().toISOString()
      }
      
      return response.data
    } catch (err: unknown) {
      const error = err as ApiError
      setError(error.response?.data?.message || 'Не удалось подтвердить бронирование')
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // 🏁 Завершить услугу (для мастеров)
  async function completeBooking(bookingId: number): Promise<any> {
    
    isLoading.value = true
    error.value = null
    
    try {
      const response: AxiosResponse<any> = await axios.post(`/api/bookings/${bookingId}/complete`, {}, {
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`,
          'Accept': 'application/json'
        }
      })
      
      // Обновляем статус в локальном списке
      const booking = bookings.value.find(b => b.id === bookingId)
      if (booking) {
        booking.status = 'completed'
        booking.payment_status = 'paid'
        booking.paid_at = new Date().toISOString()
      }
      
      return response.data
    } catch (err: unknown) {
      const error = err as ApiError
      setError(error.response?.data?.message || 'Не удалось завершить услугу')
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // =================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ===================
  
  // 🔄 Сбросить текущее бронирование
  function resetCurrentBooking(): void {
    currentBooking.value = {
      masterId: null,
      serviceId: null,
      date: null,
      time: null,
      locationType: 'home',
      clientName: '',
      clientPhone: '',
      clientEmail: '',
      address: '',
      comment: '',
      paymentMethod: 'cash'
    }
  }
  
  // 📝 Обновить данные формы
  function updateBookingData(data: Partial<BookingState>): void {
    Object.assign(currentBooking.value, data)
  }
  
  // 🔑 Получить токен авторизации
  function getAuthToken(): string {
    // Для Laravel Sanctum токен может быть в cookie или localStorage
    // В данном случае используем CSRF токен
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token || ''
  }
  
  // 🧹 Очистить ошибки
  function clearError(): void {
    error.value = null
  }
  
  // ⚠️ Установить ошибку
  function setError(message: string): void {
    error.value = message
  }
  
  // 📊 Получить статистику
  function getBookingStats(): BookingStats {
    return {
      total: totalBookings.value,
      pending: pendingBookings.value.length,
      confirmed: confirmedBookings.value.length,
      completed: bookings.value.filter(b => b.status === 'completed').length,
      cancelled: bookings.value.filter(b => b.status === 'cancelled').length
    }
  }
  
  // =================== ВОЗВРАЩАЕМ ИНТЕРФЕЙС ===================
  
  return {
    // Состояние
    bookings,
    currentBooking,
    availableSlots,
    isLoading,
    error,
    lastBooking,
    
    // Вычисляемые
    isFormValid,
    totalBookings,
    pendingBookings,
    confirmedBookings,
    
    // Действия
    fetchBookings,
    fetchAvailableSlots,
    createBooking,
    cancelBooking,
    confirmBooking,
    completeBooking,
    
    // Вспомогательные
    resetCurrentBooking,
    updateBookingData,
    clearError,
    getBookingStats
  }
})