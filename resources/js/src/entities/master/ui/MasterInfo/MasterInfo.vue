<!-- resources/js/src/entities/master/ui/MasterInfo/MasterInfo.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Р—Р°РіРѕР»РѕРІРѕРє -->
    <div :class="HEADER_CLASSES">
      <h1 :class="NAME_CLASSES">
        {{ master.display_name || master.name }}
      </h1>
      
      <!-- Р‘РµР№РґР¶Рё -->
      <div :class="BADGES_CONTAINER_CLASSES">
        <span v-if="master.is_premium" :class="PREMIUM_BADGE_CLASSES">
          РџР Р•РњРРЈРњ
        </span>
        <span v-if="master.is_verified" :class="VERIFIED_BADGE_CLASSES">
          <svg :class="VERIFIED_ICON_CLASSES" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
          </svg>
          РџСЂРѕРІРµСЂРµРЅРѕ
        </span>
        <span v-if="master.is_featured" :class="FEATURED_BADGE_CLASSES">
          РџРћРџРЈР›РЇР РќР«Р™
        </span>
      </div>
    </div>

    <!-- Р РµР№С‚РёРЅРі Рё СЃС‚Р°С‚СѓСЃ -->
    <div :class="RATING_SECTION_CLASSES">
      <div :class="RATING_CONTAINER_CLASSES">
        <div :class="STARS_CONTAINER_CLASSES">
          <svg 
            v-for="i in 5" 
            :key="i"
            :class="getStarClasses(i)"
            fill="currentColor" 
            viewBox="0 0 20 20"
          >
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
          </svg>
        </div>
        <span :class="RATING_TEXT_CLASSES">
          {{ displayRating }} ({{ formatReviewsCount }} {{ getReviewWord() }})
        </span>
      </div>
      
      <div v-if="master.is_online || master.is_available_now" :class="STATUS_CONTAINER_CLASSES">
        <span :class="STATUS_DOT_CLASSES" />
        {{ master.is_available_now ? 'Р”РѕСЃС‚СѓРїРµРЅ СЃРµР№С‡Р°СЃ' : 'Р’ СЃРµС‚Рё' }}
      </div>
    </div>

    <!-- РћСЃРЅРѕРІРЅС‹Рµ РґР°РЅРЅС‹Рµ -->
    <div :class="INFO_GRID_CLASSES">
      <!-- Р›РѕРєР°С†РёСЏ -->
      <div v-if="displayLocation" :class="INFO_ITEM_CLASSES">
        <svg
          :class="INFO_ICON_CLASSES"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
          />
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
          />
        </svg>
        <span>{{ displayLocation }}</span>
      </div>
      
      <!-- РћРїС‹С‚ СЂР°Р±РѕС‚С‹ -->
      <div v-if="master.experience_years" :class="INFO_ITEM_CLASSES">
        <svg
          :class="INFO_ICON_CLASSES"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>РћРїС‹С‚ {{ master.experience_years }} {{ getYearWord(master.experience_years) }}</span>
      </div>
      
      <!-- РџСЂРѕСЃРјРѕС‚СЂС‹ -->
      <div v-if="master.views_count" :class="INFO_ITEM_CLASSES">
        <svg
          :class="INFO_ICON_CLASSES"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
          />
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
          />
        </svg>
        <span>{{ formatViews(master.views_count) }} РїСЂРѕСЃРјРѕС‚СЂРѕРІ</span>
      </div>
      
      <!-- РњРµС‚СЂРѕ -->
      <div v-if="master.metro_station" :class="INFO_ITEM_CLASSES">
        <svg
          :class="INFO_ICON_CLASSES"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>РњРµС‚СЂРѕ {{ master.metro_station }}</span>
      </div>

      <!-- Р’РѕР·СЂР°СЃС‚ -->
      <div v-if="master.age" :class="INFO_ITEM_CLASSES">
        <svg
          :class="INFO_ICON_CLASSES"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
          />
        </svg>
        <span>{{ master.age }} {{ getAgeWord(master.age) }}</span>
      </div>

      <!-- РџРѕСЃР»РµРґРЅСЏСЏ Р°РєС‚РёРІРЅРѕСЃС‚СЊ -->
      <div v-if="master.last_activity_at" :class="INFO_ITEM_CLASSES">
        <svg
          :class="INFO_ICON_CLASSES"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <span>{{ formatLastActivity }}</span>
      </div>
    </div>

    <!-- РћРїРёСЃР°РЅРёРµ -->
    <div v-if="master.bio || master.description" :class="DESCRIPTION_CLASSES">
      <h3 :class="DESCRIPTION_TITLE_CLASSES">
        Рћ РјР°СЃС‚РµСЂРµ
      </h3>
      <p :class="DESCRIPTION_TEXT_CLASSES">
        {{ master.bio || master.description }}
      </p>
    </div>

    <!-- РЎРїРµС†РёР°Р»РёР·Р°С†РёСЏ -->
    <div v-if="master.specialty" :class="SPECIALTY_CLASSES">
      <h3 :class="SPECIALTY_TITLE_CLASSES">
        РЎРїРµС†РёР°Р»РёР·Р°С†РёСЏ
      </h3>
      <p :class="SPECIALTY_TEXT_CLASSES">
        {{ master.specialty }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import 'dayjs/locale/ru'

dayjs.extend(relativeTime)
dayjs.locale('ru')

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CONTAINER_CLASSES = 'space-y-6'
const HEADER_CLASSES = 'flex items-start justify-between gap-4'
const NAME_CLASSES = 'text-2xl font-bold text-gray-500'
const BADGES_CONTAINER_CLASSES = 'flex flex-wrap gap-2'
const PREMIUM_BADGE_CLASSES = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800'
const VERIFIED_BADGE_CLASSES = 'inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800'
const VERIFIED_ICON_CLASSES = 'w-3 h-3'
const FEATURED_BADGE_CLASSES = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800'
const RATING_SECTION_CLASSES = 'flex items-center justify-between'
const RATING_CONTAINER_CLASSES = 'flex items-center gap-2'
const STARS_CONTAINER_CLASSES = 'flex gap-0.5'
const STAR_CLASSES = 'w-5 h-5'
const STAR_FILLED_CLASSES = 'text-yellow-400'
const STAR_EMPTY_CLASSES = 'text-gray-500'
const RATING_TEXT_CLASSES = 'text-sm font-medium text-gray-500'
const STATUS_CONTAINER_CLASSES = 'flex items-center gap-1.5 text-sm text-green-600'
const STATUS_DOT_CLASSES = 'w-2 h-2 bg-green-500 rounded-full animate-pulse'
const INFO_GRID_CLASSES = 'grid grid-cols-1 md:grid-cols-2 gap-3'
const INFO_ITEM_CLASSES = 'flex items-center gap-2 text-sm text-gray-500'
const INFO_ICON_CLASSES = 'w-4 h-4 text-gray-500 flex-shrink-0'
const DESCRIPTION_CLASSES = 'space-y-2'
const DESCRIPTION_TITLE_CLASSES = 'text-lg font-semibold text-gray-500'
const DESCRIPTION_TEXT_CLASSES = 'text-gray-500 leading-relaxed'
const SPECIALTY_CLASSES = 'space-y-2'
const SPECIALTY_TITLE_CLASSES = 'text-lg font-semibold text-gray-500'
const SPECIALTY_TEXT_CLASSES = 'text-gray-500 font-medium'

const props = defineProps({
    master: {
        type: Object,
        required: true
    }
})

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const displayRating = computed(() => {
    return props.master.rating ? Number(props.master.rating).toFixed(1) : '5.0'
})

const formatReviewsCount = computed(() => {
    return props.master.reviews_count || 0
})

const displayLocation = computed(() => {
    const parts = []
  
    if (props.master.city) parts.push(props.master.city)
    if (props.master.district) parts.push(props.master.district)
  
    return parts.join(', ')
})

const formatLastActivity = computed(() => {
    if (!props.master.last_activity_at) return ''
  
    const lastActivity = dayjs(props.master.last_activity_at)
    const now = dayjs()
    const diffMinutes = now.diff(lastActivity, 'minute')
  
    if (diffMinutes < 60) {
        return `${diffMinutes} РјРёРЅ. РЅР°Р·Р°Рґ`
    } else if (diffMinutes < 1440) { // 24 С‡Р°СЃР°
        return `${Math.floor(diffMinutes / 60)} С‡. РЅР°Р·Р°Рґ`
    } else {
        return lastActivity.format('DD.MM.YYYY')
    }
})

// РњРµС‚РѕРґС‹
const getStarClasses = (starNumber) => {
    const rating = props.master.rating || 0
    const isActive = starNumber <= Math.round(rating)
  
    return [
        STAR_CLASSES,
        isActive ? STAR_FILLED_CLASSES : STAR_EMPTY_CLASSES
    ].join(' ')
}

const getReviewWord = () => {
    const count = formatReviewsCount.value
  
            if (count === 0) return 'отзывов'
  
    const lastDigit = count % 10
    const lastTwoDigits = count % 100
  
    if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
        return 'отзывов'
    }
  
            if (lastDigit === 1) return 'отзыв'
            if (lastDigit >= 2 && lastDigit <= 4) return 'отзыва'
            return 'отзывов'
}

const getYearWord = (years) => {
    if (!years) return 'Р»РµС‚'
  
    const lastDigit = years % 10
    const lastTwoDigits = years % 100
  
    if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
        return 'Р»РµС‚'
    }
  
    if (lastDigit === 1) return 'РіРѕРґ'
    if (lastDigit >= 2 && lastDigit <= 4) return 'РіРѕРґР°'
    return 'Р»РµС‚'
}

const getAgeWord = (age) => {
    if (!age) return 'Р»РµС‚'
  
    const lastDigit = age % 10
    const lastTwoDigits = age % 100
  
    if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
        return 'Р»РµС‚'
    }
  
    if (lastDigit === 1) return 'РіРѕРґ'
    if (lastDigit >= 2 && lastDigit <= 4) return 'РіРѕРґР°'
    return 'Р»РµС‚'
}

const formatViews = (views) => {
    if (!views) return '0'
  
    if (views >= 1000000) {
        return `${(views / 1000000).toFixed(1)}Рњ`
    } else if (views >= 1000) {
        return `${(views / 1000).toFixed(1)}Рљ`
    }
  
    return views.toString()
}
</script>
