import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useBookingStore = defineStore('booking', () => {
  
  // =================== СОСТОЯНИЕ ===================
  
  const bookings = ref([])
  const currentBooking = ref({
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
  
  const availableSlots = ref({})
  const isLoading = ref(false)
  const error = ref(null)
  const lastBooking = ref(null)
  
  // =================== ВЫЧИСЛЯЕМЫЕ ===================
  
  const isFormValid = computed(() => {
    return currentBooking.value.masterId &&
           currentBooking.value.serviceId &&
           currentBooking.value.date &&
           currentBooking.value.time &&
           currentBooking.value.clientName &&
           currentBooking.value.clientPhone
  })
  
  const totalBookings = computed(() => bookings.value.length)
  
  const pendingBookings = computed(() => 
    bookings.value.filter(b => b.status === 'pending')
  )
  
  const confirmedBookings = computed(() => 
    bookings.value.filter(b => b.status === 'confirmed')
  )
  
  // =================== ДЕЙСТВИЯ ===================
  
  // 📋 Получить список бронирований
  async function fetchBookings() {
    console.log('📤 Загружаем список бронирований...')
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.get('/api/bookings', {
        headers: {
          'Authorization': `Bearer ${getAuthToken()}`,
          'Accept': 'application/json'
        }
      })
      
      bookings.value = response.data.data || response.data
      console.log('✅ Загружено бронирований:', bookings.value.length)
      
      return bookings.value
    } catch (error) {
      console.error('❌ Ошибка загрузки бронирований:', error)
      
      if (error.response?.status === 401) {
        this.error = 'Необходимо войти в систему'
      } else {
        this.error = 'Не удалось загрузить бронирования'
      }
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // 📅 Получить доступные слоты
  async function fetchAvailableSlots(masterId, serviceId, date = null) {
    console.log('📤 Получаем доступные слоты для мастера:', masterId, 'услуга:', serviceId)
    
    isLoading.value = true
    error.value = null
    
    try {
      const params = {
        master_profile_id: masterId,
        service_id: serviceId
      }
      
      if (date) {
        params.date = date
      }
      
      const response = await axios.get('/api/bookings/available-slots', {
        params,
        headers: {
          'Accept': 'application/json'
        }
      })
      
      if (date) {
        // Слоты для конкретной даты
        availableSlots.value[date] = response.data.slots || []
      } else {
        // Слоты на несколько дней
        availableSlots.value = response.data.slots || {}
      }
      
      console.log('✅ Получены доступные слоты:', Object.keys(availableSlots.value).length, 'дней')
      
      return availableSlots.value
    } catch (error) {
      console.error('❌ Ошибка получения слотов:', error)
      this.error = 'Не удалось получить доступные слоты'
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // 📝 Создать новое бронирование (ОБНОВЛЕННЫЙ МЕТОД)
  async function createBooking(bookingData) {
    console.log('📤 Отправляем бронирование:', bookingData)
    
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
      const response = await axios.post('/api/bookings', dataToSend, {
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
      
      console.log('✅ Бронирование создано:', response.data.booking_number)
      
      return response.data.booking
    } catch (error) {
      console.error('❌ Ошибка создания бронирования:', error)
      
      // Обработка ошибок валидации
      if (error.response?.status === 422) {
        const validationErrors = error.response.data.errors || {}
        this.error = Object.values(validationErrors).flat().join(', ') || error.response.data.message
        throw validationErrors
      }
      
      // Обработка других ошибок
      if (error.response?.status === 401) {
        this.error = 'Необходимо войти в систему'
      } else {
        this.error = error.response?.data?.message || 'Не удалось создать бронирование'
      }
      
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // ❌ Отменить бронирование
  async function cancelBooking(bookingId, reason = null) {
    console.log('📤 Отменяем бронирование:', bookingId)
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post(`/api/bookings/${bookingId}/cancel`, 
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
      
      console.log('✅ Бронирование отменено')
      
      return response.data
    } catch (error) {
      console.error('❌ Ошибка отмены бронирования:', error)
      this.error = error.response?.data?.message || 'Не удалось отменить бронирование'
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // ✅ Подтвердить бронирование (для мастеров)
  async function confirmBooking(bookingId) {
    console.log('📤 Подтверждаем бронирование:', bookingId)
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post(`/api/bookings/${bookingId}/confirm`, {}, {
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
      
      console.log('✅ Бронирование подтверждено')
      
      return response.data
    } catch (error) {
      console.error('❌ Ошибка подтверждения бронирования:', error)
      this.error = error.response?.data?.message || 'Не удалось подтвердить бронирование'
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // 🏁 Завершить услугу (для мастеров)
  async function completeBooking(bookingId) {
    console.log('📤 Завершаем услугу:', bookingId)
    
    isLoading.value = true
    error.value = null
    
    try {
      const response = await axios.post(`/api/bookings/${bookingId}/complete`, {}, {
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
      
      console.log('✅ Услуга завершена')
      
      return response.data
    } catch (error) {
      console.error('❌ Ошибка завершения услуги:', error)
      this.error = error.response?.data?.message || 'Не удалось завершить услугу'
      throw error
    } finally {
      isLoading.value = false
    }
  }
  
  // =================== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ===================
  
  // 🔄 Сбросить текущее бронирование
  function resetCurrentBooking() {
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
  function updateBookingData(data) {
    Object.assign(currentBooking.value, data)
  }
  
  // 🔑 Получить токен авторизации
  function getAuthToken() {
    // Для Laravel Sanctum токен может быть в cookie или localStorage
    // В данном случае используем CSRF токен
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    return token || ''
  }
  
  // 🧹 Очистить ошибки
  function clearError() {
    error.value = null
  }
  
  // 📊 Получить статистику
  function getBookingStats() {
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