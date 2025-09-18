<template>
  <div class="geo-section p-5">
    <!-- Секция карты и адреса -->
    <AddressMapSection 
      :initial-address="geoData.address"
      :initial-coordinates="geoData.coordinates"
      :initial-zoom="geoData.zoom"
      :is-edit-mode="props.isEditMode"
      :force-validation="props.forceValidation"
      @update:address="handleAddressUpdate"
      @update:coordinates="handleCoordinatesUpdate"
      @data-changed="handleMapDataChange"
      @clearForceValidation="emit('clear-force-validation')"
    />

    <!-- Секция выезда -->
    <div class="pt-6 border-t border-gray-200">
      <OutcallSection 
        :initial-outcall="geoData.outcall"
        :current-city="currentCity"
        @update:outcall="handleOutcallUpdate"
        @outcall-changed="handleOutcallChange"
      />
      
      <!-- Секция зон -->
      <div v-if="geoData.outcall === 'zones'" class="mt-4">
        <ZonesSection 
          :outcall-type="geoData.outcall"
          :initial-zones="geoData.zones"
          :current-city="currentCity"
          @update:zones="handleZonesUpdate"
          @zones-changed="handleZonesChange"
        />
      </div>

      <!-- Секция метро -->
      <div v-if="geoData.outcall !== 'none'" class="mt-4">
        <MetroSection 
          :outcall-type="geoData.outcall"
          :initial-stations="geoData.metro_stations"
          :current-city="currentCity"
          @update:stations="handleStationsUpdate"
          @stations-changed="handleStationsChange"
        />
      </div>
      
      <!-- Секция типов мест -->
      <div v-if="geoData.outcall !== 'none'" class="mt-6 pt-6 border-t border-gray-200">
        <OutcallTypesSection 
          :outcall-type="geoData.outcall"
          :initial-types="outcallTypes"
          :initial-taxi-included="geoData.taxi_included"
          @update:types="handleTypesUpdate"
          @update:taxiIncluded="handleTaxiUpdate"
          @types-changed="handleTypesChange"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * GeoSection - оркестратор географических данных
 * 
 * Архитектурная ответственность:
 * - Координация между компонентами карты, выезда, зон, метро, типов мест
 * - Управление общим состоянием через useGeoData composable
 * - Обеспечение обратной совместимости с AdForm
 * - Автосохранение всех изменений
 * - Валидация и обработка ошибок
 * 
 * Принцип Single Responsibility соблюден:
 * - НЕ содержит логику карты, выезда, зон, метро, типов мест
 * - ТОЛЬКО координирует взаимодействие между компонентами
 * - ТОЛЬКО управляет общим состоянием
 */

import { computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useGeoData } from './composables/useGeoData'
import { CITIES_WITH_DISTRICTS } from '@/src/shared/config/cities'
import AddressMapSection from './components/AddressMapSection.vue'
import OutcallSection from './components/OutcallSection.vue'
import ZonesSection from './components/ZonesSection.vue'
import MetroSection from './components/MetroSection.vue'
import OutcallTypesSection from './components/OutcallTypesSection.vue'

// Типы для совместимости с оригинальным API
interface Props {
  geo?: string | Record<string, any>
  errors?: Record<string, string[]>
  forceValidation?: boolean
  isEditMode?: boolean
}

interface Emits {
  'update:geo': [geo: string]
  'clear-force-validation': []
}

// Props с дефолтными значениями (точно как в оригинале)
const props = withDefaults(defineProps<Props>(), {
  geo: () => '',
  errors: () => ({}),
  forceValidation: false,
  isEditMode: false
})

// Emits
const emit = defineEmits<Emits>()

// Инициализация useGeoData composable с автосохранением
const {
  geoData,
  isInitializing, // 🔍 Для диагностики конфликтов
  updateAddress,
  updateCoordinates,
  updateOutcall,
  updateZones,
  updateMetroStations,
  updateOutcallTypes,
  updateTaxiIncluded,
  loadFromJson,
  toJson,
  validateData,
  outcallTypes
} = useGeoData({
  autoSave: true,
  onDataChange: (data) => {
    // Автосохранение - отправляем данные родителю
    emitGeoData()
  }
})

// Таймер для debounce emit (как в оригинале)
let emitTimer: ReturnType<typeof setTimeout> | null = null

// Debounced отправка данных родителю (точно как в оригинале)
const emitGeoData = () => {
  // 🛡️ Дополнительная защита от emit во время инициализации
  if (isInitializing.value) {
    console.log('⏸️ [GeoSection] emitGeoData пропущен - идет инициализация')
    return
  }

  // Отменяем предыдущий таймер если есть
  if (emitTimer) {
    clearTimeout(emitTimer)
  }
  
  console.log('⏰ [GeoSection] emitGeoData вызван, запуск таймера 300ms')
  
  // Устанавливаем новый таймер с задержкой  
  emitTimer = setTimeout(() => {
    // Финальная проверка перед отправкой
    if (isInitializing.value) {
      console.log('⏸️ [GeoSection] emit отменен - все еще инициализация')
      return
    }
    
    // Отправляем JSON строку как в оригинале
    const jsonData = toJson()
    let parsedData = {}
    try {
      parsedData = JSON.parse(jsonData)
    } catch (error) {
      console.error('❌ [GeoSection] Ошибка парсинга JSON:', error)
      // Используем пустой объект если парсинг не удался
    }
    console.log('📤 [GeoSection] Отправляем данные родителю:', {
      address: parsedData.address,
      zones: parsedData.zones,
      metro_stations: parsedData.metro_stations,
      outcall: parsedData.outcall,
      json_length: jsonData.length,
      full_data: parsedData
    })
    emit('update:geo', jsonData)
  }, 300) // Задержка 300мс для группировки обновлений как в оригинале
}

