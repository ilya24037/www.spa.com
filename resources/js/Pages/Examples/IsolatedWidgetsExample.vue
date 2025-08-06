<!-- 
  Пример использования изолированных виджетов по принципу Ozon
  
  Демонстрирует:
  - Ленивую загрузку виджетов
  - Изоляцию состояния
  - Error boundary для каждого виджета
  - Performance мониторинг
-->
<template>
  <div class="isolated-widgets-example p-8 space-y-8">
    
    <header class="text-center">
      <h1 class="text-3xl font-bold text-gray-900 mb-4">
        Изолированные Виджеты (Принцип Ozon)
      </h1>
      <p class="text-gray-600 max-w-2xl mx-auto">
        Каждый виджет полностью самодостаточен: собственное состояние, API, обработка ошибок
      </p>
    </header>

    <!-- Performance метрики -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <h3 class="font-medium text-blue-900 mb-2">Производительность виджетов</h3>
      <div class="grid grid-cols-3 gap-4 text-sm">
        <div>Загружено: {{ loadedWidgets }}/{{ totalWidgets }}</div>
        <div>Ошибки: {{ errorCount }}</div>
        <div>Среднее время загрузки: {{ averageLoadTime }}мс</div>
      </div>
    </div>

    <!-- Секция виджетов -->
    <div class="space-y-8">
      
      <!-- Виджет профиля мастера - Компактный -->
      <section class="bg-gray-50 rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">MasterProfile (Компактный режим)</h2>
        
        <Suspense>
          <template #default>
            <MasterProfileWidget
              :master-id="selectedMasterId"
              :compact="true"
              :show-booking="true"
              :show-reviews="false"
              @service-selected="handleServiceSelected"
              @photo-clicked="handlePhotoClicked"
              @contact-clicked="handleContactClicked"
              @booking-requested="handleBookingRequested"
            />
          </template>
          
          <template #fallback>
            <div class="animate-pulse bg-white rounded-lg p-6">
              <div class="h-6 bg-gray-200 rounded w-1/3 mb-4"></div>
              <div class="h-4 bg-gray-200 rounded w-2/3"></div>
            </div>
          </template>
        </Suspense>
      </section>

      <!-- Виджет профиля мастера - Полный -->
      <section class="bg-gray-50 rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">MasterProfile (Полная версия)</h2>
        
        <Suspense>
          <template #default>
            <MasterProfileWidget
              :master-id="selectedMasterId"
              :compact="false"
              :show-booking="true"
              :show-reviews="true"
              @service-selected="handleServiceSelected"
              @photo-clicked="handlePhotoClicked"
              @contact-clicked="handleContactClicked"
              @booking-requested="handleBookingRequested"
            />
          </template>
          
          <template #fallback>
            <div class="animate-pulse bg-white rounded-lg p-6">
              <div class="h-8 bg-gray-200 rounded w-1/2 mb-4"></div>
              <div class="space-y-2 mb-6">
                <div class="h-4 bg-gray-200 rounded"></div>
                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div class="h-24 bg-gray-200 rounded"></div>
                <div class="h-24 bg-gray-200 rounded"></div>
              </div>
            </div>
          </template>
        </Suspense>
      </section>

      <!-- Демо переключения мастеров -->
      <section class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-medium mb-4">Демо изоляции состояния</h3>
        <div class="space-y-4">
          <p class="text-sm text-gray-600">
            Каждый виджет имеет изолированное состояние. Переключение мастера влияет на все виджеты независимо.
          </p>
          
          <div class="flex gap-2">
            <button
              v-for="masterId in demoMasterIds"
              :key="masterId"
              @click="selectedMasterId = masterId"
              :class="[
                'px-4 py-2 rounded text-sm font-medium transition-colors',
                selectedMasterId === masterId
                  ? 'bg-blue-600 text-white'
                  : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
              ]"
            >
              Мастер {{ masterId }}
            </button>
          </div>
        </div>
      </section>

      <!-- События виджетов -->
      <section class="bg-white rounded-lg border p-6">
        <h3 class="text-lg font-medium mb-4">События виджетов</h3>
        <div class="bg-gray-50 rounded p-4 max-h-64 overflow-auto">
          <div v-if="widgetEvents.length === 0" class="text-gray-500 text-sm">
            События будут отображаться здесь...
          </div>
          <div v-else class="space-y-2">
            <div 
              v-for="event in widgetEvents.slice().reverse()" 
              :key="event.id"
              class="text-xs font-mono bg-white p-2 rounded border"
            >
              <div class="text-blue-600 font-semibold">{{ event.type }}</div>
              <div class="text-gray-600">{{ JSON.stringify(event.data, null, 2) }}</div>
              <div class="text-gray-400 text-xs mt-1">{{ event.timestamp }}</div>
            </div>
          </div>
        </div>
      </section>

    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * Пример страницы с изолированными виджетами
 * Демонстрирует принципы Ozon для SPA Platform
 */

