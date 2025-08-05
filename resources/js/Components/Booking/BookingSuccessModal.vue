<template>
    <Modal 
        :show="true" 
        @close="handleClose" 
        max-width="sm"
        data-testid="booking-success-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="success-modal-title"
    >
        <div class="p-6" data-testid="modal-content">
            <!-- Иконка успеха -->
            <div 
                class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4"
                data-testid="success-icon"
                role="img"
                aria-label="Успех"
            >
                <svg 
                    class="h-8 w-8 text-green-600" 
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path 
                        stroke-linecap="round" 
                        stroke-linejoin="round" 
                        stroke-width="2" 
                        d="M5 13l4 4L19 7" 
                    />
                </svg>
            </div>
            
            <!-- Заголовок -->
            <h3 
                id="success-modal-title"
                class="text-center text-lg font-semibold text-gray-900 mb-2"
                data-testid="success-title"
            >
                Бронирование создано!
            </h3>
            
            <!-- Описание -->
            <p 
                class="text-center text-gray-600 mb-6"
                data-testid="success-description"
            >
                Ваша заявка отправлена мастеру. Ожидайте подтверждения.
            </p>
            
            <!-- Детали бронирования -->
            <div 
                v-if="safeBooking" 
                class="bg-gray-50 rounded-lg p-4 mb-6"
                data-testid="booking-details"
                role="region"
                aria-label="Детали бронирования"
            >
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Номер заявки:</span>
                        <span 
                            class="font-medium text-gray-900"
                            data-testid="booking-number"
                        >
                            #{{ safeBooking.id }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Мастер:</span>
                        <span 
                            class="font-medium text-gray-900"
                            data-testid="master-name"
                        >
                            {{ safeBooking.master_name }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Услуга:</span>
                        <span 
                            class="font-medium text-gray-900"
                            data-testid="service-name"
                        >
                            {{ safeBooking.service_name }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Дата и время:</span>
                        <span 
                            class="font-medium text-gray-900"
                            data-testid="booking-datetime"
                        >
                            {{ formattedDateTime }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Стоимость:</span>
                        <span 
                            class="font-medium text-gray-900"
                            data-testid="booking-price"
                        >
                            {{ formattedPrice }}
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Информация о подтверждении -->
            <div 
                class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6"
                data-testid="next-steps-info"
                role="region"
                aria-label="Информация о следующих шагах"
            >
                <div class="flex">
                    <svg 
                        class="h-5 w-5 text-blue-400 mt-0.5" 
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                        aria-hidden="true"
                    >
                        <path 
                            fill-rule="evenodd" 
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" 
                            clip-rule="evenodd" 
                        />
                    </svg>
                    <div class="ml-3 text-sm text-blue-700">
                        <p class="font-medium mb-1">Что дальше?</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>Мастер получит уведомление о вашей заявке</li>
                            <li>В течение 30 минут он подтвердит бронирование</li>
                            <li>Вы получите SMS и email с подтверждением</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Действия -->
            <div class="flex gap-3" data-testid="modal-actions">
                <Link 
                    v-if="safeBooking"
                    :href="detailsUrl"
                    class="flex-1 bg-purple-600 text-white text-center py-2.5 px-4 rounded-lg font-medium hover:bg-purple-700 transition-colors"
                    data-testid="view-details-button"
                    @click="handleViewDetails"
                >
                    Посмотреть детали
                </Link>
                <button 
                    @click="handleClose"
                    class="flex-1 bg-gray-200 text-gray-700 py-2.5 px-4 rounded-lg font-medium hover:bg-gray-300 transition-colors"
                    data-testid="close-button"
                    type="button"
                    aria-label="Закрыть модальное окно"
                >
                    Закрыть
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import Modal from '@/src/shared/ui/organisms/Modal/Modal.vue'
import { format } from 'date-fns'
import { ru } from 'date-fns/locale'
import type {
  BookingSuccessModalProps,
  BookingSuccessModalEmits,
  Booking,
  BookingSuccessError
} from './BookingSuccessModal.types'

// Props и emits с TypeScript типизацией
const props = defineProps<BookingSuccessModalProps>()
const emit = defineEmits<BookingSuccessModalEmits>()

// Безопасная обработка данных бронирования
const safeBooking = computed<Booking | null>(() => {
  if (!props.booking) return null
  
  // Проверяем обязательные поля
  if (!props.booking.id || !props.booking.master_name || !props.booking.service_name) {
    console.warn('BookingSuccessModal: Missing required booking fields', props.booking)
    return null
  }
  
  return props.booking
})

// Computed свойства для форматирования
const formattedDateTime = computed<string>(() => {
  if (!safeBooking.value) return ''
  return formatDateTime(safeBooking.value)
})

const formattedPrice = computed<string>(() => {
  if (!safeBooking.value || typeof safeBooking.value.price !== 'number') return '0 ₽'
  return formatPrice(safeBooking.value.price)
})

const detailsUrl = computed<string>(() => {
  if (!safeBooking.value) return '#'
  return `/bookings/${safeBooking.value.id}`
})

// Методы форматирования с типизацией
const formatDateTime = (booking: Booking): string => {
  try {
    if (!booking.booking_date || !booking.booking_time) {
      return 'Дата не указана'
    }
    
    // Проверяем валидность даты и времени
    const dateTimeString = `${booking.booking_date} ${booking.booking_time}`
    const date = new Date(dateTimeString)
    
    if (isNaN(date.getTime())) {
      console.warn('BookingSuccessModal: Invalid date format', {
        booking_date: booking.booking_date,
        booking_time: booking.booking_time
      })
      return `${booking.booking_date} в ${booking.booking_time}`
    }
    
    return format(date, 'd MMMM, EEEE в HH:mm', { locale: ru })
  } catch (error: unknown) {
    const bookingError: BookingSuccessError = {
      type: 'formatting',
      message: 'Ошибка форматирования даты',
      originalError: error
    }
    console.error('BookingSuccessModal formatDateTime error:', bookingError)
    return 'Ошибка отображения даты'
  }
}

const formatPrice = (price: number): string => {
  try {
    if (typeof price !== 'number' || isNaN(price)) {
      return '0 ₽'
    }
    
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(price)
  } catch (error: unknown) {
    const bookingError: BookingSuccessError = {
      type: 'formatting',
      message: 'Ошибка форматирования цены',
      originalError: error
    }
    console.error('BookingSuccessModal formatPrice error:', bookingError)
    return `${price} ₽`
  }
}

// Обработчики событий
const handleClose = (): void => {
  try {
    emit('close')
    
    // Логируем событие для аналитики
    console.log('BookingSuccessModal closed', {
      bookingId: safeBooking.value?.id,
      timestamp: new Date().toISOString()
    })
  } catch (error: unknown) {
    const bookingError: BookingSuccessError = {
      type: 'display',
      message: 'Ошибка при закрытии модального окна',
      originalError: error
    }
    console.error('BookingSuccessModal handleClose error:', bookingError)
  }
}

const handleViewDetails = (): void => {
  try {
    if (!safeBooking.value) {
      console.warn('BookingSuccessModal: Cannot view details - no valid booking data')
      return
    }
    
    // Логируем переход для аналитики
    console.log('BookingSuccessModal view details clicked', {
      bookingId: safeBooking.value.id,
      url: detailsUrl.value,
      timestamp: new Date().toISOString()
    })
  } catch (error: unknown) {
    const bookingError: BookingSuccessError = {
      type: 'navigation',
      message: 'Ошибка при переходе к деталям',
      originalError: error
    }
    console.error('BookingSuccessModal handleViewDetails error:', bookingError)
  }
}

// Валидация данных бронирования при монтировании
const validateBookingData = (): boolean => {
  if (!props.booking) {
    console.error('BookingSuccessModal: No booking data provided')
    return false
  }
  
  const requiredFields: (keyof Booking)[] = ['id', 'master_name', 'service_name', 'booking_date', 'booking_time', 'price']
  const missingFields = requiredFields.filter(field => !props.booking[field])
  
  if (missingFields.length > 0) {
    console.warn('BookingSuccessModal: Missing required fields:', missingFields)
    return false
  }
  
  return true
}

// Выполняем валидацию
validateBookingData()
</script>