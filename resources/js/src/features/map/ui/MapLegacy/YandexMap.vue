// resources/js/Components/Map/MapContainer.vue
<template>
    <div class="relative h-full w-full">
        <!-- РљРѕРЅС‚РµР№РЅРµСЂ РґР»СЏ РєР°СЂС‚С‹ -->
        <div ref="mapContainer" class="w-full h-full"></div>

        <!-- РљРЅРѕРїРєР° С†РµРЅС‚СЂРёСЂРѕРІР°РЅРёСЏ РЅР° С‚РµРєСѓС‰РµР№ РїРѕР·РёС†РёРё -->
        <button 
            @click="centerOnCurrentLocation"
            class="absolute bottom-4 right-4 p-3 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow"
            title="РњРѕС‘ РјРµСЃС‚РѕРїРѕР»РѕР¶РµРЅРёРµ"
        >
            <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </button>

        <!-- РџР°РЅРµР»СЊ СѓРїСЂР°РІР»РµРЅРёСЏ РєР°СЂС‚РѕР№ -->
        <div class="absolute top-4 left-4 bg-white rounded-lg shadow-lg p-3">
            <div class="flex flex-col space-y-2">
                <button 
                    @click="zoomIn"
                    class="p-2 hover:bg-gray-100 rounded transition-colors"
                    title="РџСЂРёР±Р»РёР·РёС‚СЊ"
                >
                    <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </button>
                <div class="border-t border-gray-200"></div>
                <button 
                    @click="zoomOut"
                    class="p-2 hover:bg-gray-100 rounded transition-colors"
                    title="РћС‚РґР°Р»РёС‚СЊ"
                >
                    <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                    </svg>
                </button>
            </div>
        </div>

        <!-- РљРЅРѕРїРєР° "РџРѕРєР°Р·Р°С‚СЊ РѕР±СЉСЏРІР»РµРЅРёСЏ РЅР° РєР°СЂС‚Рµ" -->
        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2">
            <button 
                @click="showMarkersInView"
                class="px-4 py-2 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow flex items-center space-x-2"
            >
                <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <span class="text-sm font-medium text-gray-700">РџРѕРєР°Р·Р°С‚СЊ РѕР±СЉСЏРІР»РµРЅРёСЏ РІ СЌС‚РѕР№ РѕР±Р»Р°СЃС‚Рё</span>
            </button>
        </div>

        <!-- РРЅС„РѕСЂРјР°С†РёРѕРЅРЅР°СЏ РїР°РЅРµР»СЊ РїСЂРё РЅР°РІРµРґРµРЅРёРё РЅР° РјР°СЂРєРµСЂ -->
        <transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div 
                v-if="hoveredMarker"
                class="absolute top-20 left-4 bg-white rounded-lg shadow-xl p-4 max-w-xs"
                :style="markerInfoPosition"
            >
                <div class="flex items-start space-x-3">
                    <img 
                        :src="hoveredMarker.photo" 
                        :alt="hoveredMarker.name"
                        class="w-16 h-16 rounded-lg object-cover flex-shrink-0"
                    >
                    <div class="flex-1 min-w-0">
                        <h4 class="font-semibold text-gray-900 truncate">{{ hoveredMarker.name }}</h4>
                        <p class="text-sm text-gray-600">{{ hoveredMarker.specialization }}</p>
                        <div class="flex items-center mt-1">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            <span class="text-sm text-gray-700 ml-1">{{ hoveredMarker.rating }}</span>
                            <span class="text-sm text-gray-500 ml-1">({{ hoveredMarker.reviewsCount }})</span>
                        </div>
                        <p class="text-sm font-semibold text-gray-900 mt-1">
                            РѕС‚ {{ formatPrice(hoveredMarker.pricePerHour) }}/С‡Р°СЃ
                        </p>
                    </div>
                </div>
            </div>
        </transition>

        <!-- Р—Р°РіСЂСѓР·РєР° -->
        <div 
            v-if="loading"
            class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center"
        >
            <div class="flex flex-col items-center">
                <svg class="animate-spin h-10 w-10 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-600">Р—Р°РіСЂСѓР·РєР° РєР°СЂС‚С‹...</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

