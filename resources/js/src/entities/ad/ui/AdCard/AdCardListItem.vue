<!-- resources/js/src/entities/ad/ui/AdCard/AdCardListItem.vue -->
<template>
  <div :class="CARD_CLASSES" @click="openAd">
    <div :class="CONTAINER_CLASSES">
      <!-- Р¤РѕС‚Рѕ -->
      <div :class="PHOTO_CONTAINER_CLASSES">
        <img
          :src="adPhoto"
          :alt="ad.title || ad.name"
          :class="PHOTO_CLASSES"
          @error="handleImageError"
        >
        <!-- Р‘РµР№РґР¶Рё -->
        <div :class="BADGES_CONTAINER_CLASSES">
          <span v-if="ad.is_premium" :class="PREMIUM_BADGE_CLASSES">Premium</span>
          <span v-if="ad.discount" :class="DISCOUNT_BADGE_CLASSES">-{{ ad.discount }}%</span>
        </div>
      </div>

      <!-- РРЅС„РѕСЂРјР°С†РёСЏ -->
      <div :class="INFO_CONTAINER_CLASSES">
        <div :class="INFO_HEADER_CLASSES">
          <div>
            <h3 :class="TITLE_CLASSES">{{ ad.title || ad.name || 'РњР°СЃСЃР°Р¶' }}</h3>
            <div :class="METADATA_CONTAINER_CLASSES">
              <!-- Р РµР№С‚РёРЅРі -->
              <div :class="RATING_WRAPPER_CLASSES">
                <svg :class="STAR_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span :class="RATING_VALUE_CLASSES">{{ ad.rating || '5.0' }}</span>
                <span :class="RATING_COUNT_CLASSES">({{ ad.reviews_count || 0 }})</span>
              </div>
              
              <!-- Р›РѕРєР°С†РёСЏ -->
              <div :class="LOCATION_WRAPPER_CLASSES">
                <svg :class="LOCATION_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>{{ ad.district || ad.location || 'Р¦РµРЅС‚СЂ' }}</span>
              </div>
            </div>
          </div>
          
          <!-- Р¦РµРЅР° -->
          <div :class="PRICE_CONTAINER_CLASSES">
            <div :class="PRICE_WRAPPER_CLASSES">
              <span :class="PRICE_CLASSES" data-testid="price">{{ formattedPrice }} в‚Ѕ</span>
              <span v-if="formattedOldPrice" :class="OLD_PRICE_CLASSES" data-testid="old-price">{{ formattedOldPrice }} в‚Ѕ</span>
            </div>
            <div :class="PRICE_UNIT_CLASSES">Р·Р° С‡Р°СЃ</div>
          </div>
        </div>

        <!-- РћРїРёСЃР°РЅРёРµ -->
        <div :class="DESCRIPTION_CONTAINER_CLASSES">
          <p :class="DESCRIPTION_CLASSES" data-testid="description">
            {{ adDescription }}
          </p>
        </div>

        <!-- РЈСЃР»СѓРіРё -->
        <div :class="SERVICES_CONTAINER_CLASSES">
          <span
            v-for="(service, index) in displayServices"
            :key="getServiceKey(service, index)"
            :class="SERVICE_TAG_CLASSES"
            data-testid="service-tag"
          >
            {{ getServiceName(service) }}
          </span>
          <span
            v-if="hasMoreServices"
            :class="MORE_SERVICES_CLASSES"
          >
            +{{ ad.services.length - 3 }}
          </span>
        </div>

        <!-- Р”РµР№СЃС‚РІРёСЏ -->
        <div :class="ACTIONS_CONTAINER_CLASSES">
          <button 
            @click.stop="contactMaster"
            :class="CONTACT_BUTTON_CLASSES"
          >
            <svg :class="CONTACT_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            РЎРІСЏР·Р°С‚СЊСЃСЏ
          </button>
          <button 
            @click.stop="openBooking"
            :class="BOOKING_BUTTON_CLASSES"
          >
            <svg :class="BOOKING_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v9a1 1 0 01-1 1H4a1 1 0 01-1-1V8a1 1 0 011-1h4z"/>
            </svg>
            Р—Р°РїРёСЃР°С‚СЊСЃСЏ
          </button>
          <button 
            @click.stop="toggleFavorite"
            :class="FAVORITE_BUTTON_CLASSES"
          >
            <svg :class="FAVORITE_ICON_CLASSES" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { logger } from '@/src/shared/lib/logger'
import { ref, computed, withDefaults } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from '@/src/shared/composables/useToast'
import type {
  AdCardListItemProps,AdService,
  AdCardListItemState,
  AdCardError,
  StyleConstants,} from './AdCardListItem.types'

