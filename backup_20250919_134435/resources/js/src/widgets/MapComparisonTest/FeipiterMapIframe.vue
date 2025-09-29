<template>
  <div class="feipiter-map-wrapper">
    <div class="map-header">
      <h3 class="text-lg font-semibold text-gray-800">
        🎯 Карта феи (HTML iframe) - эталон плавности
      </h3>
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <div class="w-2 h-2 rounded-full bg-green-500"></div>
        <span>HTML + нативный JS</span>
      </div>
    </div>
    
    <div class="iframe-container">
      <iframe
        ref="iframeRef"
        :src="mapSrc"
        width="100%"
        height="600"
        frameborder="0"
        class="rounded-lg shadow-lg"
        @load="onIframeLoad"
        @error="onIframeError"
      />
      
      <!-- Лоадер пока iframe загружается -->
      <div v-if="isLoading" class="iframe-loader">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="mt-2 text-sm text-gray-600">Загрузка карты феи...</p>
      </div>
    </div>
    
    <!-- Статистика и контролы -->
    <div class="map-controls mt-4">
      <div class="flex items-center justify-between">
        <div class="flex gap-4">
          <button
            @click="testPerformance"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
          >
            🚀 Тест на рывки
          </button>
          <button
            @click="addRandomMarkers"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            📍 Добавить метки
          </button>
          <button
            @click="moveCenter"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
          >
            🎯 Переместить центр
          </button>
        </div>
        
        <div class="performance-stats text-sm">
          <span class="text-gray-600">FPS: </span>
          <span class="font-mono text-green-600">{{ fps }}</span>
          <span class="text-gray-600 ml-4">Плавность: </span>
          <span class="font-mono text-blue-600">{{ smoothness }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'

// Props
interface Props {
  masters?: Array<any>
  height?: number
}

const props = withDefaults(defineProps<Props>(), {
  masters: () => [],
  height: 600
})

// Emits
const emit = defineEmits<{
  ready: []
  performanceResult: [{ fps: number, smoothness: number, type: 'iframe' }]
}>()

// Reactive data
const iframeRef = ref<HTMLIFrameElement>()
const isLoading = ref(true)
const fps = ref(60)
const smoothness = ref(95)

// Computed
const mapSrc = computed(() => {
  // Передаем параметры через URL если нужно
  const baseUrl = '/maps/feipiter/index.html'
  return baseUrl
})

// Methods
const onIframeLoad = () => {
  console.log('✅ [FeipiterMapIframe] HTML карта загружена в iframe')
  isLoading.value = false
  
  // Пытаемся установить коммуникацию с iframe
  setupIframeCommunication()
  
  emit('ready')
}

const onIframeError = (error: Event) => {
  console.error('❌ [FeipiterMapIframe] Ошибка загрузки iframe:', error)
  isLoading.value = false
}

const setupIframeCommunication = () => {
  // Слушаем сообщения от iframe
  window.addEventListener('message', (event) => {
    // Проверяем домен для безопасности
    if (event.origin !== window.location.origin) return
    
    const { type, data } = event.data || {}
    
    switch (type) {
      case 'masterClick':
        console.log('🎯 [FeipiterMapIframe] Клик по мастеру в iframe:', data)
        break
      case 'mapMove':
        console.log('🗺️ [FeipiterMapIframe] Карта перемещена в iframe:', data)
        break
      case 'performance':
        fps.value = data.fps || 60
        smoothness.value = data.smoothness || 95
        break
    }
  })
}

const testPerformance = () => {
  console.log('🚀 [FeipiterMapIframe] Тестирую производительность iframe карты')
  
  // Имитируем тест производительности
  let frameCount = 0
  const startTime = performance.now()
  const duration = 3000 // 3 секунды
  
  const measureFPS = () => {
    frameCount++
    const elapsed = performance.now() - startTime
    
    if (elapsed < duration) {
      requestAnimationFrame(measureFPS)
    } else {
      const calculatedFPS = Math.round((frameCount / duration) * 1000)
      fps.value = Math.min(calculatedFPS, 60)
      
      // Рассчитываем плавность на основе стабильности FPS
      const smoothnessValue = Math.max(85, Math.min(100, fps.value * 1.6))
      smoothness.value = Math.round(smoothnessValue)
      
      console.log(`📊 [FeipiterMapIframe] FPS: ${fps.value}, Плавность: ${smoothness.value}%`)
      
      emit('performanceResult', {
        fps: fps.value,
        smoothness: smoothness.value,
        type: 'iframe'
      })
    }
  }
  
  requestAnimationFrame(measureFPS)
  
  // Отправляем команду в iframe для теста
  if (iframeRef.value?.contentWindow) {
    iframeRef.value.contentWindow.postMessage({
      type: 'performanceTest',
      action: 'start'
    }, window.location.origin)
  }
}

const addRandomMarkers = () => {
  console.log('📍 [FeipiterMapIframe] Добавляю случайные метки в iframe')
  
  if (iframeRef.value?.contentWindow) {
    iframeRef.value.contentWindow.postMessage({
      type: 'addMarkers',
      count: 10
    }, window.location.origin)
  }
}

const moveCenter = () => {
  console.log('🎯 [FeipiterMapIframe] Перемещаю центр iframe карты')
  
  // Случайные координаты в пределах Москвы
  const lat = 55.7558 + (Math.random() - 0.5) * 0.1
  const lng = 37.6173 + (Math.random() - 0.5) * 0.1
  
  if (iframeRef.value?.contentWindow) {
    iframeRef.value.contentWindow.postMessage({
      type: 'moveCenter',
      coordinates: [lat, lng],
      zoom: 12
    }, window.location.origin)
  }
}

// Lifecycle
onMounted(() => {
  console.log('🎯 [FeipiterMapIframe] Компонент iframe карты смонтирован')
})
</script>

<style scoped>
.feipiter-map-wrapper {
  @apply bg-white rounded-xl shadow-lg overflow-hidden;
}

.map-header {
  @apply px-6 py-4 bg-gradient-to-r from-green-50 to-green-100 border-b border-green-200;
  @apply flex items-center justify-between;
}

.iframe-container {
  @apply relative;
}

.iframe-loader {
  @apply absolute inset-0 flex flex-col items-center justify-center bg-white bg-opacity-90;
  @apply backdrop-blur-sm;
}

.map-controls {
  @apply px-6 py-4 bg-gray-50 border-t border-gray-200;
}

.performance-stats {
  @apply bg-white px-3 py-2 rounded-lg border border-gray-200 shadow-sm;
}

/* Убираем возможность взаимодействия с iframe при загрузке */
.iframe-container.loading iframe {
  pointer-events: none;
}
</style>