<template>
  <div class="geography-section">
    <h2 class="form-group-title">География работы</h2>
    <p class="section-description">
      Укажите, где вы работаете и куда готовы выезжать к клиентам.
    </p>
    
    <div class="geography-fields">
      <!-- Основной адрес -->
      <div class="field-group">
        <label class="field-label">Основной адрес</label>
        <input
          type="text"
          v-model="localAddress"
          placeholder="Введите адрес салона или офиса"
          class="form-input"
          :class="{ 'error': errors?.address }"
        />
        <p class="field-hint">Укажите точный адрес, где вы принимаете клиентов</p>
        <span v-if="errors?.address" class="error-message">
          {{ errors.address[0] }}
        </span>
      </div>

      <!-- Районы выезда -->
      <div class="field-group">
        <h4 class="field-label">Куда выезжаете</h4>
        <p class="field-hint">Укажите все зоны выезда, чтобы клиенты понимали, доберётесь ли вы до них.</p>
        
        <div class="radio-group">
          <label 
            v-for="option in travelAreaOptions"
            :key="option.value"
            class="radio-option"
          >
            <input
              type="radio"
              v-model="localTravelArea"
              :value="option.value"
              class="radio-input"
            />
            <span class="radio-label">{{ option.label }}</span>
          </label>
        </div>
        
        <span v-if="errors?.travel_area" class="error-message">
          {{ errors.travel_area[0] }}
        </span>

        <!-- Дополнительные районы -->
        <div v-if="localTravelArea === 'custom'" class="custom-areas">
          <label class="field-label mt-4">Выберите районы</label>
          <div class="checkbox-group">
            <label 
              v-for="area in customTravelAreasOptions"
              :key="area.value"
              class="checkbox-option"
            >
              <input
                type="checkbox"
                :value="area.value"
                v-model="localCustomTravelAreas"
                class="checkbox-input"
              />
              <span class="checkbox-label">{{ area.label }}</span>
            </label>
          </div>
          <span v-if="errors?.custom_travel_areas" class="error-message">
            {{ errors.custom_travel_areas[0] }}
          </span>
        </div>
      </div>

      <!-- Радиус выезда -->
      <div v-if="localTravelArea !== 'no_travel'" class="field-group">
        <label class="field-label">Радиус выезда</label>
        <select 
          v-model="localTravelRadius"
          class="form-select"
          :class="{ 'error': errors?.travel_radius }"
        >
          <option value="">Выберите радиус</option>
          <option value="5">До 5 км</option>
          <option value="10">До 10 км</option>
          <option value="15">До 15 км</option>
          <option value="20">До 20 км</option>
          <option value="30">До 30 км</option>
          <option value="50">До 50 км</option>
          <option value="100">До 100 км</option>
          <option value="unlimited">Без ограничений</option>
        </select>
        <span v-if="errors?.travel_radius" class="error-message">
          {{ errors.travel_radius[0] }}
        </span>
      </div>

      <!-- Стоимость выезда -->
      <div v-if="localTravelArea !== 'no_travel'" class="field-group">
        <label class="field-label">Стоимость выезда</label>
        <div class="price-input-group">
          <input
            type="number"
            v-model="localTravelPrice"
            placeholder="0"
            class="form-input price-input"
            :class="{ 'error': errors?.travel_price }"
          />
          <select 
            v-model="localTravelPriceType"
            class="form-select price-unit"
          >
            <option value="free">Бесплатно</option>
            <option value="fixed">Фиксированная</option>
            <option value="per_km">За километр</option>
            <option value="negotiable">По договоренности</option>
          </select>
        </div>
        <span v-if="errors?.travel_price" class="error-message">
          {{ errors.travel_price[0] }}
        </span>
      </div>

      <!-- Карта (опционально) -->
      <div class="field-group">
        <label class="field-label">Отметьте на карте</label>
        <div class="map-placeholder">
          <p class="text-gray-500 text-center py-8">
            Интеграция с картой будет добавлена позже
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'

// Types
interface Props {
  address?: string
  travelArea?: string
  customTravelAreas?: string[]
  travelRadius?: string | number
  travelPrice?: number | null
  travelPriceType?: string
  errors?: Record<string, string[]>
}

// Props
const props = withDefaults(defineProps<Props>(), {
  address: '',
  travelArea: 'no_travel',
  customTravelAreas: () => [],
  travelRadius: '',
  travelPrice: null,
  travelPriceType: 'free',
  errors: () => ({})
})

