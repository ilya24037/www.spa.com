<template>
  <div class="geo-section">
    <!-- Секция карты и адреса -->
    <AddressMapSection 
      :initial-address="geoData.address"
      :initial-coordinates="geoData.coordinates"
      :initial-zoom="geoData.zoom"
      @update:address="handleAddressUpdate"
      @update:coordinates="handleCoordinatesUpdate"
      @data-changed="handleMapDataChange"
    />

    <!-- Секция выезда -->
    <div class="pt-6 border-t border-gray-200">
      <OutcallSection 
        :initial-outcall="geoData.outcall"
        @update:outcall="handleOutcallUpdate"
        @outcall-changed="handleOutcallChange"
      />
      
      <!-- Секция зон -->
      <div v-if="geoData.outcall === 'zones'" class="mt-4">
        <ZonesSection 
          :outcall-type="geoData.outcall"
          :initial-zones="geoData.zones"
          @update:zones="handleZonesUpdate"
          @zones-changed="handleZonesChange"
        />
      </div>

      <!-- Секция метро -->
      <div v-if="geoData.outcall !== 'none'" class="mt-4">
        <MetroSection 
          :outcall-type="geoData.outcall"
          :initial-stations="geoData.metro_stations"
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
}

interface Emits {
  'update:geo': [geo: string]
  'clear-force-validation': []
}

// Props с дефолтными значениями (точно как в оригинале)
const props = withDefaults(defineProps<Props>(), {
  geo: () => '',
  errors: () => ({}),
  forceValidation: false
})

// Emits
const emit = defineEmits<Emits>()

// Инициализация useGeoData composable с автосохранением
const {
  geoData,
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
  // Отменяем предыдущий таймер если есть
  if (emitTimer) {
    clearTimeout(emitTimer)
  }
  
  // Устанавливаем новый таймер с задержкой  
  emitTimer = setTimeout(() => {
    // Отправляем JSON строку как в оригинале
    const jsonData = toJson()
    emit('update:geo', jsonData)
  }, 300) // Задержка 300мс для группировки обновлений как в оригинале
}

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
}

const handleZonesChange = (data: { zones: string[] }) => {
  updateZones(data.zones)
}

/**
 * Обработка изменений станций метро
 */
const handleStationsUpdate = (stations: string[]) => {
  updateMetroStations(stations)
}

const handleStationsChange = (data: { stations: string[] }) => {
  updateMetroStations(data.stations)
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
  if (props.geo) {
    if (typeof props.geo === 'string') {
      loadFromJson(props.geo)
    } else {
      // Если передан объект, конвертируем в JSON
      loadFromJson(JSON.stringify(props.geo))
    }
  }
})

// Следим за изменениями props.geo для обновления данных извне
watch(() => props.geo, (newGeo) => {
  if (newGeo) {
    if (typeof newGeo === 'string') {
      loadFromJson(newGeo)
    } else {
      loadFromJson(JSON.stringify(newGeo))
    }
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