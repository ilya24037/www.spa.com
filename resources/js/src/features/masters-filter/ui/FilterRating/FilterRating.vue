<!-- resources/js/src/features/masters-filter/ui/FilterRating/FilterRating.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h4 :class="TITLE_CLASSES">Р РµР№С‚РёРЅРі</h4>
    
    <div :class="RATING_OPTIONS_CLASSES">
      <label
        v-for="option in ratingOptions"
        :key="option.value"
        :class="RATING_OPTION_CLASSES"
      >
        <input
          type="radio"
          :value="option.value"
          :checked="value === option.value"
          @change="selectRating(option.value)"
          :class="RADIO_CLASSES"
        >
        <div :class="OPTION_CONTENT_CLASSES">
          <div :class="STARS_CONTAINER_CLASSES">
            <svg
              v-for="i in 5"
              :key="i"
              :class="getStarClasses(i, option.value)"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
          </div>
          <span :class="OPTION_LABEL_CLASSES">{{ option.label }}</span>
        </div>
      </label>
    </div>

    <!-- РљРЅРѕРїРєР° СЃР±СЂРѕСЃР° -->
    <button
      v-if="value !== null"
      @click="clearRating"
      :class="CLEAR_BUTTON_CLASSES"
    >
      РЎР±СЂРѕСЃРёС‚СЊ СЂРµР№С‚РёРЅРі
    </button>
  </div>
</template>

<script setup>
// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CONTAINER_CLASSES = 'space-y-3'
const TITLE_CLASSES = 'font-medium text-gray-900'
const RATING_OPTIONS_CLASSES = 'space-y-2'
const RATING_OPTION_CLASSES = 'flex items-center cursor-pointer hover:bg-gray-50 p-2 rounded-lg transition-colors'
const RADIO_CLASSES = 'mr-3 text-blue-600 focus:ring-blue-500 flex-shrink-0'
const OPTION_CONTENT_CLASSES = 'flex items-center gap-2'
const STARS_CONTAINER_CLASSES = 'flex gap-0.5'
const STAR_CLASSES = 'w-4 h-4'
const STAR_FILLED_CLASSES = 'text-yellow-400'
const STAR_EMPTY_CLASSES = 'text-gray-300'
const OPTION_LABEL_CLASSES = 'text-sm text-gray-700'
const CLEAR_BUTTON_CLASSES = 'text-sm text-red-600 hover:text-red-700 font-medium'

const props = defineProps({
  value: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['update'])

// РћРїС†РёРё СЂРµР№С‚РёРЅРіР°
const ratingOptions = [
  { value: 5, label: 'РўРѕР»СЊРєРѕ 5 Р·РІРµР·Рґ' },
  { value: 4, label: '4 Р·РІРµР·РґС‹ Рё РІС‹С€Рµ' },
  { value: 3, label: '3 Р·РІРµР·РґС‹ Рё РІС‹С€Рµ' },
  { value: 2, label: '2 Р·РІРµР·РґС‹ Рё РІС‹С€Рµ' },
  { value: 1, label: '1 Р·РІРµР·РґР° Рё РІС‹С€Рµ' }
]

// РњРµС‚РѕРґС‹
const getStarClasses = (starNumber, rating) => {
  const isActive = starNumber <= rating
  
  return [
    STAR_CLASSES,
    isActive ? STAR_FILLED_CLASSES : STAR_EMPTY_CLASSES
  ].join(' ')
}

const selectRating = (rating) => {
  emit('update', rating)
}

const clearRating = () => {
  emit('update', null)
}
</script>

