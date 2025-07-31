<template>
  <FormSection
    title="Где вы оказываете услуги"
    hint="Выберите удобные для вас варианты работы"
    required
    :error="errors.service_location"
  >
    <div class="space-y-6">
      <!-- Основные типы локации -->
      <ServiceTypes
        :model-value="serviceLocation"
        @update:model-value="updateServiceLocation"
        :error="errors.service_location"
      />

      <!-- Районы выезда (показывается только если выбран выезд) -->
      <OutcallDistricts
        :model-value="outcallLocations"
        @update:model-value="updateOutcallLocations"
        :selected-service-types="serviceLocation"
        :error="errors.outcall_locations"
      />

      <!-- Опция такси (показывается только если выбран выезд) -->
      <TaxiOption
        :model-value="taxiOption"
        @update:model-value="updateTaxiOption"
        :selected-service-types="serviceLocation"
        :error="errors.taxi_option"
      />

      <!-- Предпросмотр -->
      <LocationPreview
        :service-types="serviceLocation"
        :districts="outcallLocations"
        :taxi-option="taxiOption"
      />

      <!-- Советы -->
      <LocationTips />
    </div>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Микрокомпоненты
import ServiceTypes from './components/ServiceTypes.vue'
import OutcallDistricts from './components/OutcallDistricts.vue'
import TaxiOption from './components/TaxiOption.vue'
import LocationPreview from './components/LocationPreview.vue'
import LocationTips from './components/LocationTips.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: { type: Object, default: () => ({}) }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const serviceLocation = computed(() => store.formData.service_location || [])
const outcallLocations = computed(() => store.formData.outcall_locations || [])
const taxiOption = computed(() => store.formData.taxi_option || false)

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateServiceLocation = (value) => {
  console.log('updateServiceLocation called:', value)
  // Убеждаемся что передаём массив
  const arrayValue = Array.isArray(value) ? value : []
  store.updateField('service_location', arrayValue)
}

const updateOutcallLocations = (value) => {
  console.log('updateOutcallLocations called:', value)
  // Убеждаемся что передаём массив
  const arrayValue = Array.isArray(value) ? value : []
  store.updateField('outcall_locations', arrayValue)
}

const updateTaxiOption = (value) => {
  console.log('updateTaxiOption called:', value)
  store.updateField('taxi_option', value)
}
</script>