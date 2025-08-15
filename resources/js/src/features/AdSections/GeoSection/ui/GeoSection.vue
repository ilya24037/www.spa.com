<template>
  <div class="geo-section">
    <!-- Заголовок с Badge -->
    <div class="geo-section__header">
      <h2 class="geo-section__title">География</h2>
      <Badge text="Новое" variant="primary" size="md" />
    </div>

    <!-- Секция адреса -->
    <div class="geo-section__address">
      <h3 class="geo-section__subtitle">Ваш адрес</h3>
      <p class="geo-section__description">
        Клиенты выбирают исполнителя по точному адресу, когда ищут услуги поблизости.
      </p>
      
      <div class="geo-section__input">
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
    <div class="geo-section__map">
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
    <div class="geo-section__outcall">
      <h3 class="geo-section__subtitle">Куда выезжаете</h3>
      <p class="geo-section__description">
        Укажите все зоны выезда, чтобы клиенты понимали, доберётесь ли вы до них.
      </p>
      
      <div class="geo-section__radio-group">
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
      <div v-if="geoData.outcall === 'zones'" class="geo-section__zones">
        <p id="zones-hint" class="geo-section__zones-hint">
          Выберите районы, в которые вы готовы выезжать:
        </p>
        <div class="geo-section__zones-list" role="group" aria-labelledby="zones-hint">
          <div v-for="zone in availableZones" :key="zone" class="geo-section__zone-checkbox">
            <input
              :id="`zone-${zone.toLowerCase().replace(/\s+/g, '-')}`"
              type="checkbox"
              name="delivery-zones"
              :value="zone"
              :checked="geoData.zones.includes(zone)"
              @change="toggleZone(zone)"
            >
            <label :for="`zone-${zone.toLowerCase().replace(/\s+/g, '-')}`">
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
  console.log('GeoSection: получен запрос поиска адреса:', address)
  
  if (!address.trim()) {
    console.log('GeoSection: адрес пустой, отменяем поиск')
    return
  }
  
  searchLoading.value = true
  
  try {
    if (mapRef.value && typeof mapRef.value.searchAddress === 'function') {
      console.log('GeoSection: вызываем searchAddress у карты')
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

<style scoped>
.geo-section {
  background: white;
  border-radius: 8px;
  padding: 24px;
}

.geo-section__header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
}

.geo-section__title {
  font-size: 24px;
  font-weight: 600;
  color: #1a1a1a;
  margin: 0;
}

.geo-section__address {
  margin-bottom: 24px;
}

.geo-section__subtitle {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
  margin: 0 0 8px 0;
}

.geo-section__description {
  font-size: 13px;
  color: #666;
  line-height: 1.5;
  margin: 0 0 16px 0;
}

.geo-section__input {
  margin-bottom: 16px;
}

.geo-section__map {
  margin-bottom: 24px;
  border-radius: 8px;
  overflow: hidden;
}

.geo-section__outcall {
  padding-top: 24px;
  border-top: 1px solid #e5e5e5;
}

.geo-section__radio-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.geo-section__zones {
  margin-top: 16px;
  padding: 16px;
  background: #f5f5f5;
  border-radius: 8px;
}

.geo-section__zones-hint {
  font-size: 14px;
  color: #666;
  margin: 0 0 12px 0;
}

.geo-section__zones-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 12px;
}

.geo-section__zone-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: #1a1a1a;
}

.geo-section__zone-checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  border: 1px solid #d6d6d6;
  border-radius: 4px;
  cursor: pointer;
  flex-shrink: 0;
}

.geo-section__zone-checkbox label {
  cursor: pointer;
  user-select: none;
  transition: color 0.2s ease;
}

.geo-section__zone-checkbox:hover label {
  color: #0066ff;
}

/* Адаптивность */
@media (max-width: 768px) {
  .geo-section {
    padding: 16px;
  }
  
  .geo-section__title {
    font-size: 20px;
  }
  
  .geo-section__zones-list {
    grid-template-columns: 1fr;
  }
}
</style>