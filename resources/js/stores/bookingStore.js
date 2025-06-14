import { defineStore } from 'pinia'
import axios from 'axios'

export const useBookingStore = defineStore('booking', {
  // 📦 СОСТОЯНИЕ - здесь храним данные
  state: () => ({
    // Текущее бронирование
    currentBooking: {
      masterId: null,
      serviceId: null,
      date: null,
      time: null,
      clientName: '',
      clientPhone: '',
      clientEmail: '',
      address: '',
      comment: '',
      locationType: 'salon',
      paymentMethod: 'cash'
    },
    
    // Доступные даты и время
    availableDates: [],
    timeSlots: [],
    
    // Состояние загрузки
    isLoading: false,
    error: null,
    
    // История бронирований пользователя
    userBookings: [],
    
    // Детали последнего успешного бронирования
    lastBooking: null
  }),

  // 🔍 ГЕТТЕРЫ - вычисляемые свойства
  getters: {
    // Проверка, заполнены ли все обязательные поля
    isBookingValid: (state) => {
      return !!(
        state.currentBooking.masterId &&
        state.currentBooking.serviceId &&
        state.currentBooking.date &&
        state.currentBooking.time &&
        state.currentBooking.clientName &&
        state.currentBooking.clientPhone
      )
    },
    
    // Получить выбранную дату в читаемом формате
    formattedDate: (state) => {
      if (!state.currentBooking.date) return ''
      
      const date = new Date(state.currentBooking.date)
      return date.toLocaleDateString('ru-RU', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric'
      })
    },
    
    // Подсчёт общей стоимости
    totalPrice: (state) => {
      let price = 0
      
      // Здесь будет логика подсчёта цены
      // price = state.currentService?.price || 0
      // if (state.currentBooking.locationType === 'home') price += 500
      
      return price
    },
    
    // Проверка, есть ли свободные слоты на выбранную дату
    hasAvailableSlots: (state) => {
      return state.timeSlots.length > 0
    }
  },

  // 🎯 ДЕЙСТВИЯ - функции для изменения данных
  actions: {
    // 📅 Загрузить доступные даты мастера
    async loadAvailableDates(masterId) {
      console.log('🔄 Загружаем доступные даты для мастера:', masterId)
      
      this.isLoading = true
      this.error = null
      
      try {
        // Запрос к серверу
        const response = await axios.get(`/api/masters/${masterId}/available-dates`)
        
        // Сохраняем даты
        this.availableDates = response.data.dates
        
        console.log('✅ Загружено дат:', this.availableDates.length)
        
        return this.availableDates
      } catch (error) {
        console.error('❌ Ошибка загрузки дат:', error)
        this.error = 'Не удалось загрузить доступные даты'
        
        // Временные тестовые данные (уберите когда будет API)
        this.availableDates = this.generateTestDates()
        
        throw error
      } finally {
        this.isLoading = false
      }
    },
    
    // ⏰ Загрузить доступные слоты времени на дату
    async loadTimeSlots(masterId, date) {
      console.log('🔄 Загружаем слоты времени на дату:', date)
      
      this.isLoading = true
      this.error = null
      
      try {
        // Запрос к серверу
        const response = await axios.get(`/api/masters/${masterId}/time-slots`, {
          params: { date }
        })
        
        // Сохраняем слоты
        this.timeSlots = response.data.slots
        
        console.log('✅ Загружено слотов:', this.timeSlots.length)
        
        return this.timeSlots
      } catch (error) {
        console.error('❌ Ошибка загрузки слотов:', error)
        this.error = 'Не удалось загрузить доступное время'
        
        // Временные тестовые данные (уберите когда будет API)
        this.timeSlots = this.generateTestTimeSlots()
        
        throw error
      } finally {
        this.isLoading = false
      }
    },
    
    // 📝 Создать новое бронирование
    async createBooking(bookingData) {
      console.log('📤 Отправляем бронирование:', bookingData)
      
      this.isLoading = true
      this.error = null
      
      try {
        // Подготавливаем данные
        const dataToSend = {
          master_id: bookingData.masterId,
          service_id: bookingData.serviceId,
          date: bookingData.date,
          time: bookingData.time,
          location_type: bookingData.locationType,
          client_name: bookingData.clientName,
          client_phone: bookingData.clientPhone,
          client_email: bookingData.clientEmail,
          address: bookingData.address,
          comment: bookingData.comment,
          payment_method: bookingData.paymentMethod
        }
        
        // Отправляем на сервер
        const response = await axios.post('/api/bookings', dataToSend)
        
        // Сохраняем результат
        this.lastBooking = response.data.booking
        
        // Очищаем форму
        this.resetCurrentBooking()
        
        console.log('✅ Бронирование создано:', this.lastBooking.id)
        
        return this.lastBooking
      } catch (error) {
        console.error('❌ Ошибка создания бронирования:', error)
        
        // Обработка ошибок валидации
        if (error.response?.status === 422) {
          this.error = 'Проверьте правильность заполнения формы'
          throw error.response.data.errors
        }
        
        this.error = 'Не удалось создать бронирование. Попробуйте позже.'
        throw error
      } finally {
        this.isLoading = false
      }
    },
    
    // 📋 Загрузить историю бронирований пользователя
    async loadUserBookings() {
      console.log('🔄 Загружаем историю бронирований')
      
      try {
        const response = await axios.get('/api/user/bookings')
        this.userBookings = response.data.bookings
        
        console.log('✅ Загружено бронирований:', this.userBookings.length)
        
        return this.userBookings
      } catch (error) {
        console.error('❌ Ошибка загрузки истории:', error)
        this.userBookings = []
        throw error
      }
    },
    
    // ❌ Отменить бронирование
    async cancelBooking(bookingId) {
      console.log('🗑️ Отменяем бронирование:', bookingId)
      
      try {
        await axios.post(`/api/bookings/${bookingId}/cancel`)
        
        // Удаляем из списка
        this.userBookings = this.userBookings.filter(b => b.id !== bookingId)
        
        console.log('✅ Бронирование отменено')
        
        return true
      } catch (error) {
        console.error('❌ Ошибка отмены:', error)
        throw error
      }
    },
    
    // 🔧 ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ
    
    // Установить данные текущего бронирования
    setBookingData(data) {
      this.currentBooking = { ...this.currentBooking, ...data }
    },
    
    // Установить мастера и услугу
    setMasterAndService(masterId, serviceId) {
      this.currentBooking.masterId = masterId
      this.currentBooking.serviceId = serviceId
    },
    
    // Установить дату
    setDate(date) {
      this.currentBooking.date = date
      this.currentBooking.time = null // Сбрасываем время при смене даты
    },
    
    // Установить время
    setTime(time) {
      this.currentBooking.time = time
    },
    
    // Очистить текущее бронирование
    resetCurrentBooking() {
      this.currentBooking = {
        masterId: null,
        serviceId: null,
        date: null,
        time: null,
        clientName: '',
        clientPhone: '',
        clientEmail: '',
        address: '',
        comment: '',
        locationType: 'salon',
        paymentMethod: 'cash'
      }
      this.availableDates = []
      this.timeSlots = []
    },
    
    // 🧪 ТЕСТОВЫЕ ДАННЫЕ (удалите когда будет готов backend)
    
    // Генерация тестовых дат
    generateTestDates() {
      const dates = []
      const today = new Date()
      
      // Генерируем даты на месяц вперёд
      for (let i = 1; i <= 30; i++) {
        const date = new Date(today)
        date.setDate(today.getDate() + i)
        
        // 70% вероятность что день доступен
        if (Math.random() > 0.3) {
          dates.push(date.toISOString().split('T')[0])
        }
      }
      
      return dates
    },
    
    // Генерация тестовых слотов времени
    generateTestTimeSlots() {
      const slots = []
      
      // Генерируем слоты с 9:00 до 20:00
      for (let hour = 9; hour < 20; hour++) {
        // Слоты каждые 30 минут
        slots.push({
          time: `${hour}:00`,
          available: Math.random() > 0.3 // 70% доступны
        })
        
        if (hour < 19) {
          slots.push({
            time: `${hour}:30`,
            available: Math.random() > 0.3
          })
        }
      }
      
      return slots
    }
  }
})