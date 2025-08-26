<!-- resources/js/src/features/masters-filter/ui/FilterAdditional/FilterAdditional.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h4 :class="TITLE_CLASSES">
      Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅРѕ
    </h4>
    
    <div :class="OPTIONS_CONTAINER_CLASSES">
      <!-- РџСЂРѕРІРµСЂРµРЅРЅС‹Рµ РјР°СЃС‚РµСЂР° -->
      <label :class="OPTION_ITEM_CLASSES">
        <input
          type="checkbox"
          :checked="verified"
          :class="CHECKBOX_CLASSES"
          @change="updateOption('verified', $event.target.checked)"
        >
        <div :class="OPTION_CONTENT_CLASSES">
          <span :class="OPTION_TEXT_CLASSES">РџСЂРѕРІРµСЂРµРЅРЅС‹Рµ РјР°СЃС‚РµСЂР°</span>
          <svg :class="VERIFIED_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
        </div>
      </label>

      <!-- РџСЂРµРјРёСѓРј РјР°СЃС‚РµСЂР° -->
      <label :class="OPTION_ITEM_CLASSES">
        <input
          type="checkbox"
          :checked="premium"
          :class="CHECKBOX_CLASSES"
          @change="updateOption('premium', $event.target.checked)"
        >
        <div :class="OPTION_CONTENT_CLASSES">
          <span :class="OPTION_TEXT_CLASSES">РџСЂРµРјРёСѓРј РјР°СЃС‚РµСЂР°</span>
          <span :class="PREMIUM_BADGE_CLASSES">PREMIUM</span>
        </div>
      </label>

      <!-- РћРЅР»Р°Р№РЅ СЃРµР№С‡Р°СЃ -->
      <label :class="OPTION_ITEM_CLASSES">
        <input
          type="checkbox"
          :checked="online"
          :class="CHECKBOX_CLASSES"
          @change="updateOption('online', $event.target.checked)"
        >
        <div :class="OPTION_CONTENT_CLASSES">
          <span :class="OPTION_TEXT_CLASSES">РћРЅР»Р°Р№РЅ СЃРµР№С‡Р°СЃ</span>
          <span :class="ONLINE_INDICATOR_CLASSES" />
        </div>
      </label>

      <!-- Р’С‹РµР·Рґ РЅР° РґРѕРј -->
      <label :class="OPTION_ITEM_CLASSES">
        <input
          type="checkbox"
          :checked="homeService"
          :class="CHECKBOX_CLASSES"
          @change="updateOption('home_service', $event.target.checked)"
        >
        <div :class="OPTION_CONTENT_CLASSES">
          <span :class="OPTION_TEXT_CLASSES">Р’С‹РµР·Рґ РЅР° РґРѕРј</span>
          <svg
            :class="HOME_ICON_CLASSES"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
            />
          </svg>
        </div>
      </label>

      <!-- РћРЅР»Р°Р№РЅ-Р·Р°РїРёСЃСЊ -->
      <label :class="OPTION_ITEM_CLASSES">
        <input
          type="checkbox"
          :checked="onlineBooking"
          :class="CHECKBOX_CLASSES"
          @change="updateOption('online_booking', $event.target.checked)"
        >
        <div :class="OPTION_CONTENT_CLASSES">
          <span :class="OPTION_TEXT_CLASSES">РћРЅР»Р°Р№РЅ-Р·Р°РїРёСЃСЊ</span>
          <svg
            :class="BOOKING_ICON_CLASSES"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        </div>
      </label>
    </div>

            <!-- Показать активные фильтры -->
    <div v-if="hasActiveFilters" :class="ACTIVE_FILTERS_CLASSES">
      <span :class="ACTIVE_COUNT_CLASSES">
        РђРєС‚РёРІРЅРѕ С„РёР»СЊС‚СЂРѕРІ: {{ activeFiltersCount }}
      </span>
      <button
        :class="CLEAR_ALL_BUTTON_CLASSES"
        @click="clearAllFilters"
      >
        РЎР±СЂРѕСЃРёС‚СЊ РІСЃРµ
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CONTAINER_CLASSES = 'space-y-3'
const TITLE_CLASSES = 'font-medium text-gray-500'
const OPTIONS_CONTAINER_CLASSES = 'space-y-3'
const OPTION_ITEM_CLASSES = 'flex items-center cursor-pointer hover:bg-gray-500 p-2 rounded-lg transition-colors'
const CHECKBOX_CLASSES = 'mr-3 rounded text-blue-600 focus:ring-blue-500 flex-shrink-0'
const OPTION_CONTENT_CLASSES = 'flex items-center justify-between w-full'
const OPTION_TEXT_CLASSES = 'text-sm text-gray-500'
const VERIFIED_ICON_CLASSES = 'w-4 h-4 text-green-500'
const PREMIUM_BADGE_CLASSES = 'text-xs font-medium px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded'
const ONLINE_INDICATOR_CLASSES = 'w-2 h-2 bg-green-400 rounded-full animate-pulse'
const HOME_ICON_CLASSES = 'w-4 h-4 text-gray-500'
const BOOKING_ICON_CLASSES = 'w-4 h-4 text-gray-500'
const ACTIVE_FILTERS_CLASSES = 'flex items-center justify-between p-2 bg-blue-50 border border-blue-200 rounded-lg'
const ACTIVE_COUNT_CLASSES = 'text-sm text-blue-700 font-medium'
const CLEAR_ALL_BUTTON_CLASSES = 'text-xs text-blue-600 hover:text-blue-800 font-medium'

const props = defineProps({
    verified: {
        type: Boolean,
        default: false
    },
    premium: {
        type: Boolean,
        default: false
    },
    online: {
        type: Boolean,
        default: false
    },
    homeService: {
        type: Boolean,
        default: false
    },
    onlineBooking: {
        type: Boolean,
        default: false
    }
})

const emit = defineEmits(['update'])

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const activeFiltersCount = computed(() => {
    return [
        props.verified,
        props.premium,
        props.online,
        props.homeService,
        props.onlineBooking
    ].filter(Boolean).length
})

const hasActiveFilters = computed(() => activeFiltersCount.value > 0)

// РњРµС‚РѕРґС‹
const updateOption = (key, value) => {
    emit('update', { [key]: value })
}

const clearAllFilters = () => {
    emit('update', {
        verified: false,
        premium: false,
        online: false,
        home_service: false,
        online_booking: false
    })
}
</script>

