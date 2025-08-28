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
          :show-search-button="false"
          :show-address-icon="true"
          :full-address="fullAddressForTooltip"
          :loading="searchLoading"
          @update:modelValue="updateAddress"
          @clear="clearAddress"
          @search="handleAddressSearch"
          @suggestion-selected="handleSuggestionSelected"
        />
      </div>
    </div>

    <!-- Карта -->
    <div class="mb-6 rounded-lg overflow-hidden">
      <YandexMap
        ref="mapRef"
        v-model="coordinatesString"
        mode="single"
        :height="360"
        :center="mapCenter"
        :api-key="mapApiKey"
        :show-geolocation-button="true"
        :auto-detect-location="true"
        :draggable="true"
        :current-address="geoData.address"
        @address-found="handleAddressFound"
        @search-error="handleSearchError"
        @marker-moved="handleMarkerMoved"
        @marker-address-hover="handleMarkerAddressHover"
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
      <div v-if="geoData.outcall === 'zones'" class="mt-4">
        <p class="text-sm text-gray-600 mb-3">
          Выберите районы, в которые вы готовы выезжать:
        </p>
        <ZoneSelector 
          v-model="geoData.zones"
          :zones="availableZones"
        />
      </div>
      
      <!-- Типы мест для выезда -->
      <div v-if="geoData.outcall !== 'none'" class="mt-6 pt-6 border-t border-gray-200">
        <h4 class="text-base font-medium text-gray-900 mb-2">Типы мест для выезда</h4>
        <p class="text-sm text-gray-600 leading-relaxed mb-4">
          Выберите, в какие места вы готовы выезжать к клиентам.
        </p>
        
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 mb-6">
          <BaseCheckbox
            v-model="geoData.outcall_apartment"
            name="outcall_apartment"
            label="На квартиру"
            @update:modelValue="(value) => updateOutcallPlaceType('outcall_apartment', value)"
          />
          <BaseCheckbox
            v-model="geoData.outcall_hotel"
            name="outcall_hotel"
            label="В гостиницу"
            @update:modelValue="(value) => updateOutcallPlaceType('outcall_hotel', value)"
          />
          <BaseCheckbox
            v-model="geoData.outcall_office"
            name="outcall_office"
            label="В офис"
            @update:modelValue="(value) => updateOutcallPlaceType('outcall_office', value)"
          />
          <BaseCheckbox
            v-model="geoData.outcall_sauna"
            name="outcall_sauna"
            label="В сауну"
            @update:modelValue="(value) => updateOutcallPlaceType('outcall_sauna', value)"
          />
          <BaseCheckbox
            v-model="geoData.outcall_house"
            name="outcall_house"
            label="В загородный дом"
            @update:modelValue="(value) => updateOutcallPlaceType('outcall_house', value)"
          />
        </div>
        
        <!-- Такси -->
        <div class="pt-4 border-t border-gray-200">
          <h4 class="text-base font-medium text-gray-900 mb-2">Такси</h4>
          <p class="text-sm text-gray-600 leading-relaxed mb-4">
            Укажите, как оплачивается такси до места выезда.
          </p>
          
          <div class="flex flex-col gap-2">
            <BaseRadio
              v-model="geoData.taxi_included"
              :value="false"
              label="Оплачивается отдельно"
              name="taxi"
              @update:modelValue="updateTaxiIncluded"
            />
            <BaseRadio
              v-model="geoData.taxi_included"
              :value="true"
              label="Включено в стоимость"
              name="taxi"
              @update:modelValue="updateTaxiIncluded"
            />
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
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import AddressInput from '@/src/shared/ui/molecules/AddressInput/AddressInput.vue'
import YandexMap from '@/src/shared/ui/molecules/YandexMapPicker/YandexMap.vue'
import ZoneSelector from '@/src/shared/ui/molecules/ZoneSelector/ZoneSelector.vue'

// Типы
interface GeoData {
  address: string
  coordinates: { lat: number; lng: number } | null
  outcall: 'none' | 'city' | 'zones'
  zones: string[]
  // Типы мест для выезда
  outcall_apartment: boolean
  outcall_hotel: boolean
  outcall_house: boolean
  outcall_sauna: boolean
  outcall_office: boolean
  taxi_included: boolean
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
  zones: [],
  // Типы мест для выезда - значения по умолчанию
  outcall_apartment: true,
  outcall_hotel: false,
  outcall_house: false,
  outcall_sauna: false,
  outcall_office: false,
  taxi_included: false
})

// Дополнительное состояние
const searchLoading = ref(false)
const mapApiKey = import.meta.env.VITE_YANDEX_MAPS_API_KEY || '23ff8acc-835f-4e99-8b19-d33c5d346e18'
const detailedAddress = ref('') // Подробный адрес с карты

// Refs для компонентов
const addressInputRef = ref()
const mapRef = ref()

