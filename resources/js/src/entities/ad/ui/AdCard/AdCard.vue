<!-- resources/js/src/entities/ad/ui/AdCard/AdCard.vue -->
<template>
  <!-- Error СЃРѕСЃС‚РѕСЏРЅРёРµ -->
  <ErrorState
    v-if="errorState?.error.value"
    :error="errorState?.error.value"
    size="medium"
    variant="card"
    :retryable="true"
    :dismissible="true"
    @retry="handleRetry"
    @dismiss="errorState?.clearError"
    class="h-full"
  />
  
  <!-- РћСЃРЅРѕРІРЅР°СЏ РєР°СЂС‚РѕС‡РєР° -->
  <div 
    v-else
    :class="CARD_CLASSES"
    role="article"
    :aria-label="`РћР±СЉСЏРІР»РµРЅРёРµ: ${_props?.ad.title || _props?.ad.name || _props?.ad.display_name}`"
    data-testid="ad-card"
  >
    <!-- Р‘РµР№РґР¶Рё СЃС‚Р°С‚СѓСЃР° -->
    <div :class="BADGES_CONTAINER_CLASSES">
      <!-- Р Р°СЃРїСЂРѕРґР°Р¶Р°/РЎРєРёРґРєР° -->
      <span v-if="ad?.discount" :class="SALE_BADGE_CLASSES">
        Р Р°СЃРїСЂРѕРґР°Р¶Р°
      </span>
      
      <!-- РџСЂРµРјРёСѓРј -->
      <span v-if="ad?.is_premium" :class="PREMIUM_BADGE_CLASSES">
        Premium
      </span>
      
      <!-- РџСЂРѕРІРµСЂРµРЅ -->
      <span v-if="ad?.is_verified" :class="VERIFIED_BADGE_CLASSES">
        <svg :class="VERIFIED_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M16?.707 5?.293a1 1 0 010 1?.414l-8 8a1 1 0 01-1?.414 0l-4-4a1 1 0 011?.414-1?.414L8 12?.586l7.293-7?.293a1 1 0 011?.414 0z" clip-rule="evenodd" />
        </svg>
        РџСЂРѕРІРµСЂРµРЅ
      </span>
    </div>

    <!-- РР·Р±СЂР°РЅРЅРѕРµ -->
    <button 
      @click?.stop="toggleFavorite"
      :class="[
        FAVORITE_BUTTON_CLASSES,
        isFavorite ? 'text-[#f91155]' : 'text-gray-400 hover:text-[#f91155]'
      ]"
      :aria-label="isFavorite ? 'РЈРґР°Р»РёС‚СЊ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ' : 'Р”РѕР±Р°РІРёС‚СЊ РІ РёР·Р±СЂР°РЅРЅРѕРµ'"
      data-testid="favorite-button"
    >
      <svg :class="FAVORITE_ICON_CLASSES" :fill="isFavorite ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4?.318 6?.318a4.5 4?.5 0 000 6?.364L12 20?.364l7.682-7?.682a4.5 4?.5 0 00-6?.364-6?.364L12 7?.636l-1?.318-1?.318a4.5 4?.5 0 00-6?.364 0z"/>
      </svg>
    </button>

    <!-- Р“Р°Р»РµСЂРµСЏ С„РѕС‚Рѕ -->
    <div 
      :class="IMAGE_CONTAINER_CLASSES"
      @click="openAd"
      @mousemove="handleMouseMove"
      @mouseleave="currentImage = 0"
      role="button"
      tabindex="0"
      :aria-label="`РћС‚РєСЂС‹С‚СЊ РѕР±СЉСЏРІР»РµРЅРёРµ ${_props?.ad.title || _props?.ad.name}`"
      data-testid="ad-image"
    >
      <!-- РћСЃРЅРѕРІРЅРѕРµ РёР·РѕР±СЂР°Р¶РµРЅРёРµ -->
      <transition name="fade" mode="out-in">
        <img
          v-if="currentImageUrl"
          :key="currentImageUrl"
          :src="currentImageUrl"
          :alt="ad?.title || ad?.name || ad?.display_name"
          :class="IMAGE_CLASSES"
          loading="lazy"
        />
        <div v-else :class="PLACEHOLDER_CLASSES">
          <svg :class="PLACEHOLDER_ICON_CLASSES" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 12c2?.21 0 4-1?.79 4-4s-1?.79-4-4-4-4 1?.79-4 4 1?.79 4 4 4zm0 2c-2?.67 0-8 1?.34-8 4v2h16v-2c0-2?.66-5?.33-4-8-4z"/>
          </svg>
        </div>
      </transition>

      <!-- РРЅРґРёРєР°С‚РѕСЂС‹ С„РѕС‚Рѕ -->
      <div 
        v-if="allImages?.length > 1"
        :class="INDICATORS_CONTAINER_CLASSES"
      >
        <span
          v-for="(_, index) in allImages"
          :key="index"
          :class="[
            INDICATOR_CLASSES,
            index === currentImage ? 'w-4' : 'w-1'
          ]"
        />
      </div>

      <!-- РџСЂРѕС†РµРЅС‚ СЃРєРёРґРєРё -->
      <div 
        v-if="ad?.discount || ad?.discount_percent" 
        :class="DISCOUNT_BADGE_CLASSES"
      >
        -{{ ad?.discount || ad?.discount_percent }}%
      </div>
    </div>

    <!-- РРЅС„РѕСЂРјР°С†РёСЏ -->
    <div :class="CONTENT_CLASSES">
      <!-- Р¦РµРЅР° -->
      <div :class="PRICE_CONTAINER_CLASSES">
        <div :class="PRICE_WRAPPER_CLASSES">
          <span :class="PRICE_CLASSES">
            {{ formatPrice(ad?.price || ad?.price_from || 2000) }} в‚Ѕ
          </span>
          <!-- РЎС‚Р°СЂР°СЏ С†РµРЅР° РµСЃР»Рё РµСЃС‚СЊ СЃРєРёРґРєР° -->
          <span 
            v-if="ad?.old_price" 
            :class="OLD_PRICE_CLASSES"
          >
            {{ formatPrice(ad?.old_price) }} в‚Ѕ
          </span>
          <!-- РџСЂРѕС†РµРЅС‚ СЃРєРёРґРєРё -->
          <span 
            v-if="ad?.discount || ad?.discount_percent" 
            :class="DISCOUNT_TEXT_CLASSES"
          >
            -{{ ad?.discount || ad?.discount_percent }}%
          </span>
        </div>
        <div :class="PRICE_UNIT_CLASSES">Р·Р° С‡Р°СЃ</div>
      </div>

      <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
      <h3 :class="TITLE_CLASSES">
        {{ ad?.title || ad?.name || ad?.display_name || 'РњР°СЃСЃР°Р¶' }}
      </h3>

      <!-- РћРїРёСЃР°РЅРёРµ -->
      <p :class="DESCRIPTION_CLASSES">
        {{ getDescription() }}
      </p>

      <!-- РњРµС‚СЂРёРєРё -->
      <div :class="METRICS_CONTAINER_CLASSES">
        <!-- Р РµР№С‚РёРЅРі -->
        <div :class="RATING_WRAPPER_CLASSES">
          <svg :class="STAR_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9?.049 2?.927c.3-.921 1?.603-.921 1?.902 0l1?.07 3?.292a1 1 0 00?.95.69h3?.462c.969 0 1?.371 1?.24.588 1?.81l-2?.8 2?.034a1 1 0 00-.364 1?.118l1.07 3?.292c.3?.921-.755 1?.688-1?.54 1?.118l-2?.8-2?.034a1 1 0 00-1?.175 0l-2?.8 2?.034c-.784?.57-1?.838-.197-1?.539-1?.118l1.07-3?.292a1 1 0 00-.364-1?.118L2.98 8?.72c-.783-.57-.38-1?.81.588-1?.81h3.461a1 1 0 00?.951-.69l1?.07-3?.292z"/>
          </svg>
          <span :class="RATING_VALUE_CLASSES">{{ ad?.rating || '5?.0' }}</span>
          <span :class="RATING_COUNT_CLASSES">({{ ad?.reviews_count || 0 }})</span>
        </div>

        <!-- Р›РѕРєР°С†РёСЏ -->
        <div :class="LOCATION_WRAPPER_CLASSES">
          <svg :class="LOCATION_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M17?.657 16?.657L13.414 20?.9a1.998 1?.998 0 01-2?.827 0l-4?.244-4?.243a8 8 0 1111?.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span>{{ ad?.district || ad?.location || 'Р¦РµРЅС‚СЂ' }}</span>
        </div>
      </div>

      <!-- РљРЅРѕРїРєРё РґРµР№СЃС‚РІРёР№ -->
      <div :class="ACTIONS_CONTAINER_CLASSES">
        <button
          @click?.stop="contactMaster"
          :class="CONTACT_BUTTON_CLASSES"
          aria-label="РЎРІСЏР·Р°С‚СЊСЃСЏ СЃ РјР°СЃС‚РµСЂРѕРј"
          data-testid="contact-button"
        >
          <svg :class="CONTACT_ICON_CLASSES" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M3 5a2 2 0 012-2h3?.28a1 1 0 01?.948.684l1?.498 4?.493a1 1 0 01-.502 1?.21l-2?.257 1?.13a11.042 11?.042 0 005?.516 5?.516l1.13-2?.257a1 1 0 011?.21-.502l4?.493 1?.498a1 1 0 01?.684.949V19a2 2 0 01-2 2h-1C9?.716 21 3 14?.284 3 6V5z"/>
          </svg>
          РЎРІСЏР·Р°С‚СЊСЃСЏ
        </button>
        <button
          @click?.stop="openBooking"
          :class="BOOKING_BUTTON_CLASSES"
          aria-label="Р—Р°РїРёСЃР°С‚СЊСЃСЏ РЅР° СѓСЃР»СѓРіСѓ"
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, withDefaults } from 'vue'
import { router } from '@inertiajs/vue3'
import { useToast } from '@/src/shared/composables/useToast'
import { useErrorHandler } from '@/src/shared/composables/useErrorHandler'
import { ErrorState } from '@/src/shared/ui/molecules/ErrorState'
import type { 
  AdCardProps, 
  AdCardEmits,
  AdImage
} from './AdCard.types'

