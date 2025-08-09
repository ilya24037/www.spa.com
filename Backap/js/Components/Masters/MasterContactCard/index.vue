<template>
  <div class="contact-card">
    <!-- Цена -->
    <div class="price-section">
      <div class="price">
        от {{ formatPrice(master.price_from) }} ₽
      </div>
      <div class="price-note">
        за сеанс
      </div>
    </div>

    <!-- Кнопки действий -->
    <div class="action-buttons">
      <button 
        @click="showPhone"
        class="btn btn-primary"
      >
        <PhoneIcon class="w-5 h-5" />
        Показать телефон
      </button>
      
      <button 
        @click="openBooking"
        class="btn btn-secondary"
      >
        <CalendarIcon class="w-5 h-5" />
        Записаться
      </button>
    </div>

    <!-- Мессенджеры -->
    <div v-if="hasMessengers" class="messengers">
      <button 
        v-if="master.whatsapp"
        @click="openWhatsApp"
        class="messenger-btn whatsapp"
      >
        <WhatsAppIcon class="w-5 h-5" />
        WhatsApp
      </button>
      
      <button 
        v-if="master.telegram"
        @click="openTelegram"
        class="messenger-btn telegram"
      >
        <TelegramIcon class="w-5 h-5" />
        Telegram
      </button>
    </div>

    <!-- График работы -->
    <div class="schedule-section">
      <h3 class="section-title">График работы</h3>
      <div class="schedule-list">
        <div 
          v-for="schedule in master.schedules" 
          :key="schedule.day_of_week"
          class="schedule-item"
        >
          <span class="day">{{ getDayName(schedule.day_of_week) }}</span>
          <span class="time">{{ schedule.start_time }} - {{ schedule.end_time }}</span>
        </div>
      </div>
    </div>

    <!-- Ближайшие слоты -->
    <div class="slots-section">
      <h3 class="section-title">Ближайшие слоты</h3>
      <div class="slots-list">
        <div 
          v-for="slot in nearestSlots" 
          :key="slot.id"
          class="slot-item"
        >
          <span class="slot-date">{{ slot.date }}</span>
          <span class="slot-time">{{ slot.time }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { 
  PhoneIcon, 
  CalendarIcon 
} from '@heroicons/vue/24/outline'
import WhatsAppIcon from '@/Components/Icons/WhatsAppIcon.vue'
import TelegramIcon from '@/Components/Icons/TelegramIcon.vue'

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})

const hasMessengers = computed(() => 
  props.master.whatsapp || props.master.telegram
)

const nearestSlots = computed(() => [
  { id: 1, date: 'Сегодня', time: '13:00' },
  { id: 2, date: 'Завтра', time: '12:00' },
  { id: 3, date: '17.07', time: '15:00' }
])

const formatPrice = (price) => {
  return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
}

const getDayName = (dayOfWeek) => {
  const days = [
    'Воскресенье', 'Понедельник', 'Вторник', 'Среда', 
    'Четверг', 'Пятница', 'Суббота'
  ]
  return days[dayOfWeek]
}

const showPhone = () => {
  // Показываем телефон
  alert(`Телефон: ${props.master.phone}`)
}

const openBooking = () => {
  // Открываем бронирование
}

const openWhatsApp = () => {
  window.open(`https://wa.me/${props.master.whatsapp}`, '_blank')
}

const openTelegram = () => {
  window.open(`https://t.me/${props.master.telegram}`, '_blank')
}
</script>

<style scoped>
.contact-card {
  @apply bg-white rounded-lg p-6 shadow-sm;
}

.price-section {
  @apply text-center mb-6;
}

.price {
  @apply text-3xl font-bold text-gray-900;
}

.price-note {
  @apply text-gray-500 text-sm;
}

.action-buttons {
  @apply space-y-3 mb-6;
}

.btn {
  @apply w-full flex items-center justify-center gap-2 py-3 px-4 rounded-lg font-medium transition-colors;
}

.btn-primary {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.btn-secondary {
  @apply bg-green-600 text-white hover:bg-green-700;
}

.messengers {
  @apply flex gap-2 mb-6;
}

.messenger-btn {
  @apply flex-1 flex items-center justify-center gap-2 py-2 px-3 rounded-lg font-medium transition-colors;
}

.messenger-btn.whatsapp {
  @apply bg-green-500 text-white hover:bg-green-600;
}

.messenger-btn.telegram {
  @apply bg-blue-500 text-white hover:bg-blue-600;
}

.section-title {
  @apply text-lg font-semibold text-gray-900 mb-3;
}

.schedule-list {
  @apply space-y-2 mb-6;
}

.schedule-item {
  @apply flex justify-between text-sm;
}

.day {
  @apply text-gray-600;
}

.time {
  @apply font-medium;
}

.slots-list {
  @apply space-y-2;
}

.slot-item {
  @apply flex justify-between text-sm p-2 bg-gray-50 rounded-lg;
}

.slot-date {
  @apply text-gray-600;
}

.slot-time {
  @apply font-medium;
}
</style> 
