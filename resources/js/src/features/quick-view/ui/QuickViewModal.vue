<!-- 
  QuickViewModal - Модальное окно быстрого просмотра мастера
  Показывает основную информацию без перехода на страницу профиля
-->
<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="isOpen && master"
        class="quick-view-modal"
        @click="handleBackdropClick"
      >
        <!-- Backdrop -->
        <div class="quick-view-modal__backdrop" />
        
        <!-- Modal Content -->
        <div 
          class="quick-view-modal__content"
          @click.stop
        >
          <!-- Close button -->
          <button
            class="quick-view-modal__close"
            @click="close"
            aria-label="Закрыть"
          >
            <svg class="w-6 h-6"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor">
              <path stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Content Grid -->
          <div class="quick-view-modal__grid">
            <!-- Left: Gallery -->
            <div class="quick-view-modal__gallery">
              <!-- Main Image -->
              <div class="quick-view-modal__main-image">
                <ImageWithBlur
                  :src="currentImage"
                  :placeholder="currentImagePlaceholder"
                  :alt="master.name"
                  container-class="w-full h-full"
                  image-class="w-full h-full object-cover rounded-lg"
                />
                
                <!-- Badges -->
                <div class="absolute top-3 left-3 flex gap-2">
                  <span 
                    v-if="master.is_premium"
                    class="px-2 py-1 bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-xs font-semibold rounded"
                  >
                    Premium
                  </span>
                  <span 
                    v-if="master.is_verified" 
                    class="px-2 py-1 bg-green-500 text-white text-xs font-semibold rounded"
                  >
                    ✓ Проверен
                  </span>
                </div>
              </div>
              
              <!-- Thumbnails -->
              <div v-if="galleryImages.length > 1" class="quick-view-modal__thumbnails">
                <button
                  v-for="(image, index) in galleryImages"
                  :key="index"
                  class="quick-view-modal__thumbnail"
                  :class="{ 'quick-view-modal__thumbnail--active': currentImageIndex === index }"
                  @click="currentImageIndex = index"
                >
                  <img 
                    :src="image.thumbnail || image.url" 
                    :alt="`${master.name} фото ${index + 1}`"
                    class="w-full h-full object-cover rounded"
                  >
                </button>
              </div>
            </div>

            <!-- Right: Info -->
            <div class="quick-view-modal__info">
              <!-- Header -->
              <div class="quick-view-modal__header">
                <div>
                  <h2 class="text-2xl font-bold text-gray-900">{{ master.name }}</h2>
                  <div class="flex items-center gap-4 mt-2">
                    <!-- Rating -->
                    <div class="flex items-center gap-1">
                      <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                      </svg>
                      <span class="font-semibold">{{ formatRating(master.rating) }}</span>
                      <span class="text-gray-500">({{ master.reviews_count }} отзывов)</span>
                    </div>
                    <!-- Online status -->
                    <span 
                      v-if="master.is_online"
                      class="px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full"
                    >
                      • Онлайн
                    </span>
                  </div>
                </div>
                
                <!-- Favorite button -->
                <button 
                  class="p-2 rounded-full hover:bg-gray-100 transition-colors"
                  :class="isFavorite ? 'text-red-500' : 'text-gray-400'"
                  @click="$emit('toggle-favorite', master.id)"
                >
                  <svg
                    class="w-6 h-6"
                    :fill="isFavorite ? 'currentColor' : 'none'"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2" 
                      d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"
                    />
                  </svg>
                </button>
              </div>

              <!-- Price -->
              <div class="quick-view-modal__price">
                <div class="text-3xl font-bold text-gray-900">
                  от {{ formatPrice(master.price_from || 2000) }} ₽
                </div>
                <div class="text-gray-500">за час</div>
              </div>

              <!-- Info blocks -->
              <div class="quick-view-modal__details">
                <!-- Location -->
                <div class="quick-view-modal__detail-item">
                  <svg class="w-5 h-5 text-gray-400"
                       fill="none"
                       viewBox="0 0 24 24"
                       stroke="currentColor">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  <div>
                    <div class="text-sm text-gray-500">Локация</div>
                    <div class="font-medium">{{ master.district }}</div>
                    <div v-if="master.metro_station" class="text-sm text-gray-600">
                      м. {{ master.metro_station }}
                    </div>
                  </div>
                </div>

                <!-- Experience -->
                <div v-if="master.experience_years" class="quick-view-modal__detail-item">
                  <svg class="w-5 h-5 text-gray-400"
                       fill="none"
                       viewBox="0 0 24 24"
                       stroke="currentColor">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <div class="text-sm text-gray-500">Опыт работы</div>
                    <div class="font-medium">{{ master.experience_years }} лет</div>
                  </div>
                </div>

                <!-- Working hours -->
                <div v-if="master.working_hours" class="quick-view-modal__detail-item">
                  <svg class="w-5 h-5 text-gray-400"
                       fill="none"
                       viewBox="0 0 24 24"
                       stroke="currentColor">
                    <path stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  <div>
                    <div class="text-sm text-gray-500">График работы</div>
                    <div class="font-medium">{{ master.working_hours }}</div>
                  </div>
                </div>
              </div>

              <!-- Services -->
              <div class="quick-view-modal__services">
                <h3 class="font-semibold text-gray-900 mb-3">Услуги</h3>
                <div class="flex flex-wrap gap-2">
                  <span 
                    v-for="service in master.services" 
                    :key="service.id"
                    class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm"
                  >
                    {{ service.name }}
                  </span>
                </div>
              </div>

              <!-- Description -->
              <div v-if="master.description" class="quick-view-modal__description">
                <h3 class="font-semibold text-gray-900 mb-2">О мастере</h3>
                <p class="text-gray-600 line-clamp-3">
                  {{ master.description }}
                </p>
              </div>

              <!-- Actions -->
              <div class="quick-view-modal__actions">
                <PrimaryButton
                  type="button"
                  class="flex-1"
                  @click="handleBooking"
                >
                  Записаться
                </PrimaryButton>
                
                <SecondaryButton
                  type="button"
                  class="flex-1"
                  @click="handleViewProfile"
                >
                  Полный профиль
                </SecondaryButton>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import type { Master } from '@/src/entities/master/model/types'