// Props СЃ TypeScript С‚РёРїРёР·Р°С†РёРµР№
const _props = withDefaults(defineProps<AdCardListItemProps>(), {})

// Toast РґР»СЏ Р·Р°РјРµРЅС‹ alert()
const toast = useToast()

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ (СЃ С‚РёРїРёР·Р°С†РёРµР№)
const styleConstants: StyleConstants = {
  CARD_CLASSES: 'bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-4 cursor-pointer',
  CONTAINER_CLASSES: 'flex gap-4',
  PHOTO_CONTAINER_CLASSES: 'w-32 h-32 flex-shrink-0 relative',
  PHOTO_CLASSES: 'w-full h-full object-cover rounded-lg',
  BADGES_CONTAINER_CLASSES: 'absolute top-1 left-1 flex flex-col gap-0.5',
  PREMIUM_BADGE_CLASSES: 'bg-[#7000ff] text-white px-1.5 py-0.5 rounded text-xs font-medium',
  DISCOUNT_BADGE_CLASSES: 'bg-[#f91155] text-white px-1.5 py-0.5 rounded text-xs font-medium',
  INFO_CONTAINER_CLASSES: 'flex-1 min-w-0',
  INFO_HEADER_CLASSES: 'flex items-start justify-between mb-2',
  TITLE_CLASSES: 'font-semibold text-lg line-clamp-1',
  METADATA_CONTAINER_CLASSES: 'flex items-center gap-4 mt-1',
  RATING_WRAPPER_CLASSES: 'flex items-center gap-1',
  STAR_ICON_CLASSES: 'w-4 h-4 text-yellow-400',
  RATING_VALUE_CLASSES: 'text-sm font-medium',
  RATING_COUNT_CLASSES: 'text-sm text-gray-500',
  LOCATION_WRAPPER_CLASSES: 'flex items-center gap-1 text-sm text-gray-500',
  LOCATION_ICON_CLASSES: 'w-3 h-3',
  PRICE_CONTAINER_CLASSES: 'text-right',
  PRICE_WRAPPER_CLASSES: 'flex items-baseline gap-2 justify-end',
  PRICE_CLASSES: 'font-bold text-xl',
  OLD_PRICE_CLASSES: 'text-sm line-through text-gray-400',
  PRICE_UNIT_CLASSES: 'text-sm text-gray-500',
  DESCRIPTION_CONTAINER_CLASSES: 'mb-3',
  DESCRIPTION_CLASSES: 'text-sm text-gray-600 line-clamp-2',
  SERVICES_CONTAINER_CLASSES: 'flex flex-wrap gap-1 mb-3',
  SERVICE_TAG_CLASSES: 'text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded',
  MORE_SERVICES_CLASSES: 'text-xs text-gray-500 px-2 py-1',
  ACTIONS_CONTAINER_CLASSES: 'flex items-center gap-2',
  CONTACT_BUTTON_CLASSES: 'flex-1 bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition-colors text-sm font-medium flex items-center justify-center gap-1',
  CONTACT_ICON_CLASSES: 'w-4 h-4',
  BOOKING_BUTTON_CLASSES: 'flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg transition-colors text-sm font-medium flex items-center justify-center gap-1',
  BOOKING_ICON_CLASSES: 'w-4 h-4',
  FAVORITE_BUTTON_CLASSES: 'p-2 text-gray-400 hover:text-red-500 transition-colors',
  FAVORITE_ICON_CLASSES: 'w-5 h-5'
}

// РР·РІР»РµРєР°РµРј РєРѕРЅСЃС‚Р°РЅС‚С‹ РґР»СЏ РёСЃРїРѕР»СЊР·РѕРІР°РЅРёСЏ РІ С‚РµРјРїР»РµР№С‚Рµ
const {
  CARD_CLASSES,
  CONTAINER_CLASSES,
  PHOTO_CONTAINER_CLASSES,
  PHOTO_CLASSES,
  BADGES_CONTAINER_CLASSES,
  PREMIUM_BADGE_CLASSES,
  DISCOUNT_BADGE_CLASSES,
  INFO_CONTAINER_CLASSES,
  INFO_HEADER_CLASSES,
  TITLE_CLASSES,
  METADATA_CONTAINER_CLASSES,
  RATING_WRAPPER_CLASSES,
  STAR_ICON_CLASSES,
  RATING_VALUE_CLASSES,
  RATING_COUNT_CLASSES,
  LOCATION_WRAPPER_CLASSES,
  LOCATION_ICON_CLASSES,
  PRICE_CONTAINER_CLASSES,
  PRICE_WRAPPER_CLASSES,
  PRICE_CLASSES,
  OLD_PRICE_CLASSES,
  PRICE_UNIT_CLASSES,
  DESCRIPTION_CONTAINER_CLASSES,
  DESCRIPTION_CLASSES,
  SERVICES_CONTAINER_CLASSES,
  SERVICE_TAG_CLASSES,
  MORE_SERVICES_CLASSES,
  ACTIONS_CONTAINER_CLASSES,
  CONTACT_BUTTON_CLASSES,
  CONTACT_ICON_CLASSES,
  BOOKING_BUTTON_CLASSES,
  BOOKING_ICON_CLASSES,
  FAVORITE_BUTTON_CLASSES,
  FAVORITE_ICON_CLASSES
} = styleConstants

