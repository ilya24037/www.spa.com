<!-- Компонент настроек отображения медиа с чекбоксами -->
<template>
  <div class="media-settings-section border-t pt-4 mt-4">
    <h4 class="text-sm font-medium text-gray-700 mb-3">
      Настройки отображения
    </h4>
    
    <div class="space-y-2">
      <BaseCheckbox 
        id="show-additional-info"
        v-model="localShowAdditionalInfo"
        name="show_additional_info"
        label="Показывать дополнительную информацию"
      />
      
      <BaseCheckbox 
        id="show-services"
        v-model="localShowServices"
        name="show_services"
        label="Показывать услуги"
      />
      
      <BaseCheckbox 
        id="show-prices"
        v-model="localShowPrices"
        name="show_prices"
        label="Показывать цены"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

interface Props {
  showAdditionalInfo?: boolean
  showServices?: boolean
  showPrices?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showAdditionalInfo: false,
  showServices: false,
  showPrices: false
})

const emit = defineEmits<{
  'update:showAdditionalInfo': [value: boolean]
  'update:showServices': [value: boolean]
  'update:showPrices': [value: boolean]
}>()

// v-model для чекбоксов
const localShowAdditionalInfo = computed({
  get: () => props.showAdditionalInfo,
  set: (value) => emit('update:showAdditionalInfo', value)
})

const localShowServices = computed({
  get: () => props.showServices,
  set: (value) => emit('update:showServices', value)
})

const localShowPrices = computed({
  get: () => props.showPrices,
  set: (value) => emit('update:showPrices', value)
})
</script>