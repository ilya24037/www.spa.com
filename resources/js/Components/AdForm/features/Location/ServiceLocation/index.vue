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
        v-model="localServiceLocation"
        :error="errors.service_location"
      />

      <!-- Районы выезда (показывается только если выбран выезд) -->
      <OutcallDistricts
        v-model="localOutcallLocations"
        :selected-service-types="localServiceLocation"
        :error="errors.outcall_locations"
      />

      <!-- Опция такси (показывается только если выбран выезд) -->
      <TaxiOption
        v-model="localTaxiOption"
        :selected-service-types="localServiceLocation"
        :error="errors.taxi_option"
      />

      <!-- Предпросмотр -->
      <LocationPreview
        :service-types="localServiceLocation"
        :districts="localOutcallLocations"
        :taxi-option="localTaxiOption"
      />

      <!-- Советы -->
      <LocationTips />
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'

// Микрокомпоненты
import ServiceTypes from './components/ServiceTypes.vue'
import OutcallDistricts from './components/OutcallDistricts.vue'
import TaxiOption from './components/TaxiOption.vue'
import LocationPreview from './components/LocationPreview.vue'
import LocationTips from './components/LocationTips.vue'

const props = defineProps({
  serviceLocation: { type: [Array, String], default: () => [] },
  outcallLocations: { type: [Array, String], default: () => [] },
  taxiOption: { type: Boolean, default: false },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:serviceLocation',
  'update:outcallLocations',
  'update:taxiOption'
])

// Локальное состояние
const localServiceLocation = ref([])
const localOutcallLocations = ref([])
const localTaxiOption = ref(props.taxiOption)

// Инициализация массивов
const initializeArrays = () => {
  // Service Location
  let serviceLocation = props.serviceLocation
  if (typeof serviceLocation === 'string') {
    try {
      serviceLocation = JSON.parse(serviceLocation) || []
    } catch (e) {
      serviceLocation = []
    }
  }
  localServiceLocation.value = Array.isArray(serviceLocation) ? [...serviceLocation] : []

  // Outcall Locations
  let outcallLocations = props.outcallLocations
  if (typeof outcallLocations === 'string') {
    try {
      outcallLocations = JSON.parse(outcallLocations) || []
    } catch (e) {
      outcallLocations = []
    }
  }
  localOutcallLocations.value = Array.isArray(outcallLocations) ? [...outcallLocations] : []
}

// Отслеживание изменений пропсов
watch(() => props.serviceLocation, () => {
  initializeArrays()
}, { immediate: true })

watch(() => props.outcallLocations, () => {
  initializeArrays()
}, { immediate: true })

watch(() => props.taxiOption, (newValue) => { 
  localTaxiOption.value = newValue 
})

// Отправка изменений родителю
watch(localServiceLocation, (newValue) => {
  emit('update:serviceLocation', newValue)
}, { deep: true })

watch(localOutcallLocations, (newValue) => {
  emit('update:outcallLocations', newValue)
}, { deep: true })

watch(localTaxiOption, (newValue) => {
  emit('update:taxiOption', newValue)
})
</script>