// Вычисляемое свойство для полного адреса в tooltip
const fullAddressForTooltip = computed(() => {
  if (detailedAddress.value) {
    return detailedAddress.value
  }
  
  if (geoData.coordinates) {
    return `${geoData.address}\n📍 ${geoData.coordinates.lat.toFixed(6)}, ${geoData.coordinates.lng.toFixed(6)}`
  }
  
  return geoData.address
})

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
          zones: parsed.zones || [],
          outcall_apartment: parsed.outcall_apartment ?? true,
          outcall_hotel: parsed.outcall_hotel ?? false,
          outcall_house: parsed.outcall_house ?? false,
          outcall_sauna: parsed.outcall_sauna ?? false,
          outcall_office: parsed.outcall_office ?? false,
          taxi_included: parsed.taxi_included ?? false
        }
      } catch {
        // Если не JSON, считаем что это просто адрес
        return {
          address: value,
          coordinates: null,
          outcall: 'none',
          zones: [],
          outcall_apartment: true,
          outcall_hotel: false,
          outcall_house: false,
          outcall_sauna: false,
          outcall_office: false,
          taxi_included: false
        }
      }
    }
  } else if (value && typeof value === 'object') {
    // Если объект, извлекаем данные
    return {
      address: value.address || '',
      coordinates: value.coordinates || null,
      outcall: value.outcall || 'none',
      zones: value.zones || [],
      outcall_apartment: value.outcall_apartment ?? true,
      outcall_hotel: value.outcall_hotel ?? false,
      outcall_house: value.outcall_house ?? false,
      outcall_sauna: value.outcall_sauna ?? false,
      outcall_office: value.outcall_office ?? false,
      taxi_included: value.taxi_included ?? false
    }
  }
  
  return {
    address: '',
    coordinates: null,
    outcall: 'none',
    zones: [],
    outcall_apartment: true,
    outcall_hotel: false,
    outcall_house: false,
    outcall_sauna: false,
    outcall_office: false,
    taxi_included: false
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

// ✅ ИСПРАВЛЕНИЕ: Следим за изменениями zones для сохранения районов выезда
watch(() => geoData.zones, () => {
  // При изменении zones через ZoneSelector нужно отправить данные
  emitGeoData()
}, { deep: true })

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
  // Сохраняем предыдущий адрес для сравнения
  const previousAddress = geoData.address
  geoData.address = value
  
  // Если пользователь вводит новый текст (не программное обновление), 
  // очищаем координаты чтобы маркер исчез до нового поиска
  if (value !== previousAddress && geoData.coordinates) {
    geoData.coordinates = null
    detailedAddress.value = ''
  }
  
  emitGeoData()
}

const clearAddress = () => {
  geoData.address = ''
  geoData.coordinates = null
  detailedAddress.value = ''
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

const updateOutcallPlaceType = (field: keyof GeoData, value: boolean) => {
  geoData[field] = value
  emitGeoData()
}

const updateTaxiIncluded = (value: boolean) => {
  geoData.taxi_included = value
  emitGeoData()
}

// Новые методы для работы с Яндекс.Картами
const handleAddressSearch = async (address: string) => {
  if (!address.trim()) {
    return
  }
  
  searchLoading.value = true
  
  try {
    if (mapRef.value && typeof mapRef.value.searchAddress === 'function') {
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

const handleSuggestionSelected = async (suggestion: any) => {
  // Автоматически ищем выбранный адрес на карте
  await handleAddressSearch(suggestion.value)
}

const handleAddressFound = (address: string, coordinates: { lat: number; lng: number }) => {
  geoData.address = address
  geoData.coordinates = coordinates
  
  // Сохраняем подробный адрес для tooltip
  detailedAddress.value = address
  
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
  
  // При перемещении маркера очищаем подробный адрес, 
  // так как он больше не соответствует точным координатам
  detailedAddress.value = ''
  
  emitGeoData()
}

const handleMarkerAddressHover = (address: string) => {
  // Обновляем адрес в поле при наведении на маркер
  geoData.address = address
  detailedAddress.value = address
  emitGeoData()
}

const emitGeoData = () => {
  // Формируем JSON строку для отправки
  const dataToEmit = JSON.stringify({
    address: geoData.address,
    coordinates: geoData.coordinates,
    outcall: geoData.outcall,
    zones: geoData.zones,
    outcall_apartment: geoData.outcall_apartment,
    outcall_hotel: geoData.outcall_hotel,
    outcall_house: geoData.outcall_house,
    outcall_sauna: geoData.outcall_sauna,
    outcall_office: geoData.outcall_office,
    taxi_included: geoData.taxi_included
  })
  emit('update:geo', dataToEmit)
}
</script>

<!-- Все стили мигрированы на Tailwind CSS в template с адаптивностью -->