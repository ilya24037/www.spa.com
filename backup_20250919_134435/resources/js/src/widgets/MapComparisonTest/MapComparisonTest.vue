<template>
  <div class="map-comparison-test">
    <!-- Заголовок секции -->
    <div class="comparison-header">
      <h2 class="text-3xl font-bold text-gray-900 mb-4">
        🆚 A/B Тестирование карт: HTML vs Vue
      </h2>
      <p class="text-lg text-gray-600 mb-8">
        Сравнение производительности двух реализаций Яндекс.Карт для выбора оптимального решения
      </p>
    </div>

    <!-- Общие контролы и результаты тестирования -->
    <div class="test-controls-panel">
      <div class="flex items-center justify-between mb-6">
        <div class="test-actions">
          <button
            @click="runBothTests"
            class="px-6 py-3 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors"
            :disabled="isRunningTests"
          >
            {{ isRunningTests ? '⏳ Тестирую оба варианта...' : '🚀 Запустить сравнительный тест' }}
          </button>
          
          <button
            @click="resetTests"
            class="ml-4 px-4 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
          >
            🔄 Сбросить результаты
          </button>
          
          <button
            v-if="testResults.iframe && testResults.vue"
            @click="exportResults"
            class="ml-2 px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors"
          >
            📊 Экспорт результатов
          </button>
        </div>
        
        <!-- Сводная таблица результатов -->
        <div v-if="testResults.iframe || testResults.vue" class="comparison-results">
          <div class="bg-white rounded-lg shadow-lg p-4">
            <h3 class="text-lg font-semibold mb-3">📊 Результаты сравнения</h3>
            <div class="grid grid-cols-3 gap-4 text-center">
              <div class="font-medium text-gray-600">Версия</div>
              <div class="font-medium text-gray-600">FPS</div>
              <div class="font-medium text-gray-600">Плавность</div>
              
              <div class="text-green-600 font-medium">🎯 HTML (iframe)</div>
              <div class="font-mono text-xl" :class="getPerformanceClass(testResults.iframe?.fps)">
                {{ testResults.iframe?.fps || '-' }}
              </div>
              <div class="font-mono text-xl" :class="getPerformanceClass(testResults.iframe?.smoothness)">
                {{ testResults.iframe?.smoothness ? `${testResults.iframe.smoothness}%` : '-' }}
              </div>
              
              <div class="text-blue-600 font-medium">⚡ Vue (оптимизированная)</div>
              <div class="font-mono text-xl" :class="getPerformanceClass(testResults.vue?.fps)">
                {{ testResults.vue?.fps || '-' }}
              </div>
              <div class="font-mono text-xl" :class="getPerformanceClass(testResults.vue?.smoothness)">
                {{ testResults.vue?.smoothness ? `${testResults.vue.smoothness}%` : '-' }}
              </div>
            </div>
            
            <!-- Победитель -->
            <div v-if="winner" class="mt-4 p-3 rounded-lg text-center font-semibold" :class="winner.class">
              {{ winner.message }}
            </div>
            
            <!-- Детальная аналитика -->
            <div v-if="testResults.iframe && testResults.vue" class="mt-6 grid grid-cols-2 gap-4 text-sm">
              <!-- Преимущества HTML -->
              <div class="bg-green-50 rounded-lg p-4">
                <h4 class="font-semibold text-green-800 mb-2">🎯 HTML (iframe) - Преимущества</h4>
                <ul class="text-green-700 space-y-1">
                  <li v-if="testResults.iframe.fps > testResults.vue.fps">
                    • Выше FPS на {{ testResults.iframe.fps - testResults.vue.fps }} кадров
                  </li>
                  <li v-if="testResults.iframe.smoothness > testResults.vue.smoothness">
                    • Плавнее на {{ testResults.iframe.smoothness - testResults.vue.smoothness }}%
                  </li>
                  <li>• Изоляция от Vue реактивности</li>
                  <li>• Нативные API без обёрток</li>
                  <li>• Минимальная архитектура</li>
                </ul>
              </div>
              
              <!-- Преимущества Vue -->
              <div class="bg-blue-50 rounded-lg p-4">
                <h4 class="font-semibold text-blue-800 mb-2">⚡ Vue - Преимущества</h4>
                <ul class="text-blue-700 space-y-1">
                  <li v-if="testResults.vue.fps > testResults.iframe.fps">
                    • Выше FPS на {{ testResults.vue.fps - testResults.iframe.fps }} кадров
                  </li>
                  <li v-if="testResults.vue.smoothness > testResults.iframe.smoothness">
                    • Плавнее на {{ testResults.vue.smoothness - testResults.iframe.smoothness }}%
                  </li>
                  <li>• Интеграция с SPA архитектурой</li>
                  <li>• Реактивные данные и состояния</li>
                  <li>• Единообразный стек технологий</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Две карты рядом для сравнения -->
    <div class="maps-comparison-grid">
      <!-- HTML карта через iframe -->
      <div class="map-variant">
        <FeipiterMapIframe
          ref="iframeMapRef"
          @ready="onIframeMapReady"
          @performanceResult="onPerformanceResult"
        />
      </div>
      
      <!-- Vue оптимизированная карта -->
      <div class="map-variant">
        <VueOptimizedMap
          ref="vueMapRef"
          @ready="onVueMapReady"
          @performanceResult="onPerformanceResult"
        />
      </div>
    </div>
    
    <!-- Детальная информация о тестах -->
    <div class="test-details mt-8">
      <div class="bg-gray-50 rounded-xl p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">
          📋 Методология тестирования
        </h3>
        
        <div class="grid md:grid-cols-2 gap-6">
          <div>
            <h4 class="font-semibold text-gray-700 mb-2">🎯 HTML версия (Карта феи)</h4>
            <ul class="text-sm text-gray-600 space-y-1">
              <li>• Нативный JavaScript + Yandex Maps API 2.1</li>
              <li>• Встроена через iframe для изоляции</li>
              <li>• Минимальная архитектура без фреймворков</li>
              <li>• Прямые вызовы ymaps без обёрток</li>
            </ul>
          </div>
          
          <div>
            <h4 class="font-semibold text-gray-700 mb-2">⚡ Vue версия (Оптимизированная)</h4>
            <ul class="text-sm text-gray-600 space-y-1">
              <li>• Vue 3 Composition API + TypeScript</li>
              <li>• Оптимизированная реактивность (shallowRef)</li>
              <li>• Прямые вызовы ymaps без кастомных обёрток</li>
              <li>• Упрощённая обработка событий</li>
            </ul>
          </div>
        </div>
        
        <div class="mt-6 p-4 bg-white rounded-lg border border-gray-200">
          <h4 class="font-semibold text-gray-700 mb-2">🔬 Параметры тестирования</h4>
          <div class="text-sm text-gray-600">
            <p><strong>Тест производительности:</strong> 3 секунды интенсивных операций с картой</p>
            <p><strong>Операции:</strong> быстрое перемещение центра, изменение зума, добавление меток</p>
            <p><strong>Метрики:</strong> FPS (кадры в секунду), плавность анимаций</p>
            <p><strong>Цель:</strong> определить наиболее плавную и отзывчивую версию карты</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import FeipiterMapIframe from './FeipiterMapIframe.vue'
