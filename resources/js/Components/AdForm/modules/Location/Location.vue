<template>
  <FormSection
    title="Где вы оказываете услуги"
    hint="Выберите удобные для вас варианты работы"
    required
    :error="errors.service_location"
  >
    <div class="location-container">
      <!-- Основные варианты -->
      <div class="location-options">
        <FormField
          label="Место оказания услуг"
          :error="errors.service_location"
        >
          <div class="location-checkboxes">
            <label
              v-for="option in locationOptions"
              :key="option.value"
              class="location-checkbox-wrapper"
            >
              <input
                type="checkbox"
                :value="option.value"
                :checked="isLocationSelected(option.value)"
                @change="toggleLocation(option.value, $event.target.checked)"
                class="location-checkbox"
              />
              <div class="location-card">
                <div class="location-icon">{{ option.icon }}</div>
                <div class="location-content">
                  <div class="location-title">{{ option.title }}</div>
                  <div class="location-description">{{ option.description }}</div>
                </div>
              </div>
            </label>
          </div>
        </FormField>

        <!-- Дополнительные настройки для выездов -->
        <div v-if="hasOutcallService" class="outcall-settings">
          <FormField
            label="Районы выезда"
            hint="Выберите районы, куда вы готовы выезжать"
            :error="errors.outcall_locations"
          >
            <div class="districts-grid">
              <label
                v-for="district in districts"
                :key="district"
                class="district-checkbox-wrapper"
              >
                <input
                  type="checkbox"
                  :value="district"
                  :checked="isDistrictSelected(district)"
                  @change="toggleDistrict(district, $event.target.checked)"
                  class="district-checkbox"
                />
                <span class="district-label">{{ district }}</span>
              </label>
            </div>
          </FormField>

          <!-- Опция такси -->
          <FormField
            label="Дополнительные услуги"
            :error="errors.taxi_option"
          >
            <label class="taxi-option-wrapper">
              <input
                type="checkbox"
                :checked="localTaxiOption"
                @change="updateTaxiOption($event.target.checked)"
                class="taxi-checkbox"
              />
              <div class="taxi-content">
                <div class="taxi-title">🚗 Встречаю на такси</div>
                <div class="taxi-description">Могу встретить клиента и довезти до места</div>
              </div>
            </label>
          </FormField>
        </div>

        <!-- Предварительный просмотр -->
        <div v-if="hasSelectedLocations" class="location-preview">
          <div class="preview-header">
            <span class="preview-icon">📍</span>
            <span class="preview-title">Ваши варианты работы:</span>
          </div>
          
          <div class="preview-list">
            <div 
              v-for="location in selectedLocationDetails"
              :key="location.value"
              class="preview-item"
            >
              <span class="preview-icon-small">{{ location.icon }}</span>
              <span class="preview-text">{{ location.title }}</span>
            </div>
            
            <div v-if="hasOutcallService && selectedDistricts.length > 0" class="preview-districts">
              <span class="preview-districts-label">Районы выезда:</span>
              <span class="preview-districts-list">{{ selectedDistricts.join(', ') }}</span>
            </div>
            
            <div v-if="localTaxiOption" class="preview-taxi">
              <span class="preview-icon-small">🚗</span>
              <span class="preview-text">Встречаю на такси</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Советы по местоположению -->
      <div class="location-tips">
        <div class="tip-icon">💡</div>
        <div class="tip-content">
          <p class="tip-title">Советы по местоположению</p>
          <ul class="tip-list">
            <li>Выезд к клиенту увеличивает количество заказов на 40%</li>
            <li>У себя позволяет создать комфортную атмосферу</li>
            <li>Салон дает дополнительную безопасность</li>
            <li>Несколько вариантов расширяют клиентскую базу</li>
          </ul>
        </div>
      </div>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

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

// Варианты местоположения
const locationOptions = [
  {
    value: 'incall',
    title: 'У себя',
    description: 'Клиенты приезжают ко мне',
    icon: '🏠'
  },
  {
    value: 'outcall',
    title: 'Выезд к клиенту',
    description: 'Выезжаю к клиенту домой или в отель',
    icon: '🚗'
  },
  {
    value: 'salon',
    title: 'В салоне',
    description: 'Работаю в массажном салоне',
    icon: '🏢'
  },
  {
    value: 'hotel',
    title: 'В отеле',
    description: 'Встречи в отелях',
    icon: '🏨'
  }
]

