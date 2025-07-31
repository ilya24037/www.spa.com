<template>
  <FormSection
    title="География"
    hint="Укажите ваше местоположение для поиска"
    required
    :error="errors.geo"
  >
    <div class="space-y-6">
      <!-- Поле адреса с автодополнением -->
      <AddressInput
        v-model="localAddress"
        :error="errors.geo"
        @suggestion-selected="handleSuggestionSelected"
      />

      <!-- Карта с координатами -->
      <MapPreview
        :coordinates="{ lat: currentGeo.lat, lng: currentGeo.lng }"
        :accuracy="currentGeo.accuracy"
      />

      <!-- Быстрый выбор районов -->
      <PopularDistricts
        @district-selected="handleDistrictSelected"
      />

      <!-- Детали локации -->
      <LocationDetails
        :location="currentGeo"
        :privacy-level="privacyLevel"
      />

      <!-- Настройки приватности -->
      <PrivacySettings
        v-model="privacyLevel"
      />

      <!-- Советы -->
      <GeographyTips />
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'

// Микрокомпоненты
import AddressInput from './components/AddressInput.vue'
import MapPreview from './components/MapPreview.vue'
import PopularDistricts from './components/PopularDistricts.vue'
import LocationDetails from './components/LocationDetails.vue'
import PrivacySettings from './components/PrivacySettings.vue'
import GeographyTips from './components/GeographyTips.vue'

const props = defineProps({
  geo: { type: [Object, String], default: () => ({}) },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:geo'])

// Локальное состояние
const localAddress = ref('')
const currentGeo = ref({})
const privacyLevel = ref('district')

// Инициализация данных
const initializeGeo = () => {
  let geo = props.geo
  
  if (typeof geo === 'string') {
    try {
      geo = JSON.parse(geo) || {}
    } catch (e) {
      geo = {}
    }
  }
  
  currentGeo.value = { ...geo }
  localAddress.value = geo.address || ''
  privacyLevel.value = geo.privacy || 'district'
}

// Отслеживание изменений пропсов
watch(() => props.geo, () => {
  initializeGeo()
}, { immediate: true })

// Отслеживание изменений локальных данных
watch([localAddress, privacyLevel], () => {
  updateGeo()
})

// Методы
const handleSuggestionSelected = (suggestion) => {
  // Парсим детали подсказки
  const parts = suggestion.details.split(', ')
  currentGeo.value = {
    ...currentGeo.value,
    address: suggestion.address,
    district: parts[0] || '',
    metro: parts[1] || '',
    lat: 55.7558, // Заглушка - в реальности получаем через геокодинг
    lng: 37.6176,  // Заглушка
    accuracy: 100
  }
  updateGeo()
}

const handleDistrictSelected = (districtData) => {
  localAddress.value = districtData.address
  currentGeo.value = {
    ...currentGeo.value,
    ...districtData
  }
  updateGeo()
}

const updateGeo = () => {
  const geoData = {
    ...currentGeo.value,
    address: localAddress.value,
    privacy: privacyLevel.value
  }
  
  emit('update:geo', geoData)
}
</script>