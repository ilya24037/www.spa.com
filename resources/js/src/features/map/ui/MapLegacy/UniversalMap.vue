<template>
  <div 
    class="relative rounded-lg overflow-hidden"
    :class="containerClasses"
    :style="{ height: computedHeight + 'px' }"
  >
    <!-- Фон карты -->
    <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-blue-100">
      <!-- Сетка для имитации карты -->
      <div 
        v-if="mode !== 'mini'"
        class="h-full w-full opacity-10" 
        style="
          background-image: 
            linear-gradient(0deg, #333 1px, transparent 1px),
            linear-gradient(90deg, #333 1px, transparent 1px);
          background-size: 50px 50px;
        "
      ></div>
    </div>

    <!-- Заголовок карты (для preview режима) -->
    <div 
      v-if="mode === 'preview' && title"
      class="absolute top-4 left-4 z-20 bg-white px-3 py-2 rounded-lg shadow-md"
    >
      <h3 class="text-sm font-medium text-gray-800">{{ title }}</h3>
      <p v-if="subtitle" class="text-xs text-gray-600">{{ subtitle }}</p>
    </div>

    <!-- Центральная иконка (для mini и picker режимов) -->
    <div 
      v-if="['mini', 'picker'].includes(mode) && !markers.length"
      class="absolute inset-0 flex items-center justify-center z-10"
    >
      <div class="text-center">
        <svg class="mx-auto h-8 w-8 text-blue-400 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        <p v-if="mode === 'picker'" class="text-xs text-gray-600">{{ placeholderText }}</p>
      </div>
    </div>

    <!-- Маркеры -->
    <div v-if="markers.length" class="absolute inset-0 z-10">
      <div
        v-for="(marker, index) in visibleMarkers"
        :key="marker.id || index"
        class="absolute transform -translate-x-1/2 -translate-y-1/2 cursor-pointer"
        :style="getMarkerPosition(index)"
        @click="handleMarkerClick(marker)"
        @mouseenter="handleMarkerHover(marker, true)"
        @mouseleave="handleMarkerHover(marker, false)"
      >
        <!-- Маркер -->
        <div class="relative">
          <!-- Цена или название -->
          <div 
            :class="markerClasses"
            class="transition-all duration-200 hover:scale-110"
          >
            {{ getMarkerLabel(marker) }}
          </div>
          <!-- Стрелка -->
          <div 
            v-if="mode !== 'mini'"
            class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1"
          >
            <div class="w-0 h-0 border-l-2 border-r-2 border-t-2 border-transparent border-t-blue-600"></div>
          </div>
        </div>

        <!-- Тултип при наведении -->
        <div
          v-if="hoveredMarker === marker.id && marker.tooltip"
          class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 bg-white px-3 py-2 rounded-lg shadow-lg text-sm whitespace-nowrap"
        >
          <div class="font-medium">{{ marker.tooltip.title }}</div>
          <div v-if="marker.tooltip.subtitle" class="text-gray-600">{{ marker.tooltip.subtitle }}</div>
          <!-- Стрелка тултипа -->
          <div class="absolute top-full left-1/2 transform -translate-x-1/2">
            <div class="w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-white"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Контролы карты -->
    <div 
      v-if="showControls && mode === 'full'"
      class="absolute top-4 right-4 flex flex-col gap-2 z-20"
    >
      <button 
        @click="zoomIn"
        class="bg-white p-2 rounded shadow hover:bg-gray-50 transition-colors"
        title="Приблизить"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
      </button>
      <button 
        @click="zoomOut"
        class="bg-white p-2 rounded shadow hover:bg-gray-50 transition-colors"
        title="Отдалить"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
        </svg>
      </button>
    </div>

    <!-- Кнопка геолокации -->
    <div 
      v-if="showGeolocation && ['full', 'picker'].includes(mode)"
      class="absolute bottom-4 right-4 z-20"
    >
      <button 
        @click="centerOnCurrentLocation"
        class="bg-white p-2 rounded-lg shadow hover:bg-gray-50 transition-colors"
        title="Моё местоположение"
      >
        <svg class="w-5 h-5 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </button>
    </div>

    <!-- Кнопка действий (для preview режима) -->
    <div 
      v-if="mode === 'preview' && actionButton"
      class="absolute bottom-4 left-4 z-20"
    >
      <button 
        @click="handleActionClick"
        class="bg-white px-4 py-2 rounded-lg shadow hover:bg-gray-50 text-sm font-medium transition-colors"
      >
        {{ actionButton.text }}
      </button>
    </div>

    <!-- Загрузка -->
    <div 
      v-if="loading"
      class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-30"
    >
      <div class="flex flex-col items-center">
        <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <p class="mt-2 text-sm text-gray-600">Загрузка карты...</p>
      </div>
    </div>

    <!-- Оверлей для picker режима -->
    <div 
      v-if="mode === 'picker'"
      class="absolute inset-0 cursor-pointer z-15"
      @click="handleMapClick"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const props = defineProps({
  // Режим отображения карты
  mode: {
    type: String,
    default: 'preview',
    validator: (value) => ['preview', 'full', 'picker', 'mini'].includes(value)
  },
  
  // Размеры
  height: {
    type: Number,
    default: null
  },
  width: {
    type: String,
    default: '100%'
  },
  
  // Данные маркеров
  markers: {
    type: Array,
    default: () => []
  },
  
  // Центр карты
  center: {
    type: Object,
    default: () => ({ lat: 58.0105, lng: 56.2502 }) // Пермь
  },
  
  // Заголовок и подзаголовок
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  
  // Настройки отображения
  showControls: {
    type: Boolean,
    default: true
  },
  showGeolocation: {
    type: Boolean,
    default: true
  },
  maxMarkers: {
    type: Number,
    default: 10
  },
  
  // Кнопка действия для preview режима
  actionButton: {
    type: Object,
    default: null // { text: 'Показать списком', action: 'toggle-view' }
  },
  
  // Плейсхолдер для picker режима
  placeholderText: {
    type: String,
    default: 'Выберите место на карте'
  },
  
  // Состояние загрузки
  loading: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits([
  'marker-click',
  'marker-hover', 
  'map-click',
  'action-click',
  'zoom-change',
  'center-change'
])

// Состояние
const hoveredMarker = ref(null)
const zoom = ref(12)

// Вычисляемые свойства
const computedHeight = computed(() => {
  if (props.height) return props.height
  
  switch (props.mode) {
    case 'mini': return 120
    case 'picker': return 200
    case 'preview': return 400
    case 'full': return 500
    default: return 400
  }
})

const containerClasses = computed(() => {
  const classes = ['bg-gray-100']
  
  switch (props.mode) {
    case 'mini':
      classes.push('border border-gray-200')
      break
    case 'picker':
      classes.push('border-2 border-dashed border-gray-300 hover:border-blue-400 transition-colors')
      break
    default:
      break
  }
  
  return classes
})

const markerClasses = computed(() => {
  const baseClasses = 'text-white px-2 py-1 rounded-full text-xs font-medium shadow-lg'
  
  switch (props.mode) {
    case 'mini':
      return `${baseClasses} bg-blue-600 px-1 text-xs`
    case 'picker':
      return `${baseClasses} bg-green-600`
    default:
      return `${baseClasses} bg-blue-600 hover:bg-blue-700`
  }
})

const visibleMarkers = computed(() => {
  return props.markers.slice(0, props.maxMarkers)
})

// Методы
const getMarkerPosition = (index) => {
  // Генерируем псевдослучайные позиции для маркеров
  const positions = [
    { top: '25%', left: '30%' },
    { top: '40%', left: '60%' },
    { top: '30%', left: '45%' },
    { top: '55%', left: '35%' },
    { top: '45%', left: '70%' },
    { top: '60%', left: '55%' },
    { top: '35%', left: '25%' },
    { top: '65%', left: '40%' },
    { top: '50%', left: '65%' },
    { top: '40%', left: '80%' }
  ]
  
  return positions[index % positions.length] || { top: '50%', left: '50%' }
}

const getMarkerLabel = (marker) => {
  if (props.mode === 'mini') return '•'
  if (marker.price) return formatPrice(marker.price)
  if (marker.min_price) return formatPrice(marker.min_price)
  return marker.name || marker.title || '•'
}

const formatPrice = (price) => {
  if (!price) return ''
  return new Intl.NumberFormat('ru-RU').format(price) + ' ₽'
}

const handleMarkerClick = (marker) => {
  emit('marker-click', marker)
}

const handleMarkerHover = (marker, isHovered) => {
  hoveredMarker.value = isHovered ? marker.id : null
  emit('marker-hover', { marker, isHovered })
}

const handleMapClick = (event) => {
  if (props.mode === 'picker') {
    // Эмулируем выбор координат
    const rect = event.currentTarget.getBoundingClientRect()
    const x = ((event.clientX - rect.left) / rect.width) * 100
    const y = ((event.clientY - rect.top) / rect.height) * 100
    
    emit('map-click', {
      coordinates: { lat: props.center.lat, lng: props.center.lng },
      position: { x, y },
      event
    })
  }
}

const handleActionClick = () => {
  emit('action-click', props.actionButton?.action || 'action')
}

const zoomIn = () => {
  zoom.value = Math.min(zoom.value + 1, 18)
  emit('zoom-change', zoom.value)
}

const zoomOut = () => {
  zoom.value = Math.max(zoom.value - 1, 1)
  emit('zoom-change', zoom.value)
}

const centerOnCurrentLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        const { latitude, longitude } = position.coords
        emit('center-change', { lat: latitude, lng: longitude })
      },
      (error) => {
        console.error('Ошибка получения геолокации:', error)
      }
    )
  }
}

// Lifecycle
onMounted(() => {
  // Здесь можно инициализировать реальную карту (Яндекс.Карты, Google Maps)
})
</script>

<style scoped>
/* Дополнительные стили для анимаций */
.marker-hover {
  transform: scale(1.1);
}
</style> 