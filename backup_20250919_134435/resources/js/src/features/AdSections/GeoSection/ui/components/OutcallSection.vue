<template>
  <div class="outcall-section">
    <!-- Заголовок секции -->
    <div class="mb-4">
      <h3 class="text-base font-medium text-gray-900 mb-2">Куда выезжаете</h3>
      <p class="text-sm text-gray-600 leading-relaxed">
        Укажите все зоны выезда, чтобы клиенты понимали, доберётесь ли вы до них.
      </p>
    </div>
    
    <!-- Радиокнопки выбора типа выезда -->
    <div class="flex flex-col gap-2">
      <BaseRadio
        :model-value="currentOutcall"
        value="none"
        name="outcall"
        label="Не выезжаю"
        @update:modelValue="handleOutcallChange"
      />
      <BaseRadio
        :model-value="currentOutcall"
        value="city"
        name="outcall"
        label="По всему городу"
        @update:modelValue="handleOutcallChange"
      />
      <BaseRadio
        :model-value="currentOutcall"
        value="zones"
        name="outcall"
        label="В выбранные зоны"
        @update:modelValue="handleOutcallChange"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * OutcallSection - компонент выбора типа выезда
 * 
 * Ответственность (Single Responsibility):
 * - Отображение радиокнопок типов выезда
 * - Обработка изменения типа выезда
 * - Очистка зон при смене типа
 * 
 * НЕ содержит:
 * - Логику выбора зон (ZonesSection.vue)
 * - Логику выбора метро (MetroSection.vue)
 * - Логику карты (AddressMapSection.vue)
 * - Автосохранение (делегируется родителю)
 */

import { ref, watch } from 'vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'

// Типы
type OutcallType = 'none' | 'city' | 'zones'

// Интерфейсы
interface Props {
  initialOutcall?: OutcallType
}

interface Emits {
  'update:outcall': [outcall: OutcallType]
  'outcall-changed': [data: { outcall: OutcallType; shouldClearZones: boolean }]
}

// Props с дефолтными значениями
const props = withDefaults(defineProps<Props>(), {
  initialOutcall: 'none'
})

// Emits
const emit = defineEmits<Emits>()

// Реактивное состояние
const currentOutcall = ref<OutcallType>(props.initialOutcall)

// Обработка изменения типа выезда
const handleOutcallChange = (value: OutcallType) => {
  const previousOutcall = currentOutcall.value
  currentOutcall.value = value
  
  // Определяем, нужно ли очищать зоны
  const shouldClearZones = previousOutcall === 'zones' && value !== 'zones'
  
  // Отправляем события родителю
  emit('update:outcall', value)
  emit('outcall-changed', { 
    outcall: value, 
    shouldClearZones 
  })
}

// Следим за изменениями props
watch(() => props.initialOutcall, (newOutcall) => {
  if (newOutcall) {
    currentOutcall.value = newOutcall
  }
})
</script>

<style scoped>
/**
 * Стили OutcallSection - только для выбора типа выезда
 */

.outcall-section {
  @apply w-full;
}

/* Компонент использует стили BaseRadio из shared/ui/atoms */
/* Никаких дополнительных стилей не требуется - следуем принципу KISS */
</style>