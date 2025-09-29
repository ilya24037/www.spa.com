<template>
  <div class="outcall-types-section">
    <!-- Секция показывается когда выезд НЕ равен "none" -->
    <div v-if="shouldShow" class="transition-all duration-200">
      
      <!-- Типы мест для выезда -->
      <div class="mb-6">
        <h4 class="text-base font-medium text-gray-900 mb-2">Типы мест для выезда</h4>
        <p class="text-sm text-gray-600 leading-relaxed mb-4">
          Выберите, в какие места вы готовы выезжать к клиентам.
        </p>
        
        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
          <BaseCheckbox
            :model-value="currentTypes.apartment"
            name="outcall_apartment"
            label="На квартиру"
            @update:modelValue="updateType('apartment', $event)"
          />
          <BaseCheckbox
            :model-value="currentTypes.hotel"
            name="outcall_hotel"
            label="В гостиницу"
            @update:modelValue="updateType('hotel', $event)"
          />
          <BaseCheckbox
            :model-value="currentTypes.office"
            name="outcall_office"
            label="В офис"
            @update:modelValue="updateType('office', $event)"
          />
          <BaseCheckbox
            :model-value="currentTypes.sauna"
            name="outcall_sauna"
            label="В сауну"
            @update:modelValue="updateType('sauna', $event)"
          />
          <BaseCheckbox
            :model-value="currentTypes.house"
            name="outcall_house"
            label="В загородный дом"
            @update:modelValue="updateType('house', $event)"
          />
        </div>
      </div>
      
      <!-- Такси -->
      <div class="pt-4 border-t border-gray-200">
        <h4 class="text-base font-medium text-gray-900 mb-2">Такси</h4>
        <p class="text-sm text-gray-600 leading-relaxed mb-4">
          Укажите, как оплачивается такси до места выезда.
        </p>
        
        <div class="flex flex-col gap-2">
          <BaseRadio
            :model-value="currentTaxiIncluded"
            :value="false"
            label="Оплачивается отдельно"
            name="taxi"
            @update:modelValue="handleTaxiChange"
          />
          <BaseRadio
            :model-value="currentTaxiIncluded"
            :value="true"
            label="Включено в стоимость"
            name="taxi"
            @update:modelValue="handleTaxiChange"
          />
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup lang="ts">
/**
 * OutcallTypesSection - компонент выбора типов мест и такси
 * 
 * Ответственность (Single Responsibility):
 * - Показ/скрытие секции типов мест в зависимости от выезда
 * - Обработка выбора типов мест (чекбоксы)
 * - Обработка настройки такси (радиокнопки)
 * 
 * НЕ содержит:
 * - Логику радиокнопок выезда (OutcallSection.vue)
 * - Логику зон (ZonesSection.vue)  
 * - Логику метро (MetroSection.vue)
 * - Логику карты (AddressMapSection.vue)
 */

import { ref, computed, watch, reactive } from 'vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'

// Типы
type OutcallType = 'none' | 'city' | 'zones'

interface OutcallTypes {
  apartment: boolean
  hotel: boolean
  office: boolean
  sauna: boolean
  house: boolean
}

// Интерфейсы
interface Props {
  outcallType?: OutcallType
  initialTypes?: Partial<OutcallTypes>
  initialTaxiIncluded?: boolean
}

interface Emits {
  'update:types': [types: OutcallTypes]
  'update:taxiIncluded': [taxiIncluded: boolean]
  'types-changed': [data: { 
    outcallTypes: OutcallTypes
    taxiIncluded: boolean 
  }]
}

// Props с дефолтными значениями (точно как в оригинале)
const props = withDefaults(defineProps<Props>(), {
  outcallType: 'none',
  initialTypes: () => ({
    apartment: true,  // По умолчанию true как в оригинале
    hotel: false,
    office: false,
    sauna: false,
    house: false
  }),
  initialTaxiIncluded: false
})

// Emits
const emit = defineEmits<Emits>()

// Реактивное состояние
const currentTypes = reactive<OutcallTypes>({
  apartment: props.initialTypes?.apartment ?? true,
  hotel: props.initialTypes?.hotel ?? false,
  office: props.initialTypes?.office ?? false,
  sauna: props.initialTypes?.sauna ?? false,
  house: props.initialTypes?.house ?? false
})

const currentTaxiIncluded = ref<boolean>(props.initialTaxiIncluded)

// Показывать секцию когда outcallType НЕ равен 'none'
const shouldShow = computed(() => props.outcallType !== 'none')

// Обработка изменения типа места
const updateType = (type: keyof OutcallTypes, value: boolean) => {
  currentTypes[type] = value
  emitChanges()
}

// Обработка изменения настройки такси
const handleTaxiChange = (value: boolean) => {
  currentTaxiIncluded.value = value
  emitChanges()
}

// Отправка изменений родителю
const emitChanges = () => {
  const typesData = { ...currentTypes }
  
  emit('update:types', typesData)
  emit('update:taxiIncluded', currentTaxiIncluded.value)
  emit('types-changed', {
    outcallTypes: typesData,
    taxiIncluded: currentTaxiIncluded.value
  })
}

// Следим за изменениями props
watch(() => props.initialTypes, (newTypes) => {
  if (newTypes) {
    Object.assign(currentTypes, {
      apartment: newTypes.apartment ?? true,
      hotel: newTypes.hotel ?? false,
      office: newTypes.office ?? false,
      sauna: newTypes.sauna ?? false,
      house: newTypes.house ?? false
    })
  }
}, { deep: true })

watch(() => props.initialTaxiIncluded, (newTaxi) => {
  currentTaxiIncluded.value = newTaxi
})

// Очищаем данные если outcallType изменился на 'none'
watch(() => props.outcallType, (newType) => {
  if (newType === 'none') {
    // Сбрасываем на дефолтные значения
    Object.assign(currentTypes, {
      apartment: true,
      hotel: false,
      office: false,
      sauna: false,
      house: false
    })
    currentTaxiIncluded.value = false
    emitChanges()
  }
})
</script>

<style scoped>
/**
 * Стили OutcallTypesSection - минимальные, основная стилизация в shared компонентах
 */

.outcall-types-section {
  @apply w-full;
}

/* Плавная анимация показа/скрытия */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}
</style>