<!-- resources/js/src/features/map/ui/UniversalMap/UniversalMap.vue -->
<template>
  <div
    :class="getContainerClasses()"
    :style="containerStyle"
  >
    <!-- Фон карты -->
    <div :class="BACKGROUND_CLASSES">
      <!-- Сетка для имитации карты -->
      <div
        v-if="mode !== 'mini'"
        :class="GRID_CLASSES"
      ></div>
    </div>

    <!-- Заголовок карты -->
    <div
      v-if="mode === 'preview' && title"
      :class="HEADER_CLASSES"
    >
      <h3 :class="TITLE_CLASSES">{{ title }}</h3>
      <p v-if="subtitle" :class="SUBTITLE_CLASSES">{{ subtitle }}</p>
    </div>

    <!-- Центральная иконка -->
    <div
      v-if="showCenterIcon"
      :class="CENTER_ICON_CONTAINER_CLASSES"
    >
      <div :class="CENTER_ICON_CONTENT_CLASSES">
        <svg :class="LOCATION_ICON_CLASSES" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <p v-if="mode === 'picker'" :class="PLACEHOLDER_TEXT_CLASSES">
          {{ placeholderText }}
        </p>
      </div>
    </div>

    <!-- Маркеры -->
    <div v-if="hasMarkers" :class="MARKERS_CONTAINER_CLASSES">
      <MapMarker
        v-for="(marker, index) in visibleMarkers"
        :key="marker.id || index"
        :marker="marker"
        :position="getMarkerPosition(index)"
        :mode="mode"
        :is-hovered="hoveredMarker === marker.id"
        @click="handleMarkerClick(marker)"
        @hover="handleMarkerHover(marker, $event)"
      />
    </div>

    <!-- Контролы карты -->
    <MapControls
      v-if="showControls && mode === 'full'"
      :zoom="currentZoom"
      :can-zoom-in="canZoomIn"
      :can-zoom-out="canZoomOut"
      @zoom-in="zoomIn"
      @zoom-out="zoomOut"
      @my-location="goToMyLocation"
      @fullscreen="toggleFullscreen"
    />

    <!-- Статистика -->
    <div
      v-if="showStats && hasMarkers"
      :class="STATS_CLASSES"
    >
      <span :class="STATS_TEXT_CLASSES">
        {{ visibleMarkers.length }} из {{ markers.length }}
      </span>
    </div>

    <!-- Загрузка -->
    <div v-if="loading" :class="LOADING_CLASSES">
      <svg :class="LOADING_ICON_CLASSES" fill="none" viewBox="0 0 24 24">
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        />
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import MapMarker from './MapMarker.vue'
import MapControls from './MapControls.vue'

// 🎯 Стили согласно дизайн-системе
const BASE_CONTAINER_CLASSES = 'relative rounded-lg overflow-hidden'
const BACKGROUND_CLASSES = 'absolute inset-0 bg-gradient-to-br from-blue-50 to-blue-100'
const GRID_CLASSES = 'h-full w-full opacity-10 bg-grid-pattern'
const HEADER_CLASSES = 'absolute top-4 left-4 z-20 bg-white px-3 py-2 rounded-lg shadow-md'
const TITLE_CLASSES = 'text-sm font-medium text-gray-800'
const SUBTITLE_CLASSES = 'text-xs text-gray-600'
const CENTER_ICON_CONTAINER_CLASSES = 'absolute inset-0 flex items-center justify-center z-10'
const CENTER_ICON_CONTENT_CLASSES = 'text-center'
const LOCATION_ICON_CLASSES = 'mx-auto h-8 w-8 text-blue-400 mb-2'
const PLACEHOLDER_TEXT_CLASSES = 'text-xs text-gray-600'
const MARKERS_CONTAINER_CLASSES = 'absolute inset-0 z-10'
const STATS_CLASSES = 'absolute bottom-4 left-4 z-20 bg-white px-3 py-1 rounded-lg shadow-md'
const STATS_TEXT_CLASSES = 'text-xs font-medium text-gray-700'
const LOADING_CLASSES = 'absolute inset-0 flex items-center justify-center bg-white/80 z-30'
const LOADING_ICON_CLASSES = 'w-8 h-8 text-blue-600 animate-spin'

const props = defineProps({
  markers: {
    type: Array,
    default: () => []
  },
  mode: {
    type: String,
    default: 'full', // full, preview, mini, picker
    validator: (value) => ['full', 'preview', 'mini', 'picker'].includes(value)
  },
  height: {
    type: [Number, String],
    default: 400
  },
  title: {
    type: String,
    default: ''
  },
  subtitle: {
    type: String,
    default: ''
  },
  placeholderText: {
    type: String,
    default: 'Выберите место на карте'
  },
  showControls: {
    type: Boolean,
    default: true
  },
  showStats: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  maxMarkers: {
    type: Number,
    default: 50
  }
})

const emit = defineEmits(['marker-click', 'marker-hover', 'zoom-change', 'bounds-change'])

// Состояние
const currentZoom = ref(10)
const hoveredMarker = ref(null)
const isFullscreen = ref(false)

// Вычисляемые свойства
const hasMarkers = computed(() => props.markers.length > 0)

const showCenterIcon = computed(() => 
  ['mini', 'picker'].includes(props.mode) && !hasMarkers.value
)

const visibleMarkers = computed(() => 
  props.markers.slice(0, props.maxMarkers)
)

const containerStyle = computed(() => ({
  height: typeof props.height === 'number' ? `${props.height}px` : props.height
}))

const canZoomIn = computed(() => currentZoom.value < 18)
const canZoomOut = computed(() => currentZoom.value > 1)

// Методы
const getContainerClasses = () => {
  const classes = [BASE_CONTAINER_CLASSES]
  
  if (props.mode === 'mini') classes.push('cursor-pointer')
  if (isFullscreen.value) classes.push('fixed inset-0 z-50')
  
  return classes.join(' ')
}

const getMarkerPosition = (index) => {
  // Генерируем псевдо-случайные позиции для демонстрации
  const rows = Math.ceil(Math.sqrt(visibleMarkers.value.length))
  const cols = Math.ceil(visibleMarkers.value.length / rows)
  
  const row = Math.floor(index / cols)
  const col = index % cols
  
  const x = (col + 0.5) / cols * 80 + 10 // 10-90% ширины
  const y = (row + 0.5) / rows * 80 + 10 // 10-90% высоты
  
  return {
    left: `${x}%`,
    top: `${y}%`
  }
}

const handleMarkerClick = (marker) => {
  emit('marker-click', marker)
}

const handleMarkerHover = (marker, isHovered) => {
  hoveredMarker.value = isHovered ? marker.id : null
  emit('marker-hover', { marker, isHovered })
}

const zoomIn = () => {
  if (canZoomIn.value) {
    currentZoom.value++
    emit('zoom-change', currentZoom.value)
  }
}

const zoomOut = () => {
  if (canZoomOut.value) {
    currentZoom.value--
    emit('zoom-change', currentZoom.value)
  }
}

const goToMyLocation = () => {
  // Имитация определения местоположения
  console.log('Getting user location...')
}

const toggleFullscreen = () => {
  isFullscreen.value = !isFullscreen.value
}

// Жизненный цикл
onMounted(() => {
  // Инициализация карты
  console.log('Map initialized with', props.markers.length, 'markers')
})
</script>

<style scoped>
.bg-grid-pattern {
  background-image: 
    linear-gradient(0deg, #333 1px, transparent 1px),
    linear-gradient(90deg, #333 1px, transparent 1px);
  background-size: 50px 50px;
}
</style>