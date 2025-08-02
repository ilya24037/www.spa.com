<!-- resources/js/src/widgets/profile-dashboard/components/BookingsList.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <div v-if="!loading && bookings.length" :class="BOOKINGS_LIST_CLASSES">
      <div
        v-for="booking in bookings"
        :key="booking.id"
        :class="BOOKING_ITEM_CLASSES"
      >
        <div :class="BOOKING_INFO_CLASSES">
          <h3 :class="CLIENT_NAME_CLASSES">{{ booking.client_name }}</h3>
          <p :class="SERVICE_NAME_CLASSES">{{ booking.service_name }}</p>
          <div :class="BOOKING_META_CLASSES">
            <span>{{ formatDateTime(booking.scheduled_at) }}</span>
            <span>{{ booking.duration }} мин</span>
            <span>{{ formatPrice(booking.price) }} ₽</span>
          </div>
        </div>
        
        <div :class="BOOKING_ACTIONS_CLASSES">
          <span :class="getStatusClasses(booking.status)">
            {{ getStatusLabel(booking.status) }}
          </span>
          
          <div :class="ACTION_BUTTONS_CLASSES">
            <button
              v-if="booking.status === 'pending'"
              @click="$emit('confirm', booking)"
              :class="CONFIRM_BUTTON_CLASSES"
            >
              Подтвердить
            </button>
            
            <button
              v-if="booking.status === 'confirmed'"
              @click="$emit('complete', booking)"
              :class="COMPLETE_BUTTON_CLASSES"
            >
              Завершить
            </button>
            
            <button
              v-if="['pending', 'confirmed'].includes(booking.status)"
              @click="$emit('cancel', booking)"
              :class="CANCEL_BUTTON_CLASSES"
            >
              Отменить
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="loading" :class="LOADING_CLASSES">
      <span>Загрузка записей...</span>
    </div>

    <div v-else :class="EMPTY_STATE_CLASSES">
      <h3>У вас пока нет записей</h3>
      <p>Записи клиентов будут отображаться здесь</p>
    </div>
  </div>
</template>

<script setup>
import dayjs from 'dayjs'

const CONTAINER_CLASSES = 'p-6'
const BOOKINGS_LIST_CLASSES = 'space-y-4'
const BOOKING_ITEM_CLASSES = 'flex justify-between items-start p-4 border rounded-lg'
const BOOKING_INFO_CLASSES = 'flex-1'
const CLIENT_NAME_CLASSES = 'font-medium text-gray-900'
const SERVICE_NAME_CLASSES = 'text-sm text-gray-600'
const BOOKING_META_CLASSES = 'flex gap-4 text-xs text-gray-500 mt-2'
const BOOKING_ACTIONS_CLASSES = 'text-right'
const ACTION_BUTTONS_CLASSES = 'flex gap-2 mt-2'
const CONFIRM_BUTTON_CLASSES = 'px-3 py-1 bg-green-600 text-white text-sm rounded'
const COMPLETE_BUTTON_CLASSES = 'px-3 py-1 bg-blue-600 text-white text-sm rounded'
const CANCEL_BUTTON_CLASSES = 'px-3 py-1 bg-red-600 text-white text-sm rounded'
const LOADING_CLASSES = 'text-center py-12 text-gray-500'
const EMPTY_STATE_CLASSES = 'text-center py-12 text-gray-500'

defineProps({
  bookings: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})

defineEmits(['confirm', 'cancel', 'complete'])

const formatDateTime = (date) => dayjs(date).format('DD.MM.YYYY HH:mm')
const formatPrice = (price) => new Intl.NumberFormat('ru-RU').format(price)

const getStatusClasses = (status) => {
  const base = 'px-2 py-1 text-xs rounded-full'
  const colors = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-green-100 text-green-800',
    completed: 'bg-blue-100 text-blue-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return `${base} ${colors[status] || 'bg-gray-100 text-gray-800'}`
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Ожидает',
    confirmed: 'Подтверждено',
    completed: 'Завершено',
    cancelled: 'Отменено'
  }
  return labels[status] || status
}
</script>