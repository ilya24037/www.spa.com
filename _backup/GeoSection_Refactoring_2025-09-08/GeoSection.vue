<template>
  <div class="rounded-lg p-6">
    <!-- Секция адреса -->
    <div class="mb-6">
      <h3 class="text-base font-medium text-gray-900 mb-2">Ваш адрес</h3>
      <p class="text-sm text-gray-600 leading-relaxed mb-4">
        Клиенты выбирают исполнителя по точному адресу, когда ищут услуги поблизости.
      </p>
      
    </div>

    <!-- Карта с поиском адреса -->
    <div class="mb-6">
      <!-- Поисковая строка -->
      <div class="search-container">
        <input
          v-model="searchQuery"
          @input="handleSearchInput"
          @focus="showSuggestions = true"
          @blur="hideSuggestions"
          type="text"
          placeholder="Введите адрес для поиска..."
          class="search-input"
        />
        
        <!-- Список подсказок -->
        <div v-if="showSuggestions && suggestions.length > 0" class="suggestions-list">
          <div
            v-for="(suggestion, index) in suggestions"
            :key="index"
            @click="selectSuggestion(suggestion)"
            class="suggestion-item"
          >
            {{ suggestion.displayName }}
          </div>
        </div>
      </div>

      <!-- Карта -->
      <div class="map-container">
        <!-- Vue Yandex Maps компонент с обработкой ошибок -->
        <Suspense>
          <template #default>
            <YandexMap
              :settings="{
                location: {
                  center: currentCoordinates,
                  zoom: currentZoom
                }
              }"
              :width="'100%'"
              :height="'320px'"
              @click="handleMapClick"
            >
              <YandexMapDefaultSchemeLayer />
              
              <!-- Слушатель событий карты для обратного геокодинга -->
              <YandexMapListener :settings="listenerSettings" />
              
              <!-- Элементы управления картой -->
              <YandexMapControls :settings="{ position: 'right' }">
                <YandexMapControl>
                  <div class="flex flex-col bg-white rounded shadow-md">
                    <button 
                      @click.stop="zoomIn" 
                      class="px-3 py-2 hover:bg-gray-100 border-b"
                      title="Приблизить"
                    >
                      +
                    </button>
                    <button 
                      @click.stop="zoomOut" 
                      class="px-3 py-2 hover:bg-gray-100"
                      title="Отдалить"
                    >
                      -
                    </button>
                  </div>
                </YandexMapControl>
              </YandexMapControls>
            </YandexMap>
          </template>
          
          <template #fallback>
            <div class="w-full h-80 bg-gray-100 rounded-lg flex items-center justify-center">
              <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto mb-4"></div>
                <p class="text-gray-600">Загрузка карты...</p>
              </div>
            </div>
          </template>
        </Suspense>
        
        <!-- Центральный маркер -->
        <div class="center-marker">
          <div class="marker-pin"></div>
        </div>
      </div>
    </div>

    <!-- Секция выезда -->
    <div class="pt-6 border-t border-gray-200">
      <h3 class="text-base font-medium text-gray-900 mb-2">Куда выезжаете</h3>
      <p class="text-sm text-gray-600 leading-relaxed mb-4">
        Укажите все зоны выезда, чтобы клиенты понимали, доберётесь ли вы до них.
      </p>
      
      <div class="flex flex-col gap-2">
        <BaseRadio
          :model-value="geoData.outcall"
          value="none"
          name="outcall"
          label="Не выезжаю"
          @update:modelValue="updateOutcall"
        />
        <BaseRadio
          :model-value="geoData.outcall"
          value="city"
          name="outcall"
          label="По всему городу"
          @update:modelValue="updateOutcall"
        />
        <BaseRadio
          :model-value="geoData.outcall"
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

      <!-- Станции метро -->
      <div v-if="geoData.outcall !== 'none'" class="mt-4">
        <p class="text-sm text-gray-600 mb-3">
          Выберите станции метро, к которым вы готовы выезжать:
        </p>
        <MetroSelector 
          v-model="geoData.metro_stations"
          :stations="moscowMetroStations"
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
            v-model="localOutcallApartment"
            name="outcall_apartment"
            label="На квартиру"
          />
          <BaseCheckbox
            v-model="localOutcallHotel"
            name="outcall_hotel"
            label="В гостиницу"
          />
          <BaseCheckbox
            v-model="localOutcallOffice"
            name="outcall_office"
            label="В офис"
          />
          <BaseCheckbox
            v-model="localOutcallSauna"
            name="outcall_sauna"
            label="В сауну"
          />
          <BaseCheckbox
            v-model="localOutcallHouse"
            name="outcall_house"
            label="В загородный дом"
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
              v-model="localTaxiIncluded"
              :value="false"
              label="Оплачивается отдельно"
              name="taxi"
            />
            <BaseRadio
              v-model="localTaxiIncluded"
              :value="true"
              label="Включено в стоимость"
              name="taxi"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed, reactive, onMounted, onBeforeUnmount, nextTick, Suspense } from 'vue'
