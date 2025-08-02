import axios from 'axios'

/**
 * API для работы с бронированиями
 */
class BookingApi {
  
  /**
   * Создать новое бронирование
   */
  async createBooking(bookingData) {
    try {
      const response = await axios.post('/api/bookings', {
        master_id: bookingData.masterId,
        service_id: bookingData.serviceId,
        start_time: bookingData.startTime,
        duration_minutes: bookingData.duration,
        total_price: bookingData.totalPrice,
        client_name: bookingData.clientName,
        client_phone: bookingData.clientPhone,
        client_email: bookingData.clientEmail,
        notes: bookingData.notes
      })

      return {
        success: true,
        data: response.data,
        booking: response.data.booking
      }
    } catch (error) {
      console.error('Booking creation failed:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка создания бронирования',
        errors: error.response?.data?.errors || {}
      }
    }
  }

  /**
   * Получить список бронирований пользователя
   */
  async getUserBookings(filters = {}) {
    try {
      const params = new URLSearchParams()
      
      if (filters.status) params.append('status', filters.status)
      if (filters.dateFrom) params.append('date_from', filters.dateFrom)
      if (filters.dateTo) params.append('date_to', filters.dateTo)
      if (filters.masterId) params.append('master_id', filters.masterId)
      if (filters.page) params.append('page', filters.page)
      if (filters.perPage) params.append('per_page', filters.perPage)

      const response = await axios.get(`/api/user/bookings?${params}`)

      return {
        success: true,
        data: response.data,
        bookings: response.data.data,
        pagination: response.data.meta || response.data
      }
    } catch (error) {
      console.error('Failed to load user bookings:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка загрузки бронирований',
        bookings: []
      }
    }
  }

  /**
   * Получить бронирования мастера
   */
  async getMasterBookings(masterId, filters = {}) {
    try {
      const params = new URLSearchParams()
      
      if (filters.status) params.append('status', filters.status)
      if (filters.dateFrom) params.append('date_from', filters.dateFrom)
      if (filters.dateTo) params.append('date_to', filters.dateTo)
      if (filters.page) params.append('page', filters.page)

      const response = await axios.get(`/api/masters/${masterId}/bookings?${params}`)

      return {
        success: true,
        data: response.data,
        bookings: response.data.data,
        pagination: response.data.meta || response.data
      }
    } catch (error) {
      console.error('Failed to load master bookings:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка загрузки бронирований мастера',
        bookings: []
      }
    }
  }

  /**
   * Получить детали бронирования
   */
  async getBooking(bookingId) {
    try {
      const response = await axios.get(`/api/bookings/${bookingId}`)

      return {
        success: true,
        booking: response.data.booking || response.data
      }
    } catch (error) {
      console.error('Failed to load booking details:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка загрузки деталей бронирования'
      }
    }
  }

  /**
   * Подтвердить бронирование (для мастера)
   */
  async confirmBooking(bookingId) {
    try {
      const response = await axios.patch(`/api/bookings/${bookingId}/confirm`)

      return {
        success: true,
        booking: response.data.booking
      }
    } catch (error) {
      console.error('Failed to confirm booking:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка подтверждения бронирования'
      }
    }
  }

  /**
   * Отменить бронирование
   */
  async cancelBooking(bookingId, reason = null) {
    try {
      const response = await axios.patch(`/api/bookings/${bookingId}/cancel`, {
        cancellation_reason: reason
      })

      return {
        success: true,
        booking: response.data.booking
      }
    } catch (error) {
      console.error('Failed to cancel booking:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка отмены бронирования'
      }
    }
  }

  /**
   * Перенести бронирование
   */
  async rescheduleBooking(bookingId, newDateTime) {
    try {
      const response = await axios.patch(`/api/bookings/${bookingId}/reschedule`, {
        new_start_time: newDateTime
      })

      return {
        success: true,
        booking: response.data.booking
      }
    } catch (error) {
      console.error('Failed to reschedule booking:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка переноса бронирования'
      }
    }
  }

  /**
   * Получить расписание мастера
   */
  async getMasterSchedule(masterId, dateFrom, dateTo) {
    try {
      const response = await axios.get(`/api/masters/${masterId}/schedule`, {
        params: {
          date_from: dateFrom,
          date_to: dateTo
        }
      })

      return {
        success: true,
        schedule: response.data.schedule || response.data,
        availableDates: response.data.available_dates || []
      }
    } catch (error) {
      console.error('Failed to load master schedule:', error)
      
      // Возвращаем заглушку для разработки
      return {
        success: true,
        schedule: this.generateMockSchedule(dateFrom, dateTo),
        availableDates: []
      }
    }
  }

