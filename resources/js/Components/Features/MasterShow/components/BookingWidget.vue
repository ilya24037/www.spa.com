<template>
  <div class="bg-white rounded-lg p-6 shadow-sm sticky top-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4">Записаться</h2>
    
    <!-- Цена -->
    <div class="mb-6">
      <div class="text-3xl font-bold text-gray-900 mb-2">
        от {{ formatPrice(master.price_from) }} ₽
      </div>
      <p class="text-gray-600">за сеанс</p>
    </div>
    
    <!-- Физические параметры -->
    <div v-if="hasParameters" class="mb-6 p-4 bg-gray-50 rounded-lg">
      <h3 class="font-semibold text-gray-900 mb-3">Параметры</h3>
      <div class="space-y-2">
        <div v-if="master.age" class="flex justify-between text-sm">
          <span class="text-gray-600">Возраст:</span>
          <span class="font-medium">{{ master.age }} лет</span>
        </div>
        <div v-if="master.height" class="flex justify-between text-sm">
          <span class="text-gray-600">Рост:</span>
          <span class="font-medium">{{ master.height }} см</span>
        </div>
        <div v-if="master.weight" class="flex justify-between text-sm">
          <span class="text-gray-600">Вес:</span>
          <span class="font-medium">{{ master.weight }} кг</span>
        </div>
        <div v-if="master.breast_size" class="flex justify-between text-sm">
          <span class="text-gray-600">Размер груди:</span>
          <span class="font-medium">{{ master.breast_size }}</span>
        </div>
      </div>
    </div>
    
    <!-- Кнопки -->
    <div class="space-y-3">
      <button 
        @click="openBooking"
        class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition font-medium"
      >
        Записаться онлайн
      </button>
      
      <button 
        @click="showPhone"
        class="w-full border border-gray-300 py-3 px-4 rounded-lg hover:bg-gray-50 transition font-medium flex items-center justify-center gap-2"
      >
        <PhoneIcon class="w-5 h-5" />
        Показать телефон
      </button>
    </div>
    
    <!-- График работы -->
    <div class="mt-6 pt-6 border-t border-gray-200">
      <h3 class="font-semibold text-gray-900 mb-3">Время работы</h3>
      <div class="space-y-2">
        <div v-if="master.schedule" class="space-y-1">
          <div 
            v-for="(hours, day) in master.schedule" 
            :key="day"
            class="flex justify-between text-sm"
          >
            <span class="text-gray-600">{{ getDayName(day) }}</span>
            <span class="font-medium">{{ hours || 'Выходной' }}</span>
          </div>
        </div>
        <div v-else class="text-sm text-gray-500">
          По договоренности
        </div>
      </div>
    </div>
    
    <!-- Дополнительная информация -->
    <div class="mt-6 pt-6 border-t border-gray-200">
      <h3 class="font-semibold text-gray-900 mb-3">Информация</h3>
      <div class="space-y-2">
        <div v-if="master.experience" class="flex justify-between text-sm">
          <span class="text-gray-600">Опыт</span>
          <span class="font-medium">{{ master.experience }}</span>
        </div>
        <div v-if="master.education" class="flex justify-between text-sm">
          <span class="text-gray-600">Образование</span>
          <span class="font-medium">{{ master.education }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-gray-600">На сайте</span>
          <span class="font-medium">{{ master.created_at ? new Date(master.created_at).getFullYear() : 2024 }}</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Модальное окно бронирования -->
  <BookingModal
    :show="showBookingModal"
    :master="master"
    @close="showBookingModal = false"
    @success="handleBookingSuccess"
  />
</template>

<script setup>
import { computed, ref } from 'vue'
import { PhoneIcon } from '@heroicons/vue/24/outline'
import { BookingModal } from '@/src/entities/booking'

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})

// Состояние для модального окна
const showBookingModal = ref(false)

// Computed для проверки наличия параметров
const hasParameters = computed(() => {
  return props.master.age || props.master.height || props.master.weight || props.master.breast_size
})

// Методы
const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const getDayName = (dayOfWeek) => {
  const days = [
    'Воскресенье', 'Понедельник', 'Вторник', 'Среда', 
    'Четверг', 'Пятница', 'Суббота'
  ]
  return days[dayOfWeek]
}

const showPhone = () => {
  if (props.master.phone) {
    window.location.href = `tel:${props.master.phone.replace(/\D/g, '')}`
  } else {
    alert('Телефон будет доступен после бронирования')
  }
}

const openBooking = () => {
  showBookingModal.value = true
}

const handleBookingSuccess = (booking) => {
  showBookingModal.value = false
  // Перенаправляем на страницу успешного бронирования
  window.location.href = `/bookings/${booking.id}`
}
</script>

<style scoped>
/* Стили для виджета, если нужны */
</style> 