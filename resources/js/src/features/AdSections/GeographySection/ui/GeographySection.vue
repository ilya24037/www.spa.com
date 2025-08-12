<template>
  <div class="geography-section">
    <h2 class="form-group-title">География работы</h2>
    <p class="section-description">
      Укажите, где вы работаете и куда готовы выезжать к клиентам.
    </p>
    
    <div class="geography-fields">
      <!-- Основной адрес -->
      <BaseInput
        v-model="localAddress"
        type="text"
        label="Основной адрес"
        placeholder="Введите адрес салона или офиса"
        hint="Укажите точный адрес, где вы принимаете клиентов"
        :error="errors?.address?.[0]"
      />

      <!-- Районы выезда -->
      <div class="field-group">
        <h4 class="field-label">Куда выезжаете</h4>
        <p class="field-hint">Укажите все зоны выезда, чтобы клиенты понимали, доберётесь ли вы до них.</p>
        
        <div class="radio-group">
          <BaseRadio
            v-for="option in travelAreaOptions"
            :key="option.value"
            v-model="localTravelArea"
            :value="option.value"
            :label="option.label"
          />
        </div>
        
        <span v-if="errors?.travel_area" class="error-message">
          {{ errors.travel_area[0] }}
        </span>

        <!-- Дополнительные районы -->
        <div v-if="localTravelArea === 'custom'" class="custom-areas">
          <label class="field-label mt-4">Выберите районы</label>
          <div class="checkbox-group">
            <BaseCheckbox
              v-for="area in customTravelAreasOptions"
              :key="area.value"
              :model-value="localCustomTravelAreas.includes(area.value)"
              :label="area.label"
              @update:modelValue="toggleCustomArea(area.value, $event)"
            />
          </div>
          <span v-if="errors?.custom_travel_areas" class="error-message">
            {{ errors.custom_travel_areas[0] }}
          </span>
        </div>
      </div>

      <!-- Радиус выезда -->
      <BaseSelect
        v-if="localTravelArea !== 'no_travel'"
        v-model="localTravelRadius"
        label="Радиус выезда"
        :options="travelRadiusOptions"
        :error="errors?.travel_radius?.[0]"
      />

      <!-- Стоимость выезда -->
      <div v-if="localTravelArea !== 'no_travel'" class="field-group">
        <label class="field-label">Стоимость выезда</label>
        <div class="price-input-group">
          <BaseInput
            v-model="localTravelPrice"
            type="number"
            placeholder="0"
            :error="errors?.travel_price?.[0]"
            class="price-input"
          />
          <BaseSelect
            v-model="localTravelPriceType"
            :options="travelPriceTypeOptions"
            class="price-unit"
          />
        </div>
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
import { ref, watch, computed } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

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

// Опции для селектов
const travelRadiusOptions = computed(() => [
  { value: '', label: 'Выберите радиус' },
  { value: '5', label: 'До 5 км' },
  { value: '10', label: 'До 10 км' },
  { value: '15', label: 'До 15 км' },
  { value: '20', label: 'До 20 км' },
  { value: '30', label: 'До 30 км' },
  { value: '50', label: 'До 50 км' },
  { value: '100', label: 'До 100 км' },
  { value: 'unlimited', label: 'Без ограничений' }
])

const travelPriceTypeOptions = computed(() => [
  { value: 'free', label: 'Бесплатно' },
  { value: 'fixed', label: 'Фиксированная' },
  { value: 'per_km', label: 'За километр' },
  { value: 'negotiable', label: 'По договоренности' }
])

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

// Функция для чекбоксов
const toggleCustomArea = (value: string, checked: boolean) => {
  if (checked) {
    if (!localCustomTravelAreas.value.includes(value)) {
      localCustomTravelAreas.value.push(value)
    }
  } else {
    const index = localCustomTravelAreas.value.indexOf(value)
    if (index > -1) {
      localCustomTravelAreas.value.splice(index, 1)
    }
  }
}
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

.error-message {
  @apply text-red-500 text-sm mt-1 block;
}

/* Radio группа */
.radio-group {
  @apply space-y-2;
}

/* Checkbox группа */
.checkbox-group {
  @apply grid grid-cols-2 gap-3 mt-2;
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