import VueOptimizedMap from './VueOptimizedMap.vue'

// Состояние тестирования
const isRunningTests = ref(false)
const testResults = ref<{
  iframe?: { fps: number, smoothness: number }
  vue?: { fps: number, smoothness: number }
}>({})

const mapsReady = ref({
  iframe: false,
  vue: false
})

// Refs к дочерним компонентам
const iframeMapRef = ref()
const vueMapRef = ref()

// Вычисляемые свойства
const winner = computed(() => {
  if (!testResults.value.iframe || !testResults.value.vue) return null
  
  const iframeTotalScore = (testResults.value.iframe.fps * 0.6) + (testResults.value.iframe.smoothness * 0.4)
  const vueTotalScore = (testResults.value.vue.fps * 0.6) + (testResults.value.vue.smoothness * 0.4)
  
  if (iframeTotalScore > vueTotalScore) {
    const diff = Math.round(iframeTotalScore - vueTotalScore)
    return {
      message: `🏆 Победитель: HTML версия (+${diff} очков)`,
      class: 'bg-green-100 text-green-800 border border-green-200'
    }
  } else if (vueTotalScore > iframeTotalScore) {
    const diff = Math.round(vueTotalScore - iframeTotalScore)
    return {
      message: `🏆 Победитель: Vue версия (+${diff} очков)`,
      class: 'bg-blue-100 text-blue-800 border border-blue-200'
    }
  } else {
    return {
      message: `🤝 Результат: Ничья - одинаковая производительность`,
      class: 'bg-yellow-100 text-yellow-800 border border-yellow-200'
    }
  }
})

