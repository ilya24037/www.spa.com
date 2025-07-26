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
          
          <!-- Основная информация -->
          <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ master.name }}</h1>
            
            <div class="flex items-center gap-4 mb-4">
              <div class="flex items-center gap-1">
                <StarIcon class="w-5 h-5 text-yellow-400" />
                <span class="font-medium">{{ master.rating || 4.5 }}</span>
                <span class="text-gray-500">({{ master.reviews_count || 12 }} отзывов)</span>
              </div>
              
              <div class="flex items-center gap-1">
                <MapPinIcon class="w-5 h-5 text-gray-400" />
                <span>{{ master.city }}{{ master.district ? ', ' + master.district : '' }}</span>
              </div>
            </div>
            
            <div v-if="master.description" class="text-gray-700 mb-4">
              {{ master.description }}
            </div>
          </div>
          
          <!-- Услуги -->
          <div v-if="master.services && master.services.length" class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Услуги и цены</h2>
            
            <div class="space-y-4">
              <div 
                v-for="service in master.services" 
                :key="service.id"
                class="flex justify-between items-center p-4 border border-gray-200 rounded-lg"
              >
                <div>
                  <h3 class="font-medium">{{ service.name }}</h3>
                  <p class="text-sm text-gray-500">{{ service.duration }} мин</p>
                </div>
                <div class="text-right">
                  <div class="font-bold text-lg">{{ formatPrice(service.price) }} ₽</div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Отзывы -->
          <div v-if="master.reviews && master.reviews.length" class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Отзывы</h2>
            
            <div class="space-y-4">
              <div 
                v-for="review in master.reviews" 
                :key="review.id"
                class="border-b border-gray-200 pb-4 last:border-b-0"
              >
                <div class="flex items-center gap-3 mb-2">
                  <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                    {{ review.client_name?.charAt(0) || 'А' }}
                  </div>
                  <div>
                    <div class="font-medium">{{ review.client_name || 'Анонимный клиент' }}</div>
                    <div class="flex items-center gap-1">
                      <StarIcon v-for="n in 5" :key="n" 
                        :class="n <= review.rating ? 'text-yellow-400' : 'text-gray-300'"
                        class="w-4 h-4" 
                      />
                    </div>
                  </div>
                </div>
                <p class="text-gray-700">{{ review.comment }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Правая колонка: Бронирование и контакты -->
        <div class="space-y-6">
          <!-- Бронирование -->
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
            <div v-if="master.age || master.height || master.weight || master.breast_size" class="mb-6 p-4 bg-gray-50 rounded-lg">
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
            
            <!-- График работы / Информация -->
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
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { PhoneIcon, CalendarIcon, StarIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import PhotoGallery from '@/Components/Gallery/PhotoGallery.vue'

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
    alert('Телефон будет доступен после бронирования')
  }
}

const openBooking = () => {
  alert('Форма бронирования появится здесь')
}
</script>

<style scoped>
/* Убираем стили полной ширины - используем стандартную структуру как на главной */
</style>