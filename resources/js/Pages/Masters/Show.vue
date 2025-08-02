<template>
  <!-- Обертка с правильными отступами как на главной -->
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
                  <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                  <span class="font-medium">{{ master.rating || 'Нет оценок' }}</span>
                  <span class="text-gray-500">({{ master.reviews_count || 0 }} отзывов)</span>
                </div>
                
                <div class="flex items-center gap-1">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                  </svg>
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
              
              <div v-if="master.services && master.services.length" class="space-y-4">
                <div 
                  v-for="service in master.services" 
                  :key="service.id"
                  class="flex justify-between items-center p-4 border border-gray-200 rounded-lg"
                >
                  <div>
                    <h3 class="font-medium">{{ service.name }}</h3>
                    <p v-if="service.duration" class="text-sm text-gray-500">{{ service.duration }} мин</p>
                    <p v-if="service.description" class="text-sm text-gray-600 mt-1">{{ service.description }}</p>
                  </div>
                  <div class="text-right">
                    <div v-if="service.price" class="font-bold text-lg">{{ formatPrice(service.price) }} ₽</div>
                  </div>
                </div>
              </div>
              
              <div v-else class="text-center text-gray-500 py-8">
                Информация об услугах будет добавлена
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
                      <svg 
                        v-for="i in 5" 
                        :key="i"
                        :class="[
                          'w-4 h-4',
                          i <= review.rating ? 'text-yellow-400' : 'text-gray-300'
                        ]"
                        fill="currentColor" 
                        viewBox="0 0 20 20"
                      >
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                      </svg>
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
          
          <!-- Правая колонка: Контакты и бронирование -->
          <div class="lg:col-span-1">
            <div class="bg-white rounded-lg p-6 shadow-sm sticky top-4">
              <!-- Цена -->
              <div class="text-center mb-6">
                <div v-if="master.price_from && master.price_from > 0" class="text-3xl font-bold text-gray-900">
                  от {{ formatPrice(master.price_from) }} ₽
                </div>
                <div v-else class="text-3xl font-bold text-gray-900">
                  от 2000 ₽
                </div>
                <div class="text-gray-500">за сеанс</div>
              </div>

              <!-- Статус доступности -->
              <div class="flex items-center justify-center mb-6">
                <div :class="[
                  'w-3 h-3 rounded-full mr-2',
                  master.is_available_now ? 'bg-green-500' : 'bg-gray-400'
                ]"></div>
                <span :class="[
                  'text-sm',
                  master.is_available_now ? 'text-green-600' : 'text-gray-600'
                ]">
                  {{ master.is_available_now ? 'Доступен сейчас' : 'Недоступен' }}
                </span>
              </div>

              <!-- Кнопки -->
              <div class="space-y-3 mb-6">
                <button 
                  @click="showPhone"
                  class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition-colors flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                  Показать телефон
                </button>
                
                <button 
                  @click="openBookingModal"
                  class="w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v9a1 1 0 01-1 1H4a1 1 0 01-1-1V8a1 1 0 011-1h4z"/>
                  </svg>
                  Записаться онлайн
                </button>

                <button 
                  @click="toggleFavorite"
                  class="w-full border border-gray-300 py-2 px-4 rounded-lg hover:bg-gray-50 transition-colors flex items-center justify-center gap-2"
                >
                  <svg class="w-4 h-4" :class="master.is_favorite ? 'text-red-500' : ''" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path>
                  </svg>
                  {{ master.is_favorite ? 'Удалить из избранного' : 'В избранное' }}
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

              <!-- Контакты -->
              <div v-if="master.show_contacts" class="border-t pt-4 mt-6">
                <h3 class="font-semibold text-gray-900 mb-3">Контакты</h3>
                <div class="space-y-2">
                  <div v-if="master.whatsapp" class="flex items-center">
                    <svg class="w-4 h-4 mr-2 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                    </svg>
                    <a :href="`https://wa.me/${master.whatsapp}`" class="text-green-600 hover:text-green-800">WhatsApp</a>
                  </div>
                  <div v-if="master.telegram" class="flex items-center">
                    <svg class="w-4 h-4 mr-2 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                    </svg>
                    <a :href="`https://t.me/${master.telegram}`" class="text-blue-600 hover:text-blue-800">Telegram</a>
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

      <!-- Модальное окно бронирования -->
      <div 
        v-if="showBookingModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click="showBookingModal = false"
      >
        <div 
          class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <!-- Заголовок модального окна -->
          <div class="flex items-center justify-between p-6 border-b">
            <h2 class="text-xl font-bold text-gray-900">
              Запись к мастеру {{ master.name }}
            </h2>
            <button 
              @click="showBookingModal = false"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Содержимое модального окна -->
          <div class="p-6">
            <BookingWidget 
              :master="master"
              :is-open="showBookingModal"
              @booking-created="handleBookingSuccess"
              @close="showBookingModal = false"
            />
          </div>
        </div>
      </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import { BookingWidget } from '@/src/entities/booking'

const props = defineProps({
  master: {
    type: Object,
    required: true
  },
  gallery: {
    type: Array,
    default: () => []
  },
  meta: {
    type: Object,
    default: () => ({})
  },
  similarMasters: {
    type: Array,
    default: () => []
  },
  reviews: {
    type: Array,
    default: () => []
  },
  availableSlots: {
    type: Array,
    default: () => []
  },
  canReview: {
    type: Boolean,
    default: false
  }
})

// Состояние галереи
const currentPhotoIndex = ref(0)
const showLightbox = ref(false)
const showBookingModal = ref(false)

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
  if (props.gallery && props.gallery.length) {
    props.gallery.forEach((photo, index) => {
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
      url: '/images/placeholders/master-1.jpg',
      thumb: '/images/placeholders/master-1.jpg',
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

// Модальное окно бронирования
const openBookingModal = () => {
  showBookingModal.value = true
}

const handleBookingSuccess = (bookingData) => {
  showBookingModal.value = false
  
  // Показываем уведомление об успехе
  alert(`Запись успешно создана! Номер записи: ${bookingData.bookingNumber}`)
  
  // Можно добавить toast уведомление или редирект
  console.log('Booking created:', bookingData)
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
  if (props.master.phone && props.master.show_contacts) {
    window.location.href = `tel:${props.master.phone.replace(/\D/g, '')}`
  } else {
    alert('Телефон будет доступен после бронирования')
  }
}

const toggleFavorite = () => {
  router.post('/api/favorites/toggle', {
    master_profile_id: props.master.id
  }, {
    preserveState: true,
    preserveScroll: true
  })
}
</script>

<style scoped>
/* Стили страницы мастера без фона */
</style> 