// РџСЂРѕРїСЃС‹
const props = defineProps({
    masters: {
        type: Array,
        default: () => []
    },
    center: {
        type: Object,
        default: () => ({ lat: 55.7558, lng: 37.6173 }) // РњРѕСЃРєРІР°
    },
    zoom: {
        type: Number,
        default: 12
    }
})

// Р­РјРёС‚С‹
const emit = defineEmits(['bounds-changed', 'marker-clicked'])

// РЎРѕСЃС‚РѕСЏРЅРёРµ
const mapContainer = ref(null)
const map = ref(null)
const markers = ref([])
const hoveredMarker = ref(null)
const markerInfoPosition = ref({ top: '80px', left: '16px' })
const loading = ref(true)

// РњРµС‚РѕРґС‹
const initMap = () => {
    // Р—РґРµСЃСЊ Р±СѓРґРµС‚ РёРЅРёС†РёР°Р»РёР·Р°С†РёСЏ РЇРЅРґРµРєСЃ.РљР°СЂС‚ РёР»Рё Google Maps
    // Р”Р»СЏ РїСЂРёРјРµСЂР° РїРѕРєР°Р·С‹РІР°СЋ СЃС‚СЂСѓРєС‚СѓСЂСѓ
    
    loading.value = true
    
    // Р­РјСѓР»СЏС†РёСЏ Р·Р°РіСЂСѓР·РєРё РєР°СЂС‚С‹
    setTimeout(() => {
        loading.value = false
        
        // РЎРѕР·РґР°РЅРёРµ РјР°СЂРєРµСЂРѕРІ РґР»СЏ РјР°СЃС‚РµСЂРѕРІ
        props.masters.forEach(master => {
            createMarker(master)
        })
    }, 1000)
}

const createMarker = (master) => {
    // РЎРѕР·РґР°РЅРёРµ РјР°СЂРєРµСЂР° РЅР° РєР°СЂС‚Рµ
    const marker = {
        id: master.id,
        position: {
            lat: master.latitude,
            lng: master.longitude
        },
        data: master
    }
    
    markers.value.push(marker)
}

const centerOnCurrentLocation = () => {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                const { latitude, longitude } = position.coords
                // Р¦РµРЅС‚СЂРёСЂРѕРІР°РЅРёРµ РєР°СЂС‚С‹ РЅР° С‚РµРєСѓС‰РµР№ РїРѕР·РёС†РёРё
            },
            (error) => {
            }
        )
    }
}

const zoomIn = () => {
    // РЈРІРµР»РёС‡РµРЅРёРµ РјР°СЃС€С‚Р°Р±Р°
}

const zoomOut = () => {
    // РЈРјРµРЅСЊС€РµРЅРёРµ РјР°СЃС€С‚Р°Р±Р°
}

const showMarkersInView = () => {
    // РџРѕРєР°Р·Р°С‚СЊ РјР°СЂРєРµСЂС‹ РІ С‚РµРєСѓС‰РµР№ РѕР±Р»Р°СЃС‚Рё РїСЂРѕСЃРјРѕС‚СЂР°
    emit('bounds-changed', {
        // Р“СЂР°РЅРёС†С‹ РІРёРґРёРјРѕР№ РѕР±Р»Р°СЃС‚Рё РєР°СЂС‚С‹
    })
}

const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU').format(price) + ' в‚Ѕ'
}

// РҐСѓРєРё Р¶РёР·РЅРµРЅРЅРѕРіРѕ С†РёРєР»Р°
onMounted(() => {
    initMap()
})

onUnmounted(() => {
    // РћС‡РёСЃС‚РєР° СЂРµСЃСѓСЂСЃРѕРІ РєР°СЂС‚С‹
})
</script>

<style scoped>
/* РЎС‚РёР»Рё РґР»СЏ РєР°СЃС‚РѕРјРЅС‹С… РјР°СЂРєРµСЂРѕРІ */
.custom-marker {
    position: relative;
    width: 40px;
    height: 50px;
}

.custom-marker::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 40px;
    background: #3B82F6;
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.custom-marker::after {
    content: '';
    position: absolute;
    top: 10px;
    left: 50%;
    transform: translateX(-50%);
    width: 10px;
    height: 10px;
    background: white;
    border-radius: 50%;
}

.custom-marker.hovered::before {
    background: #2563EB;
    transform: rotate(-45deg) scale(1.1);
}
</style>

