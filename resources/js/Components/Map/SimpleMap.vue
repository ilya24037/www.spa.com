<!-- resources/js/Components/Map/SimpleMap.vue -->
<template>
  <div class="relative w-full h-full bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg overflow-hidden">
    <!-- Паттерн карты на фоне -->
    <div class="absolute inset-0 opacity-10">
      <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
        <defs>
          <pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse">
            <path d="M10 10h80v80H10z" fill="none" stroke="#000" stroke-width="0.5"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid)" />
      </svg>
    </div>
    
    <!-- Контейнер для маркеров -->
    <div class="relative w-full h-full">
      <!-- Маркеры мастеров -->
      <div
        v-for="(master, index) in visibleMasters"
        :key="master.id"
        class="absolute transform -translate-x-1/2 -translate-y-1/2 group"
        :style="getMarkerPosition(master, index)"
      >
        <!-- Маркер -->
        <div 
          @click="$emit('marker-click', master)"
          class="relative cursor-pointer"
        >
          <!-- Тень маркера -->
          <div class="absolute inset-0 bg-black opacity-20 rounded-full blur-sm transform translate-y-1"></div>
          
          <!-- Сам маркер -->
          <div class="relative w-10 h-10 bg-blue-600 rounded-full border-3 border-white shadow-lg transform transition-all duration-200 group-hover:scale-110 group-hover:bg-blue-700">
            <span class="absolute inset-0 flex items-center justify-center text-white text-xs font-bold">
              {{ master.price_from ? Math.floor(master.price_from / 1000) + 'k' : index + 1 }}
            </span>
          </div>
          
          <!-- Подсказка при наведении -->
          <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-3 opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none z-10">
            <div class="bg-white rounded-lg shadow-xl p-3 whitespace-nowrap">
              <div class="font-semibold text-gray-900">{{ master.display_name || master.name || 'Мастер' }}</div>
              <div class="flex items-center gap-2 text-sm text-gray-600 mt-1">
                <div class="flex items-center">
                  <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                  <span>{{ master.rating || '5.0' }}</span>
                </div>
                <span>•</span>
                <span>от {{ master.price_from || 1500 }} ₽</span>
              </div>
              <div v-if="master.home_service" class="text-xs text-blue-600 mt-1">
                🚗 Выезд на дом
              </div>
            </div>
            <!-- Стрелка подсказки -->
            <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
              <div class="w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-white"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Центральная точка (текущее местоположение) -->
      <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
        <div class="relative">
          <div class="absolute inset-0 bg-red-500 rounded-full animate-ping opacity-20"></div>
          <div class="relative w-4 h-4 bg-red-500 rounded-full border-2 border-white shadow-lg"></div>
        </div>
      </div>
    </div>
    
    <!-- Легенда -->
    <div class="absolute bottom-4 left-4 bg-white/90 backdrop-blur rounded-lg shadow-lg p-4">
      <div class="text-sm font-medium text-gray-900 mb-2">Мастера рядом с вами</div>
      <div class="space-y-1">
        <div class="flex items-center gap-2 text-xs text-gray-600">
          <div class="w-3 h-3 bg-blue-600 rounded-full"></div>
          <span>Мастер (цена в тыс. ₽)</span>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-600">
          <div class="w-3 h-3 bg-red-500 rounded-full"></div>
          <span>Ваше местоположение</span>
        </div>
      </div>
      <div class="mt-2 pt-2 border-t text-xs text-gray-500">
        Показано: {{ visibleMasters.length }} из {{ masters.length }}
      </div>
    </div>

    <!-- Кнопки управления -->
    <div class="absolute top-4 right-4 flex flex-col gap-2">
      <button 
        @click="$emit('bounds-changed', getCurrentBounds())"
        class="p-2 bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow"
        title="Обновить в этой области"
      >
        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  masters: {
    type: Array,
    default: () => []
  },
  center: {
    type: Object,
    default: () => ({ lat: 55.7558, lng: 37.6173 })
  },
  zoom: {
    type: Number,
    default: 12
  }
})

const emit = defineEmits(['marker-click', 'bounds-changed'])

// Показываем максимум 20 мастеров на карте
const visibleMasters = computed(() => {
  return props.masters.slice(0, 20).filter(master => {
    // Фильтруем только тех, у кого есть координаты или первые 20
    return master.latitude && master.longitude || props.masters.indexOf(master) < 20
  })
})

// Генерируем позиции для маркеров
const getMarkerPosition = (master, index) => {
  // Если есть реальные координаты
  if (master.latitude && master.longitude) {
    // Простое преобразование координат в проценты
    // Для Москвы примерные границы: 55.5-55.9 (lat) и 37.3-37.9 (lng)
    const latPercent = ((master.latitude - 55.5) / 0.4) * 80 + 10
    const lngPercent = ((master.longitude - 37.3) / 0.6) * 80 + 10
    
    return {
      top: `${100 - latPercent}%`,
      left: `${lngPercent}%`
    }
  }
  
  // Распределение по спирали от центра
  const angle = (index / visibleMasters.value.length) * Math.PI * 2
  const radius = 25 + (index % 3) * 15 // Три круга: 25%, 40%, 55% от центра
  const centerX = 50
  const centerY = 50
  
  const x = centerX + Math.cos(angle) * radius
  const y = centerY + Math.sin(angle) * radius
  
  // Добавляем небольшую случайность для естественности
  const randomOffset = () => (Math.random() - 0.5) * 5
  
  return {
    top: `${Math.max(10, Math.min(90, y + randomOffset()))}%`,
    left: `${Math.max(5, Math.min(95, x + randomOffset()))}%`
  }
}

// Получить текущие границы видимой области (эмуляция)
const getCurrentBounds = () => {
  // Возвращаем примерные границы для эмуляции
  return {
    northEast: { 
      lat: props.center.lat + 0.05, 
      lng: props.center.lng + 0.05 
    },
    southWest: { 
      lat: props.center.lat - 0.05, 
      lng: props.center.lng - 0.05 
    }
  }
}
</script>

<style scoped>
/* Анимация пульсации для текущего местоположения */
@keyframes ping {
  75%, 100% {
    transform: scale(2);
    opacity: 0;
  }
}

.animate-ping {
  animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite;
}

/* Плавные переходы для маркеров */
.group:hover .transition-all {
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>