// Извлечение города из адреса (исправлено для Yandex Geocoder)
const extractCityFromAddress = (address: string): string => {
  if (!address) return ''
  
  const parts = address.split(',').map(p => p.trim())
  
  // Формат Yandex Geocoder: "Россия, Москва, улица Тверская, 1"
  // или "Россия, Санкт-Петербург, Невский проспект, 50"
  // Берем вторую часть как город (пропускаем страну "Россия")
  if (parts.length >= 2) {
    const cityPart = parts[1]
    
    // Проверяем что это не область/край/республика
    if (!cityPart.match(/^(область|обл\.|край|республика|респ\.|автономный)/i)) {
      return cityPart
    }
  }
  
  // Fallback: поиск известного города в любой части адреса
  // Используем список городов из централизованной конфигурации
  for (const part of parts) {
    if (CITIES_WITH_DISTRICTS.includes(part as any)) {
      return part
    }
  }
  
  return ''
}

// ✅ ПРАВИЛЬНОЕ определение города через computed (без бесконечных циклов)
const currentCity = computed(() => {
  const city = extractCityFromAddress(geoData.address)
  console.log('🏙️ [GeoSection] Определен город:', city, 'из адреса:', geoData.address)
  return city
})

// Обработчики событий от компонентов

/**
 * Обработка изменений адреса и координат от карты
 */
const handleAddressUpdate = (address: string) => {
  updateAddress(address)
  
  // Очистка принудительной валидации при вводе адреса (как в оригинале)
  if (props.forceValidation && address && address.length > 3) {
    emit('clear-force-validation')
  }
}

const handleCoordinatesUpdate = (coords: { lat: number; lng: number } | null) => {
  updateCoordinates(coords)
}

const handleMapDataChange = (data: { address: string; coordinates: { lat: number; lng: number } | null }) => {
  updateAddress(data.address)
  updateCoordinates(data.coordinates)
}

/**
 * Обработка изменений типа выезда
 */
const handleOutcallUpdate = (outcall: 'none' | 'city' | 'zones') => {
  updateOutcall(outcall)
}

const handleOutcallChange = (data: { outcall: 'none' | 'city' | 'zones'; shouldClearZones: boolean }) => {
  updateOutcall(data.outcall, data.shouldClearZones)
}

/**
 * Обработка изменений зон
 */
const handleZonesUpdate = (zones: string[]) => {
  updateZones(zones)
  emitGeoData() // 🔥 Отправляем изменения родителю для сохранения
}

const handleZonesChange = (data: { zones: string[] }) => {
  updateZones(data.zones)
  emitGeoData() // 🔥 Отправляем изменения родителю для сохранения
}

/**
 * Обработка изменений станций метро
 */
const handleStationsUpdate = (stations: string[]) => {
  updateMetroStations(stations)
  emitGeoData() // 🔥 Отправляем изменения родителю для сохранения
}

const handleStationsChange = (data: { stations: string[] }) => {
  updateMetroStations(data.stations)
  emitGeoData() // 🔥 Отправляем изменения родителю для сохранения
}

/**
 * Обработка изменений типов мест и такси
 */
const handleTypesUpdate = (types: { apartment: boolean; hotel: boolean; office: boolean; sauna: boolean; house: boolean }) => {
  updateOutcallTypes(types)
}

const handleTaxiUpdate = (taxiIncluded: boolean) => {
  updateTaxiIncluded(taxiIncluded)
}

const handleTypesChange = (data: { 
  outcallTypes: { apartment: boolean; hotel: boolean; office: boolean; sauna: boolean; house: boolean }
  taxiIncluded: boolean 
}) => {
  updateOutcallTypes(data.outcallTypes)
  updateTaxiIncluded(data.taxiIncluded)
}

// Загрузка начальных данных при монтировании
onMounted(() => {
  console.log('🚀 [GeoSection] onMounted вызван:', {
    props_geo_type: typeof props.geo,
    props_geo_value: props.geo,
    props_geo_length: typeof props.geo === 'string' ? props.geo.length : 'not string'
  })
  
  if (props.geo) {
    if (typeof props.geo === 'string') {
      console.log('📊 [GeoSection] Загружаем из строки')
      loadFromJson(props.geo)
    } else {
      // Если передан объект, конвертируем в JSON
      console.log('📊 [GeoSection] Загружаем из объекта')
      loadFromJson(JSON.stringify(props.geo))
    }
  } else {
    console.log('⚠️ [GeoSection] props.geo пустой при монтировании')
  }
})

// Следим за изменениями props.geo для обновления данных извне
watch(() => props.geo, (newGeo, oldGeo) => {
  console.log('👁️ [GeoSection] watch props.geo сработал:', {
    newGeo_type: typeof newGeo,
    newGeo_value: newGeo,
    oldGeo_type: typeof oldGeo,
    has_changed: newGeo !== oldGeo
  })
  
  if (newGeo) {
    if (typeof newGeo === 'string') {
      console.log('📊 [GeoSection] Watch загружаем из строки')
      loadFromJson(newGeo)
    } else {
      console.log('📊 [GeoSection] Watch загружаем из объекта')
      loadFromJson(JSON.stringify(newGeo))
    }
  } else {
    console.log('⚠️ [GeoSection] newGeo пустой в watch')
  }
}, { deep: true })

// Очистка таймера при размонтировании
onBeforeUnmount(() => {
  if (emitTimer) {
    clearTimeout(emitTimer)
    emitTimer = null
  }
})
</script>

<style scoped>
/**
 * Стили GeoSection - минимальные, основная стилизация в компонентах
 */

.geo-section {
  @apply w-full space-y-0;
}

/* Все стили делегированы компонентам - принцип разделения ответственности */
</style>