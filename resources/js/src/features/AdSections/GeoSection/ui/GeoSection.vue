<template>
  <div class="bg-white rounded-lg p-6">
    <!-- Заголовок с Badge -->
    <div class="flex items-center gap-3 mb-6">
      <h2 class="text-2xl font-semibold text-gray-900 m-0">География</h2>
      <Badge text="Новое" variant="primary" size="md" />
    </div>

    <!-- Секция адреса -->
    <div class="mb-6">
      <h3 class="text-base font-medium text-gray-900 mb-2">Ваш адрес</h3>
      <p class="text-sm text-gray-600 leading-relaxed mb-4">
        Клиенты выбирают исполнителя по точному адресу, когда ищут услуги поблизости.
      </p>
      
      <div class="mb-4">
        <AddressInput
          ref="addressInputRef"
          v-model="geoData.address"
          placeholder="Начните вводить адрес"
          label="Адрес предоставления услуг"
          name="service-address"
          :error="errors?.geo?.[0]"
          :show-search-button="true"
          :loading="searchLoading"
          @update:modelValue="updateAddress"
          @clear="clearAddress"
          @search="handleAddressSearch"
        />
      </div>
    </div>

    <!-- Карта -->
    <div class="mb-6 rounded-lg overflow-hidden">
      <YandexMapPicker
        ref="mapRef"
        v-model="coordinatesString"
        :height="360"
        :center="mapCenter"
        :api-key="mapApiKey"
        @address-found="handleAddressFound"
        @search-error="handleSearchError"
        @marker-moved="handleMarkerMoved"
      />
    </div>

    <!-- Секция выезда -->
    <div class="pt-6 border-t border-gray-200">
      <h3 class="text-base font-medium text-gray-900 mb-2">Куда выезжаете</h3>
      <p class="text-sm text-gray-600 leading-relaxed mb-4">
        Укажите все зоны выезда, чтобы клиенты понимали, доберётесь ли вы до них.
      </p>
      
      <div class="flex flex-col gap-2">
        <BaseRadio
          v-model="geoData.outcall"
          value="none"
          name="outcall"
          label="Не выезжаю"
          @update:modelValue="updateOutcall"
        />
        <BaseRadio
          v-model="geoData.outcall"
          value="city"
          name="outcall"
          label="По всему городу"
          @update:modelValue="updateOutcall"
        />
        <BaseRadio
          v-model="geoData.outcall"
          value="zones"
          name="outcall"
          label="В выбранные зоны"
          @update:modelValue="updateOutcall"
        />
      </div>

      <!-- Выбор зон (показывается если выбрано "В выбранные зоны") -->
      <div v-if="geoData.outcall === 'zones'" class="mt-4 p-4 bg-gray-100 rounded-lg">
        <p id="zones-hint" class="text-sm text-gray-600 mb-3">
          Выберите районы, в которые вы готовы выезжать:
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3" role="group" aria-labelledby="zones-hint">
          <div v-for="zone in availableZones" :key="zone" class="flex items-center gap-2 text-sm text-gray-900">
            <input
              :id="`zone-${zone.toLowerCase().replace(/\s+/g, '-')}`"
              type="checkbox"
              name="delivery-zones"
              :value="zone"
              :checked="geoData.zones.includes(zone)"
              @change="toggleZone(zone)"
              class="w-4 h-4 border border-gray-300 rounded cursor-pointer flex-shrink-0"
            >
            <label :for="`zone-${zone.toLowerCase().replace(/\s+/g, '-')}`" class="cursor-pointer select-none transition-colors hover:text-blue-600">
              {{ zone }}
            </label>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed, reactive } from 'vue'
import Badge from '@/src/shared/ui/atoms/Badge/Badge.vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import AddressInput from '@/src/shared/ui/molecules/AddressInput/AddressInput.vue'
import YandexMapPicker from '@/src/shared/ui/molecules/YandexMapPicker/YandexMapSimple.vue'

// Типы
interface GeoData {
  address: string
  coordinates: { lat: number; lng: number } | null
  outcall: 'none' | 'city' | 'zones'
  zones: string[]
}

interface Props {
  geo?: string | Record<string, any>
  errors?: Record<string, string[]>
}

interface Emits {
  'update:geo': [value: string]
}

// Props
const props = withDefaults(defineProps<Props>(), {
  geo: () => '',
  errors: () => ({})
})

// Emits
const emit = defineEmits<Emits>()

// Доступные зоны города (Пермь)
const availableZones = [
  'Дзержинский район',
  'Индустриальный район',
  'Кировский район',
  'Ленинский район',
  'Мотовилихинский район',
  'Орджоникидзевский район',
  'Свердловский район'
]

// Локальные данные
const geoData = reactive<GeoData>({
  address: '',
  coordinates: null,
  outcall: 'none',
  zones: []
})

