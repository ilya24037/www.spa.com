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
          :show-address-icon="false"
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
        :show-geolocation-button="false"
        :auto-detect-location="false"
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
import { ref, watch, computed, reactive, onMounted, onBeforeUnmount, nextTick } from 'vue'
import Badge from '@/src/shared/ui/atoms/Badge/Badge.vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import AddressInput from '@/src/shared/ui/molecules/AddressInput/AddressInput.vue'
import YandexMap from '@/src/shared/ui/molecules/YandexMapPicker/YandexMap.vue'
import ZoneSelector from '@/src/shared/ui/molecules/ZoneSelector/ZoneSelector.vue'
import MetroSelector from '@/src/shared/ui/molecules/MetroSelector/MetroSelector.vue'
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

// При монтировании компонента центрируем карту если есть адрес
onMounted(async () => {
  // Ждем пока карта инициализируется
  await nextTick()
  
  // Небольшая задержка для гарантии полной инициализации карты
  setTimeout(() => {
    // Если есть адрес при загрузке, центрируем карту на нем
    if (geoData.address && geoData.address.length > 3) {
      console.log('[GeoSection] 🎯 Центрирование карты на начальном адресе:', geoData.address)
      searchAddressOnMap(geoData.address)
    } else if (geoData.coordinates) {
      // Если есть координаты но нет адреса, центрируем по координатам
      console.log('[GeoSection] 📍 Центрирование карты на начальных координатах:', geoData.coordinates)
      if (mapRef.value) {
        mapRef.value.setCoordinates(geoData.coordinates, 15)
      }
    }
  }, 1000) // Даем карте время полностью загрузиться
})

// Методы
// Таймер для debounce поиска
let searchTimer: ReturnType<typeof setTimeout> | null = null
let lastSearchedAddress = '' // Для предотвращения дублирования поиска

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
  
  // Отменяем предыдущий поиск если он был
  if (searchTimer) {
    clearTimeout(searchTimer)
  }
  
  // Если адрес не пустой и отличается от последнего искомого, запускаем поиск
  if (value && value.length > 3 && value !== lastSearchedAddress) {
    searchTimer = setTimeout(async () => {
      lastSearchedAddress = value // Запоминаем, что искали
      await searchAddressOnMap(value)
    }, 800) // Задержка 800мс после последнего ввода
  }
  
  emitGeoData()
}

// Функция поиска адреса на карте
const searchAddressOnMap = async (address: string) => {
  if (!mapRef.value || !address) return
  
  searchLoading.value = true
  try {
    console.log('[GeoSection] 🔍 Автоматический поиск адреса:', address)
    
    // Вызываем метод searchAddress у карты
    const found = await mapRef.value.searchAddress(address)
    
    if (found) {
      console.log('[GeoSection] ✅ Адрес найден и карта отцентрирована')
      // Карта сама отцентрируется и обновит координаты через события
    } else {
      console.log('[GeoSection] ⚠️ Адрес не найден на карте')
    }
  } catch (error) {
    console.error('[GeoSection] ❌ Ошибка поиска адреса:', error)
  } finally {
    searchLoading.value = false
  }
}

const clearAddress = () => {
  console.log('[GeoSection] 🗑️ Очистка адреса')
  geoData.address = ''
  // НЕ очищаем координаты сразу, чтобы карта не пропала
  // Координаты обнулятся при следующем выборе адреса
  detailedAddress.value = ''
  
  // Возвращаем карту к дефолтному центру (Пермь)
  if (mapRef.value && mapRef.value.setCoordinates) {
    const defaultCenter = { lat: 58.0105, lng: 56.2502 }
    mapRef.value.setCoordinates(defaultCenter, 12)
    geoData.coordinates = defaultCenter
  }
  
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

// Функция emitOutcallChanges больше не нужна, так как есть watch на локальные ref

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
  console.log('[GeoSection] 📍 Выбрана подсказка:', suggestion.value)
  
  // Обновляем адрес в поле
  geoData.address = suggestion.value
  
  // Запоминаем, что уже ищем этот адрес (предотвращаем дублирование)
  lastSearchedAddress = suggestion.value
  
  // Отменяем любой ожидающий поиск
  if (searchTimer) {
    clearTimeout(searchTimer)
    searchTimer = null
  }
  
  // Автоматически ищем выбранный адрес на карте и центрируем
  searchLoading.value = true
  
  try {
    if (mapRef.value && typeof mapRef.value.searchAddress === 'function') {
      const success = await mapRef.value.searchAddress(suggestion.value)
      
      if (success) {
        console.log('[GeoSection] ✅ Карта отцентрирована на адресе:', suggestion.value)
      } else {
        console.log('[GeoSection] ❌ Не удалось найти адрес на карте')
        if (addressInputRef.value) {
          addressInputRef.value.setSearchStatus('Адрес не найден на карте')
        }
      }
    } else {
      console.error('[GeoSection] ❌ mapRef или searchAddress недоступны')
    }
  } catch (error) {
    console.error('[GeoSection] ❌ Ошибка при центрировании карты:', error)
  } finally {
    searchLoading.value = false
  }
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

// Cleanup при размонтировании компонента
onBeforeUnmount(() => {
  // Очищаем таймер поиска если он активен
  if (searchTimer) {
    clearTimeout(searchTimer)
    searchTimer = null
  }
  
  // Очищаем таймер emit если он активен
  if (emitTimer) {
    clearTimeout(emitTimer)
    emitTimer = null
  }
  
  // Очищаем ссылку на карту если она существует
  if (mapRef.value) {
    mapRef.value = null
  }
  // Очищаем ссылки на другие компоненты
  if (addressInputRef.value) {
    addressInputRef.value = null
  }
})
</script>

<!-- Все стили мигрированы на Tailwind CSS в template с адаптивностью -->