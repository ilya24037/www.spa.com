<template>
  <div class="master-profile-detailed">
    <!-- Loading с детальным skeleton -->
    <MasterProfileSkeleton v-if="loading" />
    
    <!-- Profile -->
    <div v-else-if="master" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Main layout inspired by Ozon product page -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        
        <!-- Left Column - Gallery (spans 7 columns) -->
        <div class="lg:col-span-7">
          <!-- Photo Gallery -->
          <div class="bg-white rounded-lg overflow-hidden shadow-sm mb-6">
            <div v-if="galleryImages.length" class="relative">
              <PhotoGallery 
                :images="galleryImages"
                :show-thumbnails="true"
                :auto-play="false"
                class="aspect-w-4 aspect-h-3"
              />
            </div>
            <div v-else class="aspect-w-4 aspect-h-3 bg-gray-100 flex items-center justify-center">
              <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="mt-2 text-sm text-gray-500">Нет фотографий</p>
              </div>
            </div>
          </div>

          <!-- Services Section -->
          <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <MasterServices :master="master" />
          </div>

          <!-- Reviews Section -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <MasterReviews :master="master" :initial-reviews="master.reviews || []" />
          </div>
        </div>

        <!-- Right Column - Info & Booking (spans 5 columns) -->
        <div class="lg:col-span-5 space-y-6">
          
          <!-- Master Info Card -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <!-- Header with avatar and basic info -->
            <div class="flex items-start gap-4 mb-6">
              <div class="flex-shrink-0">
                <img
                  :src="master.avatar || '/placeholder-avatar.jpg'"
                  :alt="master.name"
                  class="w-20 h-20 rounded-full object-cover border-4 border-gray-100"
                >
              </div>
              
              <div class="flex-1 min-w-0">
                <h1 class="text-xl font-bold text-gray-900 mb-1">
                  {{ master.name || 'Мастер' }}
                </h1>
                
                <div v-if="master.specialty" class="text-sm text-gray-600 mb-2">
                  {{ master.specialty }}
                </div>
                
                <div class="flex items-center gap-2">
                  <StarRating :rating="master.rating || 0" :show-text="false" size="sm" />
                  <span class="text-sm font-medium text-gray-900">
                    {{ (master.rating || 0).toFixed(1) }}
                  </span>
                  <span class="text-sm text-gray-500">
                    ({{ master.reviews_count || 0 }} отзывов)
                  </span>
                </div>
              </div>
            </div>

            <!-- Quick stats inspired by Ozon -->
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div class="text-center p-3 bg-green-50 rounded-lg">
                <div class="text-lg font-bold text-green-600">
                  {{ master.completion_rate || '98%' }}
                </div>
                <div class="text-xs text-gray-500">Выполнение заказов</div>
              </div>
              <div class="text-center p-3 bg-blue-50 rounded-lg">
                <div class="text-lg font-bold text-blue-600">
                  {{ master.experience || '5+ лет' }}
                </div>
                <div class="text-xs text-gray-500">Опыт работы</div>
              </div>
            </div>

            <!-- Price range -->
            <div v-if="priceRange" class="mb-6 p-4 bg-gray-50 rounded-lg">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Цены от</span>
                <span class="text-xl font-bold text-gray-900">
                  {{ priceRange.min }} ₽
                </span>
              </div>
              <div v-if="priceRange.max !== priceRange.min" class="text-xs text-gray-500 mt-1">
                до {{ priceRange.max }} ₽
              </div>
            </div>

            <!-- Contact buttons -->
            <div class="space-y-3 mb-6">
              <button 
                @click="openBookingModal"
                class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center justify-center gap-2"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4h8m-8 0V9a2 2 0 012-2h4a2 2 0 012 2v2m-8 0h8m-8 4v2a2 2 0 002 2h4a2 2 0 002 2v-2m0-4h.01" />
                </svg>
                Записаться
              </button>
              
              <MasterContact :master="master" />
            </div>

            <!-- Additional info -->
            <MasterInfo :master="master" />
          </div>

          <!-- Master Parameters -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <MasterParameters :master="master" />
          </div>

          <!-- Safety & Delivery Info (like Ozon) -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Информация</h3>
            
            <div class="space-y-3 text-sm">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="text-gray-700">Проверенный мастер</span>
              </div>
              
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="text-gray-700">Безопасная оплата</span>
              </div>
              
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                <span class="text-gray-700">Гарантия качества</span>
              </div>

              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <div>
                  <div class="text-gray-700">Выезд на дом</div>
                  <div class="text-xs text-gray-500">{{ master.location || 'Московская область' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Empty state -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500">
        Информация о мастере недоступна
      </p>
    </div>

    <!-- Booking Modal -->
    <BookingModal 
      v-if="showBookingModal"
      :master="master"
      :is-open="showBookingModal"
      @close="closeBookingModal"
      @booking-created="handleBookingCreated"
    />
  </div>
  
  <!-- PhotoViewer - глобальный компонент -->
  <PhotoViewer />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

// Импорты компонентов
import StarRating from '@/src/shared/ui/organisms/StarRating/StarRating.vue'
import MasterProfileSkeleton from './MasterProfileSkeleton.vue'

// Gallery imports
import { PhotoGallery, PhotoViewer } from '@/src/features/gallery'

// Master components
import MasterServices from '@/src/entities/master/ui/MasterServices/MasterServices.vue'
import MasterReviews from '@/src/entities/master/ui/MasterReviews/MasterReviews.vue'
import MasterContact from '@/src/entities/master/ui/MasterContact/MasterContact.vue'
import MasterInfo from '@/src/entities/master/ui/MasterInfo/MasterInfo.vue'
import MasterParameters from '@/src/entities/master/ui/MasterInfo/MasterParameters.vue'
import BookingModal from '@/src/entities/booking/ui/BookingModal/BookingModal.vue'

interface Master {
  id?: string | number
  name?: string
  avatar?: string
  description?: string
  rating?: number
  reviews_count?: number
  specialty?: string
  completion_rate?: string
  experience?: string
  location?: string
  services?: Array<{
    id: string | number
    name: string
    price: number
    duration?: number
    description?: string
  }>
  photos?: Array<{
    id: string | number
    url: string
    thumbnail_url?: string
    alt?: string
    caption?: string
  }>
  reviews?: Array<any>
}

interface Props {
  master?: Master | null
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  master: null,
  loading: false
})

// Состояние компонента
const showBookingModal = ref(false)

// Gallery images computed
const galleryImages = computed(() => {
  if (!props.master?.photos?.length) return []
  
  return props.master.photos.map((photo, index) => ({
    id: String(photo.id),
    url: photo.url,
    thumbnail: photo.thumbnail_url || photo.url,
    alt: photo.alt || `Фото мастера ${props.master?.name} ${index + 1}`,
    caption: photo.caption,
    type: 'photo' as const
  }))
})

// Price range computed
const priceRange = computed(() => {
  if (!props.master?.services?.length) return null
  
  const prices = props.master.services
    .map(service => service.price)
    .filter(price => price > 0)
    .sort((a, b) => a - b)
  
  if (prices.length === 0) return null
  
  return {
    min: prices[0],
    max: prices[prices.length - 1]
  }
})

// Methods
const openBookingModal = () => {
  showBookingModal.value = true
}

const closeBookingModal = () => {
  showBookingModal.value = false
}

const handleBookingCreated = (booking: any) => {
  console.log('Booking created:', booking)
  closeBookingModal()
  // Здесь можно добавить уведомление об успешном создании записи
}
</script>

<style scoped>
/* Aspect ratio utilities */
.aspect-w-4 {
  position: relative;
  padding-bottom: calc(3 / 4 * 100%);
}

.aspect-h-3 {
  position: absolute;
  height: 100%;
  width: 100%;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

/* Smooth transitions */
.master-profile-detailed {
  @apply transition-all duration-300 ease-in-out;
}

/* Cards hover effects */
.master-profile-detailed .bg-white {
  @apply transition-shadow duration-200;
}

.master-profile-detailed .bg-white:hover {
  @apply shadow-md;
}

/* Button animations */
button {
  @apply transition-all duration-200;
}

button:hover:not(:disabled) {
  @apply transform -translate-y-0.5;
}

button:active:not(:disabled) {
  @apply transform translate-y-0;
}
</style>