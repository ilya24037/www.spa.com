<!-- resources/js/src/entities/master/ui/MasterInfo/MasterParameters.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h3 :class="TITLE_CLASSES">
      РџР°СЂР°РјРµС‚СЂС‹
    </h3>
    
    <div :class="PARAMETERS_GRID_CLASSES">
      <!-- Р’РѕР·СЂР°СЃС‚ -->
      <div v-if="master.age" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Р’РѕР·СЂР°СЃС‚:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.age }} {{ getAgeWord(master.age) }}</span>
      </div>

      <!-- Р РѕСЃС‚ -->
      <div v-if="master.height" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Р РѕСЃС‚:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.height }} СЃРј</span>
      </div>

      <!-- Р’РµСЃ -->
      <div v-if="master.weight" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Р’РµСЃ:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.weight }} РєРі</span>
      </div>

      <!-- Р Р°Р·РјРµСЂ РіСЂСѓРґРё -->
      <div v-if="master.breast_size" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Р Р°Р·РјРµСЂ РіСЂСѓРґРё:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.breast_size }}</span>
      </div>

      <!-- Р¦РІРµС‚ РІРѕР»РѕСЃ -->
      <div v-if="master.hair_color" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Р¦РІРµС‚ РІРѕР»РѕСЃ:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ getHairColorLabel(master.hair_color) }}</span>
      </div>

      <!-- Р¦РІРµС‚ РіР»Р°Р· -->
      <div v-if="master.eye_color" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">Р¦РІРµС‚ РіР»Р°Р·:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ getEyeColorLabel(master.eye_color) }}</span>
      </div>

      <!-- РќР°С†РёРѕРЅР°Р»СЊРЅРѕСЃС‚СЊ -->
      <div v-if="master.nationality" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">РќР°С†РёРѕРЅР°Р»СЊРЅРѕСЃС‚СЊ:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.nationality }}</span>
      </div>

      <!-- Р’РЅРµС€РЅРѕСЃС‚СЊ -->
      <div v-if="master.appearance" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">РўРµР»РѕСЃР»РѕР¶РµРЅРёРµ:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ getAppearanceLabel(master.appearance) }}</span>
      </div>

      <!-- РћРїС‹С‚ СЂР°Р±РѕС‚С‹ -->
      <div v-if="master.experience" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">РћРїС‹С‚:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.experience }}</span>
      </div>

      <!-- РћР±СЂР°Р·РѕРІР°РЅРёРµ -->
      <div v-if="master.education" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">РћР±СЂР°Р·РѕРІР°РЅРёРµ:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.education }}</span>
      </div>

      <!-- РЇР·С‹РєРё -->
      <div v-if="master.languages && master.languages.length > 0" :class="PARAMETER_ITEM_CLASSES">
        <span :class="PARAMETER_LABEL_CLASSES">РЇР·С‹РєРё:</span>
        <span :class="PARAMETER_VALUE_CLASSES">{{ master.languages.join(', ') }}</span>
      </div>
    </div>

    <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РѕСЃРѕР±РµРЅРЅРѕСЃС‚Рё -->
    <div v-if="hasFeatures" :class="FEATURES_SECTION_CLASSES">
      <h4 :class="FEATURES_TITLE_CLASSES">
        РћСЃРѕР±РµРЅРЅРѕСЃС‚Рё
      </h4>
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

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CONTAINER_CLASSES = 'space-y-4'
const TITLE_CLASSES = 'text-lg font-semibold text-gray-500'
const PARAMETERS_GRID_CLASSES = 'grid grid-cols-1 sm:grid-cols-2 gap-3'
const PARAMETER_ITEM_CLASSES = 'flex items-center justify-between py-2 border-b border-gray-500 last:border-b-0'
const PARAMETER_LABEL_CLASSES = 'text-sm text-gray-500'
const PARAMETER_VALUE_CLASSES = 'text-sm font-medium text-gray-500'
const FEATURES_SECTION_CLASSES = 'mt-6 space-y-3'
const FEATURES_TITLE_CLASSES = 'text-base font-medium text-gray-500'
const FEATURES_LIST_CLASSES = 'flex flex-wrap gap-2'
const FEATURE_TAG_CLASSES = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800'

// РЎР»РѕРІР°СЂРё РґР»СЏ Р»РµР№Р±Р»РѕРІ
const HAIR_COLOR_LABELS = {
    blonde: 'Р‘Р»РѕРЅРґРёРЅРєР°',
    brunette: 'Р‘СЂСЋРЅРµС‚РєР°',
    redhead: 'Р С‹Р¶Р°СЏ',
    black: 'Р§РµСЂРЅС‹Рµ',
    brown: 'РљР°С€С‚Р°РЅРѕРІС‹Рµ',
    gray: 'РЎРµРґС‹Рµ',
    other: 'Р”СЂСѓРіРѕР№'
}