// Методы
const onIframeMapReady = () => {
  mapsReady.value.iframe = true
  console.log('✅ [MapComparisonTest] HTML iframe карта готова')
}

const onVueMapReady = () => {
  mapsReady.value.vue = true
  console.log('✅ [MapComparisonTest] Vue карта готова')
}

const onPerformanceResult = (result: { fps: number, smoothness: number, type: 'iframe' | 'vue' }) => {
  console.log(`📊 [MapComparisonTest] Результат ${result.type}:`, result)
  
  if (result.type === 'iframe') {
    testResults.value.iframe = { fps: result.fps, smoothness: result.smoothness }
  } else if (result.type === 'vue') {
    testResults.value.vue = { fps: result.fps, smoothness: result.smoothness }
  }
}

const runBothTests = async () => {
  if (isRunningTests.value) return
  
  console.log('🚀 [MapComparisonTest] Запускаю сравнительное тестирование')
  isRunningTests.value = true
  
  try {
    // Сбрасываем предыдущие результаты
    testResults.value = {}
    
    // Проверяем готовность карт
    if (!mapsReady.value.iframe || !mapsReady.value.vue) {
      console.warn('⚠️ [MapComparisonTest] Не все карты готовы к тестированию')
      console.log('Статус готовности:', { 
        iframe: mapsReady.value.iframe, 
        vue: mapsReady.value.vue 
      })
      isRunningTests.value = false
      return
    }
    
    console.log('📋 [MapComparisonTest] Обе карты готовы, запускаю тесты производительности...')
    
    // Запускаем тесты параллельно
    const testPromises = []
    
    // Тест iframe карты
    if (iframeMapRef.value && typeof iframeMapRef.value.testPerformance === 'function') {
      testPromises.push(
        new Promise((resolve) => {
          const originalHandler = onPerformanceResult
          const timeoutId = setTimeout(() => {
            console.warn('⚠️ [MapComparisonTest] Timeout для iframe теста')
            resolve(null)
          }, 10000) // 10 секунд таймаут
          
          // Подменяем обработчик для отлова результата
          const tempHandler = (result: any) => {
            if (result.type === 'iframe') {
              clearTimeout(timeoutId)
              originalHandler(result)
              resolve(result)
            }
          }
          
          // Запускаем тест
          try {
            iframeMapRef.value.testPerformance()
          } catch (error) {
            console.error('Ошибка запуска iframe теста:', error)
            clearTimeout(timeoutId)
            resolve(null)
          }
        })
      )
    }
    
    // Тест Vue карты
    if (vueMapRef.value && typeof vueMapRef.value.testPerformance === 'function') {
      testPromises.push(
        new Promise((resolve) => {
          const originalHandler = onPerformanceResult
          const timeoutId = setTimeout(() => {
            console.warn('⚠️ [MapComparisonTest] Timeout для Vue теста')
            resolve(null)
          }, 10000) // 10 секунд таймаут
          
          // Подменяем обработчик для отлова результата
          const tempHandler = (result: any) => {
            if (result.type === 'vue') {
              clearTimeout(timeoutId)
              originalHandler(result)
              resolve(result)
            }
          }
          
          // Запускаем тест
          try {
            vueMapRef.value.testPerformance()
          } catch (error) {
            console.error('Ошибка запуска Vue теста:', error)
            clearTimeout(timeoutId)
            resolve(null)
          }
        })
      )
    }
    
    // Ждем завершения всех тестов
    if (testPromises.length > 0) {
      await Promise.all(testPromises)
      console.log('✅ [MapComparisonTest] Все тесты завершены')
    } else {
      console.warn('⚠️ [MapComparisonTest] Не удалось запустить тесты - методы недоступны')
    }
    
  } catch (error) {
    console.error('❌ [MapComparisonTest] Ошибка во время тестирования:', error)
  } finally {
    isRunningTests.value = false
  }
}