import { ImageWithBlur } from '@/src/shared/ui/molecules/ImageWithBlur'
import PrimaryButton from '@/src/shared/ui/atoms/PrimaryButton/PrimaryButton.vue'
import SecondaryButton from '@/src/shared/ui/atoms/SecondaryButton/SecondaryButton.vue'

interface Props {
  isOpen: boolean
  master: Master | null
  isFavorite?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  isOpen: false,
  master: null,
  isFavorite: false
})

const emit = defineEmits<{
  'close': []
  'toggle-favorite': [id: number]
  'booking': [master: Master]
}>()

// State
const currentImageIndex = ref(0)

// Computed
const galleryImages = computed(() => {
  if (!props.master) return []
  
  const images = []
  
  // Основное фото
  if (props.master.photo) {
    images.push({
      url: props.master.photo,
      thumbnail: props.master.photo_thumbnail || props.master.photo,
      placeholder: props.master.photo_placeholder
    })
  }
  
  // Дополнительные фото из галереи
  if (props.master.gallery) {
    props.master.gallery.forEach(img => {
      images.push({
        url: img.url,
        thumbnail: img.thumbnail || img.url,
        placeholder: img.placeholder
      })
    })
  }
  
  // Fallback если нет фото
  if (images.length === 0) {
    images.push({
      url: '/images/no-photo.svg',
      thumbnail: '/images/no-photo.svg'
    })
  }
  
  return images
})

const currentImage = computed(() => {
  return galleryImages.value[currentImageIndex.value]?.url || '/images/no-photo.svg'
})

const currentImagePlaceholder = computed(() => {
  return galleryImages.value[currentImageIndex.value]?.placeholder || ''
})

// Methods
const formatRating = (rating?: number): string => {
  return (rating || 0).toFixed(1)
}

const formatPrice = (price: number): string => {
  return new Intl.NumberFormat('ru-RU').format(price)
}

const close = () => {
  emit('close')
}

const handleBackdropClick = (event: MouseEvent) => {
  if (event.target === event.currentTarget) {
    close()
  }
}

const handleBooking = () => {
  if (props.master) {
    emit('booking', props.master)
    close()
  }
}

const handleViewProfile = () => {
  if (props.master) {
    router.visit(`/masters/${props.master.id}`)
    close()
  }
}

// Reset image index when modal opens with new master
watch(() => props.master, () => {
  currentImageIndex.value = 0
})

// Keyboard handling
const handleKeydown = (event: KeyboardEvent) => {
  if (!props.isOpen) return
  
  if (event.key === 'Escape') {
    close()
  }
}

// Lifecycle
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    document.addEventListener('keydown', handleKeydown)
    document.body.style.overflow = 'hidden'
  } else {
    document.removeEventListener('keydown', handleKeydown)
    document.body.style.overflow = ''
  }
})
</script>

<style scoped>
.quick-view-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.quick-view-modal__backdrop {
  position: absolute;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
}

.quick-view-modal__content {
  position: relative;
  background: white;
  border-radius: 1rem;
  max-width: 1200px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.quick-view-modal__close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  z-index: 10;
  padding: 0.5rem;
  background: white;
  border-radius: 0.5rem;
  transition: all 0.2s;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.quick-view-modal__close:hover {
  background: #f3f4f6;
}

.quick-view-modal__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 600px;
}

@media (max-width: 768px) {
  .quick-view-modal__grid {
    grid-template-columns: 1fr;
  }
}

.quick-view-modal__gallery {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.quick-view-modal__main-image {
  position: relative;
  flex: 1;
  min-height: 400px;
  border-radius: 0.5rem;
  overflow: hidden;
  background: #f3f4f6;
}

.quick-view-modal__thumbnails {
  display: flex;
  gap: 0.5rem;
  overflow-x: auto;
}

.quick-view-modal__thumbnail {
  flex-shrink: 0;
  width: 80px;
  height: 80px;
  border-radius: 0.5rem;
  overflow: hidden;
  border: 2px solid transparent;
  transition: all 0.2s;
}

.quick-view-modal__thumbnail:hover {
  border-color: #d1d5db;
}

.quick-view-modal__thumbnail--active {
  border-color: #3b82f6;
}

.quick-view-modal__info {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  border-left: 1px solid #e5e7eb;
}

@media (max-width: 768px) {
  .quick-view-modal__info {
    border-left: none;
    border-top: 1px solid #e5e7eb;
  }
}

.quick-view-modal__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.quick-view-modal__price {
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.quick-view-modal__details {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.quick-view-modal__detail-item {
  display: flex;
  gap: 0.75rem;
}

.quick-view-modal__services {
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.quick-view-modal__description {
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.line-clamp-3 {
  overflow: hidden;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 3;
}

.quick-view-modal__actions {
  display: flex;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

/* Animations */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .quick-view-modal__content,
.modal-leave-to .quick-view-modal__content {
  transform: scale(0.9);
  opacity: 0;
}
</style>