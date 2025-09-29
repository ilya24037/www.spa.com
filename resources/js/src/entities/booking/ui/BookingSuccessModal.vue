<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="modal-overlay" @click="handleClose">
        <div class="modal-content" @click.stop>
          <!-- Иконка успеха -->
          <div class="success-icon">
            <svg class="h-8 w-8 text-green-600"
                 fill="none"
                 stroke="currentColor"
                 viewBox="0 0 24 24">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 13l4 4L19 7" />
            </svg>
          </div>
          
          <!-- Заголовок -->
          <h3 class="modal-title">
            Бронирование создано!
          </h3>
          
          <!-- Описание -->
          <p class="modal-description">
            Ваша заявка отправлена мастеру. Ожидайте подтверждения.
          </p>
          
          <!-- Детали бронирования -->
          <div v-if="booking" class="booking-details">
            <div class="detail-row">
              <span class="detail-label">Номер заявки:</span>
              <span class="detail-value">#{{ booking.id }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Мастер:</span>
              <span class="detail-value">{{ booking.master_name || 'Не указан' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Услуга:</span>
              <span class="detail-value">{{ booking.service_name || 'Не указана' }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Дата и время:</span>
              <span class="detail-value">{{ formattedDateTime }}</span>
            </div>
            <div class="detail-row">
              <span class="detail-label">Стоимость:</span>
              <span class="detail-value">{{ formattedPrice }}</span>
            </div>
          </div>
          
          <!-- Информация о подтверждении -->
          <div class="info-block">
            <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" 
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" 
                    clip-rule="evenodd" />
            </svg>
            <div class="info-content">
              <p class="info-title">Что дальше?</p>
              <ul class="info-list">
                <li>Мастер получит уведомление о вашей заявке</li>
                <li>В течение 30 минут он подтвердит бронирование</li>
                <li>Вы получите SMS и email с подтверждением</li>
              </ul>
            </div>
          </div>
          
          <!-- Действия -->
          <div class="modal-actions">
            <Link 
              v-if="booking?.id"
              :href="`/bookings/${booking.id}`"
              class="btn-primary"
            >
              Посмотреть детали
            </Link>
            <button 
              @click="handleClose"
              class="btn-secondary"
            >
              Закрыть
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useBookingFormatter } from '../composables/useBookingFormatter'

// Types
interface BookingData {
  id: number
  master_name?: string
  service_name?: string
  booking_date?: string
  booking_time?: string
  price?: number
  status?: string
}

// Props
interface Props {
  show: boolean
  booking: BookingData | null
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  close: []
}>()

// Composables
const { formatDateTime, formatPrice } = useBookingFormatter()

// Computed
const formattedDateTime = computed(() => {
  if (!props.booking?.booking_date || !props.booking?.booking_time) {
    return 'Не указано'
  }
  return formatDateTime(props.booking.booking_date, props.booking.booking_time)
})

const formattedPrice = computed(() => {
  if (!props.booking?.price) {
    return 'Не указана'
  }
  return formatPrice(props.booking.price)
})

// Methods
const handleClose = () => {
  emit('close')
}
</script>

<style scoped>
/* Анимация модалки */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.9) translateY(-20px);
}

/* Основные стили */
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-content {
  @apply bg-white rounded-lg p-6 max-w-md w-full shadow-xl transition-all;
}

.success-icon {
  @apply mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4;
}

.modal-title {
  @apply text-center text-lg font-semibold text-gray-900 mb-2;
}

.modal-description {
  @apply text-center text-gray-600 mb-6;
}

/* Детали бронирования */
.booking-details {
  @apply bg-gray-50 rounded-lg p-4 mb-6 space-y-2;
}

.detail-row {
  @apply flex justify-between text-sm;
}

.detail-label {
  @apply text-gray-600;
}

.detail-value {
  @apply font-medium text-gray-900;
}

/* Информационный блок */
.info-block {
  @apply bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 flex;
}

.info-icon {
  @apply h-5 w-5 text-blue-400 mt-0.5 flex-shrink-0;
}

.info-content {
  @apply ml-3 text-sm text-blue-700;
}

.info-title {
  @apply font-medium mb-1;
}

.info-list {
  @apply list-disc list-inside space-y-1;
}

/* Кнопки действий */
.modal-actions {
  @apply flex gap-3;
}

.btn-primary {
  @apply flex-1 bg-purple-600 text-white text-center py-2.5 px-4 rounded-lg font-medium hover:bg-purple-700 transition-colors;
}

.btn-secondary {
  @apply flex-1 bg-gray-200 text-gray-700 py-2.5 px-4 rounded-lg font-medium hover:bg-gray-300 transition-colors;
}
</style>