// Emits
const emit = defineEmits<{
  'update:address': [value: string]
  'update:travelArea': [value: string]
  'update:customTravelAreas': [value: string[]]
  'update:travelRadius': [value: string | number]
  'update:travelPrice': [value: number | null]
  'update:travelPriceType': [value: string]
}>()

// Options
const travelAreaOptions = [
  { value: 'no_travel', label: 'Не выезжаю' },
  { value: 'nearby', label: 'Ближайшие районы' },
  { value: 'city', label: 'По всему городу' },
  { value: 'region', label: 'По области' },
  { value: 'custom', label: 'Выбрать районы' }
]

const customTravelAreasOptions = [
  { value: 'center', label: 'Центр' },
  { value: 'north', label: 'Север' },
  { value: 'south', label: 'Юг' },
  { value: 'east', label: 'Восток' },
  { value: 'west', label: 'Запад' },
  { value: 'northeast', label: 'Северо-восток' },
  { value: 'northwest', label: 'Северо-запад' },
  { value: 'southeast', label: 'Юго-восток' },
  { value: 'southwest', label: 'Юго-запад' }
]

// Local state
const localAddress = ref(props.address)
const localTravelArea = ref(props.travelArea)
const localCustomTravelAreas = ref<string[]>([...props.customTravelAreas])
const localTravelRadius = ref(props.travelRadius)
const localTravelPrice = ref(props.travelPrice)
const localTravelPriceType = ref(props.travelPriceType)

// Watch for prop changes
watch(() => props.address, (newVal) => {
  localAddress.value = newVal
})

watch(() => props.travelArea, (newVal) => {
  localTravelArea.value = newVal
})

watch(() => props.customTravelAreas, (newVal) => {
  localCustomTravelAreas.value = [...newVal]
}, { deep: true })

watch(() => props.travelRadius, (newVal) => {
  localTravelRadius.value = newVal
})

watch(() => props.travelPrice, (newVal) => {
  localTravelPrice.value = newVal
})

watch(() => props.travelPriceType, (newVal) => {
  localTravelPriceType.value = newVal
})

// Watch local changes and emit
watch(localAddress, (newVal) => {
  emit('update:address', newVal)
})

watch(localTravelArea, (newVal) => {
  emit('update:travelArea', newVal)
})

watch(localCustomTravelAreas, (newVal) => {
  emit('update:customTravelAreas', newVal)
}, { deep: true })

watch(localTravelRadius, (newVal) => {
  emit('update:travelRadius', newVal)
})

watch(localTravelPrice, (newVal) => {
  emit('update:travelPrice', newVal)
})

watch(localTravelPriceType, (newVal) => {
  emit('update:travelPriceType', newVal)
})
</script>

<style scoped>
.geography-section {
  @apply space-y-4;
}

.form-group-title {
  @apply text-xl font-semibold text-gray-900 mb-2;
}

.section-description {
  @apply text-gray-600 mb-4;
}

.geography-fields {
  @apply space-y-6;
}

.field-group {
  @apply space-y-2;
}

.field-label {
  @apply block text-sm font-medium text-gray-700;
}

.field-hint {
  @apply text-sm text-gray-500;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors;
}

.form-input.error {
  @apply border-red-500;
}

.form-select {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors bg-white;
}

.form-select.error {
  @apply border-red-500;
}

.error-message {
  @apply text-red-500 text-sm mt-1 block;
}

/* Radio группа */
.radio-group {
  @apply space-y-2;
}

.radio-option {
  @apply flex items-center space-x-2 cursor-pointer;
}

.radio-input {
  @apply w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500;
}

.radio-label {
  @apply text-sm text-gray-700;
}

/* Checkbox группа */
.checkbox-group {
  @apply grid grid-cols-2 gap-3 mt-2;
}

.checkbox-option {
  @apply flex items-center space-x-2 cursor-pointer;
}

.checkbox-input {
  @apply w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500;
}

.checkbox-label {
  @apply text-sm text-gray-700;
}

/* Custom areas */
.custom-areas {
  @apply mt-4 p-4 bg-gray-50 rounded-lg;
}

/* Цена */
.price-input-group {
  @apply flex gap-2;
}

.price-input {
  @apply w-32;
}

.price-unit {
  @apply w-48;
}

/* Карта */
.map-placeholder {
  @apply bg-gray-100 rounded-lg h-48 flex items-center justify-center;
}
</style>
