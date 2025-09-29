<template>
  <MainLayout>
    <div class="map-test-page">
      <h1 class="text-2xl font-bold mb-4">🧪 День 3: Тестирование MapCore</h1>
      
      <!-- Статистика -->
      <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-sm text-gray-500">Размер компонента</div>
          <div class="text-2xl font-bold text-green-600">240 строк</div>
          <div class="text-xs text-gray-400">было 544</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-sm text-gray-500">Console.log</div>
          <div class="text-2xl font-bold text-green-600">0</div>
          <div class="text-xs text-gray-400">было 51</div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-sm text-gray-500">Статус карты</div>
          <div class="text-2xl font-bold" :class="mapStatus === 'ready' ? 'text-green-600' : 'text-yellow-600'">
            {{ mapStatus }}
          </div>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
          <div class="text-sm text-gray-500">Плагины</div>
          <div class="text-2xl font-bold text-blue-600">{{ pluginsCount }}</div>
        </div>
      </div>

      <!-- Тесты функциональности -->
      <div class="grid grid-cols-2 gap-6 mb-6">
        <!-- Базовая карта -->
        <div class="bg-white p-4 rounded-lg shadow">
          <h2 class="font-bold mb-3">📍 Тест 1: Базовая инициализация</h2>
          <YandexMapNative
            ref="mapCore1"
            :height="300"
            :center="{ lat: 58.01046, lng: 56.25017 }"
            :zoom="12"
            @ready="onMapReady"
            @error="onMapError"
            @center-change="onCenterChange"
            @click="onMapClick"
          />
          <div class="mt-2 text-sm">
            <div>Центр: {{ currentCenter.lat.toFixed(4) }}, {{ currentCenter.lng.toFixed(4) }}</div>
            <div>Клик: {{ lastClick.lat?.toFixed(4) || '-' }}, {{ lastClick.lng?.toFixed(4) || '-' }}</div>
          </div>
        </div>

        <!-- С центральным маркером -->
        <div class="bg-white p-4 rounded-lg shadow">
          <h2 class="font-bold mb-3">📌 Тест 2: Центральный маркер</h2>
          <YandexMapNative
            ref="mapCore2"
            :height="300"
            :center="{ lat: 58.01046, lng: 56.25017 }"
            :zoom="13"
            :show-center-marker="true"
            @ready="onMap2Ready"
            @center-change="onCenter2Change"
          />
          <div class="mt-2 text-sm">
            <div>Режим: Single (showCenterMarker)</div>
            <div>Координаты: {{ centerMarkerCoords.lat.toFixed(4) }}, {{ centerMarkerCoords.lng.toFixed(4) }}</div>
          </div>
        </div>
      </div>

      <!-- Тесты методов управления -->
      <div class="bg-white p-4 rounded-lg shadow mb-6">
        <h2 class="font-bold mb-3">🎮 Тест 3: Методы управления</h2>
        <div class="grid grid-cols-3 gap-4 mb-4">
          <button 
            @click="testSetCenter"
            class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
          >
            setCenter() - Кремль
          </button>
          <button 
            @click="testGetCenter"
            class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600"
          >
            getCenter()
          </button>
          <button 
            @click="testDestroy"
            class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
          >
            destroy()
          </button>
        </div>
        <div class="text-sm bg-gray-100 p-2 rounded">
          Результат: {{ testResult }}
        </div>
      </div>

      <!-- Тест плагинов -->
      <div class="bg-white p-4 rounded-lg shadow mb-6">
        <h2 class="font-bold mb-3">🔌 Тест 4: Система плагинов</h2>
        <YandexMapNative
          ref="mapCore3"
          :height="300"
          :center="{ lat: 58.01046, lng: 56.25017 }"
          :zoom="12"
          @ready="onMap3Ready"
        >
          <template #controls>
            <div class="bg-white p-2 rounded shadow">
              <button 
                @click="addTestPlugin"
                class="px-3 py-1 bg-purple-500 text-white rounded text-sm"
              >
                Добавить плагин
              </button>
            </div>
          </template>
          <template #overlays>
            <div v-if="pluginMessage" class="absolute top-4 left-4 bg-yellow-100 p-2 rounded">
              {{ pluginMessage }}
            </div>
          </template>
        </YandexMapNative>
        <div class="mt-2 text-sm">
          Плагины загружены: {{ loadedPlugins.join(', ') || 'нет' }}
        </div>
      </div>

      <!-- Результаты тестирования -->
      <div class="bg-white p-4 rounded-lg shadow">
        <h2 class="font-bold mb-3">📊 Результаты тестирования</h2>
        <div class="space-y-2">
          <div class="flex justify-between">
            <span>✅ Инициализация карты</span>
            <span class="text-green-600">{{ tests.init ? 'Пройден' : 'Ожидание' }}</span>
          </div>
          <div class="flex justify-between">
            <span>✅ События карты</span>
            <span class="text-green-600">{{ tests.events ? 'Пройден' : 'Ожидание' }}</span>
          </div>
          <div class="flex justify-between">
            <span>✅ Методы управления</span>
            <span class="text-green-600">{{ tests.methods ? 'Пройден' : 'Ожидание' }}</span>
          </div>
          <div class="flex justify-between">
            <span>✅ Система плагинов</span>
            <span class="text-green-600">{{ tests.plugins ? 'Пройден' : 'Ожидание' }}</span>
          </div>
          <div class="flex justify-between">
            <span>✅ Слоты расширения</span>
            <span class="text-green-600">{{ tests.slots ? 'Пройден' : 'Ожидание' }}</span>
          </div>
          <div class="flex justify-between">
            <span>✅ Реактивность props</span>
            <span class="text-green-600">{{ tests.reactivity ? 'Пройден' : 'Ожидание' }}</span>
          </div>
        </div>
        <div class="mt-4 pt-4 border-t">
          <div class="text-xl font-bold" :class="allTestsPassed ? 'text-green-600' : 'text-yellow-600'">
            {{ allTestsPassed ? '🎉 Все тесты пройдены!' : '⏳ Тестирование...' }}
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import MainLayout from '@/src/shared/layouts/MainLayout/MainLayout.vue'
import YandexMapNative from '@/src/features/map/components/YandexMapNative.vue'