// import Badge from '@/src/shared/ui/atoms/Badge/Badge.vue' // УДАЛЕН - больше не используется
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import ZoneSelector from '@/src/shared/ui/molecules/ZoneSelector/ZoneSelector.vue'
import MetroSelector from '@/src/shared/ui/molecules/MetroSelector/MetroSelector.vue'
// Импорт компонентов vue-yandex-maps для Vue 3
import { YandexMap, YandexMapDefaultSchemeLayer, YandexMapControls, YandexMapControl, YandexMapListener } from 'vue-yandex-maps'
import { useMetroData } from '@/src/shared/ui/molecules/MetroSelector/composables/useMetroData'

// Типы
interface GeoData {
  address: string
  coordinates: { lat: number; lng: number } | null
  outcall: 'none' | 'city' | 'zones'
  zones: string[]
  metro_stations: string[]
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
  forceValidation?: boolean
}

interface Emits {
  'update:geo': [value: string]
  'clear-force-validation': []
}

// Props
const props = withDefaults(defineProps<Props>(), {
  geo: () => '',
  errors: () => ({}),
  forceValidation: false
})

// Emits
const emit = defineEmits<Emits>()

// Переменные для карты
const searchQuery = ref('')
const suggestions = ref<any[]>([])
const showSuggestions = ref(false)
const mapRef = ref<any>(null)
const currentCoordinates = ref<[number, number]>([37.6176, 55.7558])
const currentZoom = ref(12)
const isNavigating = ref(false)
let geocodeTimeout: NodeJS.Timeout | null = null
let updateTimeout: NodeJS.Timeout | null = null
let actionEndTimeout: NodeJS.Timeout | null = null

// Настройки карты
const mapSettings = computed(() => ({
  location: {
    center: currentCoordinates.value,
    zoom: currentZoom.value
  },
  mode: 'raster',
  behaviors: ['default', 'scrollZoom', 'dblClickZoom', 'drag'],
  controls: []
}))

const controlsSettings = {
  position: 'right'
}

// Вспомогательные функции для парсинга (должны быть объявлены до использования)
const toBoolean = (value: any, defaultValue: boolean = false): boolean => {
  if (typeof value === 'boolean') return value
  if (typeof value === 'string') {
    if (value === '1' || value === 'true') return true
    if (value === '0' || value === 'false') return false
  }
  if (typeof value === 'number') return value === 1
  return defaultValue
}

const safeJsonParse = (str: string): any => {
  try {
    return JSON.parse(str)
  } catch {
    return str
  }
}

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
          metro_stations: parsed.metro_stations || [],
          // Явное преобразование в boolean с правильными дефолтами
          outcall_apartment: toBoolean(parsed.outcall_apartment, true),
          outcall_hotel: toBoolean(parsed.outcall_hotel, false),
          outcall_house: toBoolean(parsed.outcall_house, false),
          outcall_sauna: toBoolean(parsed.outcall_sauna, false),
          outcall_office: toBoolean(parsed.outcall_office, false),
          taxi_included: toBoolean(parsed.taxi_included, false)
        }
      } catch {
        // Если не JSON, считаем что это просто адрес
        return {
          address: value,
          coordinates: null,
          outcall: 'none',
          zones: [],
          metro_stations: [],
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
      metro_stations: value.metro_stations || [],
      // Явное преобразование в boolean с правильными дефолтами
      outcall_apartment: toBoolean(value.outcall_apartment, true),
      outcall_hotel: toBoolean(value.outcall_hotel, false),
      outcall_house: toBoolean(value.outcall_house, false),
      outcall_sauna: toBoolean(value.outcall_sauna, false),
      outcall_office: toBoolean(value.outcall_office, false),
      taxi_included: toBoolean(value.taxi_included, false)
    }
  }
  
  return {
    address: '',
    coordinates: null,
    outcall: 'none',
    zones: [],
    metro_stations: [],
    outcall_apartment: true,
    outcall_hotel: false,
    outcall_house: false,
    outcall_sauna: false,
    outcall_office: false,
    taxi_included: false
  }
}

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

