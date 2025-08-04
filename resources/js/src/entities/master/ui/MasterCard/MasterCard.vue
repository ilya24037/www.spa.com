<!-- resources/js/src/entities/master/ui/MasterCard/MasterCard.vue -->
<template>
  <article 
    :class="CARD_CLASSES"
    @click="goToProfile"
    role="button"
    tabindex="0"
    :aria-label="`Профиль мастера ${props.master.display_name || props.master.name}`"
    data-testid="master-card"
  >
    <!-- Бейджи -->
    <div :class="BADGES_CONTAINER_CLASSES">
      <span 
        v-if="master.is_premium"
        :class="PREMIUM_BADGE_CLASSES"
      >Premium</span>
      <span 
        v-if="master.is_verified" 
        :class="VERIFIED_BADGE_CLASSES"
      >
        <svg :class="VERIFIED_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        Проверен
      </span>
    </div>

    <!-- Избранное -->
    <button 
      @click.stop="toggleFavorite"
      :class="[
        FAVORITE_BUTTON_CLASSES,
        isFavorite ? 'text-red-500' : 'text-gray-400 hover:text-red-500'
      ]"
      :aria-label="isFavorite ? 'Удалить из избранного' : 'Добавить в избранное'"
      data-testid="favorite-button"
    >
      <svg :class="FAVORITE_ICON_CLASSES" :fill="isFavorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
      </svg>
    </button>

    <!-- Изображение -->
    <div :class="IMAGE_CONTAINER_CLASSES">
      <img 
        :src="masterPhoto"
        :alt="master.display_name || master.name || 'Мастер массажа'"
        :class="IMAGE_CLASSES"
        loading="lazy"
        @error="handleImageError"
        data-testid="master-avatar"
      >
      <!-- Онлайн статус -->
      <div 
        v-if="master.is_online || master.is_available_now"
        :class="ONLINE_STATUS_CLASSES"
      >
        <span :class="ONLINE_INDICATOR_CLASSES"></span>
        Онлайн
      </div>
    </div>

    <!-- Контент -->
    <div :class="CONTENT_CLASSES">
      <!-- Цена и рейтинг -->
      <div :class="PRICE_RATING_CONTAINER_CLASSES">
        <div>
          <div :class="PRICE_CLASSES">
            от {{ formatPrice(master.price_from || 2000) }} ₽
          </div>
          <div :class="PRICE_UNIT_CLASSES">/час</div>
        </div>
        <div :class="RATING_CONTAINER_CLASSES">
          <svg :class="STAR_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
          <span :class="RATING_VALUE_CLASSES">{{ master.rating || '5.0' }}</span>
          <span :class="RATING_COUNT_CLASSES">({{ master.reviews_count || 0 }})</span>
        </div>
      </div>
      
      <!-- Имя и специализация -->
      <h3 :class="NAME_CLASSES">
        {{ master.display_name || master.name || 'Мастер' }}
      </h3>
      
      <!-- Услуги -->
      <p :class="SERVICES_CLASSES">
        {{ getServicesText() }}
      </p>
      
      <!-- Локация -->
      <div :class="LOCATION_CONTAINER_CLASSES">
        <svg :class="LOCATION_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span>{{ master.district || 'Центр' }}</span>
        <span v-if="master.metro_station">• м. {{ master.metro_station }}</span>
      </div>
      
      <!-- Кнопки действий -->
      <div :class="ACTIONS_CONTAINER_CLASSES">
        <button
          @click.stop="showPhone"
          :class="PHONE_BUTTON_CLASSES"
          aria-label="Показать телефон мастера"
          data-testid="phone-button"
        >
          <svg :class="PHONE_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          Связаться
        </button>
        <button
          @click.stop="openBooking"
          :class="BOOKING_BUTTON_CLASSES"
          aria-label="Записаться к мастеру"
          data-testid="booking-button"
        >
          <svg :class="BOOKING_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v9a1 1 0 01-1 1H4a1 1 0 01-1-1V8a1 1 0 011-1h4z"/>
          </svg>
          Записаться
        </button>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { ref, computed, type Ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from '@/src/shared/composables/useToast'
import type { 
  MasterCardProps, 
  MasterCardEmits, 
  MasterCardStyles,
  MasterCardState,
  FavoriteToggleResponse 
} from './MasterCard.types'

// Toast для замены alert()
const toast = useToast()

// Props
const props = defineProps<MasterCardProps>()

// Emits  
const emit = defineEmits<MasterCardEmits>()

// 🎯 Стили согласно дизайн-системе
const CARD_CLASSES: string = 'relative bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden cursor-pointer'
const BADGES_CONTAINER_CLASSES: string = 'absolute top-2 left-2 z-10 flex flex-col gap-1'
const PREMIUM_BADGE_CLASSES: string = 'bg-purple-600 text-white px-2 py-0.5 rounded text-xs font-medium'
const VERIFIED_BADGE_CLASSES: string = 'bg-green-500 text-white px-2 py-0.5 rounded text-xs font-medium flex items-center gap-0.5'
const VERIFIED_ICON_CLASSES: string = 'w-3 h-3'
const FAVORITE_BUTTON_CLASSES: string = 'absolute top-2 right-2 z-10 p-2 bg-white/90 backdrop-blur rounded-lg hover:bg-white shadow-sm transition-all'
const FAVORITE_ICON_CLASSES: string = 'w-5 h-5'
const IMAGE_CONTAINER_CLASSES: string = 'relative aspect-[4/5] overflow-hidden bg-gray-100'
const IMAGE_CLASSES: string = 'w-full h-full object-cover'
const ONLINE_STATUS_CLASSES: string = 'absolute bottom-2 left-2 px-2 py-1 bg-green-500 text-white text-xs font-medium rounded-full flex items-center gap-1'
const ONLINE_INDICATOR_CLASSES: string = 'w-2 h-2 bg-white rounded-full animate-pulse'
const CONTENT_CLASSES: string = 'p-4'
const PRICE_RATING_CONTAINER_CLASSES: string = 'flex items-start justify-between gap-2 mb-2'
const PRICE_CLASSES: string = 'font-bold text-xl text-gray-900'
const PRICE_UNIT_CLASSES: string = 'text-xs text-gray-500'
const RATING_CONTAINER_CLASSES: string = 'flex items-center gap-1 text-sm'
const STAR_ICON_CLASSES: string = 'w-4 h-4 text-yellow-400'
const RATING_VALUE_CLASSES: string = 'font-medium'
const RATING_COUNT_CLASSES: string = 'text-gray-400'
const NAME_CLASSES: string = 'font-semibold text-gray-900 truncate mb-1'
const SERVICES_CLASSES: string = 'text-sm text-gray-600 line-clamp-2 mb-3'
const LOCATION_CONTAINER_CLASSES: string = 'flex items-center gap-1 text-xs text-gray-500 mb-3'
const LOCATION_ICON_CLASSES: string = 'w-3 h-3'
const ACTIONS_CONTAINER_CLASSES: string = 'flex gap-2'
const PHONE_BUTTON_CLASSES: string = 'flex-1 bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-3 rounded-lg transition-colors flex items-center justify-center gap-1'
const PHONE_ICON_CLASSES: string = 'w-4 h-4'
const BOOKING_BUTTON_CLASSES: string = 'flex-1 bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-3 rounded-lg transition-colors flex items-center justify-center gap-1'
const BOOKING_ICON_CLASSES: string = 'w-4 h-4'

// Состояние
const imageError: Ref<boolean> = ref(false)

// Вычисляемые свойства
const isFavorite = computed((): boolean => props.master.is_favorite || false)

const masterPhoto = computed((): string => {
  if (imageError.value) {
    return '/images/placeholders/master-1.jpg'
  }
  return props.master.avatar || 
         props.master.main_photo || 
         props.master.photo ||
         '/images/placeholders/master-1.jpg'
})

// Методы
const formatPrice = (price: number | undefined): string => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const getServicesText = (): string => {
  if (props.master.specialty) {
    return props.master.specialty
  }
  
  if (props.master.services && props.master.services.length > 0) {
    return props.master.services.slice(0, 2).map(s => s.name).join(', ')
  }
  
  return 'Массаж и SPA услуги'
}

const handleImageError = (): void => {
  imageError.value = true
}

const goToProfile = (): void => {
  try {
    emit('profileVisited', props.master.id)
    router.visit(`/masters/${props.master.id}`)
  } catch (error: unknown) {
    console.error('Ошибка перехода к профилю:', error)
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка перехода: ' + errorMessage)
  }
}

const toggleFavorite = async (): Promise<void> => {
  try {
    const currentState = isFavorite.value
    emit('favoriteToggled', props.master.id, !currentState)
    
    await router.post('/api/favorites/toggle', {
      master_profile_id: props.master.id
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        toast.success(currentState ? 'Удалено из избранного' : 'Добавлено в избранное')
      },
      onError: (errors) => {
        console.error('Ошибка избранного:', errors)
        toast.error('Ошибка обновления избранного')
      }
    })
  } catch (error: unknown) {
    console.error('Ошибка toggleFavorite:', error)
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка: ' + errorMessage)
  }
}

const showPhone = (): void => {
  try {
    emit('phoneRequested', props.master.id)
    
    if (props.master.phone && props.master.show_contacts) {
      const cleanPhone = props.master.phone.replace(/\D/g, '')
      window.location.href = `tel:${cleanPhone}`
    } else {
      toast.info('Телефон будет доступен после записи к мастеру')
    }
  } catch (error: unknown) {
    console.error('Ошибка showPhone:', error)
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка звонка: ' + errorMessage)
  }
}

const openBooking = (): void => {
  try {
    emit('bookingRequested', props.master.id)
    router.visit(`/masters/${props.master.id}?booking=true`)
  } catch (error: unknown) {
    console.error('Ошибка openBooking:', error)
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка бронирования: ' + errorMessage)
  }
}
</script>