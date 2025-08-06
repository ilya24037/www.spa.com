<!-- resources/js/src/entities/master/ui/MasterCard/MasterCard.vue -->
<template>
  <!-- Error СЃРѕСЃС‚РѕСЏРЅРёРµ -->
  <ErrorState
    v-if="errorState.error"
    :error="errorState.error"
    size="medium"
    variant="card"
    :retryable="true"
    :dismissible="true"
    @retry="handleRetry"
    @dismiss="errorState.clearError"
    class="h-full"
  />
  
  <!-- РћСЃРЅРѕРІРЅР°СЏ РєР°СЂС‚РѕС‡РєР° -->
  <article 
    v-else
    :class="CARD_CLASSES"
    @click="goToProfile"
    role="button"
    tabindex="0"
    :aria-label="`РџСЂРѕС„РёР»СЊ РјР°СЃС‚РµСЂР° ${props.master.display_name || props.master.name}`"
    data-testid="master-card"
  >
    <!-- Р‘РµР№РґР¶Рё -->
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
        РџСЂРѕРІРµСЂРµРЅ
      </span>
    </div>

    <!-- РР·Р±СЂР°РЅРЅРѕРµ -->
    <button 
      @click.stop="toggleFavorite"
      :class="[
        FAVORITE_BUTTON_CLASSES,
        isFavorite ? 'text-red-500' : 'text-gray-400 hover:text-red-500'
      ]"
      :aria-label="isFavorite ? 'РЈРґР°Р»РёС‚СЊ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ' : 'Р”РѕР±Р°РІРёС‚СЊ РІ РёР·Р±СЂР°РЅРЅРѕРµ'"
      data-testid="favorite-button"
    >
      <svg :class="FAVORITE_ICON_CLASSES" :fill="isFavorite ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
      </svg>
    </button>

    <!-- РР·РѕР±СЂР°Р¶РµРЅРёРµ -->
    <div :class="IMAGE_CONTAINER_CLASSES">
      <img 
        :src="masterPhoto"
        :alt="master.display_name || master.name || 'РњР°СЃС‚РµСЂ РјР°СЃСЃР°Р¶Р°'"
        :class="IMAGE_CLASSES"
        loading="lazy"
        @error="handleImageError"
        data-testid="master-avatar"
      >
      <!-- РћРЅР»Р°Р№РЅ СЃС‚Р°С‚СѓСЃ -->
      <div 
        v-if="master.is_online || master.is_available_now"
        :class="ONLINE_STATUS_CLASSES"
      >
        <span :class="ONLINE_INDICATOR_CLASSES"></span>
        РћРЅР»Р°Р№РЅ
      </div>
    </div>

    <!-- РљРѕРЅС‚РµРЅС‚ -->
    <div :class="CONTENT_CLASSES">
      <!-- Р¦РµРЅР° Рё СЂРµР№С‚РёРЅРі -->
      <div :class="PRICE_RATING_CONTAINER_CLASSES">
        <div>
          <div :class="PRICE_CLASSES">
            РѕС‚ {{ formatPrice(master.price_from || 2000) }} в‚Ѕ
          </div>
          <div :class="PRICE_UNIT_CLASSES">/С‡Р°СЃ</div>
        </div>
        <div :class="RATING_CONTAINER_CLASSES">
          <svg :class="STAR_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
          <span :class="RATING_VALUE_CLASSES">{{ master.rating || '5.0' }}</span>
          <span :class="RATING_COUNT_CLASSES">({{ master.reviews_count || 0 }})</span>
        </div>
      </div>
      
      <!-- РРјСЏ Рё СЃРїРµС†РёР°Р»РёР·Р°С†РёСЏ -->
      <h3 :class="NAME_CLASSES">
        {{ master.display_name || master.name || 'РњР°СЃС‚РµСЂ' }}
      </h3>
      
      <!-- РЈСЃР»СѓРіРё -->
      <p :class="SERVICES_CLASSES">
        {{ getServicesText() }}
      </p>
      
      <!-- Р›РѕРєР°С†РёСЏ -->
      <div :class="LOCATION_CONTAINER_CLASSES">
        <svg :class="LOCATION_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <span>{{ master.district || 'Р¦РµРЅС‚СЂ' }}</span>
        <span v-if="master.metro_station">вЂў Рј. {{ master.metro_station }}</span>
      </div>
      
      <!-- РљРЅРѕРїРєРё РґРµР№СЃС‚РІРёР№ -->
      <div :class="ACTIONS_CONTAINER_CLASSES">
        <button
          @click.stop="showPhone"
          :class="PHONE_BUTTON_CLASSES"
          aria-label="РџРѕРєР°Р·Р°С‚СЊ С‚РµР»РµС„РѕРЅ РјР°СЃС‚РµСЂР°"
          data-testid="phone-button"
        >
          <svg :class="PHONE_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
          </svg>
          РЎРІСЏР·Р°С‚СЊСЃСЏ
        </button>
        <button
          @click.stop="openBooking"
          :class="BOOKING_BUTTON_CLASSES"
          aria-label="Р—Р°РїРёСЃР°С‚СЊСЃСЏ Рє РјР°СЃС‚РµСЂСѓ"
          data-testid="booking-button"
        >
          <svg :class="BOOKING_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v9a1 1 0 01-1 1H4a1 1 0 01-1-1V8a1 1 0 011-1h4z"/>
          </svg>
          Р—Р°РїРёСЃР°С‚СЊСЃСЏ
        </button>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { ref, computed, type Ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from '@/src/shared/composables/useToast'
import { useErrorHandler } from '@/src/shared/composables/useErrorHandler'
import { ErrorState } from '@/src/shared/ui/molecules/ErrorState'
import type { 
  MasterCardProps, 
  MasterCardEmits, 
  MasterCardStyles,
  MasterCardState,
  FavoriteToggleResponse 
} from './MasterCard.types'
import type { ErrorInfo } from '@/src/shared/ui/molecules/ErrorState/ErrorState.types'

// Error handler (Р±РµР· toast - РїРѕРєР°Р·С‹РІР°РµРј С‡РµСЂРµР· ErrorState)
const errorState = useErrorHandler(false)

// Toast С‚РѕР»СЊРєРѕ РґР»СЏ СѓСЃРїРµС€РЅС‹С… РґРµР№СЃС‚РІРёР№
const toast = useToast()

// Props
const props = defineProps<MasterCardProps>()

// Emits  
const emit = defineEmits<MasterCardEmits>()

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
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

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const imageError: import("vue").Ref<boolean> = ref(false)

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
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

// РњРµС‚РѕРґС‹
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
  
  return 'РњР°СЃСЃР°Р¶ Рё SPA СѓСЃР»СѓРіРё'
}

