<!-- 
  Изолированный виджет MasterProfile по принципу Ozon
  
  Особенности:
  - Полная изоляция состояния
  - Собственный API слой
  - Error boundary
  - Performance мониторинг
  - Ленивая загрузка компонентов
-->
<template>
  <div :class="getWidgetClasses()">
    <!-- Loading состояние -->
    <div v-if="isLoading" class="master-profile-widget__loading">
      <div class="animate-pulse space-y-4">
        <div class="h-8 bg-gray-200 rounded w-1/2"></div>
        <div class="h-4 bg-gray-200 rounded w-3/4"></div>
        <div class="grid grid-cols-2 gap-4">
          <div class="h-24 bg-gray-200 rounded"></div>
          <div class="h-24 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Error состояние -->
    <div v-else-if="error" class="master-profile-widget__error">
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="text-red-800 text-sm mb-2">{{ error }}</div>
        <button 
          @click="retryLoad"
          class="text-xs text-red-600 hover:text-red-800 underline"
        >
          Попробовать еще раз
        </button>
      </div>
    </div>

    <!-- Основной контент -->
    <div v-else-if="masterData" class="master-profile-widget__content space-y-6">
      
      <!-- Header с основной информацией -->
      <div class="master-profile-widget__header bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-start gap-4">
          <!-- Аватар -->
          <div v-if="masterData.avatar" class="flex-shrink-0">
            <img 
              :src="masterData.avatar" 
              :alt="masterData.name"
              class="w-16 h-16 rounded-full object-cover"
            />
          </div>
          
          <!-- Основная информация -->
          <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
              {{ masterData.name }}
            </h1>
            
            <!-- Статус и рейтинг -->
            <div class="flex items-center gap-4 mb-3">
              <span 
                :class="[
                  'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                  getMasterStatus() === 'online' 
                    ? 'bg-green-100 text-green-800' 
                    : 'bg-gray-100 text-gray-600'
                ]"
              >
                {{ getMasterStatus() === 'online' ? 'Онлайн' : 'Не в сети' }}
              </span>
              
              <div v-if="masterData.rating" class="flex items-center">
                <span class="text-yellow-400">★</span>
                <span class="text-sm text-gray-600 ml-1">
                  {{ masterData.rating }} ({{ masterData.reviewsCount || 0 }})
                </span>
              </div>
            </div>
            
            <!-- Описание -->
            <p v-if="masterData.description" class="text-gray-600 mb-4">
              {{ masterData.description }}
            </p>
            
            <!-- Контакты -->
            <div v-if="masterData.contacts" class="flex gap-2">
              <button 
                v-if="masterData.contacts.phone"
                @click="handleContactClick('phone', masterData.contacts.phone)"
                class="text-blue-600 hover:text-blue-800 text-sm underline"
              >
                Позвонить
              </button>
              <button 
                v-if="masterData.contacts.whatsapp"
                @click="handleContactClick('whatsapp', masterData.contacts.whatsapp)"
                class="text-green-600 hover:text-green-800 text-sm underline"
              >
                WhatsApp
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Услуги -->
      <div v-if="hasServices" class="master-profile-widget__services bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Услуги</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="service in masterData.services" 
            :key="service.id"
            class="p-4 border rounded-lg hover:border-blue-300 transition-colors cursor-pointer"
            @click="handleServiceSelect(service)"
          >
            <h3 class="font-medium text-gray-900 mb-1">{{ service.name }}</h3>
            <p class="text-sm text-gray-600 mb-2">{{ service.description }}</p>
            <div class="flex justify-between items-center">
              <span class="font-semibold text-blue-600">{{ formatPrice(service) }}</span>
              <span class="text-xs text-gray-500">{{ service.duration }} мин</span>
            </div>
            
            <!-- Кнопка бронирования -->
            <button 
              v-if="isServiceBookable(service)"
              @click.stop="handleBookingRequest"
              class="mt-3 w-full bg-blue-600 text-white py-2 px-4 rounded text-sm hover:bg-blue-700 transition-colors"
            >
              Записаться
            </button>
          </div>
        </div>
      </div>
      
      <!-- Галерея (только если не компактный режим) -->
      <div 
        v-if="hasPhotos && displayConfig.showGallery" 
        class="master-profile-widget__gallery bg-white rounded-lg shadow-sm p-6"
      >
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Фотографии работ</h2>
        
        <!-- Главное фото -->
        <div v-if="mainPhoto" class="mb-4">
          <img 
            :src="mainPhoto.url" 
            :alt="mainPhoto.alt"
            class="w-full h-64 object-cover rounded-lg cursor-pointer hover:opacity-90 transition-opacity"
            @click="handlePhotoClick(mainPhoto)"
          />
        </div>
        
        <!-- Остальные фото -->
        <div v-if="galleryPhotos.length > 0" class="grid grid-cols-3 md:grid-cols-4 gap-2">
          <div 
            v-for="photo in galleryPhotos" 
            :key="photo.id" 
            class="aspect-square"
          >
            <img 
              :src="photo.url" 
              :alt="photo.alt" 
              class="w-full h-full object-cover rounded cursor-pointer hover:opacity-90 transition-opacity"
              @click="handlePhotoClick(photo)"
            />
          </div>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Изолированный виджет MasterProfile
 * 
 * Принципы Ozon:
 * - Не зависит от внешних состояний
 * - Имеет собственный API и store
 * - Полностью переиспользуемый
 * - Изолированная обработка ошибок
 */

