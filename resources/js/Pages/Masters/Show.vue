<template>
  <div class="master-page">
    <!-- Контент с максимальной шириной как на главной -->
    <div class="py-6 lg:py-8">
      <!-- Основной контент -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Левая колонка: Фото и основная информация -->
        <div class="lg:col-span-2">
          <!-- Галерея фото в стиле Ozon -->
          <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <!-- Десктопная версия -->
            <div class="hidden md:flex gap-4 p-4">
              <!-- Миниатюры слева -->
              <div class="flex flex-col gap-2 w-20">
                <div 
                  v-for="(photo, index) in galleryPhotos" 
                  :key="index"
                  @click="selectPhoto(index)"
                  :class="[
                    'relative w-16 h-16 rounded-lg overflow-hidden cursor-pointer border-2 transition-all',
                    currentPhotoIndex === index ? 'border-blue-500' : 'border-gray-200 hover:border-gray-300'
                  ]"
                >
                  <img 
                    :src="photo.thumb || photo.url"
                    :alt="`Фото ${index + 1}`"
                    class="w-full h-full object-cover"
                  >
                  <!-- Индикатор активного фото -->
                  <div 
                    v-if="currentPhotoIndex === index"
                    class="absolute inset-0 bg-blue-500 bg-opacity-10"
                  ></div>
                </div>
              </div>
              
              <!-- Основное изображение -->
              <div class="flex-1">
                <div class="relative aspect-[4/3] bg-gray-100 rounded-lg overflow-hidden">
                  <img 
                    :src="currentPhoto.url"
                    :alt="currentPhoto.alt || 'Фото мастера'"
                    class="w-full h-full object-cover cursor-pointer"
                    @click="openLightbox"
                  >
                  
                  <!-- Бейджи -->
                  <div class="absolute top-4 left-4 flex flex-col gap-2">
                    <span v-if="master.is_verified" class="bg-green-500 text-white px-3 py-1 rounded-full text-sm font-medium">
                      ✓ Проверен
                    </span>
                    <span v-if="master.is_premium" class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm font-medium">
                      ПРЕМИУМ
                    </span>
                  </div>
                  
                  <!-- Кнопка увеличения -->
                  <button 
                    @click="openLightbox"
                    class="absolute bottom-4 right-4 bg-black bg-opacity-50 text-white p-2 rounded-full hover:bg-opacity-70 transition-all"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                    </svg>
                  </button>
                  
                  <!-- Счетчик фото -->
                  <div class="absolute bottom-4 left-4 bg-black bg-opacity-50 text-white px-3 py-1 rounded-full text-sm">
                    {{ currentPhotoIndex + 1 }} / {{ galleryPhotos.length }}
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Мобильная версия -->
            <div class="md:hidden">
              <!-- Основное изображение -->
              <div class="relative aspect-[4/3] bg-gray-100">
                <img 
                  :src="currentPhoto.url"
                  :alt="currentPhoto.alt || 'Фото мастера'"
                  class="w-full h-full object-cover cursor-pointer"
                  @click="openLightbox"
                >
                
                <!-- Бейджи -->
                <div class="absolute top-4 left-4 flex flex-col gap-2">
                  <span v-if="master.is_verified" class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                    ✓ Проверен
                  </span>
                  <span v-if="master.is_premium" class="bg-purple-600 text-white px-2 py-1 rounded-full text-xs font-medium">
                    ПРЕМИУМ
                  </span>
                </div>
                
                <!-- Навигация стрелками -->
                <button 
                  v-if="currentPhotoIndex > 0"
                  @click="previousPhoto"
                  class="absolute left-2 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                  </svg>
                </button>
                
                <button 
                  v-if="currentPhotoIndex < galleryPhotos.length - 1"
                  @click="nextPhoto"
                  class="absolute right-2 top-1/2 -translate-y-1/2 bg-black bg-opacity-50 text-white p-2 rounded-full"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                  </svg>
                </button>
                
                <!-- Счетчик фото -->
                <div class="absolute bottom-4 right-4 bg-black bg-opacity-50 text-white px-2 py-1 rounded-full text-sm">
                  {{ currentPhotoIndex + 1 }} / {{ galleryPhotos.length }}
                </div>
              </div>
              
              <!-- Миниатюры горизонтально -->
              <div class="flex gap-2 p-4 overflow-x-auto">
                <div 
                  v-for="(photo, index) in galleryPhotos" 
                  :key="index"
                  @click="selectPhoto(index)"
                  :class="[
                    'relative w-16 h-16 flex-shrink-0 rounded-lg overflow-hidden cursor-pointer border-2 transition-all',
                    currentPhotoIndex === index ? 'border-blue-500' : 'border-gray-200'
                  ]"
                >
                  <img 
                    :src="photo.thumb || photo.url"
                    :alt="`Фото ${index + 1}`"
                    class="w-full h-full object-cover"
                  >
                  <!-- Индикатор активного фото -->
                  <div 
                    v-if="currentPhotoIndex === index"
                    class="absolute inset-0 bg-blue-500 bg-opacity-10"
                  ></div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Основная информация -->
          <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ master.name }}</h1>
            
            <div class="flex items-center gap-4 mb-4">
              <div class="flex items-center gap-1">
                <StarIcon class="w-5 h-5 text-yellow-400" />
                <span class="font-medium">{{ master.rating }}</span>
                <span class="text-gray-500">({{ master.reviews_count }} отзывов)</span>
              </div>
              
              <div class="flex items-center gap-1">
                <MapPinIcon class="w-5 h-5 text-gray-400" />
                <span>{{ master.city }}{{ master.district ? ', ' + master.district : '' }}</span>
              </div>
            </div>
            
            <div v-if="master.bio" class="text-gray-700 mb-4">
              {{ master.bio }}
            </div>
          </div>
          
          <!-- Услуги -->
          <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
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
          <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Отзывы</h2>
            
            <div v-if="master.reviews && master.reviews.length" class="space-y-4">
              <div 
                v-for="review in master.reviews.slice(0, 3)" 
                :key="review.id"
                class="border-b border-gray-200 pb-4"
              >
                <div class="flex items-center gap-2 mb-2">
                  <div class="flex">
                    <StarIcon 
                      v-for="i in 5" 
                      :key="i"
                      :class="[
                        'w-4 h-4',
                        i <= review.rating ? 'text-yellow-400' : 'text-gray-300'
                      ]"
                    />
                  </div>
                  <span class="text-sm text-gray-500">{{ review.client_name }}</span>
                </div>
                <p class="text-gray-700">{{ review.comment }}</p>
              </div>
            </div>
            
            <div v-else class="text-center text-gray-500 py-8">
              Пока нет отзывов
            </div>
          </div>
        </div>
        
        <!-- Правая колонка: Контакты -->
        <div class="lg:col-span-1">
          <div class="bg-white rounded-lg p-6 shadow-sm sticky top-4">
            <!-- Цена -->
            <div class="text-center mb-6">
              <div class="text-3xl font-bold text-gray-900">
                от {{ formatPrice(master.price_from) }} ₽
              </div>
              <div class="text-gray-500">за сеанс</div>
            </div>

            <!-- Кнопки -->
            <div class="space-y-3 mb-6">
              <button 
                @click="showPhone"
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
              >
                <PhoneIcon class="w-5 h-5" />
                Показать телефон
              </button>
              
              <button 
                @click="openBooking"
                class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center justify-center gap-2"
              >
                <CalendarIcon class="w-5 h-5" />
                Записаться
              </button>
            </div>

            <!-- График работы -->
            <div v-if="master.schedules && master.schedules.length">
              <h3 class="font-semibold text-gray-900 mb-3">График работы</h3>
              <div class="space-y-2">
                <div 
                  v-for="schedule in master.schedules" 
                  :key="schedule.id"
                  class="flex justify-between text-sm"
                >
                  <span class="text-gray-600">{{ getDayName(schedule.day_of_week) }}</span>
                  <span class="font-medium">{{ schedule.start_time }} - {{ schedule.end_time }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
  
      </div>
    </div>
    
    <!-- Lightbox для полноэкранного просмотра -->
    <div 
      v-if="showLightbox" 
      class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50"
      @click="closeLightbox"
    >
      <div class="relative max-w-4xl max-h-full p-4">
        <img 
          :src="currentPhoto.url"
          :alt="currentPhoto.alt"
          class="max-w-full max-h-full object-contain"
          @click.stop
        >
        
        <!-- Кнопка закрытия -->
        <button 
          @click="closeLightbox"
          class="absolute top-4 right-4 text-white bg-black bg-opacity-50 p-2 rounded-full hover:bg-opacity-70"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
        
        <!-- Навигация -->
        <button 
          v-if="currentPhotoIndex > 0"
          @click.stop="previousPhoto"
          class="absolute left-4 top-1/2 -translate-y-1/2 text-white bg-black bg-opacity-50 p-2 rounded-full hover:bg-opacity-70"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        
        <button 
          v-if="currentPhotoIndex < galleryPhotos.length - 1"
          @click.stop="nextPhoto"
          class="absolute right-4 top-1/2 -translate-y-1/2 text-white bg-black bg-opacity-50 p-2 rounded-full hover:bg-opacity-70"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
        
        <!-- Счетчик -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white bg-black bg-opacity-50 px-3 py-1 rounded-full">
          {{ currentPhotoIndex + 1 }} / {{ galleryPhotos.length }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { PhoneIcon, CalendarIcon, StarIcon, MapPinIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  master: Object
})

// Состояние галереи
const currentPhotoIndex = ref(0)
const showLightbox = ref(false)

// Генерируем фотографии для галереи
const galleryPhotos = computed(() => {
  const photos = []
  
  // Добавляем аватар как первое фото
  if (props.master.avatar) {
    photos.push({
      url: props.master.avatar,
      thumb: props.master.avatar,
      alt: `Фото ${props.master.name}`
    })
  }
  
  // Добавляем фотографии из галереи
  if (props.master.gallery && props.master.gallery.length) {
    props.master.gallery.forEach((photo, index) => {
      photos.push({
        url: photo.url,
        thumb: photo.thumb || photo.url,
        alt: photo.alt || `Фото ${index + 1}`
      })
    })
  }
  
  // Если нет фотографий, добавляем placeholder
  if (photos.length === 0) {
    photos.push({
      url: '/images/no-photo.jpg',
      thumb: '/images/no-photo.jpg',
      alt: 'Фото недоступно'
    })
  }
  
  return photos
})

// Текущее фото
const currentPhoto = computed(() => 
  galleryPhotos.value[currentPhotoIndex.value] || galleryPhotos.value[0]
)

// Методы навигации
const selectPhoto = (index) => {
  currentPhotoIndex.value = index
}

const nextPhoto = () => {
  if (currentPhotoIndex.value < galleryPhotos.value.length - 1) {
    currentPhotoIndex.value++
  }
}

const previousPhoto = () => {
  if (currentPhotoIndex.value > 0) {
    currentPhotoIndex.value--
  }
}

const openLightbox = () => {
  showLightbox.value = true
}

const closeLightbox = () => {
  showLightbox.value = false
}

// Обработка клавиатуры
const handleKeydown = (event) => {
  if (!showLightbox.value) return
  
  switch (event.key) {
    case 'Escape':
      closeLightbox()
      break
    case 'ArrowLeft':
      previousPhoto()
      break
    case 'ArrowRight':
      nextPhoto()
      break
  }
}

// Подключение обработчиков клавиатуры
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
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
