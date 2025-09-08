<template>
  <div class="geo-section-simple">
    <!-- Заголовок секции -->
    <div class="mb-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-2">📍 Местоположение</h2>
      <p class="text-sm text-gray-600">
        Укажите точный адрес для отображения на карте. Это поможет клиентам найти вас.
      </p>
    </div>

    <!-- Секция карты и адреса - ТОЛЬКО КАРТА для тестирования Singleton -->
    <AddressMapSection 
      :initial-address="geoData.address || ''"
      :initial-coordinates="geoData.coordinates || [55.751244, 37.618423]"
      :initial-zoom="geoData.zoom || 10"
      @update:address="handleAddressUpdate"
      @update:coordinates="handleCoordinatesUpdate"
      @data-changed="handleMapDataChange"
    />

    <!-- Информация о тестировании -->
    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
      <h3 class="text-sm font-medium text-blue-800 mb-2">🧪 Тестирование Singleton решения:</h3>
      <ol class="text-xs text-blue-700 space-y-1">
        <li>1. Откройте DevTools (F12) → Console</li>
        <li>2. Ищите логи <code>[MapSingleton]</code></li>
        <li>3. Переходите: Home → AddItem → Home</li>
        <li>4. Убедитесь, что НЕТ ошибки <code>"vector: internal error"</code></li>
      </ol>
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * GeoSectionSimple - упрощенная версия для тестирования Singleton
 * 
 * Содержит только AddressMapSection для тестирования решения
 * "vector: internal error" без других проблемных компонентов
 */

import { ref, reactive, computed, watch, onMounted } from 'vue'
import AddressMapSection from './components/AddressMapSection.vue'

// Типы
interface GeoData {
  address: string
  coordinates: [number, number]
  zoom: number
}

// Props
interface Props {
  initialData?: Partial<GeoData>
}

const props = withDefaults(defineProps<Props>(), {
  initialData: () => ({})
})

// Emits
const emit = defineEmits<{
  'update:data': [data: GeoData]
  'data-changed': [data: GeoData]
}>()

// Состояние
const geoData = reactive<GeoData>({
  address: props.initialData?.address || '',
  coordinates: props.initialData?.coordinates || [55.751244, 37.618423], // Москва по умолчанию
  zoom: props.initialData?.zoom || 10
})

// Обработчики событий карты
const handleAddressUpdate = (address: string) => {
  console.log('[GeoSectionSimple] Обновление адреса:', address)
  geoData.address = address
  emitData()
}

const handleCoordinatesUpdate = (coordinates: [number, number]) => {
  console.log('[GeoSectionSimple] Обновление координат:', coordinates)
  geoData.coordinates = coordinates
  emitData()
}

const handleMapDataChange = (data: any) => {
  console.log('[GeoSectionSimple] Изменение данных карты:', data)
  if (data.address) geoData.address = data.address
  if (data.coordinates) geoData.coordinates = data.coordinates
  if (data.zoom) geoData.zoom = data.zoom
  emitData()
}

// Эмит данных
const emitData = () => {
  const data = { ...geoData }
  emit('update:data', data)
  emit('data-changed', data)
}

// Computed для отладки
const debugInfo = computed(() => ({
  address: geoData.address,
  coordinates: geoData.coordinates,
  zoom: geoData.zoom
}))

// Логирование изменений
watch(debugInfo, (newVal) => {
  console.log('[GeoSectionSimple] Состояние изменилось:', newVal)
}, { deep: true })

onMounted(() => {
  console.log('[GeoSectionSimple] Компонент смонтирован с данными:', debugInfo.value)
})
</script>

<style scoped>
.geo-section-simple {
  @apply space-y-6;
}

code {
  @apply bg-blue-100 text-blue-800 px-1 py-0.5 rounded text-xs;
}
</style>