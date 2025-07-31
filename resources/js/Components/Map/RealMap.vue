<template>
    <div class="relative">
        <!-- Контейнер карты -->
        <div 
            :id="mapId" 
            class="w-full rounded-lg overflow-hidden border border-gray-200"
            :style="{ height: height + 'px' }"
        ></div>
        
        <!-- Загрузка -->
        <div 
            v-if="loading"
            class="absolute inset-0 bg-white bg-opacity-90 flex items-center justify-center rounded-lg"
        >
            <div class="text-center">
                <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-600">Загрузка карты...</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
    // Размеры карты
    height: {
        type: Number,
        default: 400
    },
    
    // Центр карты (широта, долгота)
    center: {
        type: Array,
        default: () => [58.0105, 56.2502] // Пермь
    },
    
    // Уровень зума
    zoom: {
        type: Number,
        default: 14
    },
    
    // Текст маркера
    markerText: {
        type: String,
        default: 'Здесь находится объект'
    }
})

const emit = defineEmits(['map-ready', 'marker-click'])

// Состояние
const loading = ref(true)
const mapId = ref(`real-map-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`)
let map = null

// Инициализация карты
const initMap = async () => {
    try {
        loading.value = true
        
        // Загружаем Leaflet если не загружен
        if (!window.L) {
            await loadLeaflet()
        }
        
        await nextTick()

        // Создаем карту
        map = window.L.map(mapId.value).setView(props.center, props.zoom)

        // Добавляем Яндекс тайлы
        window.L.tileLayer('https://core-renderer-tiles.maps.yandex.net/tiles?l=map&x={x}&y={y}&z={z}&scale=2&lang=ru_RU', {
            tileSize: 256,
            maxZoom: 18,
            minZoom: 1,
            noWrap: true,
            attribution: '© Яндекс.Карты'
        }).addTo(map)

        // Добавляем маркер
        const marker = window.L.marker(props.center).addTo(map)
        marker.bindPopup(props.markerText).openPopup()
        
        // Обработчик клика на маркер
        marker.on('click', () => {
            emit('marker-click', { coordinates: props.center, text: props.markerText })
        })

        loading.value = false
        emit('map-ready', map)

    } catch (error) {
        console.error('Ошибка инициализации карты:', error)
        loading.value = false
    }
}

// Загрузка Leaflet
const loadLeaflet = () => {
    return new Promise((resolve, reject) => {
        // Загружаем CSS
        const link = document.createElement('link')
        link.rel = 'stylesheet'
        link.href = 'https://unpkg.com/leaflet/dist/leaflet.css'
        document.head.appendChild(link)

        // Загружаем JS
        const script = document.createElement('script')
        script.src = 'https://unpkg.com/leaflet/dist/leaflet.js'
        script.onload = () => resolve()
        script.onerror = reject
        document.head.appendChild(script)
    })
}

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

// Экспорт карты
defineExpose({
    getMap: () => map
})
</script>