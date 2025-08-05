<template>
    <Head title="Демо карты" />
    
    <!-- Обертка с правильными отступами -->
    <div class="py-6 lg:py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            
            <!-- Заголовок -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Демо интерактивной карты</h1>
                <p class="text-gray-600">Leaflet с Яндекс.Картами для SPA Platform</p>
            </div>

            <!-- Контролы карты -->
            <div class="mb-6 bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">Настройки карты</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Выбор города -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Город</label>
                        <select 
                            v-model="selectedCity" 
                            @change="changeCity"
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option v-for="city in cities" :key="city.name" :value="city">
                                {{ city.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Тип карты -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Тип карты</label>
                        <select 
                            v-model="mapType" 
                            class="w-full p-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="yandex">Яндекс.Карты</option>
                            <option value="osm">OpenStreetMap</option>
                        </select>
                    </div>

                    <!-- Высота карты -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Высота: {{ mapHeight }}px</label>
                        <input 
                            v-model="mapHeight" 
                            type="range" 
                            min="300" 
                            max="800" 
                            step="50"
                            class="w-full"
                        >
                    </div>
                </div>

                <!-- Кнопки действий -->
                <div class="mt-4 flex flex-wrap gap-2">
                    <button 
                        @click="addRandomMarker"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                    >
                        Добавить маркер
                    </button>
                    <button 
                        @click="clearMarkers"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
                    >
                        Очистить маркеры
                    </button>
                    <button 
                        @click="addMasterMarkers"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors"
                    >
                        Показать мастеров
                    </button>
                </div>
            </div>

            <!-- Карта -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
                <LeafletMap
                    :height="mapHeight"
                    :center="mapCenter"
                    :zoom="mapZoom"
                    :markers="markers"
                    :map-type="mapType"
                    :show-location-button="true"
                    @marker-click="handleMarkerClick"
                    @map-click="handleMapClick"
                    @center-change="handleCenterChange"
                    @zoom-change="handleZoomChange"
                    @map-ready="handleMapReady"
                />
            </div>

            <!-- Информация о событиях -->
            <div class="mt-6 bg-white rounded-lg shadow-sm border p-6">
                <h2 class="text-lg font-semibold mb-4">События карты</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">Центр карты</h3>
                        <p class="text-sm text-gray-600">
                            Широта: {{ mapCenter.lat.toFixed(4) }}<br>
                            Долгота: {{ mapCenter.lng.toFixed(4) }}<br>
                            Зум: {{ mapZoom }}
                        </p>
                    </div>
                    
                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">Маркеры</h3>
                        <p class="text-sm text-gray-600">
                            Всего маркеров: {{ markers.length }}<br>
                            Последний клик: {{ lastClick || 'нет' }}
                        </p>
                    </div>
                </div>

                <!-- Список маркеров -->
                <div v-if="markers.length" class="mt-4">
                    <h3 class="font-medium text-gray-900 mb-2">Список маркеров:</h3>
                    <div class="space-y-2">
                        <div 
                            v-for="(marker, index) in markers" 
                            :key="index"
                            class="flex items-center justify-between p-2 bg-gray-50 rounded"
                        >
                            <span class="text-sm">{{ marker.title }}</span>
                            <span class="text-xs text-gray-500">
                                {{ marker.lat.toFixed(4) }}, {{ marker.lng.toFixed(4) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Head } from '@inertiajs/vue3'
import LeafletMap from '@/src/features/map/ui/MapLegacy/LeafletMap.vue'

// Состояние
const mapHeight = ref(500)
const mapType = ref('yandex')
const mapZoom = ref(14)
const markers = ref([])
const lastClick = ref('')

// Города для демо
const cities = [
    { name: 'Пермь', lat: 58.0105, lng: 56.2502 },
    { name: 'Москва', lat: 55.7558, lng: 37.6176 },
    { name: 'Санкт-Петербург', lat: 59.9311, lng: 30.3609 },
    { name: 'Екатеринбург', lat: 56.8431, lng: 60.6454 },
    { name: 'Новосибирск', lat: 55.0084, lng: 82.9357 },
    { name: 'Казань', lat: 55.8304, lng: 49.0661 }
]

const selectedCity = ref(cities[0])
const mapCenter = reactive({ ...selectedCity.value })

// Методы
const changeCity = () => {
    mapCenter.lat = selectedCity.value.lat
    mapCenter.lng = selectedCity.value.lng
    mapZoom.value = 14
}

const addRandomMarker = () => {
    const latOffset = (Math.random() - 0.5) * 0.02
    const lngOffset = (Math.random() - 0.5) * 0.02
    
    markers.value.push({
        lat: mapCenter.lat + latOffset,
        lng: mapCenter.lng + lngOffset,
        title: `Маркер ${markers.value.length + 1}`,
        description: `Случайный маркер в районе ${selectedCity.value.name}`,
        popup: `<b>Маркер ${markers.value.length + 1}</b><br>Случайная точка на карте`
    })
}

const clearMarkers = () => {
    markers.value = []
}

const addMasterMarkers = () => {
    // Добавляем несколько маркеров мастеров массажа
    const masterMarkers = [
        {
            lat: mapCenter.lat + 0.005,
            lng: mapCenter.lng + 0.010,
            title: 'Анна Иванова',
            description: 'Классический массаж, 5 лет опыта',
            popup: '<b>Анна Иванова</b><br>Классический массаж<br>от 2000 ₽/час'
        },
        {
            lat: mapCenter.lat - 0.008,
            lng: mapCenter.lng + 0.015,
            title: 'Елена Петрова',
            description: 'Релаксирующий массаж, 3 года опыта',
            popup: '<b>Елена Петрова</b><br>Релаксирующий массаж<br>от 1800 ₽/час'
        },
        {
            lat: mapCenter.lat + 0.012,
            lng: mapCenter.lng - 0.005,
            title: 'Мария Сидорова',
            description: 'Лечебный массаж, 7 лет опыта',
            popup: '<b>Мария Сидорова</b><br>Лечебный массаж<br>от 2500 ₽/час'
        }
    ]
    
    markers.value = [...markers.value, ...masterMarkers]
}

// Обработчики событий карты
const handleMarkerClick = (marker) => {
    lastClick.value = `Маркер: ${marker.title}`
}

const handleMapClick = (event) => {
    lastClick.value = `Карта: ${event.coordinates.lat.toFixed(4)}, ${event.coordinates.lng.toFixed(4)}`
}

const handleCenterChange = (newCenter) => {
    mapCenter.lat = newCenter.lat
    mapCenter.lng = newCenter.lng
}

const handleZoomChange = (newZoom) => {
    mapZoom.value = newZoom
}

const handleMapReady = (mapInstance) => {
}

// Инициализация с несколькими маркерами
addMasterMarkers()
</script>