// Error handler (Р±РµР· toast - РїРѕРєР°Р·С‹РІР°РµРј С‡РµСЂРµР· ErrorState)
const errorState = useErrorHandler(false)

// Toast С‚РѕР»СЊРєРѕ РґР»СЏ СѓСЃРїРµС€РЅС‹С… РґРµР№СЃС‚РІРёР№
const toast = useToast()

// Props
const props = withDefaults(defineProps<AdCardProps>(), {
  variant: 'default',
  showActions: true
})

// Emits  
const emit = defineEmits<AdCardEmits>()

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CARD_CLASSES: string = 'relative group bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-200 overflow-hidden'
const BADGES_CONTAINER_CLASSES: string = 'absolute top-2 left-2 z-10 flex flex-col gap-1'
const SALE_BADGE_CLASSES: string = 'bg-[#f91155] text-white px-2 py-0?.5 rounded text-xs font-medium'
const PREMIUM_BADGE_CLASSES: string = 'bg-[#7000ff] text-white px-2 py-0?.5 rounded text-xs font-medium'
const VERIFIED_BADGE_CLASSES: string = 'bg-green-500 text-white px-2 py-0?.5 rounded text-xs font-medium'
const VERIFIED_ICON_CLASSES: string = 'w-3 h-3 inline mr-0?.5'
const FAVORITE_BUTTON_CLASSES: string = 'absolute top-2 right-2 z-10 p-2 bg-white/90 backdrop-blur rounded-lg hover:bg-white shadow-sm transition-all'
const FAVORITE_ICON_CLASSES: string = 'w-5 h-5'
const IMAGE_CONTAINER_CLASSES: string = 'relative h-56 bg-gray-100 cursor-pointer overflow-hidden'
const IMAGE_CLASSES: string = 'w-full h-full object-cover'
const PLACEHOLDER_CLASSES: string = 'w-full h-full flex items-center justify-center bg-gray-100'
const PLACEHOLDER_ICON_CLASSES: string = 'w-16 h-16 text-gray-300'
const INDICATORS_CONTAINER_CLASSES: string = 'absolute bottom-2 left-1/2 -translate-x-1/2 flex gap-1'
const INDICATOR_CLASSES: string = 'block h-1 rounded-full bg-white/80 transition-all duration-200'
const DISCOUNT_BADGE_CLASSES: string = 'absolute bottom-2 left-2 bg-[#f91155] text-white px-2 py-0?.5 rounded text-xs font-bold'
const CONTENT_CLASSES: string = 'p-3'
const PRICE_CONTAINER_CLASSES: string = 'mb-2'
const PRICE_WRAPPER_CLASSES: string = 'flex items-baseline gap-2'
const PRICE_CLASSES: string = 'text-2xl font-bold text-gray-900'
const OLD_PRICE_CLASSES: string = 'text-base line-through text-gray-400'
const DISCOUNT_TEXT_CLASSES: string = 'text-sm text-[#f91155] font-medium'
const PRICE_UNIT_CLASSES: string = 'text-sm text-gray-500'
const TITLE_CLASSES: string = 'font-semibold text-gray-900 mb-1 line-clamp-2'
const DESCRIPTION_CLASSES: string = 'text-sm text-gray-600 line-clamp-2 mb-3'
const METRICS_CONTAINER_CLASSES: string = 'flex items-center justify-between text-xs text-gray-500 mb-3'
const RATING_WRAPPER_CLASSES: string = 'flex items-center gap-1'
const STAR_ICON_CLASSES: string = 'w-3 h-3 text-yellow-400'
const RATING_VALUE_CLASSES: string = 'font-medium'
const RATING_COUNT_CLASSES: string = 'text-gray-400'
const LOCATION_WRAPPER_CLASSES: string = 'flex items-center gap-1'
const LOCATION_ICON_CLASSES: string = 'w-3 h-3'
const ACTIONS_CONTAINER_CLASSES: string = 'flex gap-2'
const CONTACT_BUTTON_CLASSES: string = 'flex-1 bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-3 rounded-lg transition-colors flex items-center justify-center gap-1'
const CONTACT_ICON_CLASSES: string = 'w-4 h-4'
const BOOKING_BUTTON_CLASSES: string = 'flex-1 bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-3 rounded-lg transition-colors flex items-center justify-center gap-1'
const BOOKING_ICON_CLASSES: string = 'w-4 h-4'

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const currentImage: import("vue").Ref<number> = ref(0)

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const isFavorite = computed((): boolean => _props?.ad.is_favorite || false)