// РЎРѕСЃС‚РѕСЏРЅРёРµ РєРѕРјРїРѕРЅРµРЅС‚Р°
const state = ref<AdCardListItemState>({
  imageError: false,
  isProcessingFavorite: false,
  isContactingMaster: false,
  isOpeningBooking: false
})

// РћС‚РґРµР»СЊРЅС‹Рµ РґР»СЏ СѓРґРѕР±СЃС‚РІР°
const imageError = computed<boolean>({
  get: () => state.value.imageError,
  set: (value: boolean) => { state.value.imageError = value }
})

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР° СЃ С‚РёРїРёР·Р°С†РёРµР№
const isFavorite = computed<boolean>(() => 
  Boolean(props.ad.is_favorite)
)

const adPhoto = computed<string>(() => {
  if (imageError.value) {
    return '/images/placeholders/master-1.jpg'
  }
  
  // РџСЂРѕРІРµСЂСЏРµРј images
  if (props.ad.images && Array.isArray(props.ad.images) && props.ad.images.length > 0) {
    const firstImage = props.ad.images[0]
    if (typeof firstImage === 'object' && firstImage) {
      return firstImage.url || firstImage.path || '/images/placeholders/master-1.jpg'
    }
    return String(firstImage) || '/images/placeholders/master-1.jpg'
  }
  
  // РџСЂРѕРІРµСЂСЏРµРј photos
  if (props.ad.photos && Array.isArray(props.ad.photos) && props.ad.photos.length > 0) {
    const firstPhoto = props.ad.photos[0]
    if (typeof firstPhoto === 'object' && firstPhoto) {
      return firstPhoto.url || firstPhoto.path || '/images/placeholders/master-1.jpg'
    }
    return String(firstPhoto) || '/images/placeholders/master-1.jpg'
  }
  
  // РћСЃС‚Р°Р»СЊРЅС‹Рµ РІР°СЂРёР°РЅС‚С‹
  return props.ad.avatar || 
         props.ad.main_photo || 
         '/images/placeholders/master-1.jpg'
})

const displayServices = computed<AdService[]>(() => {
  if (!props.ad.services || !Array.isArray(props.ad.services)) {
    return []
  }
  return props.ad.services.slice(0, 3)
})

const hasMoreServices = computed<boolean>(() => {
  return Boolean(props.ad.services && props.ad.services.length > 3)
})

const formattedPrice = computed<string>(() => {
  const price = props.ad.price || props.ad.price_from
  return formatPrice(price)
})

const formattedOldPrice = computed<string | undefined>(() => {
  return props.ad.old_price ? formatPrice(props.ad.old_price) : undefined
})

const adDescription = computed<string>(() => {
  return getDescription()
})

// РњРµС‚РѕРґС‹ СЃ С‚РёРїРёР·Р°С†РёРµР№
const formatPrice = (price?: number): string => {
  if (!price || typeof price !== 'number') {
    return '2 000'
  }
  return new Intl.NumberFormat('ru-RU').format(price)
}

const getDescription = (): string => {
  return props.ad.description || 
         props.ad.specialty || 
         'РџСЂРѕС„РµСЃСЃРёРѕРЅР°Р»СЊРЅС‹Р№ РјР°СЃСЃР°Р¶ Рё SPA СѓСЃР»СѓРіРё'
}

const handleImageError = (): void => {
  try {
    imageError.value = true
    
    // Р›РѕРіРёСЂСѓРµРј РѕС€РёР±РєСѓ РґР»СЏ Р°РЅР°Р»РёС‚РёРєРё
    logger.warn(`Image load error for ad ${props.ad.id}`, {
      metadata: {
        attemptedUrl: adPhoto.value,
        adId: props.ad.id,
        timestamp: new Date().toISOString()
      }
    })
  } catch (error: unknown) {
    logger.error('Error in handleImageError:', error)
  }
}