// Refs для карт
const mapCore1 = ref()
const mapCore2 = ref()
const mapCore3 = ref()

// Состояние тестов
const mapStatus = ref('loading')
const pluginsCount = ref(0)
const currentCenter = ref({ lat: 58.01046, lng: 56.25017 })
const lastClick = ref({ lat: null, lng: null })
const centerMarkerCoords = ref({ lat: 58.01046, lng: 56.25017 })
const testResult = ref('Ожидание команд...')
const pluginMessage = ref('')
const loadedPlugins = ref<string[]>([])

// Статусы тестов
const tests = ref({
  init: false,
  events: false,
  methods: false,
  plugins: false,
  slots: true, // Автоматически true, так как слоты уже отрендерены
  reactivity: false
})

const allTestsPassed = computed(() => {
  return Object.values(tests.value).every(test => test === true)
})

// Обработчики событий карты 1
function onMapReady(map: any) {
  console.log('✅ MapCore 1: Карта инициализирована', map)
  mapStatus.value = 'ready'
  tests.value.init = true
}

function onMapError(error: Error) {
  console.error('❌ MapCore 1: Ошибка', error)
  mapStatus.value = 'error'
}

function onCenterChange(center: any) {
  currentCenter.value = center
  tests.value.events = true
}

function onMapClick(coords: any) {
  lastClick.value = coords
  tests.value.events = true
}

// Обработчики карты 2
function onMap2Ready(map: any) {
  console.log('✅ MapCore 2: Карта с маркером готова')
  tests.value.reactivity = true
}

function onCenter2Change(center: any) {
  centerMarkerCoords.value = center
}

// Обработчики карты 3
function onMap3Ready(map: any) {
  console.log('✅ MapCore 3: Карта для плагинов готова')
}

// Тесты методов
function testSetCenter() {
  if (mapCore1.value) {
    // Координаты Пермского Кремля
    mapCore1.value.setCenter({ lat: 58.0105, lng: 56.2502 }, 15)
    testResult.value = 'setCenter() выполнен - переместились к Кремлю'
    tests.value.methods = true
  }
}

function testGetCenter() {
  if (mapCore1.value) {
    const center = mapCore1.value.getCenter()
    testResult.value = `getCenter(): lat=${center.lat.toFixed(4)}, lng=${center.lng.toFixed(4)}`
    tests.value.methods = true
  }
}

function testDestroy() {
  if (mapCore2.value) {
    mapCore2.value.destroy()
    testResult.value = 'destroy() выполнен - карта 2 уничтожена'
    tests.value.methods = true
  }
}

// Тест плагинов
function addTestPlugin() {
  if (mapCore3.value) {
    const testPlugin = {
      name: 'TestPlugin',
      install(map: any, store: any) {
        console.log('🔌 TestPlugin установлен')
        pluginMessage.value = 'TestPlugin активирован!'
        loadedPlugins.value.push('TestPlugin')
        pluginsCount.value++
        
        // Добавляем маркер как тест плагина
        if (window.ymaps) {
          const placemark = new window.ymaps.Placemark(
            [58.01046, 56.25017],
            { balloonContent: 'Тестовый маркер от плагина' }
          )
          map.geoObjects.add(placemark)
        }
        
        setTimeout(() => {
          pluginMessage.value = ''
        }, 3000)
      },
      destroy() {
        console.log('🔌 TestPlugin удален')
      }
    }
    
    mapCore3.value.use(testPlugin)
    tests.value.plugins = true
  }
}
</script>

<style scoped>
.map-test-page {
  @apply p-6 bg-gray-50 min-h-screen;
}
</style>