// Локальные данные - основные поля остаются в reactive для карты и других компонентов
const geoData = reactive<GeoData>({
  address: '',
  coordinates: null,
  outcall: 'none',
  zones: [],
  metro_stations: [],
  // Эти поля теперь будут управляться через локальные ref (для совместимости структуры)
  outcall_apartment: true,
  outcall_hotel: false,
  outcall_house: false,
  outcall_sauna: false,
  outcall_office: false,
  taxi_included: false
})

// Локальные ref переменные для outcall полей (паттерн из DescriptionSection)
const localOutcallApartment = ref(true)
const localOutcallHotel = ref(false)
const localOutcallHouse = ref(false)
const localOutcallSauna = ref(false)
const localOutcallOffice = ref(false)
const localTaxiIncluded = ref(false)

// Методы управления картой
const zoomIn = () => {
  if (currentZoom.value < 18) {
    currentZoom.value++
  }
}

const zoomOut = () => {
  if (currentZoom.value > 5) {
    currentZoom.value--
  }
}

// Инициализируем локальные ref из props при монтировании (паттерн из DescriptionSection)
if (props.geo) {
  const parsed = parseGeoData(props.geo)
  Object.assign(geoData, parsed)
  
  // Синхронизируем локальные ref переменные
  localOutcallApartment.value = parsed.outcall_apartment
  localOutcallHotel.value = parsed.outcall_hotel
  localOutcallHouse.value = parsed.outcall_house
  localOutcallSauna.value = parsed.outcall_sauna
  localOutcallOffice.value = parsed.outcall_office
  localTaxiIncluded.value = parsed.taxi_included
}

// Данные станций метро
const { moscowMetroStations } = useMetroData()


// Состояние для гибридной архитектуры
const detailedAddress = ref('') // Подробный адрес с карты

// Ref переменные для координат удалены - теперь используется VueYandexMap

// Refs для компонентов больше не нужны для гибридного подхода

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


// Инициализация данных из props
const initData = parseGeoData(props.geo)

// Применяем начальные данные к reactive объекту
Object.assign(geoData, initData)

// Следим за изменениями props (убираем immediate, так как уже применили начальные данные)
watch(() => props.geo, (newValue) => {
  const parsed = parseGeoData(newValue)
  Object.assign(geoData, parsed)
  
  // Синхронизируем локальные ref переменные (паттерн из DescriptionSection)
  localOutcallApartment.value = parsed.outcall_apartment
  localOutcallHotel.value = parsed.outcall_hotel
  localOutcallHouse.value = parsed.outcall_house
  localOutcallSauna.value = parsed.outcall_sauna
  localOutcallOffice.value = parsed.outcall_office
  localTaxiIncluded.value = parsed.taxi_included
})

// ВАЖНО: Следим за изменениями локальных ref и автоматически сохраняем
watch([localOutcallApartment, localOutcallHotel, localOutcallHouse, localOutcallSauna, localOutcallOffice, localTaxiIncluded], () => {
  // Обновляем geoData из локальных ref переменных
  geoData.outcall_apartment = localOutcallApartment.value
  geoData.outcall_hotel = localOutcallHotel.value
  geoData.outcall_house = localOutcallHouse.value
  geoData.outcall_sauna = localOutcallSauna.value
  geoData.outcall_office = localOutcallOffice.value
  geoData.taxi_included = localTaxiIncluded.value
  
  // Эмитим изменения geo
  emitGeoData()
})