import { ref, computed, onMounted, onUnmounted } from 'vue'
import { defineAsyncComponent } from 'vue'

// Ленивая загрузка виджетов
const MasterProfileWidget = defineAsyncComponent(
  () => import('@/widgets/master-profile')
)

// === СОСТОЯНИЕ ===
const selectedMasterId = ref(1)
const demoMasterIds = [1, 2, 3, 4, 5]

const widgetEvents = ref<Array<{
  id: number
  type: string
  data: any
  timestamp: string
}>>([])

const performanceMetrics = ref({
  loadedWidgets: 0,
  totalWidgets: 2,
  errorCount: 0,
  loadTimes: [] as number[]
})

// === COMPUTED ===
const loadedWidgets = computed(() => performanceMetrics.value.loadedWidgets)
const totalWidgets = computed(() => performanceMetrics.value.totalWidgets)
const errorCount = computed(() => performanceMetrics.value.errorCount)

const averageLoadTime = computed(() => {
  const times = performanceMetrics.value.loadTimes
  if (times.length === 0) return 0
  return Math.round(times.reduce((a, b) => a + b, 0) / times.length)
})

// === ОБРАБОТЧИКИ СОБЫТИЙ ВИДЖЕТОВ ===

function handleServiceSelected(service: any) {
  addWidgetEvent('service-selected', service)
}

function handlePhotoClicked(photo: any) {
  addWidgetEvent('photo-clicked', { photoId: photo.id })
}

function handleContactClicked(type: string, value: string) {
  addWidgetEvent('contact-clicked', { type, value })
}

function handleBookingRequested(masterId: number) {
  addWidgetEvent('booking-requested', { masterId })
}

// Убрали handleWidgetError - теперь не используется

// === УТИЛИТЫ ===

function addWidgetEvent(type: string, data: any) {
  widgetEvents.value.push({
    id: Date.now() + Math.random(),
    type,
    data,
    timestamp: new Date().toLocaleTimeString()
  })
  
  // Ограничиваем количество событий
  if (widgetEvents.value.length > 50) {
    widgetEvents.value = widgetEvents.value.slice(-50)
  }
}

// === МОНИТОРИНГ ПРОИЗВОДИТЕЛЬНОСТИ ===

function setupPerformanceMonitoring() {
  // Слушаем события производительности виджетов
  window.addEventListener('widget-analytics', handleWidgetAnalytics)
  window.addEventListener('widget-error', handleWidgetErrorEvent)
}

function handleWidgetAnalytics(event: CustomEvent) {
  const { widget, event: eventType, data } = event.detail
  
  if (eventType === 'master_profile_loaded') {
    performanceMetrics.value.loadedWidgets++
    if (data.loadTime) {
      performanceMetrics.value.loadTimes.push(data.loadTime)
    }
  }
  
  addWidgetEvent(`${widget}:${eventType}`, data)
}

function handleWidgetErrorEvent(event: CustomEvent) {
  performanceMetrics.value.errorCount++
  addWidgetEvent('widget-error', event.detail)
}

function cleanupPerformanceMonitoring() {
  window.removeEventListener('widget-analytics', handleWidgetAnalytics)
  window.removeEventListener('widget-error', handleWidgetErrorEvent)
}

// === ЖИЗНЕННЫЙ ЦИКЛ ===

onMounted(() => {
  setupPerformanceMonitoring()
  
  // Симулируем аналитику
  setTimeout(() => {
    addWidgetEvent('page-loaded', { 
      totalWidgets: totalWidgets.value,
      timestamp: Date.now() 
    })
  }, 1000)
})

onUnmounted(() => {
  cleanupPerformanceMonitoring()
})
</script>

<style scoped>
/* Стили для демо страницы */
.isolated-widgets-example {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

/* Анимации для виджетов */
.widget-container {
  transition: all 0.3s ease;
}

.widget-container:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}

/* Индикаторы производительности */
.performance-indicator {
  position: relative;
}

.performance-indicator::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 3px;
  height: 100%;
  background: linear-gradient(to bottom, #10b981, #3b82f6, #8b5cf6);
  border-radius: 2px;
}
</style>