const resetTests = () => {
  console.log('🔄 [MapComparisonTest] Сброс результатов тестирования')
  testResults.value = {}
  isRunningTests.value = false
}

const getPerformanceClass = (value?: number) => {
  if (!value) return 'text-gray-400'
  
  if (value >= 55) return 'text-green-600'
  if (value >= 45) return 'text-yellow-600'
  return 'text-red-600'
}

const exportResults = () => {
  if (!testResults.value.iframe || !testResults.value.vue) {
    console.warn('⚠️ [MapComparisonTest] Нет данных для экспорта')
    return
  }
  
  const timestamp = new Date().toLocaleString('ru-RU')
  const browserInfo = {
    userAgent: navigator.userAgent,
    language: navigator.language,
    platform: navigator.platform,
    cookieEnabled: navigator.cookieEnabled,
    onLine: navigator.onLine
  }
  
  const reportData = {
    timestamp,
    testResults: {
      iframe: {
        ...testResults.value.iframe,
        technology: 'HTML + нативный JS',
        integration: 'iframe',
        architecture: 'Минимальная'
      },
      vue: {
        ...testResults.value.vue,
        technology: 'Vue 3 + Composition API',
        integration: 'Компонент',
        architecture: 'SPA с оптимизациями'
      }
    },
    winner: winner.value,
    environment: {
      ...browserInfo,
      screenResolution: `${screen.width}x${screen.height}`,
      windowSize: `${window.innerWidth}x${window.innerHeight}`,
      pixelRatio: window.devicePixelRatio
    },
    testConditions: {
      duration: '3 секунды',
      operations: 'Перемещение центра карты, изменение зума, добавление меток',
      metrics: 'FPS (requestAnimationFrame), плавность анимаций'
    }
  }
  
  const jsonData = JSON.stringify(reportData, null, 2)
  const blob = new Blob([jsonData], { type: 'application/json' })
  const url = URL.createObjectURL(blob)
  
  const link = document.createElement('a')
  link.href = url
  link.download = `map-performance-test-${Date.now()}.json`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  URL.revokeObjectURL(url)
  
  console.log('📊 [MapComparisonTest] Результаты экспортированы:', reportData)
}

// Lifecycle
onMounted(() => {
  console.log('🆚 [MapComparisonTest] Компонент A/B тестирования смонтирован')
})
</script>

<style scoped>
.map-comparison-test {
  @apply bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl shadow-lg overflow-hidden;
  @apply p-8;
}

.comparison-header {
  @apply text-center mb-8;
}

.test-controls-panel {
  @apply bg-white rounded-xl shadow-md p-6 mb-8;
}

.comparison-results {
  @apply min-w-fit;
}

.maps-comparison-grid {
  @apply grid lg:grid-cols-2 gap-8;
}

.map-variant {
  @apply relative;
}

.test-details {
  @apply border-t border-gray-200 pt-8;
}

/* Анимации */
.comparison-results {
  animation: slideInFromRight 0.5s ease-out;
}

@keyframes slideInFromRight {
  from {
    transform: translateX(100px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

/* Мобильная адаптация */
@media (max-width: 1024px) {
  .maps-comparison-grid {
    @apply grid-cols-1;
  }
  
  .test-controls-panel .flex {
    @apply flex-col gap-4 items-stretch;
  }
  
  .comparison-results {
    @apply w-full;
  }
}

@media (max-width: 640px) {
  .map-comparison-test {
    @apply p-4;
  }
  
  .comparison-results .grid {
    @apply text-sm;
  }
  
  .test-actions {
    @apply flex flex-col gap-2;
  }
}
</style>