// Дополнительное состояние
const searchLoading = ref(false)
const mapApiKey = import.meta.env.VITE_YANDEX_MAPS_API_KEY || '23ff8acc-835f-4e99-8b19-d33c5d346e18'

// Refs для компонентов
const addressInputRef = ref()
const mapRef = ref()

// Парсинг входных данных
const parseGeoData = (value: string | Record<string, any>): GeoData => {
  if (typeof value === 'string') {
    // Если строка, пробуем распарсить JSON
    if (value) {
      try {
        const parsed = JSON.parse(value)
        return {
          address: parsed.address || '',
          coordinates: parsed.coordinates || null,
          outcall: parsed.outcall || 'none',
          zones: parsed.zones || []
        }
      } catch {
        // Если не JSON, считаем что это просто адрес
        return {
          address: value,
          coordinates: null,
          outcall: 'none',
          zones: []
        }
      }
    }
  } else if (value && typeof value === 'object') {
    // Если объект, извлекаем данные
    return {
      address: value.address || '',
      coordinates: value.coordinates || null,
      outcall: value.outcall || 'none',
      zones: value.zones || []
    }
  }
  
  return {
    address: '',
    coordinates: null,
    outcall: 'none',
    zones: []
  }
}

// Инициализация данных из props
const initData = parseGeoData(props.geo)
Object.assign(geoData, initData)

// Следим за изменениями props
watch(() => props.geo, (newValue) => {
  const parsed = parseGeoData(newValue)
  Object.assign(geoData, parsed)
}, { immediate: true })

// Принудительная инициализация карты (например, при открытии секции)
const forceMapInit = async () => {
  if (mapRef.value && typeof mapRef.value.forceInit === 'function') {
    try {
      await mapRef.value.forceInit()
    } catch (error) {
      console.error('Не удалось принудительно инициализировать карту:', error)
    }
  }
}

// Экспортируем метод для внешнего использования
defineExpose({
  forceMapInit
})

// Вычисляемые свойства для карты
const mapCenter = computed(() => {
  if (geoData.coordinates) {
    return geoData.coordinates
  }
  // Центр Перми по умолчанию
  return { lat: 58.0105, lng: 56.2502 }
})

// Строка координат для v-model карты
const coordinatesString = computed({
  get() {
    if (geoData.coordinates) {
      return `${geoData.coordinates.lat},${geoData.coordinates.lng}`
    }
    return ''
  },
  set(value: string) {
    if (value && value.includes(',')) {
      const [lat, lng] = value.split(',').map(Number)
      geoData.coordinates = { lat, lng }
      emitGeoData()
    }
  }
})

// Методы
const updateAddress = (value: string) => {
  geoData.address = value
  emitGeoData()
}

const clearAddress = () => {
  geoData.address = ''
  geoData.coordinates = null
  emitGeoData()
}

const updateOutcall = (value: 'none' | 'city' | 'zones') => {
  geoData.outcall = value
  if (value !== 'zones') {
    geoData.zones = []
  }
  emitGeoData()
}

const toggleZone = (zone: string) => {
  const index = geoData.zones.indexOf(zone)
  if (index > -1) {
    geoData.zones.splice(index, 1)
  } else {
    geoData.zones.push(zone)
  }
  emitGeoData()
}

// Новые методы для работы с Яндекс.Картами
const handleAddressSearch = async (address: string) => {
  // Debug log removed
  
  if (!address.trim()) {
    // Debug log removed
    return
  }
  
  searchLoading.value = true
  
  try {
    if (mapRef.value && typeof mapRef.value.searchAddress === 'function') {
      // Debug log removed
      await mapRef.value.searchAddress(address)
    } else {
      console.error('GeoSection: mapRef или searchAddress недоступны')
    }
  } catch (error) {
    console.error('GeoSection: ошибка поиска адреса:', error)
    if (addressInputRef.value) {
      addressInputRef.value.setSearchStatus('Ошибка поиска адреса')
    }
  } finally {
    searchLoading.value = false
  }
}

const handleAddressFound = (address: string, coordinates: { lat: number; lng: number }) => {
  geoData.address = address
  geoData.coordinates = coordinates
  
  if (addressInputRef.value) {
    addressInputRef.value.setSearchStatus('Адрес найден успешно')
  }
  
  emitGeoData()
}

const handleSearchError = (error: string) => {
  if (addressInputRef.value) {
    addressInputRef.value.setSearchStatus(error)
  }
}

const handleMarkerMoved = (coordinates: { lat: number; lng: number }) => {
  geoData.coordinates = coordinates
  emitGeoData()
}

const emitGeoData = () => {
  // Формируем JSON строку для отправки
  const dataToEmit = JSON.stringify({
    address: geoData.address,
    coordinates: geoData.coordinates,
    outcall: geoData.outcall,
    zones: geoData.zones
  })
  emit('update:geo', dataToEmit)
}
</script>

<!-- Все стили мигрированы на Tailwind CSS в template с адаптивностью -->