import type { 
  MasterProfileWidgetProps, 
  MasterProfileWidgetEmits 
} from './types/masterProfile.types'
import { useMasterProfileWidget } from './composables/useMasterProfileWidget'

// === PROPS И EVENTS ===
const props = withDefaults(defineProps<MasterProfileWidgetProps>(), {
  compact: false,
  showBooking: true,
  showReviews: false
})

const emit = defineEmits<MasterProfileWidgetEmits>()

// === КОМПОЗАБЛ ВИДЖЕТА ===
const {
  // Данные
  masterData,
  isLoading,
  error,
  displayConfig,
  
  // Состояния
  isLoaded,
  hasServices,
  hasPhotos,
  mainPhoto,
  galleryPhotos,
  
  // Методы
  handleServiceSelect: _handleServiceSelect,
  handlePhotoClick: _handlePhotoClick,
  handleContactClick: _handleContactClick,
  handleBookingRequest: _handleBookingRequest,
  retryLoad,
  
  // Утилиты
  getWidgetClasses,
  isServiceBookable,
  formatPrice,
  getMasterStatus
} = useMasterProfileWidget(props)

// === ОБРАБОТЧИКИ СОБЫТИЙ ===

function handleServiceSelect(service: any) {
  _handleServiceSelect(service)
  emit('service-selected', service)
}

function handlePhotoClick(photo: any) {
  _handlePhotoClick(photo)
  emit('photo-clicked', photo)
}

function handleContactClick(type: string, value: string) {
  _handleContactClick(type, value)
  emit('contact-clicked', type, value)
}

function handleBookingRequest() {
  _handleBookingRequest()
  if (masterData.value) {
    emit('booking-requested', masterData.value.id)
  }
}
</script>

<style scoped>
/* Изолированные стили виджета */
.master-profile-widget {
  @apply max-w-4xl mx-auto;
  
  /* CSS переменные из дизайн-токенов */
  --widget-primary: var(--color-blue-500);
  --widget-surface: var(--bg-surface);
  --widget-text: var(--text-primary);
  --widget-spacing: var(--spacing-4);
  --widget-radius: var(--card-radius);
}

.master-profile-widget--compact {
  @apply max-w-2xl;
}

.master-profile-widget--loading {
  @apply opacity-70;
}

.master-profile-widget--error {
  @apply opacity-50;
}

.master-profile-widget__header {
  @apply border-l-4 border-blue-500;
}

.master-profile-widget__services .service-card:hover {
  @apply shadow-md transform scale-105;
  transition: all 0.2s ease;
}

.master-profile-widget__gallery img:hover {
  @apply shadow-lg;
}

/* Адаптивность */
@media (max-width: 768px) {
  .master-profile-widget__content {
    @apply space-y-4;
  }
  
  .master-profile-widget__header {
    @apply p-4;
  }
  
  .master-profile-widget__services,
  .master-profile-widget__gallery {
    @apply p-4;
  }
}
</style>