const handleImageError = (): void => {
  imageError.value = true
}

const goToProfile = (): void => {
  try {
    errorState.clearError()
    emit('profileVisited', props.master.id)
    router.visit(`/masters/${props.master.id}`)
  } catch (error: unknown) {
    errorState.handleError(error, 'data_load')
  }
}

const toggleFavorite = async (): Promise<void> => {
  try {
    errorState.clearError()
    const currentState = isFavorite.value
    emit('favoriteToggled', props.master.id, !currentState)
    
    await router.post('/api/favorites/toggle', {
      master_profile_id: props.master.id
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        toast.success(currentState ? 'РЈРґР°Р»РµРЅРѕ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ' : 'Р”РѕР±Р°РІР»РµРЅРѕ РІ РёР·Р±СЂР°РЅРЅРѕРµ')
      },
      onError: (errors) => {
        errorState.handleError({
          message: 'РќРµ СѓРґР°Р»РѕСЃСЊ РѕР±РЅРѕРІРёС‚СЊ РёР·Р±СЂР°РЅРЅРѕРµ',
          details: 'РџСЂРѕРІРµСЂСЊС‚Рµ РїРѕРґРєР»СЋС‡РµРЅРёРµ Рє РёРЅС‚РµСЂРЅРµС‚Сѓ Рё РїРѕРїСЂРѕР±СѓР№С‚Рµ СЃРЅРѕРІР°',
          status: errors?.response?.status
        }, 'data_load')
      }
    })
  } catch (error: unknown) {
    errorState.handleError(error, 'data_load')
  }
}

const showPhone = (): void => {
  try {
    errorState.clearError()
    emit('phoneRequested', props.master.id)
    
    if (props.master.phone && props.master.show_contacts) {
      const cleanPhone = props.master.phone.replace(/\D/g, '')
      window.location.href = `tel:${cleanPhone}`
    } else {
      toast.info('РўРµР»РµС„РѕРЅ Р±СѓРґРµС‚ РґРѕСЃС‚СѓРїРµРЅ РїРѕСЃР»Рµ Р·Р°РїРёСЃРё Рє РјР°СЃС‚РµСЂСѓ')
    }
  } catch (error: unknown) {
    errorState.handleError(error, 'generic')
  }
}

const openBooking = (): void => {
  try {
    errorState.clearError()
    emit('bookingRequested', props.master.id)
    router.visit(`/masters/${props.master.id}?booking=true`)
  } catch (error: unknown) {
    errorState.handleError(error, 'booking')
  }
}

// РњРµС‚РѕРґ РґР»СЏ РїРѕРІС‚РѕСЂРЅРѕР№ РїРѕРїС‹С‚РєРё РїРѕСЃР»Рµ РѕС€РёР±РєРё
const handleRetry = async (): Promise<void> => {
  errorState.clearError()
  
  // РџСЂРѕРІРµСЂСЏРµРј, С‡С‚Рѕ РґР°РЅРЅС‹Рµ РјР°СЃС‚РµСЂР° РєРѕСЂСЂРµРєС‚РЅС‹
  if (!props.master || !props.master.id) {
    errorState.handleError({
      message: 'Р”Р°РЅРЅС‹Рµ РјР°СЃС‚РµСЂР° РЅРµ Р·Р°РіСЂСѓР¶РµРЅС‹',
      details: 'РџРѕРїСЂРѕР±СѓР№С‚Рµ РѕР±РЅРѕРІРёС‚СЊ СЃС‚СЂР°РЅРёС†Сѓ'
    }, 'data_load')
    return
  }
  
  // РџС‹С‚Р°РµРјСЃСЏ РїРµСЂРµР·Р°РіСЂСѓР·РёС‚СЊ РєР°СЂС‚РѕС‡РєСѓ
  await errorState.retryOperation(async () => {
    // Р­РјРёС‚РёСЂСѓРµРј СЃРѕР±С‹С‚РёРµ РґР»СЏ СЂРѕРґРёС‚РµР»СЊСЃРєРѕРіРѕ РєРѕРјРїРѕРЅРµРЅС‚Р°
    emit('retryRequested', props.master.id)
    
    // Р’ СЂРµР°Р»СЊРЅРѕРј РїСЂРёР»РѕР¶РµРЅРёРё Р·РґРµСЃСЊ Р±С‹Р» Р±С‹ API РІС‹Р·РѕРІ
    // await api.getMaster(props.master.id)
  })
}
</script>