// Районы Москвы (примерный список)
const districts = [
  'Центральный', 'Северный', 'Северо-Восточный', 'Восточный', 
  'Юго-Восточный', 'Южный', 'Юго-Западный', 'Западный', 
  'Северо-Западный', 'Зеленоград'
]

// Методы
const isLocationSelected = (location) => {
  return localServiceLocation.value.includes(location)
}

const toggleLocation = (location, checked) => {
  if (checked) {
    if (!localServiceLocation.value.includes(location)) {
      localServiceLocation.value.push(location)
    }
  } else {
    const index = localServiceLocation.value.indexOf(location)
    if (index > -1) {
      localServiceLocation.value.splice(index, 1)
    }
  }
  emit('update:serviceLocation', [...localServiceLocation.value])
}

const isDistrictSelected = (district) => {
  return localOutcallLocations.value.includes(district)
}

const toggleDistrict = (district, checked) => {
  if (checked) {
    if (!localOutcallLocations.value.includes(district)) {
      localOutcallLocations.value.push(district)
    }
  } else {
    const index = localOutcallLocations.value.indexOf(district)
    if (index > -1) {
      localOutcallLocations.value.splice(index, 1)
    }
  }
  emit('update:outcallLocations', [...localOutcallLocations.value])
}

const updateTaxiOption = (checked) => {
  localTaxiOption.value = checked
  emit('update:taxiOption', checked)
}

// Computed
const hasOutcallService = computed(() => {
  return localServiceLocation.value.includes('outcall')
})

const hasSelectedLocations = computed(() => {
  return localServiceLocation.value.length > 0
})

const selectedLocationDetails = computed(() => {
  return locationOptions.filter(option => 
    localServiceLocation.value.includes(option.value)
  )
})

const selectedDistricts = computed(() => {
  return localOutcallLocations.value
})
</script>

<style scoped>
.location-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.location-checkboxes {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
}

.location-checkbox-wrapper {
  cursor: pointer;
  display: block;
}

.location-checkbox {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.location-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.75rem;
  transition: all 0.2s;
  background: white;
}

.location-checkbox:checked + .location-card {
  border-color: #3b82f6;
  background: #eff6ff;
}

.location-card:hover {
  border-color: #9ca3af;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.location-icon {
  font-size: 2rem;
  flex-shrink: 0;
}

.location-content {
  flex: 1;
}

.location-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.location-description {
  font-size: 0.875rem;
  color: #6b7280;
}

.outcall-settings {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 1.5rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
}

.districts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 0.75rem;
}

.district-checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 0.375rem;
  transition: background-color 0.2s;
}

.district-checkbox-wrapper:hover {
  background: #f1f5f9;
}

.district-checkbox {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
}

.district-label {
  font-size: 0.875rem;
  color: #374151;
}

.taxi-option-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.taxi-option-wrapper:hover {
  border-color: #9ca3af;
  background: #f9fafb;
}

.taxi-checkbox {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
}

.taxi-content {
  flex: 1;
}

.taxi-title {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.taxi-description {
  font-size: 0.875rem;
  color: #6b7280;
}

.location-preview {
  padding: 1rem;
  background: #f0f9ff;
  border: 1px solid #0ea5e9;
  border-radius: 0.5rem;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.preview-icon {
  font-size: 1rem;
}

.preview-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #0c4a6e;
}

.preview-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.preview-item,
.preview-taxi {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.preview-icon-small {
  font-size: 0.875rem;
}

.preview-text {
  font-size: 0.875rem;
  color: #0c4a6e;
}

.preview-districts {
  padding-top: 0.5rem;
  border-top: 1px solid #bae6fd;
}

.preview-districts-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #0c4a6e;
}

.preview-districts-list {
  font-size: 0.875rem;
  color: #0c4a6e;
  margin-left: 0.5rem;
}

.location-tips {
  display: flex;
  gap: 0.75rem;
  padding: 1rem;
  background: #ecfdf5;
  border: 1px solid #10b981;
  border-radius: 0.5rem;
}

.tip-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.tip-content {
  flex: 1;
}

.tip-title {
  font-weight: 600;
  color: #065f46;
  margin-bottom: 0.5rem;
}

.tip-list {
  font-size: 0.875rem;
  color: #065f46;
  margin: 0;
  padding-left: 1.25rem;
}

.tip-list li {
  margin-bottom: 0.25rem;
}

@media (max-width: 768px) {
  .location-checkboxes {
    grid-template-columns: 1fr;
  }
  
  .districts-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>