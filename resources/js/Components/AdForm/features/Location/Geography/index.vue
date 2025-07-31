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
        :model-value="localAddress"
        @update:model-value="updateAddress"
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
        :model-value="privacyLevel"
        @update:model-value="updatePrivacy"
      />

      <!-- Советы -->
      <GeographyTips />
    </div>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Микрокомпоненты
import AddressInput from './components/AddressInput.vue'
import MapPreview from './components/MapPreview.vue'
import PopularDistricts from './components/PopularDistricts.vue'
import LocationDetails from './components/LocationDetails.vue'
import PrivacySettings from './components/PrivacySettings.vue'
import GeographyTips from './components/GeographyTips.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: { type: Object, default: () => ({}) }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const geo = computed(() => store.formData.geo || {})
const localAddress = computed(() => geo.value.address || '')
const currentGeo = computed(() => geo.value)
const privacyLevel = computed(() => geo.value.privacy || 'district')

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateGeo = (newGeoData) => {
  console.log('updateGeo called:', newGeoData)
  store.updateField('geo', newGeoData)
}

const handleSuggestionSelected = (suggestion) => {
  // Парсим детали подсказки
  const parts = suggestion.details.split(', ')
  const geoData = {
    ...currentGeo.value,
    address: suggestion.address,
    district: parts[0] || '',
    metro: parts[1] || '',
    lat: 55.7558, // Заглушка - в реальности получаем через геокодинг
    lng: 37.6176,  // Заглушка
    accuracy: 100
  }
  updateGeo(geoData)
}

const handleDistrictSelected = (districtData) => {
  const geoData = {
    ...currentGeo.value,
    ...districtData
  }
  updateGeo(geoData)
}

const updatePrivacy = (newPrivacy) => {
  const geoData = {
    ...currentGeo.value,
    privacy: newPrivacy
  }
  updateGeo(geoData)
}

const updateAddress = (newAddress) => {
  const geoData = {
    ...currentGeo.value,
    address: newAddress
  }
  updateGeo(geoData)
}
</script>