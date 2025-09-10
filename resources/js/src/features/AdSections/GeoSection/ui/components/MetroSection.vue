<template>
  <div class="metro-section">
    <!-- Секция показывается когда выезд НЕ равен "none" -->
    <div v-if="shouldShow" class="transition-all duration-200">
      <p class="text-sm text-gray-600 mb-3">
        Выберите станции метро, к которым вы готовы выезжать:
      </p>
      
      <!-- Использую существующий MetroSelector -->
      <MetroSelector 
        :model-value="currentStations"
        :stations="availableStations"
        @update:modelValue="handleStationsChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * MetroSection - компонент выбора станций метро
 * 
 * Ответственность (Single Responsibility):
 * - Показ/скрытие секции метро в зависимости от типа выезда
 * - Получение данных станций через useMetroData composable
 * - Обработка изменений выбранных станций
 * 
 * НЕ содержит:
 * - Логику радиокнопок (OutcallSection.vue)
 * - Логику зон (ZonesSection.vue)
 * - Логику карты (AddressMapSection.vue)
 * - Внутреннюю логику селектора метро (делегируется MetroSelector)
 */

import { ref, computed, watch, onMounted } from 'vue'
import MetroSelector, { useMetroData } from '@/src/shared/ui/molecules/MetroSelector'

// Типы
type OutcallType = 'none' | 'city' | 'zones'

// Интерфейсы
interface Props {
  outcallType?: OutcallType
  initialStations?: string[]
  currentCity?: string
}

interface Emits {
  'update:stations': [stations: string[]]
  'stations-changed': [data: { stations: string[] }]
}

// Props с дефолтными значениями
const props = withDefaults(defineProps<Props>(), {
  outcallType: 'none',
  initialStations: () => [],
  currentCity: ''
})

// Emits
const emit = defineEmits<Emits>()

// Получение данных станций метро через composable
const { hasCityMetro, getStationsForCity, updateCity } = useMetroData(props.currentCity)

// Реактивное состояние
const currentStations = ref<string[]>([...props.initialStations])
const availableStations = ref<string[]>([])

// 🛡️ Флаг инициализации для предотвращения очистки данных при первой загрузке
const isInitializing = ref(true)

// Показывать секцию когда outcallType НЕ равен 'none' И в городе есть метро
const shouldShow = computed(() => 
  props.outcallType !== 'none' && 
  props.currentCity && 
  hasCityMetro(props.currentCity)
)

// Обработка изменения выбранных станций
const handleStationsChange = (stations: string[]) => {
  currentStations.value = [...stations]
  
  // Отправляем события родителю
  emit('update:stations', stations)
  emit('stations-changed', { stations })
}

// Следим за изменениями props
watch(() => props.initialStations, (newStations) => {
  if (newStations) {
    currentStations.value = [...newStations]
  }
}, { deep: true })

// Очищаем станции если outcallType изменился на 'none'
watch(() => props.outcallType, (newType, oldType) => {
  if (newType === 'none' && oldType !== 'none') {
    currentStations.value = []
    emit('update:stations', [])
    emit('stations-changed', { stations: [] })
  }
})

// Обновляем доступные станции при изменении города
watch(() => props.currentCity, (newCity, oldCity) => {
  if (newCity && hasCityMetro(newCity)) {
    availableStations.value = getStationsForCity(newCity)
    updateCity(newCity)
    
    // 🛡️ НЕ очищаем станции при первой инициализации
    if (isInitializing.value) {
      console.log('🔒 [MetroSection] Инициализация - сохраняем существующие станции:', currentStations.value)
      return
    }
    
    // Очищаем выбранные станции ТОЛЬКО при реальной смене города
    if (oldCity && oldCity !== newCity) {
      console.log('🔄 [MetroSection] Смена города с', oldCity, 'на', newCity, '- очищаем станции')
      currentStations.value = []
      emit('update:stations', [])
      emit('stations-changed', { stations: [] })
    }
  } else {
    availableStations.value = []
  }
}, { immediate: true })

// 🛡️ Снимаем флаг инициализации после монтирования компонента
onMounted(() => {
  setTimeout(() => {
    isInitializing.value = false
    console.log('🔓 [MetroSection] Инициализация завершена')
  }, 100)
})
</script>

<style scoped>
/**
 * Стили MetroSection - минимальные, основная стилизация в MetroSelector
 */

.metro-section {
  @apply w-full;
}

/* Плавная анимация показа/скрытия */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>