const EYE_COLOR_LABELS = {
    blue: 'Р“РѕР»СѓР±С‹Рµ',
    green: 'Р—РµР»РµРЅС‹Рµ',
    brown: 'РљР°СЂРёРµ',
    gray: 'РЎРµСЂС‹Рµ',
    hazel: 'РћСЂРµС…РѕРІС‹Рµ',
    black: 'Р§РµСЂРЅС‹Рµ',
    other: 'Р”СЂСѓРіРѕР№'
}

const APPEARANCE_LABELS = {
    slim: 'РЎС‚СЂРѕР№РЅР°СЏ',
    athletic: 'РЎРїРѕСЂС‚РёРІРЅР°СЏ',
    curvy: 'РЎ С„РѕСЂРјР°РјРё',
    plus_size: 'РџС‹С€РЅР°СЏ',
    average: 'РћР±С‹С‡РЅРѕРµ',
    other: 'Р”СЂСѓРіРѕРµ'
}

const props = defineProps({
    master: {
        type: Object,
        required: true
    }
})

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const hasFeatures = computed(() => {
    return displayFeatures.value.length > 0
})

const displayFeatures = computed(() => {
    const features = []
  
    // РћР±СЂР°Р±Р°С‚С‹РІР°РµРј features РєР°Рє РѕР±СЉРµРєС‚
    if (props.master.features && typeof props.master.features === 'object') {
        Object.entries(props.master.features).forEach(([key, value]) => {
            if (value === true || value === 'true') {
                features.push(getFeatureLabel(key))
            }
        })
    }
  
    // Р”РѕР±Р°РІР»СЏРµРј РґРѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РѕСЃРѕР±РµРЅРЅРѕСЃС‚Рё
    if (props.master.additional_features) {
        const additional = props.master.additional_features.split(',').map(f => f.trim()).filter(Boolean)
        features.push(...additional)
    }
  
    return features
})

// РњРµС‚РѕРґС‹
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
        massage_classic: 'РљР»Р°СЃСЃРёС‡РµСЃРєРёР№ РјР°СЃСЃР°Р¶',
        massage_relax: 'Р Р°СЃСЃР»Р°Р±Р»СЏСЋС‰РёР№ РјР°СЃСЃР°Р¶',
        massage_sport: 'РЎРїРѕСЂС‚РёРІРЅС‹Р№ РјР°СЃСЃР°Р¶',
        massage_therapeutic: 'Р›РµС‡РµР±РЅС‹Р№ РјР°СЃСЃР°Р¶',
        massage_anti_cellulite: 'РђРЅС‚РёС†РµР»Р»СЋР»РёС‚РЅС‹Р№ РјР°СЃСЃР°Р¶',
        massage_lymphatic: 'Р›РёРјС„РѕРґСЂРµРЅР°Р¶РЅС‹Р№ РјР°СЃСЃР°Р¶',
        massage_hot_stone: 'РЎС‚РѕСѓРЅ-С‚РµСЂР°РїРёСЏ',
        massage_aromatherapy: 'РђСЂРѕРјР°С‚РµСЂР°РїРёСЏ',
        has_girlfriend: 'Р Р°Р±РѕС‚Р°СЋ СЃ РїРѕРґСЂСѓРіРѕР№',
        incall_available: 'РџСЂРёРЅРёРјР°СЋ Сѓ СЃРµР±СЏ',
        outcall_available: 'Р’С‹РµР·Р¶Р°СЋ Рє РєР»РёРµРЅС‚Сѓ',
        apartment_service: 'РљРІР°СЂС‚РёСЂР°-СЃС‚СѓРґРёСЏ',
        hotel_service: 'Р Р°Р±РѕС‚Р°СЋ РІ РѕС‚РµР»СЏС…',
        sauna_service: 'Р Р°Р±РѕС‚Р°СЋ РІ СЃР°СѓРЅР°С…',
        accepts_couples: 'РџСЂРёРЅРёРјР°СЋ РїР°СЂС‹',
        photo_verified: 'Р¤РѕС‚Рѕ РїСЂРѕРІРµСЂРµРЅС‹',
        real_photos: 'Р РµР°Р»СЊРЅС‹Рµ С„РѕС‚Рѕ'
    }
  
    return featureLabels[feature] || feature
}
</script>
