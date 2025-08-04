<template>
    <div class="relative">
        <!-- Контейнер карты -->
        <div 
            :id="mapId" 
            class="rounded-lg overflow-hidden"
            :style="{ height: height + 'px' }"
        ></div>

        <!-- Кнопка центрирования на текущей позиции -->
        <button 
            v-if="showLocationButton"
            @click="centerOnCurrentLocation"
            class="absolute bottom-4 right-4 p-3 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow z-[1000]"
            title="Моё местоположение"
        >
            <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </button>

        <!-- Информационная панель при клике на маркер -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div 
                v-if="selectedMarker"
                class="absolute top-4 left-4 bg-white rounded-lg shadow-xl p-4 max-w-xs z-[1000]"
            >
                <div class="flex justify-between items-start mb-3">
                    <h4 class="font-semibold text-gray-900">{{ selectedMarker.title || 'Объект на карте' }}</h4>
                    <button 
                        @click="selectedMarker = null"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <div v-if="selectedMarker.description" class="text-sm text-gray-600 mb-3">
                    {{ selectedMarker.description }}
                </div>
                
                <div v-if="selectedMarker.coordinates" class="text-xs text-gray-500">
                    Координаты: {{ selectedMarker.coordinates.lat.toFixed(4) }}, {{ selectedMarker.coordinates.lng.toFixed(4) }}
                </div>
            </div>
        </transition>

        <!-- Загрузка -->
        <div 
            v-if="loading"
            class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-[1000] rounded-lg"
        >
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-600">Загрузка карты...</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useToast } from '@/src/shared/composables/useToast'

// Toast для замены alert()
const toast = useToast()

const props = defineProps({
    // Размеры карты
    height: {
        type: Number,
        default: 500
    },
    
    // Центр карты
    center: {
        type: Object,
        default: () => ({ lat: 58.0105, lng: 56.2502 }) // Пермь по умолчанию
    },
    
    // Уровень зума
    zoom: {
        type: Number,
        default: 14
    },
    
    // Маркеры на карте
    markers: {
        type: Array,
        default: () => []
    },
    
    // Показывать кнопку геолокации
    showLocationButton: {
        type: Boolean,
        default: true
    },
    
    // Тип карты (yandex, osm)
    mapType: {
        type: String,
        default: 'yandex',
        validator: (value) => ['yandex', 'osm'].includes(value)
    },
    
    // Язык карты
    language: {
        type: String,
        default: 'ru_RU'
    }
})

const emit = defineEmits([
    'marker-click',
    'map-click', 
    'center-change',
    'zoom-change',
    'map-ready'
])

// Состояние
const loading = ref(true)
const selectedMarker = ref(null)
const mapId = ref(`map-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`)
let map = null
let markersLayer = null

// Загрузка Leaflet
const loadLeaflet = () => {
    return new Promise((resolve, reject) => {
        // Проверяем, загружен ли уже Leaflet
        if (window.L) {
            resolve(window.L)
            return
        }

        // Загружаем CSS
        const link = document.createElement('link')
        link.rel = 'stylesheet'
        link.href = 'https://unpkg.com/leaflet/dist/leaflet.css'
        document.head.appendChild(link)

        // Загружаем JS
        const script = document.createElement('script')
        script.src = 'https://unpkg.com/leaflet/dist/leaflet.js'
        script.onload = () => {
            resolve(window.L)
        }
        script.onerror = reject
        document.head.appendChild(script)
    })
}

