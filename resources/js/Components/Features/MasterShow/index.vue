<template>
  <div>
    <!-- Контейнер как на главной -->
    <div class="py-6 lg:py-8">
      <!-- Основной контент с правильными отступами -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Левая колонка: Фото и основная информация -->
        <div class="lg:col-span-2">
          <!-- Универсальная галерея фото -->
          <PhotoGallery 
            :photos="master.photos || []"
            mode="full"
            :show-badges="true"
            :show-thumbnails="true"
            :show-counter="true"
            :enable-lightbox="true"
          >
            <template #badges>
              <span v-if="master.is_verified" class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                ✓ Проверен
              </span>
              <span v-if="master.is_premium" class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm font-medium">
                ПРЕМИУМ
              </span>
            </template>
          </PhotoGallery>
          
          <MasterInfo :master="master" />
          
          <ServicesList :services="master.services || []" />
          
          <ReviewsList :reviews="master.reviews || []" />
        </div>

        <!-- Правая колонка: Бронирование и контакты -->
        <BookingWidget :master="master" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { PhoneIcon, CalendarIcon, StarIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import { PhotoGallery } from '@/src/features/gallery'
import MasterInfo from './components/MasterInfo.vue'
import ServicesList from './components/ServicesList.vue'
import ReviewsList from './components/ReviewsList.vue'
import BookingWidget from './components/BookingWidget.vue'
import { useToast } from '@/src/shared/composables/useToast'

// Toast для замены alert()
const toast = useToast()

const props = defineProps({
  master: Object
})

// Остальные методы
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
    toast.info('Телефон будет доступен после бронирования')
  }
}

const openBooking = () => {
  toast.info('Форма бронирования появится здесь')
}
</script>

<style scoped>
/* Убираем стили полной ширины - используем стандартную структуру как на главной */
</style>