// Автосохранение при изменении zones и metro_stations
// Эти поля используют v-model без @update:modelValue, поэтому нужны watcher'ы
watch(() => geoData.zones, () => {
  emitGeoData()
}, { deep: true })

watch(() => geoData.metro_stations, () => {
  emitGeoData()
}, { deep: true })

// Следим за заполнением адреса для сброса валидации
watch(() => geoData.address, (newAddress) => {
  if (props.forceValidation && newAddress && newAddress.length > 3) {
    emit('clear-force-validation')
  }
})

// Watchers для координат удалены - теперь используется VueYandexMap

// Экспорт методов для внешнего использования (гибридная архитектура не требует принудительной инициализации)
defineExpose({
  // Пустой объект - все работает автоматически через postMessage
})

// Computed для гибридного компонента AddressSearchWithMap
const addressModel = computed({
  get() {
    return {
      address: geoData.address || '',
      lat: geoData.coordinates?.lat || null,
      lng: geoData.coordinates?.lng || null
    }
  },
  set(value: { address: string, lat: number | null, lng: number | null }) {
    geoData.address = value.address || ''
    if (value.lat && value.lng) {
      geoData.coordinates = { lat: value.lat, lng: value.lng }
    } else {
      geoData.coordinates = null
    }
    emitGeoData()
  }
})

// Гибридная архитектура не требует дополнительных computed для координат

// При монтировании компонента
onMounted(async () => {
  // Инициализация начальных значений для карты
  searchQuery.value = geoData.address || 'Москва, Красная площадь, 1'
  
  if (geoData.coordinates) {
    currentCoordinates.value = [geoData.coordinates.lng, geoData.coordinates.lat]
  } else {
    currentCoordinates.value = [37.6176, 55.7558] // Координаты по умолчанию (Москва)
  }
  
  // Если есть начальные координаты, получаем адрес с задержкой
  if (geoData.coordinates) {
    setTimeout(() => {
      getAddressFromCoordinates(geoData.coordinates.lat, geoData.coordinates.lng)
    }, 1000) // Задержка для инициализации карты
  }
})

// Очистка при размонтировании
onBeforeUnmount(() => {
  if (geocodeTimeout) {
    clearTimeout(geocodeTimeout)
  }
  if (updateTimeout) {
    clearTimeout(updateTimeout)
  }
  if (actionEndTimeout) {
    clearTimeout(actionEndTimeout)
  }
})

// Методы


// Методы для работы с гибридной архитектурой

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

// Функция emitOutcallChanges больше не нужна, так как есть watch на локальные ref

// Все методы поиска адреса теперь обрабатываются гибридным компонентом AddressSearchWithMap

// Методы для работы с картой
const handleSearchInput = () => {
  if (geocodeTimeout) {
    clearTimeout(geocodeTimeout)
  }
  
  geocodeTimeout = setTimeout(() => {
    if (searchQuery.value.trim().length > 2) {
      searchAddress(searchQuery.value)
    } else {
      suggestions.value = []
      showSuggestions.value = false
    }
  }, 300)
}

// Поиск адреса через Yandex Geocoder API
const searchAddress = async (query: string) => {
  try {
    const response = await fetch(
      `https://geocode-maps.yandex.ru/1.x/?apikey=23ff8acc-835f-4e99-8b19-d33c5d346e18&format=json&geocode=${encodeURIComponent(query)}&results=5`
    )
    
    const data = await response.json()
    
    if (data.response && data.response.GeoObjectCollection) {
      const geoObjects = data.response.GeoObjectCollection.featureMember || []
      
      suggestions.value = geoObjects.map((item: any) => {
        const geoObject = item.GeoObject
        const coords = geoObject.Point.pos.split(' ').map(Number)
        
        return {
          displayName: geoObject.name,
          coordinates: [coords[1], coords[0]], // [lat, lng]
          precision: geoObject.metaDataProperty?.GeocoderMetaData?.precision || 'unknown'
        }
      })
      
      showSuggestions.value = true
    }
  } catch (error) {
    console.error('Ошибка поиска адреса:', error)
  }
}

