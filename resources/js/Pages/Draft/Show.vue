<template>
  <Head :title="`${ad.title || 'Черновик'} - SPA Platform`" />
  
  <div class="master-page">
    <!-- Контент с максимальной шириной как на главной -->
    <div class="py-6 lg:py-8">
      <!-- Основной контент -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Левая колонка: Фото и основная информация -->
        <div class="lg:col-span-2">
          <!-- Кнопки управления черновиком НАД фото -->
          <div class="mb-4 flex justify-start gap-3">
            <Link 
              :href="route('ads.edit', ad.id)"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2 text-sm font-medium shadow-lg"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Редактировать
            </Link>
            <button 
              @click.stop.prevent="() => { console.log('Delete button clicked'); showDeleteModal = true }"
              class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center gap-2 text-sm font-medium shadow-lg"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
              </svg>
              Удалить
            </button>
          </div>

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
                    :alt="currentPhoto.alt || 'Фото объявления'"
                    class="w-full h-full object-cover cursor-pointer"
                    @click="openLightbox"
                  >
                  

                  
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
                  :alt="currentPhoto.alt || 'Фото объявления'"
                  class="w-full h-full object-cover cursor-pointer"
                  @click="openLightbox"
                >
                

                
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
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ ad.title || 'Без названия' }}</h1>
            
            <div class="flex items-center gap-4 mb-4">
              <div class="flex items-center gap-1">
                <MapPinIcon class="w-5 h-5 text-gray-400" />
                <span>{{ ad.city }}{{ ad.district ? ', ' + ad.district : '' }}</span>
              </div>
            </div>
            
            <div v-if="ad.description" class="text-gray-700 mb-4">
              {{ ad.description }}
            </div>
          </div>
          
          <!-- Услуги -->
          <div v-if="ad.services && ad.services.length" class="bg-white rounded-lg p-6 shadow-sm mt-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">Услуги и цены</h2>
            
            <div class="space-y-4">
              <div 
                v-for="service in ad.services" 
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
            
            <div class="text-center text-gray-500 py-8">
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
                {{ ad.price ? formatPrice(ad.price) + ' ₽' : 'Цена не указана' }}
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

            <!-- График работы / Информация -->
            <div>
              <h3 class="font-semibold text-gray-900 mb-3">Контактная информация</h3>
              <div class="space-y-2">
                <div v-if="ad.phone" class="flex justify-between text-sm">
                  <span class="text-gray-600">Телефон</span>
                  <span class="font-medium">{{ ad.phone }}</span>
                </div>
                <div v-if="ad.address" class="flex justify-between text-sm">
                  <span class="text-gray-600">Адрес</span>
                  <span class="font-medium">{{ ad.address }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Создано</span>
                  <span class="font-medium">{{ formatDate(ad.created_at) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">ID</span>
                  <span class="font-medium">{{ ad.id }}</span>
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

    <!-- Модальное окно подтверждения удаления -->
    <ConfirmModal
      :show="showDeleteModal"
      @cancel="() => { console.log('Modal canceled'); showDeleteModal = false }"
      @confirm="() => { console.log('Modal confirmed'); deleteDraft() }"
      title="Удалить черновик?"
      message="Это действие нельзя отменить. Черновик будет удален навсегда."
      confirm-text="Удалить"
      cancel-text="Отмена"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { PhoneIcon, CalendarIcon, MapPinIcon } from '@heroicons/vue/24/outline'
import ConfirmModal from '@/Components/UI/ConfirmModal.vue'

// Импортируем route из window.route (Ziggy)
const route = window.route || ((name, params) => {
  console.warn('Route helper not found, using fallback')
  // Fallback для роутов
  if (name === 'my-ads.destroy' && params) {
    return `/my-ads/${params}`
  }
  if (name === 'ads.edit' && params) {
    return `/ads/${params}/edit`
  }
  if (name === 'my-ads.index') {
    return '/my-ads'
  }
  return '/'
})

const props = defineProps({
  ad: Object
})

// Состояние галереи (точно как у мастера)
const currentPhotoIndex = ref(0)
const showLightbox = ref(false)
const showDeleteModal = ref(false)

// Генерируем фотографии для галереи (адаптированная версия)
const galleryPhotos = computed(() => {
  const photos = []
  
  // Добавляем фотографии из объявления
  if (props.ad.photos && props.ad.photos.length) {
    props.ad.photos.forEach((photo, index) => {
      photos.push({
        url: photo.url || `/storage/${photo.path}`,
        thumb: photo.thumb || photo.url || `/storage/${photo.path}`,
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

// Текущее фото (точно как у мастера)
const currentPhoto = computed(() => 
  galleryPhotos.value[currentPhotoIndex.value] || galleryPhotos.value[0]
)

// Методы навигации (точно как у мастера)
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

// Обработка клавиатуры (точно как у мастера)
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

// Подключение обработчиков клавиатуры (точно как у мастера)
onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})

// Остальные методы (адаптированные под черновик)
const formatPrice = (price) => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const formatDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const showPhone = () => {
  if (props.ad.phone) {
    window.location.href = `tel:${props.ad.phone.replace(/\D/g, '')}`
  } else {
    alert('Телефон будет доступен после публикации')
  }
}

const openBooking = () => {
  alert('Форма бронирования появится после публикации')
}

// Удаление черновика
const deleteDraft = () => {
  console.log('Deleting draft with ID:', props.ad.id)
  
  // Используем общий роут для удаления
  console.log('Deleting with my-ads route')
  
  // Используем общий роут удаления my-ads.destroy
      router.delete(`/my-ads/${props.ad.id}`, {
      preserveScroll: false,
      preserveState: false,
      onBefore: () => {
        console.log('Before delete request')
        showDeleteModal.value = false // Закрываем модалку сразу
        return true
      },
      onSuccess: (page) => {
        console.log('Delete successful, redirecting...')
        // Контроллер сам перенаправит куда нужно
      },
      onError: (errors) => {
        console.error('Delete failed:', errors)
        alert('Ошибка удаления: ' + JSON.stringify(errors))
      }
    })
}
</script>

<style scoped>
/* Полная ширина страницы как у мастера */
.full-width-page {
  margin-left: calc(-50vw + 50%);
  margin-right: calc(-50vw + 50%);
  width: 100vw;
  padding-left: 2rem;
  padding-right: 2rem;
  box-sizing: border-box;
}

@media (max-width: 768px) {
  .full-width-page {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}

/* Плавная анимация для галереи */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 300ms;
}
</style> 