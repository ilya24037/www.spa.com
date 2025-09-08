<template>
  <div class="zones-section">
    <!-- Секция показывается только когда выбран выезд "В выбранные зоны" -->
    <div v-if="shouldShow" class="transition-all duration-200">
      <p class="text-sm text-gray-600 mb-3">
        Выберите районы, в которые вы готовы выезжать:
      </p>
      
      <!-- Использую существующий ZoneSelector -->
      <ZoneSelector 
        :model-value="currentZones"
        :zones="availableZones"
        @update:modelValue="handleZonesChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * ZonesSection - компонент выбора зон выезда
 * 
 * Ответственность (Single Responsibility):
 * - Показ/скрытие секции зон в зависимости от типа выезда
 * - Передача списка доступных зон в ZoneSelector
 * - Обработка изменений выбранных зон
 * 
 * НЕ содержит:
 * - Логику радиокнопок (OutcallSection.vue)
 * - Логику метро (MetroSection.vue)
 * - Логику карты (AddressMapSection.vue)
 * - Внутреннюю логику селектора зон (делегируется ZoneSelector)
 */

import { ref, computed, watch } from 'vue'
import ZoneSelector from '@/src/shared/ui/molecules/ZoneSelector/ZoneSelector.vue'

// Типы
type OutcallType = 'none' | 'city' | 'zones'

// Интерфейсы
interface Props {
  outcallType?: OutcallType
  initialZones?: string[]
}

interface Emits {
  'update:zones': [zones: string[]]
  'zones-changed': [data: { zones: string[] }]
}

// Props с дефолтными значениями
const props = withDefaults(defineProps<Props>(), {
  outcallType: 'none',
  initialZones: () => []
})

// Emits
const emit = defineEmits<Emits>()

// Доступные зоны города (Пермь) - точно как в оригинале
const availableZones = [
  'Дзержинский район',
  'Индустриальный район', 
  'Кировский район',
  'Ленинский район',
  'Мотовилихинский район',
  'Орджоникидзевский район',
  'Свердловский район'
]

// Реактивное состояние
const currentZones = ref<string[]>([...props.initialZones])

// Показывать секцию только если выбран тип "zones"
const shouldShow = computed(() => props.outcallType === 'zones')

// Обработка изменения выбранных зон
const handleZonesChange = (zones: string[]) => {
  currentZones.value = [...zones]
  
  // Отправляем события родителю
  emit('update:zones', zones)
  emit('zones-changed', { zones })
}

// Следим за изменениями props
watch(() => props.initialZones, (newZones) => {
  if (newZones) {
    currentZones.value = [...newZones]
  }
}, { deep: true })

// Очищаем зоны если outcallType изменился с 'zones' на другой
watch(() => props.outcallType, (newType, oldType) => {
  if (oldType === 'zones' && newType !== 'zones') {
    currentZones.value = []
    emit('update:zones', [])
    emit('zones-changed', { zones: [] })
  }
})
</script>

<style scoped>
/**
 * Стили ZonesSection - минимальные, основная стилизация в ZoneSelector
 */

.zones-section {
  @apply w-full;
}

/* Плавная анимация показа/скрытия */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>