const openAd = (): void => {
  try {
    const url = `/ads/${props.ad.id}`
    router.visit(url)
    
    // РћС‚РєСЂС‹С‚РёРµ РѕР±СЉСЏРІР»РµРЅРёСЏ
  } catch (error: unknown) {
    const adError: AdCardError = {
      type: 'navigation',
      message: 'РћС€РёР±РєР° РїСЂРё РѕС‚РєСЂС‹С‚РёРё РѕР±СЉСЏРІР»РµРЅРёСЏ',
      adId: props.ad.id,
      originalError: error
    }
    handleError(adError)
  }
}

const toggleFavorite = async (): Promise<void> => {
  if (state.value.isProcessingFavorite) {
    return
  }
  
  try {
    state.value.isProcessingFavorite = true
    
    await router.post('/api/favorites/toggle', {
      ad_id: props.ad.id
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (_response) => {
        const currentState = isFavorite.value
        toast.success(currentState ? 'РЈРґР°Р»РµРЅРѕ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ' : 'Р”РѕР±Р°РІР»РµРЅРѕ РІ РёР·Р±СЂР°РЅРЅРѕРµ')
      },
      onError: (errors) => {
        logger.error('Favorite toggle error:', errors)
        toast.error('РћС€РёР±РєР° РїСЂРё РґРѕР±Р°РІР»РµРЅРёРё РІ РёР·Р±СЂР°РЅРЅРѕРµ')
      }
    })
  } catch (error: unknown) {
    const adError: AdCardError = {
      type: 'favorite_toggle',
      message: 'РћС€РёР±РєР° РїСЂРё РґРѕР±Р°РІР»РµРЅРёРё РІ РёР·Р±СЂР°РЅРЅРѕРµ',
      adId: props.ad.id,
      originalError: error
    }
    handleError(adError)
  } finally {
    state.value.isProcessingFavorite = false
  }
}

const contactMaster = (): void => {
  if (state.value.isContactingMaster) {
    return
  }
  
  try {
    state.value.isContactingMaster = true
    
    if (props.ad.phone && props.ad.show_contacts) {
      const cleanPhone = props.ad.phone.replace(/\D/g, '')
      
      if (cleanPhone.length >= 10) {
        window.location.href = `tel:${cleanPhone}`
        toast.info('РћС‚РєСЂС‹РІР°СЋ РїСЂРёР»РѕР¶РµРЅРёРµ РґР»СЏ Р·РІРѕРЅРєР°...')
      } else {
        toast.error('РќРµРєРѕСЂСЂРµРєС‚РЅС‹Р№ РЅРѕРјРµСЂ С‚РµР»РµС„РѕРЅР°')
      }
    } else {
      toast.info('РљРѕРЅС‚Р°РєС‚С‹ Р±СѓРґСѓС‚ РґРѕСЃС‚СѓРїРЅС‹ РїРѕСЃР»Рµ Р·Р°РїРёСЃРё')
    }
  } catch (error: unknown) {
    const adError: AdCardError = {
      type: 'contact',
      message: 'РћС€РёР±РєР° РїСЂРё РѕР±СЂР°С‰РµРЅРёРё Рє РјР°СЃС‚РµСЂСѓ',
      adId: props.ad.id,
      originalError: error
    }
    handleError(adError)
  } finally {
    setTimeout(() => {
      state.value.isContactingMaster = false
    }, 1000)
  }
}

const openBooking = (): void => {
  if (state.value.isOpeningBooking) {
    return
  }
  
  try {
    state.value.isOpeningBooking = true
    
    const url = `/ads/${props.ad.id}?booking=true`
    router.visit(url)
    
    // РћС‚РєСЂС‹С‚РёРµ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ
  } catch (error: unknown) {
    const adError: AdCardError = {
      type: 'booking',
      message: 'РћС€РёР±РєР° РїСЂРё РѕС‚РєСЂС‹С‚РёРё Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ',
      adId: props.ad.id,
      originalError: error
    }
    handleError(adError)
  } finally {
    setTimeout(() => {
      state.value.isOpeningBooking = false
    }, 500)
  }
}

// РћР±СЂР°Р±РѕС‚РєР° РѕС€РёР±РѕРє
const handleError = (error: AdCardError): void => {
  logger.error('AdCardListItem Error [${error.type}]:', undefined, { metadata: {
    message: error.message,
    adId: error.adId,
    originalError: error.originalError,
    timestamp: new Date().toISOString()
  } })
  
  toast.error(error.message)
}

// Р’СЃРїРѕРјРѕРіР°С‚РµР»СЊРЅС‹Рµ РјРµС‚РѕРґС‹
const getServiceName = (service: AdService | string): string => {
  if (typeof service === 'string') {
    return service
  }
  return service.name || 'РЈСЃР»СѓРіР°'
}

const getServiceKey = (service: AdService | string, index: number): string | number => {
  if (typeof service === 'object' && service.id) {
    return service.id
  }
  return index
}
</script>