// Выбор подсказки
const selectSuggestion = (suggestion: any) => {
  searchQuery.value = suggestion.displayName
  suggestions.value = []
  showSuggestions.value = false
  
  const [lat, lng] = suggestion.coordinates
  
  // Устанавливаем флаг навигации
  isNavigating.value = true
  
  // Обновляем реактивные переменные
  currentCoordinates.value = [lng, lat]
  currentZoom.value = 15
  
  // Навигируем карту к выбранному адресу с задержкой
  if (mapRef.value) {
    setTimeout(() => {
      try {
        if (mapRef.value && typeof mapRef.value.setLocation === 'function') {
          mapRef.value.setLocation({
            center: [lng, lat],
            zoom: 15,
            duration: 500
          })
        }
        
        setTimeout(() => {
          isNavigating.value = false
        }, 600)
      } catch (error) {
        console.error('Ошибка навигации карты:', error)
        isNavigating.value = false
      }
    }, 200)
  } else {
    isNavigating.value = false
  }
  
  // Обновляем geoData
  geoData.address = suggestion.displayName
  geoData.coordinates = { lat, lng }
  emitGeoData()
}

// Скрытие подсказок
const hideSuggestions = () => {
  setTimeout(() => {
    showSuggestions.value = false
  }, 200)
}

// Обработка получения экземпляра карты
const handleMapInput = (map: any) => {
  if (map) {
    mapRef.value = map
  }
}

// Обработка окончания действия на карте (перемещение, зум)
const handleActionEnd = (event: any) => {
  try {
    if (event && event.location && Array.isArray(event.location.center) && event.location.center.length >= 2) {
      const [lng, lat] = event.location.center
      const zoom = event.location.zoom
      
      // Проверяем что координаты валидные
      if (typeof lng === 'number' && typeof lat === 'number') {
        // Мгновенно обновляем координаты
        currentCoordinates.value = [lng, lat]
        if (typeof zoom === 'number') {
          currentZoom.value = zoom
        }
        geoData.coordinates = { lat, lng }
        emitGeoData()
        
        // Debounce для обратного геокодинга при движении карты
        if (actionEndTimeout) {
          clearTimeout(actionEndTimeout)
        }
        
        actionEndTimeout = setTimeout(() => {
          // Получаем адрес только если не происходит навигация
          if (!isNavigating.value) {
            getAddressFromCoordinates(lat, lng)
          }
        }, 500) // Задержка 500мс для плавного движения карты
      }
    }
  } catch (error) {
    console.error('Ошибка обработки завершения действия на карте:', error)
  }
}

// Настройки слушателя событий карты (объявляется после handleActionEnd)
const listenerSettings = {
  onActionEnd: handleActionEnd
}

// Обработка клика по карте
const handleMapClick = (event: any) => {
  try {
    // В vue-yandex-maps для Vue 3 координаты приходят в event.location
    if (event && event.location && Array.isArray(event.location.center) && event.location.center.length >= 2) {
      const [lng, lat] = event.location.center
      
      // Проверяем что координаты валидные
      if (typeof lng === 'number' && typeof lat === 'number') {
        // Debounce обновления координат
        if (updateTimeout) {
          clearTimeout(updateTimeout)
        }
        
        updateTimeout = setTimeout(() => {
          currentCoordinates.value = [lng, lat]
          geoData.coordinates = { lat, lng }
          emitGeoData()
          
          // Получаем адрес только если не происходит навигация
          if (!isNavigating.value) {
            getAddressFromCoordinates(lat, lng)
          }
        }, 100)
      }
    }
  } catch (error) {
    console.error('Ошибка обработки клика по карте:', error)
  }
}

// Обработка обновления карты
const handleMapUpdate = (event: any) => {
  try {
    if (event && event.location && Array.isArray(event.location.center) && event.location.center.length >= 2) {
      const [lng, lat] = event.location.center
      const zoom = event.location.zoom
      
      // Проверяем что координаты и зум валидные
      if (typeof lng === 'number' && typeof lat === 'number' && typeof zoom === 'number') {
        // Debounce обновления координат
        if (updateTimeout) {
          clearTimeout(updateTimeout)
        }
        
        updateTimeout = setTimeout(() => {
          currentCoordinates.value = [lng, lat]
          currentZoom.value = zoom
          geoData.coordinates = { lat, lng }
          emitGeoData()
          
          // Получаем адрес только если не происходит навигация
          if (!isNavigating.value) {
            getAddressFromCoordinates(lat, lng)
          }
        }, 100)
      }
    }
  } catch (error) {
    console.error('Ошибка обновления карты:', error)
  }
}