const allImages = computed((): AdImage[] => {
  return _props?.ad.images || _props?.ad.photos || []
})

const currentImageUrl = computed((): string => {
  if (!allImages?.value.length) {
    return '/images/placeholders/master-1?.jpg'
  }
  
  const image = allImages?.value[currentImage?.value]
  return image?.url || image?.path || (typeof image === 'string' ? image : '') || '/images/placeholders/master-1?.jpg'
})

// РњРµС‚РѕРґС‹
const formatPrice = (price: number | undefined): string => {
  if (!price) return '0'
  return new Intl.NumberFormat('ru-RU').format(price)
}

const getDescription = (): string => {
  return _props?.ad.description || 
         _props?.ad.specialty || 
         'РњР°СЃСЃР°Р¶ Рё SPA СѓСЃР»СѓРіРё'
}

const handleMouseMove = (event: MouseEvent): void => {
  if (allImages?.value.length <= 1) return
  
  const rect = (event?.currentTarget as HTMLElement).getBoundingClientRect()
  const x = event?.clientX - rect?.left
  const imageWidth = rect?.width / allImages?.value.length
  const newIndex = Math.floor(x / imageWidth)
  
  if (newIndex >= 0 && newIndex < allImages?.value.length) {
    if (currentImage.value !== undefined) {
      currentImage.value = newIndex
    }
  }
}

