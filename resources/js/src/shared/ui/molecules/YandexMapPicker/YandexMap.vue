<template>
  <div class="yandex-map" role="region" :aria-label="ariaLabel">
    <MapContainer
      ref="mapContainerRef"
      v-bind="adapterProps"
      @update:modelValue="handleUpdate"
      @ready="handleReady"
      @marker-click="$emit('marker-click', $event)"
      @cluster-click="$emit('cluster-click', $event)"
      @address-found="handleAddressFound"
      @center-change="$emit('bounds-change', { center: $event })"
    >
      <template #overlays>
        <!-- Сохраняем старые оверлеи для совместимости -->
        <slot name="overlays" />
      </template>
    </MapContainer>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import type { MapMarker, Coordinates } from '@/src/features/map/core/MapStore'
import { parseCoordinates } from '@/src/features/map/utils/mapHelpers'
import MapContainer from '@/src/features/map/components/MapContainer.vue'

// КРИТИЧЕСКИ ВАЖНО: Экспорт composables вынесен в index.ts для обратной совместимости

export type { MapMarker } from '@/src/features/map/core/MapStore'

interface Props {
  modelValue?: string
  height?: number
  center?: Coordinates
  zoom?: number
  apiKey?: string
  mode?: 'single' | 'multiple'
  markers?: MapMarker[]
  showGeolocationButton?: boolean
  autoDetectLocation?: boolean
  clusterize?: boolean
  draggable?: boolean
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  height: 400,
  zoom: 14,
  apiKey: '23ff8acc-835f-4e99-8b19-d33c5d346e18',
  mode: 'single',
  markers: () => [],
  showGeolocationButton: false,
  autoDetectLocation: false,
  clusterize: false,
  draggable: true,
  ariaLabel: 'Интерактивная карта'
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  'marker-moved': [coords: Coordinates]
  'marker-click': [marker: MapMarker]
  'cluster-click': [markers: MapMarker[]]
  'address-found': [address: string, coords: Coordinates]
  'search-error': [error: string]
  'marker-address-hover': [address: string]
  'bounds-change': [bounds: any]
}>()

// Refs
const mapContainerRef = ref()

// Логирование при инициализации
onMounted(() => {
  console.log('[YandexMap] 🚀 YandexMap компонент смонтирован')
  console.log('[YandexMap] 📋 Полученные props:', {
    modelValue: props.modelValue,
    height: props.height,
    center: props.center,
    zoom: props.zoom,
    apiKey: props.apiKey,
    mode: props.mode,
    markers: props.markers?.length || 0,
    showGeolocationButton: props.showGeolocationButton,
    autoDetectLocation: props.autoDetectLocation,
    clusterize: props.clusterize,
    draggable: props.draggable
  })
  
  // Проверяем center
  const computedCenter = props.center || parseCoordinates(props.modelValue)
  console.log('[YandexMap] 📍 Вычисленный центр:', computedCenter)
})

// Computed props for new MapContainer (оптимизировано без избыточных логов)
const adapterProps = computed(() => {
  return {
    modelValue: props.modelValue,
    height: props.height,
    center: props.center || parseCoordinates(props.modelValue),
    zoom: props.zoom,
    apiKey: props.apiKey,
    mode: props.mode,
    markers: props.markers,
    showGeolocationButton: props.showGeolocationButton,
    autoDetectLocation: props.autoDetectLocation,
    clusterize: props.clusterize,
    draggable: props.draggable
  }
})

// Event handlers for adapter compatibility (оптимизированы логи)
function handleUpdate(value: string) {
  emit('update:modelValue', value)
}

function handleReady(map: any) {
  console.log('[YandexMap] 🎉 Карта инициализирована')
  // Эмулируем старый API для обратной совместимости
}

function handleAddressFound(data: { address: string, coords: Coordinates }) {
  console.log('[YandexMap] 📍 Найден адрес:', data.address)
  emit('address-found', data.address, data.coords)
}

// Public API для обратной совместимости (оптимизированы логи)
async function searchAddress(address: string): Promise<boolean> {
  if (!mapContainerRef.value || !mapContainerRef.value.searchAddress) {
    console.warn('[YandexMap] ❌ MapContainer не готов')
    return false
  }
  
  try {
    const result = await mapContainerRef.value.searchAddress(address)
    return result || false
  } catch (error) {
    console.error('[YandexMap] ❌ Ошибка поиска адреса:', error)
    return false
  }
}

function setCoordinates(coords: Coordinates, zoom?: number) {
  return mapContainerRef.value?.setCenter?.(coords, zoom)
}

defineExpose({ 
  searchAddress, 
  setCoordinates, 
  forceInit: () => {} 
})
</script>

<style scoped>
.yandex-map { @apply relative w-full; }
@media (max-width: 640px) { .yandex-map { @apply rounded-none; } }
</style>