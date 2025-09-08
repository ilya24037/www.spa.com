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
      <VueYandexMap
        :initial-address="geoData.address || 'Москва, Красная площадь, 1'"
        :initial-coordinates="[geoData.coordinates?.lng || 37.6176, geoData.coordinates?.lat || 55.7558]"
        :height="360"
        @address-selected="handleAddressSelected"
        @coordinates-changed="handleCoordinatesChanged"
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
// import Badge from '@/src/shared/ui/atoms/Badge/Badge.vue' // УДАЛЕН - больше не используется
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import ZoneSelector from '@/src/shared/ui/molecules/ZoneSelector/ZoneSelector.vue'
import MetroSelector from '@/src/shared/ui/molecules/MetroSelector/MetroSelector.vue'
import VueYandexMap from '@/src/shared/ui/molecules/VueYandexMap/VueYandexMap.vue'
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

// При монтировании компонента (VueYandexMap работает автоматически)
onMounted(async () => {
  // VueYandexMap автоматически инициализируется с переданными данными
  // console.log('🚀 [GeoSection] Компонент смонтирован, используется VueYandexMap')
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

// Обработчики для VueYandexMap компонента
const handleAddressSelected = (data: { address: string, lat: number, lng: number, precision: string }) => {
  // console.log('🏠 [GeoSection] Адрес выбран через VueYandexMap:', data)
  
  // Обновляем geoData
  geoData.address = data.address
  geoData.coordinates = { lat: data.lat, lng: data.lng }
  
  // Автосохранение
  emitGeoData()
}

const handleCoordinatesChanged = (data: { lat: number, lng: number }) => {
  // console.log('📍 [GeoSection] Координаты изменены через VueYandexMap:', data)
  
  // Обновляем только координаты
  geoData.coordinates = { lat: data.lat, lng: data.lng }
  
  // Автосохранение
  emitGeoData()
  
  // Очищаем принудительную валидацию
  if (props.forceValidation) {
    emit('clear-force-validation')
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

<!-- Все стили мигрированы на Tailwind CSS в template с адаптивностью -->