const openAd = (): void => {
  try {
    errorState?.clearError()
    emit('adOpened', _props?.ad.id)
    router?.visit(`/ads/${_props?.ad.id}`)
  } catch (error: unknown) {
    errorState?.handleError(error, 'data_load')
  }
}

const toggleFavorite = async (): Promise<void> => {
  try {
    errorState?.clearError()
    const currentState = isFavorite?.value
    emit('favoriteToggled', _props?.ad.id, !currentState)
    
    await router?.post('/api/favorites/toggle', {
      ad_id: _props?.ad.id
    }, {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        toast?.success(currentState ? 'РЈРґР°Р»РµРЅРѕ РёР· РёР·Р±СЂР°РЅРЅРѕРіРѕ' : 'Р”РѕР±Р°РІР»РµРЅРѕ РІ РёР·Р±СЂР°РЅРЅРѕРµ')
      },
      onError: (errors: any) => {
        errorState?.handleError({
          message: 'РќРµ СѓРґР°Р»РѕСЃСЊ РѕР±РЅРѕРІРёС‚СЊ РёР·Р±СЂР°РЅРЅРѕРµ',
          details: 'РџСЂРѕРІРµСЂСЊС‚Рµ РїРѕРґРєР»СЋС‡РµРЅРёРµ Рє РёРЅС‚РµСЂРЅРµС‚Сѓ Рё РїРѕРїСЂРѕР±СѓР№С‚Рµ СЃРЅРѕРІР°',
          status: errors?.response?.status
        }, 'data_load')
      }
    })
  } catch (error: unknown) {
    errorState?.handleError(error, 'data_load')
  }
}