  /**
   * Генерация заглушки расписания для разработки
   */
  generateMockSchedule(dateFrom, dateTo) {
    const schedule = []
    let currentDate = new Date(dateFrom)
    const endDate = new Date(dateTo)
    
    while (currentDate <= endDate) {
      // Пропускаем выходные (суббота, воскресенье)
      if (![0, 6].includes(currentDate.getDay())) {
        schedule.push({
          date: currentDate.toISOString().split('T')[0],
          available: Math.random() > 0.2, // 80% дней доступны
          timeSlots: this.generateMockTimeSlots()
        })
      }
      currentDate.setDate(currentDate.getDate() + 1)
    }
    
    return schedule
  }

  /**
   * Генерация заглушки временных слотов
   */
  generateMockTimeSlots() {
    const slots = []
    for (let hour = 9; hour < 21; hour++) {
      slots.push({
        time: `${hour.toString().padStart(2, '0')}:00`,
        available: Math.random() > 0.3
      })
    }
    return slots
  }

  /**
   * Получить доступные временные слоты для конкретной даты
   */
  async getAvailableTimeSlots(masterId, date, serviceId = null) {
    try {
      const params = { date }
      if (serviceId) params.service_id = serviceId

      const response = await axios.get(`/api/masters/${masterId}/time-slots`, {
        params
      })

      return {
        success: true,
        timeSlots: response.data.time_slots || response.data.slots || [],
        date: response.data.date
      }
    } catch (error) {
      console.error('Failed to load time slots:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка загрузки временных слотов',
        timeSlots: []
      }
    }
  }

  /**
   * Проверить доступность времени перед созданием бронирования
   */
  async checkTimeAvailability(masterId, startTime, duration) {
    try {
      const response = await axios.post('/api/bookings/check-availability', {
        master_id: masterId,
        start_time: startTime,
        duration_minutes: duration
      })

      return {
        success: true,
        available: response.data.available,
        conflictingBookings: response.data.conflicting_bookings || []
      }
    } catch (error) {
      console.error('Failed to check time availability:', error)
      
      return {
        success: false,
        available: false,
        error: error.response?.data?.message || 'Ошибка проверки доступности времени'
      }
    }
  }

  /**
   * Получить статистику бронирований
   */
  async getBookingStats(filters = {}) {
    try {
      const params = new URLSearchParams()
      
      if (filters.period) params.append('period', filters.period)
      if (filters.masterId) params.append('master_id', filters.masterId)

      const response = await axios.get(`/api/bookings/stats?${params}`)

      return {
        success: true,
        stats: response.data
      }
    } catch (error) {
      console.error('Failed to load booking stats:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка загрузки статистики',
        stats: {}
      }
    }
  }

  /**
   * Отправить напоминание о бронировании
   */
  async sendBookingReminder(bookingId) {
    try {
      const response = await axios.post(`/api/bookings/${bookingId}/reminder`)

      return {
        success: true,
        message: response.data.message
      }
    } catch (error) {
      console.error('Failed to send booking reminder:', error)
      
      return {
        success: false,
        error: error.response?.data?.message || 'Ошибка отправки напоминания'
      }
    }
  }
}

// Создаем и экспортируем единственный экземпляр
export const bookingApi = new BookingApi()

// Также экспортируем класс для тестирования
export { BookingApi }

// Вспомогательные функции для форматирования данных

/**
 * Подготовить данные бронирования для отправки на сервер
 */
export function prepareBookingData(formData) {
  return {
    masterId: formData.masterId,
    serviceId: formData.serviceId,
    startTime: `${formData.date} ${formData.time}:00`,
    duration: formData.duration || 60,
    totalPrice: formData.totalPrice,
    clientName: formData.clientName.trim(),
    clientPhone: formData.clientPhone.trim(),
    clientEmail: formData.clientEmail?.trim() || null,
    notes: formData.notes?.trim() || null
  }
}

/**
 * Форматировать данные бронирования для отображения
 */
export function formatBookingForDisplay(booking) {
  return {
    id: booking.id,
    bookingNumber: booking.booking_number,
    masterName: booking.master?.name || 'Неизвестный мастер',
    serviceName: booking.service?.name || 'Услуга',
    startTime: booking.start_time,
    endTime: booking.end_time,
    duration: booking.duration_minutes,
    price: booking.total_price,
    status: booking.status,
    clientName: booking.client_name,
    clientPhone: booking.client_phone,
    notes: booking.notes,
    createdAt: booking.created_at
  }
}

/**
 * Получить цвет статуса бронирования
 */
export function getBookingStatusColor(status) {
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    in_progress: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800',
    rescheduled: 'bg-purple-100 text-purple-800'
  }
  
  return colors[status] || 'bg-gray-100 text-gray-800'
}

/**
 * Получить текст статуса бронирования
 */
export function getBookingStatusText(status) {
  const texts = {
    pending: 'Ожидает подтверждения',
    confirmed: 'Подтверждено',
    in_progress: 'В процессе',
    completed: 'Завершено',
    cancelled: 'Отменено',
    rescheduled: 'Перенесено'
  }
  
  return texts[status] || 'Неизвестный статус'
}