// Получение адреса по координатам (обратное геокодирование)
const getAddressFromCoordinates = async (lat: number, lng: number) => {
  // Проверяем валидность координат
  if (typeof lat !== 'number' || typeof lng !== 'number' || isNaN(lat) || isNaN(lng)) {
    console.error('Невалидные координаты для геокодинга:', { lat, lng })
    return
  }
  
  try {
    const url = `https://geocode-maps.yandex.ru/1.x/?apikey=23ff8acc-835f-4e99-8b19-d33c5d346e18&format=json&geocode=${lng},${lat}&results=1&lang=ru_RU`
    
    const response = await fetch(url)
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    
    if (data?.response?.GeoObjectCollection?.featureMember) {
      const geoObjects = data.response.GeoObjectCollection.featureMember
      
      if (geoObjects.length > 0) {
        const geoObject = geoObjects[0]?.GeoObject
        const address = geoObject?.name || geoObject?.metaDataProperty?.GeocoderMetaData?.text
        
        if (address) {
          searchQuery.value = address
          geoData.address = address
          emitGeoData()
          return
        }
      }
    }
    
    // Если адрес не найден, показываем координаты
    searchQuery.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`
  } catch (error) {
    console.error('Ошибка геокодирования:', error)
    // В случае ошибки показываем координаты
    searchQuery.value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`
  }
}

const handleAddressCleared = () => {
  // console.log('🧹 [GeoSection] Адрес очищен через гибридный компонент')
  
  // Очищаем данные адреса
  geoData.address = ''
  geoData.coordinates = null
  detailedAddress.value = ''
  
  // Автосохранение
  emitGeoData()
}

// Таймер для debounce emit
let emitTimer: ReturnType<typeof setTimeout> | null = null

const emitGeoData = () => {
  // Отменяем предыдущий таймер если есть
  if (emitTimer) {
    clearTimeout(emitTimer)
  }
  
  // Устанавливаем новый таймер с задержкой
  emitTimer = setTimeout(() => {
    // Формируем JSON строку для отправки
    const dataToEmit = JSON.stringify({
      address: geoData.address,
      coordinates: geoData.coordinates,
      outcall: geoData.outcall,
      zones: geoData.zones,
      metro_stations: geoData.metro_stations,
      outcall_apartment: geoData.outcall_apartment,
      outcall_hotel: geoData.outcall_hotel,
      outcall_house: geoData.outcall_house,
      outcall_sauna: geoData.outcall_sauna,
      outcall_office: geoData.outcall_office,
      taxi_included: geoData.taxi_included
    })
    emit('update:geo', dataToEmit)
  }, 300) // Задержка 300мс для группировки обновлений
}

// Cleanup при размонтировании компонента (гибридная архитектура)
onBeforeUnmount(() => {
  // Очищаем таймер emit если он активен
  if (emitTimer) {
    clearTimeout(emitTimer)
    emitTimer = null
  }
  
  // Гибридная архитектура очищается автоматически
})
</script>

<style scoped>
/* Стили для карты */
.search-container {
  position: relative;
  margin-bottom: 12px;
}

.search-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.suggestions-list {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e0e0e0;
  border-top: none;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  max-height: 200px;
  overflow-y: auto;
}

.suggestion-item {
  padding: 12px 16px;
  cursor: pointer;
  border-bottom: 1px solid #f0f0f0;
  font-size: 14px;
  transition: background-color 0.2s ease;
}

.suggestion-item:hover {
  background-color: #f8f9fa;
}

.suggestion-item:last-child {
  border-bottom: none;
}

.map-container {
  position: relative;
  width: 100%;
  height: 360px;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid #e0e0e0;
}

.center-marker {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -100%);
  z-index: 10;
  pointer-events: none;
}

.marker-pin {
  width: 24px;
  height: 24px;
  background: #ff4444;
  border: 3px solid white;
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.marker-pin::after {
  content: '';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(45deg);
  width: 8px;
  height: 8px;
  background: white;
  border-radius: 50%;
}
</style>