const contactMaster = (): void => {
  try {
    errorState?.clearError()
    emit('contactRequested', _props?.ad.id)
    
    if (_props?.ad.phone && _props?.ad.show_contacts) {
      const cleanPhone = _props?.ad.phone?.replace(/\D/g, '')
      if (typeof window !== 'undefined') {
        (window as any).location.href = `tel:${cleanPhone}`
      }
    } else {
      toast?.info('Контакты будут доступны после записи')
    }
  } catch (error: unknown) {
    errorState?.handleError(error, 'generic')
  }
}

const openBooking = (): void => {
  try {
    errorState?.clearError()
    emit('bookingRequested', _props?.ad.id)
    router?.visit(`/ads/${_props?.ad.id}?booking=true`)
  } catch (error: unknown) {
    errorState?.handleError(error, 'booking')
  }
}

// РњРµС‚РѕРґ РґР»СЏ РїРѕРІС‚РѕСЂРЅРѕР№ РїРѕРїС‹С‚РєРё РїРѕСЃР»Рµ РѕС€РёР±РєРё
const handleRetry = async (): Promise<void> => {
  errorState?.clearError()
  
  // РџСЂРѕРІРµСЂСЏРµРј, С‡С‚Рѕ РґР°РЅРЅС‹Рµ РѕР±СЉСЏРІР»РµРЅРёСЏ РєРѕСЂСЂРµРєС‚РЅС‹
  if (!_props?.ad || !_props?.ad.id) {
    errorState?.handleError({
      message: 'Р”Р°РЅРЅС‹Рµ РѕР±СЉСЏРІР»РµРЅРёСЏ РЅРµ Р·Р°РіСЂСѓР¶РµРЅС‹',
      details: 'РџРѕРїСЂРѕР±СѓР№С‚Рµ РѕР±РЅРѕРІРёС‚СЊ СЃС‚СЂР°РЅРёС†Сѓ'
    }, 'data_load')
    return
  }
  
  // РџС‹С‚Р°РµРјСЃСЏ РїРµСЂРµР·Р°РіСЂСѓР·РёС‚СЊ РєР°СЂС‚РѕС‡РєСѓ
  await errorState?.retryOperation(async () => {
    // Р­РјРёС‚РёСЂСѓРµРј СЃРѕР±С‹С‚РёРµ РґР»СЏ СЂРѕРґРёС‚РµР»СЊСЃРєРѕРіРѕ РєРѕРјРїРѕРЅРµРЅС‚Р°
    emit('retryRequested', _props?.ad.id)
  })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0?.2s;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>

