<!-- resources/js/src/entities/master/ui/MasterInfo/MasterParameters.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h3 :class="TITLE_CLASSES">Параметры</h3>
    
    <div :class="PARAMETERS_GRID_CLASSES">
      <!-- Возраст -->
      <div v-if="master.age" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Возраст:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.age }} {{ getAgeWord(master.age) }}</span>
      </div>

      <!-- Рост -->
      <div v-if="master.height" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Рост:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.height }} см</span>
      </div>

      <!-- Вес -->
      <div v-if="master.weight" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Вес:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.weight }} кг</span>
      </div>

      <!-- Размер груди -->
      <div v-if="master.breast_size" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Размер груди:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.breast_size }}</span>
      </div>

      <!-- Цвет волос -->
      <div v-if="master.hair_color" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Цвет волос:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ getHairColorLabel(master.hair_color) }}</span>
      </div>

      <!-- Цвет глаз -->
      <div v-if="master.eye_color" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Цвет глаз:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ getEyeColorLabel(master.eye_color) }}</span>
      </div>

      <!-- Национальность -->
      <div v-if="master.nationality" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Национальность:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.nationality }}</span>
      </div>

      <!-- Внешность -->
      <div v-if="master.appearance" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Телосложение:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ getAppearanceLabel(master.appearance) }}</span>
      </div>

      <!-- Опыт работы -->
      <div v-if="master.experience" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Опыт:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.experience }}</span>
      </div>

      <!-- Образование -->
      <div v-if="master.education" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Образование:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.education }}</span>
      </div>

      <!-- Языки -->
      <div v-if="master.languages && master.languages.length > 0" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Языки:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.languages.join(', ') }}</span>
      </div>
    </div>

    <!-- Дополнительные особенности -->
    <div v-if="hasFeatures" :class="FEATURES_SECTION_CLASSES">
      <h4 :class="FEATURES_TITLE_CLASSES">Особенности</h4>
      <div :class="FEATURES_LIST_CLASSES">
        <span
          v-for="feature in displayFeatures"
          :key="feature"
          :class="FEATURE_TAG_CLASSES"
        >
          {{ feature }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'space-y-4'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-900'
const PARAMETERS_GRID_CLASSES = 'grid grid-cols-1 sm:grid-cols-2 gap-3'
const PARAMETER_ITEM_CLASSES = 'flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0'
const PARAMETER_LABEL_CLASSES = 'text-sm text-gray-600'
const PARAMETER_VALUE_CLASSES = 'text-sm font-medium text-gray-900'
const FEATURES_SECTION_CLASSES = 'mt-6 space-y-3'
const FEATURES_TITLE_CLASSES = 'text-base font-medium text-gray-900'
const FEATURES_LIST_CLASSES = 'flex flex-wrap gap-2'
const FEATURE_TAG_CLASSES = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800'

// Словари для лейблов
const HAIR_COLOR_LABELS = {
  blonde: 'Блондинка',
  brunette: 'Брюнетка',
  redhead: 'Рыжая',
  black: 'Черные',
  brown: 'Каштановые',
  gray: 'Седые',
  other: 'Другой'
}

const EYE_COLOR_LABELS = {
  blue: 'Голубые',
  green: 'Зеленые',
  brown: 'Карие',
  gray: 'Серые',
  hazel: 'Ореховые',
  black: 'Черные',
  other: 'Другой'
}

const APPEARANCE_LABELS = {
  slim: 'Стройная',
  athletic: 'Спортивная',
  curvy: 'С формами',
  plus_size: 'Пышная',
  average: 'Обычное',
  other: 'Другое'
}

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})

// Вычисляемые свойства
const hasFeatures = computed(() => {
  return displayFeatures.value.length > 0
})

const displayFeatures = computed(() => {
  const features = []
  
  // Обрабатываем features как объект
  if (props.master.features && typeof props.master.features === 'object') {
    Object.entries(props.master.features).forEach(([key, value]) => {
      if (value === true || value === 'true') {
        features.push(getFeatureLabel(key))
      }
    })
  }
  
  // Добавляем дополнительные особенности
  if (props.master.additional_features) {
    const additional = props.master.additional_features.split(',').map(f => f.trim()).filter(Boolean)
    features.push(...additional)
  }
  
  return features
})

// Методы
const getAgeWord = (age) => {
  if (!age) return 'лет'
  
  const lastDigit = age % 10
  const lastTwoDigits = age % 100
  
  if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
    return 'лет'
  }
  
  if (lastDigit === 1) return 'год'
  if (lastDigit >= 2 && lastDigit <= 4) return 'года'
  return 'лет'
}

const getHairColorLabel = (color) => {
  return HAIR_COLOR_LABELS[color] || color
}

const getEyeColorLabel = (color) => {
  return EYE_COLOR_LABELS[color] || color
}

const getAppearanceLabel = (appearance) => {
  return APPEARANCE_LABELS[appearance] || appearance
}

const getFeatureLabel = (feature) => {
  const featureLabels = {
    massage_classic: 'Классический массаж',
    massage_relax: 'Расслабляющий массаж',
    massage_sport: 'Спортивный массаж',
    massage_therapeutic: 'Лечебный массаж',
    massage_anti_cellulite: 'Антицеллюлитный массаж',
    massage_lymphatic: 'Лимфодренажный массаж',
    massage_hot_stone: 'Стоун-терапия',
    massage_aromatherapy: 'Ароматерапия',
    has_girlfriend: 'Работаю с подругой',
    incall_available: 'Принимаю у себя',
    outcall_available: 'Выезжаю к клиенту',
    apartment_service: 'Квартира-студия',
    hotel_service: 'Работаю в отелях',
    sauna_service: 'Работаю в саунах',
    accepts_couples: 'Принимаю пары',
    photo_verified: 'Фото проверены',
    real_photos: 'Реальные фото'
  }
  
  return featureLabels[feature] || feature
}
</script>