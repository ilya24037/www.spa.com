<!-- resources/js/src/entities/master/ui/MasterCard/MasterCardListItem.vue -->
<template>
  <div :class="CARD_CLASSES" @click="goToProfile">
    <div :class="CONTAINER_CLASSES">
      <!-- Р¤РѕС‚Рѕ -->
      <div :class="PHOTO_CONTAINER_CLASSES">
        <img
          :src="masterPhoto"
          :alt="master.display_name || master.name"
          :class="PHOTO_CLASSES"
          @error="handleImageError"
        >
      </div>

      <!-- РРЅС„РѕСЂРјР°С†РёСЏ -->
      <div :class="INFO_CONTAINER_CLASSES">
        <div :class="INFO_HEADER_CLASSES">
          <div>
            <h3 :class="NAME_CLASSES">
              {{ master.display_name || master.name }}
            </h3>
            <div :class="RATING_CONTAINER_CLASSES">
              <div :class="RATING_WRAPPER_CLASSES">
                <svg :class="STAR_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                <span :class="RATING_VALUE_CLASSES">{{ master.rating || '4.8' }}</span>
                <span :class="RATING_COUNT_CLASSES">({{ master.reviews_count || 0 }})</span>
              </div>
            </div>
          </div>
          
          <!-- Р¦РµРЅР° -->
          <div :class="PRICE_CONTAINER_CLASSES">
            <div :class="PRICE_CLASSES">
              РѕС‚ {{ formatPrice(master.price_from || master.min_price) }} в‚Ѕ
            </div>
            <div :class="PRICE_UNIT_CLASSES">
              Р·Р° СЃРµР°РЅСЃ
            </div>
          </div>
        </div>

        <!-- РЈСЃР»СѓРіРё -->
        <div :class="SERVICES_CONTAINER_CLASSES">
          <span
            v-for="(service, index) in displayServices"
            :key="service.id || index"
            :class="SERVICE_TAG_CLASSES"
          >
            {{ service.name || service }}
          </span>
          <span
            v-if="hasMoreServices"
            :class="MORE_SERVICES_CLASSES"
          >
            +{{ master.services.length - 3 }}
          </span>
        </div>

        <!-- Р”РµР№СЃС‚РІРёСЏ -->
        <div :class="ACTIONS_CONTAINER_CLASSES">
          <button 
            :class="BOOKING_BUTTON_CLASSES"
            @click.stop="openBooking"
          >
            Р—Р°РїРёСЃР°С‚СЊСЃСЏ
          </button>
          <button 
            :class="FAVORITE_BUTTON_CLASSES"
            @click.stop="toggleFavorite"
          >
            <svg
              :class="FAVORITE_ICON_CLASSES"
              :fill="isFavorite ? 'currentColor' : 'none'"
              stroke="currentColor"
              viewBox="0 0 24 24"
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CARD_CLASSES = 'bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow p-4 cursor-pointer'
const CONTAINER_CLASSES = 'flex gap-4'
const PHOTO_CONTAINER_CLASSES = 'w-24 h-24 flex-shrink-0'
const PHOTO_CLASSES = 'w-full h-full object-cover rounded-lg'
const INFO_CONTAINER_CLASSES = 'flex-1 min-w-0'
const INFO_HEADER_CLASSES = 'flex items-start justify-between'
const NAME_CLASSES = 'font-semibold text-lg'
const RATING_CONTAINER_CLASSES = 'flex items-center gap-2 mt-1'
const RATING_WRAPPER_CLASSES = 'flex items-center'
const STAR_ICON_CLASSES = 'w-4 h-4 text-yellow-400'
const RATING_VALUE_CLASSES = 'text-sm font-medium ml-1'
const RATING_COUNT_CLASSES = 'text-sm text-gray-500 ml-1'
const PRICE_CONTAINER_CLASSES = 'text-right'
const PRICE_CLASSES = 'font-bold text-xl'
const PRICE_UNIT_CLASSES = 'text-sm text-gray-500'
const SERVICES_CONTAINER_CLASSES = 'mt-2 flex flex-wrap gap-1'
const SERVICE_TAG_CLASSES = 'text-xs bg-gray-500 text-gray-500 px-2 py-1 rounded'
const MORE_SERVICES_CLASSES = 'text-xs text-gray-500 px-2 py-1'
const ACTIONS_CONTAINER_CLASSES = 'mt-3 flex items-center gap-3'
const BOOKING_BUTTON_CLASSES = 'flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium'
const FAVORITE_BUTTON_CLASSES = 'p-2 text-gray-500 hover:text-red-500 transition-colors'
const FAVORITE_ICON_CLASSES = 'w-5 h-5'

const props = defineProps({
    master: {
        type: Object,
        required: true
    }
})

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const imageError = ref(false)

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const isFavorite = computed(() => props.master.is_favorite || false)

const masterPhoto = computed(() => {
    if (imageError.value) {
        return '/images/placeholder-avatar.jpg'
    }
    return props.master.avatar || 
         props.master.main_photo || 
         '/images/placeholder-avatar.jpg'
})

const displayServices = computed(() => {
    if (!props.master.services || !Array.isArray(props.master.services)) {
        return []
    }
    return props.master.services.slice(0, 3)
})

const hasMoreServices = computed(() => {
    return props.master.services && props.master.services.length > 3
})

// РњРµС‚РѕРґС‹
const formatPrice = (price) => {
    if (!price) return '0'
    return new Intl.NumberFormat('ru-RU').format(price)
}

const handleImageError = () => {
    imageError.value = true
}

const goToProfile = () => {
    // Используем slug для навигации к профилю пользователя
    const slug = props.master.slug || `user-${props.master.id}`
    router.visit(`/users/${slug}`)
}

const toggleFavorite = () => {
    router.post('/api/favorites/toggle', {
        user_id: props.master.id
    }, {
        preserveState: true,
        preserveScroll: true
    })
}

const openBooking = () => {
    // Используем slug для навигации к профилю пользователя
    const slug = props.master.slug || `user-${props.master.id}`
    router.visit(`/users/${slug}?booking=true`)
}
</script>
