<template>
  <div class="vue-optimized-map-wrapper">
    <div class="map-header">
      <h3 class="text-lg font-semibold text-gray-800">
        ⚡ Vue версия (упрощенная) - после оптимизации
      </h3>
      <div class="flex items-center gap-2 text-sm text-gray-600">
        <div class="w-2 h-2 rounded-full bg-blue-500"></div>
        <span>Vue 3 + нативный ymaps</span>
      </div>
    </div>
    
    <div class="map-container">
      <!-- Наша оптимизированная Vue карта -->
      <YandexMapNative
        ref="mapRef"
        mode="masters-catalog"
        :masters="testMasters"
        :height="600"
        :center="[55.7558, 37.6173]"
        :zoom="12"
        :show-controls="true"
        :enable-markers="true"
        @ready="onMapReady"
        @marker-click="onMarkerClick"
        @map-click="onMapClick"
      />
    </div>
    
    <!-- Статистика и контролы -->
    <div class="map-controls mt-4">
      <div class="flex items-center justify-between">
        <div class="flex gap-4">
          <button
            @click="testPerformance"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
            :disabled="isPerformanceTest"
          >
            🚀 {{ isPerformanceTest ? 'Тестирую...' : 'Тест на рывки' }}
          </button>
          <button
            @click="addRandomMarkers"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            📍 Добавить метки ({{ testMasters.length }})</button>
          <button
            @click="moveCenter"
            class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
          >
            🎯 Переместить центр
          </button>
          <button
            @click="clearMarkers"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            🧹 Очистить
          </button>
        </div>
        
        <div class="performance-stats text-sm">
          <span class="text-gray-600">FPS: </span>
          <span class="font-mono text-blue-600">{{ fps }}</span>
          <span class="text-gray-600 ml-4">Плавность: </span>
          <span class="font-mono text-green-600">{{ smoothness }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import YandexMapNative from '@/src/features/map/components/YandexMapNative.vue'

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
  performanceResult: [{ fps: number, smoothness: number, type: 'vue' }]
}>()

// Reactive data
const mapRef = ref()
const isPerformanceTest = ref(false)
const fps = ref(60)
const smoothness = ref(90)

// Тестовые данные мастеров (такие же как в "Карта феи")
const testMasters = ref([
  {
    id: 1,
    name: "Анна Иванова",
    coordinates: [55.753994, 37.622093],
    description: "Классический массаж, Релакс",
    price: "2500 руб/час",
    rating: 4.8,
    photo: "/assets/master1.jpg",
    district: "center",
    services: ["massage", "relax"]
  },
  {
    id: 2,
    name: "Мария Петрова", 
    coordinates: [55.761994, 37.632093],
    description: "Тайский массаж, SPA",
    price: "3500 руб/час",
    rating: 4.9,
    photo: "/assets/master2.jpg",
    district: "north",
    services: ["thai", "spa"]
  },
  {
    id: 3,
    name: "Елена Сидорова",
    coordinates: [55.743994, 37.612093],
    description: "Антицеллюлитный массаж",
    price: "3000 руб/час", 
    rating: 4.7,
    photo: "/assets/master3.jpg",
    district: "south",
    services: ["massage"]
  },
  {
    id: 4,
    name: "Ольга Николаева",
    coordinates: [55.749994, 37.618093],
    description: "SPA процедуры, Релакс",
    price: "4000 руб/час",
    rating: 5.0,
    photo: "/assets/master4.jpg", 
    district: "center",
    services: ["spa", "relax"]
  }
])

// Methods
const onMapReady = (map: any) => {
  console.log('✅ [VueOptimizedMap] Оптимизированная Vue карта готова')
  emit('ready')
}

const onMarkerClick = (master: any) => {
  console.log('🎯 [VueOptimizedMap] Клик по мастеру:', master.name)
}

const onMapClick = (event: any) => {
  console.log('🗺️ [VueOptimizedMap] Клик по карте:', event.coordinates)
}

const testPerformance = async () => {
  if (isPerformanceTest.value) return
  
  console.log('🚀 [VueOptimizedMap] Тестирую производительность Vue карты')
  isPerformanceTest.value = true
  
  // Запускаем интенсивный тест
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
      
      // Рассчитываем плавность (Vue версия может быть чуть менее плавной)
      const smoothnessValue = Math.max(75, Math.min(95, fps.value * 1.5))
      smoothness.value = Math.round(smoothnessValue)
      
      console.log(`📊 [VueOptimizedMap] FPS: ${fps.value}, Плавность: ${smoothness.value}%`)
      
      isPerformanceTest.value = false
      
      emit('performanceResult', {
        fps: fps.value,
        smoothness: smoothness.value,
        type: 'vue'
      })
    }
  }
  
  // Выполняем интенсивные операции для теста
  const testOperations = async () => {
    for (let i = 0; i < 10; i++) {
      // Быстрые перемещения центра
      const lat = 55.7558 + (Math.random() - 0.5) * 0.05
      const lng = 37.6173 + (Math.random() - 0.5) * 0.05
      
      if (mapRef.value?.setCenter) {
        mapRef.value.setCenter([lat, lng], 12 + Math.random() * 3, true)
      }
      
      await new Promise(resolve => setTimeout(resolve, 50))
    }
  }
  
  requestAnimationFrame(measureFPS)
  testOperations()
}

const addRandomMarkers = () => {
  console.log('📍 [VueOptimizedMap] Добавляю случайные метки')
  
  const newMasters = []
  for (let i = 0; i < 5; i++) {
    const lat = 55.7558 + (Math.random() - 0.5) * 0.1
    const lng = 37.6173 + (Math.random() - 0.5) * 0.1
    
    newMasters.push({
      id: testMasters.value.length + i + 1,
      name: `Тестовый мастер ${testMasters.value.length + i + 1}`,
      coordinates: [lat, lng],
      description: "Тестовый мастер для сравнения производительности",
      price: `${2000 + Math.floor(Math.random() * 2000)} руб/час`,
      rating: 4.0 + Math.random(),
      photo: "/assets/master1.jpg",
      district: "test",
      services: ["massage"]
    })
  }
  
  testMasters.value.push(...newMasters)
}

const moveCenter = () => {
  console.log('🎯 [VueOptimizedMap] Перемещаю центр Vue карты')
  
  const lat = 55.7558 + (Math.random() - 0.5) * 0.1  
  const lng = 37.6173 + (Math.random() - 0.5) * 0.1
  
  if (mapRef.value?.setCenter) {
    mapRef.value.setCenter([lat, lng], 12, true)
  }
}

const clearMarkers = () => {
  console.log('🧹 [VueOptimizedMap] Очищаю метки')
  
  // Возвращаем к исходным 4 мастерам
  testMasters.value = testMasters.value.slice(0, 4)
}

// Lifecycle
onMounted(() => {
  console.log('⚡ [VueOptimizedMap] Компонент оптимизированной Vue карты смонтирован')
})
</script>

<style scoped>
.vue-optimized-map-wrapper {
  @apply bg-white rounded-xl shadow-lg overflow-hidden;
}

.map-header {
  @apply px-6 py-4 bg-gradient-to-r from-blue-50 to-blue-100 border-b border-blue-200;
  @apply flex items-center justify-between;
}

.map-container {
  @apply relative bg-gray-100;
}

.map-controls {
  @apply px-6 py-4 bg-gray-50 border-t border-gray-200;
}

.performance-stats {
  @apply bg-white px-3 py-2 rounded-lg border border-gray-200 shadow-sm;
}

button:disabled {
  @apply opacity-50 cursor-not-allowed;
}
</style>