// Инициализация карты
const initMap = async () => {
    try {
        loading.value = true
        
        const L = await loadLeaflet()
        await nextTick()

        // Создаем карту
        map = L.map(mapId.value).setView([props.center.lat, props.center.lng], props.zoom)

        // Добавляем тайлы в зависимости от типа карты
        let tileLayer
        if (props.mapType === 'yandex') {
            tileLayer = L.tileLayer('https://core-renderer-tiles.maps.yandex.net/tiles?l=map&x={x}&y={y}&z={z}&scale=2&lang=' + props.language, {
                tileSize: 256,
                maxZoom: 18,
                minZoom: 1,
                noWrap: true,
                attribution: '© Яндекс.Карты'
            })
        } else {
            tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 18,
                attribution: '© OpenStreetMap contributors'
            })
        }
        
        tileLayer.addTo(map)

        // Создаем слой для маркеров
        markersLayer = L.layerGroup().addTo(map)

        // Добавляем маркеры
        updateMarkers()

        // Обработчики событий
        map.on('click', handleMapClick)
        map.on('moveend', handleMapMove)
        map.on('zoomend', handleZoomChange)

        loading.value = false
        emit('map-ready', map)

    } catch (error) {
        loading.value = false
    }
}

// Обновление маркеров
const updateMarkers = () => {
    if (!map || !markersLayer || !window.L) return

    // Очищаем существующие маркеры
    markersLayer.clearLayers()

    // Добавляем новые маркеры
    props.markers.forEach((markerData, index) => {
        const marker = window.L.marker([markerData.lat, markerData.lng])
            .bindPopup(markerData.title || markerData.popup || `Маркер ${index + 1}`)
            .on('click', () => handleMarkerClick(markerData))

        markersLayer.addLayer(marker)
    })
}

// Обработчики событий
const handleMapClick = (e) => {
    selectedMarker.value = null
    emit('map-click', {
        coordinates: { lat: e.latlng.lat, lng: e.latlng.lng },
        originalEvent: e
    })
}

const handleMarkerClick = (markerData) => {
    selectedMarker.value = {
        ...markerData,
        coordinates: { lat: markerData.lat, lng: markerData.lng }
    }
    emit('marker-click', markerData)
}

const handleMapMove = () => {
    if (!map) return
    const center = map.getCenter()
    emit('center-change', { lat: center.lat, lng: center.lng })
}

const handleZoomChange = () => {
    if (!map) return
    emit('zoom-change', map.getZoom())
}

// Центрирование на текущей позиции
const centerOnCurrentLocation = () => {
    if (!navigator.geolocation) {
        toast.error('Геолокация не поддерживается вашим браузером')
        return
    }

    navigator.geolocation.getCurrentPosition(
        (position) => {
            const { latitude, longitude } = position.coords
            if (map) {
                map.setView([latitude, longitude], props.zoom)
                
                // Добавляем маркер текущей позиции
                const currentLocationMarker = window.L.marker([latitude, longitude])
                    .bindPopup('Ваше местоположение')
                    .addTo(map)
                    .openPopup()
                
                // Удаляем маркер через 5 секунд
                setTimeout(() => {
                    map.removeLayer(currentLocationMarker)
                }, 5000)
            }
            emit('center-change', { lat: latitude, lng: longitude })
        },
        (error) => {
            toast.error('Не удалось получить ваше местоположение')
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        }
    )
}

// Публичные методы
const setCenter = (lat, lng, zoom = null) => {
    if (map) {
        map.setView([lat, lng], zoom || map.getZoom())
    }
}

const addMarker = (lat, lng, options = {}) => {
    if (!map || !window.L) return null
    
    const marker = window.L.marker([lat, lng], options)
    if (options.popup) {
        marker.bindPopup(options.popup)
    }
    marker.addTo(map)
    return marker
}

// Watchers
watch(() => props.markers, updateMarkers, { deep: true })

watch(() => props.center, (newCenter) => {
    if (map && newCenter) {
        map.setView([newCenter.lat, newCenter.lng], map.getZoom())
    }
}, { deep: true })

// Lifecycle
onMounted(() => {
    initMap()
})

onUnmounted(() => {
    if (map) {
        map.remove()
        map = null
    }
})

// Экспортируем методы
defineExpose({
    setCenter,
    addMarker,
    getMap: () => map
})
</script>

<style>
/* Leaflet стили будут загружены автоматически */
.leaflet-popup-content-wrapper {
    @apply rounded-lg;
}

.leaflet-popup-content {
    @apply text-sm;
}
</style>