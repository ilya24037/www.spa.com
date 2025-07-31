<template>
  <FormSection
    title="Цена за час"
    hint="Укажите стоимость ваших услуг. Цена влияет на позицию в поиске"
    required
    :error="errors.price_per_hour"
  >
    <div class="space-y-6">
      <!-- Сетка основных полей -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Основная цена -->
        <MainPriceInput
          v-model="localPricePerHour"
          :error="errors.price_per_hour"
        />

        <!-- Цена за выезд -->
        <OutcallPriceInput
          v-model="localOutcallPrice"
          :error="errors.outcall_price"
        />

        <!-- Минимальное время -->
        <DurationSelect
          v-model="localMinDuration"
          :error="errors.min_duration"
        />
      </div>

      <!-- Советы по ценообразованию -->
      <PricingTips />
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'

// Микрокомпоненты
import MainPriceInput from './components/MainPriceInput.vue'
import OutcallPriceInput from './components/OutcallPriceInput.vue'
import DurationSelect from './components/DurationSelect.vue'
import PricingTips from './components/PricingTips.vue'

const props = defineProps({
  pricePerHour: { type: [String, Number], default: '' },
  outcallPrice: { type: [String, Number], default: '' },
  minDuration: { type: [String, Number], default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:pricePerHour',
  'update:outcallPrice', 
  'update:minDuration'
])

// Локальное состояние
const localPricePerHour = ref(String(props.pricePerHour || ''))
const localOutcallPrice = ref(String(props.outcallPrice || ''))
const localMinDuration = ref(String(props.minDuration || ''))

// Отслеживание изменений пропсов
watch(() => props.pricePerHour, (newValue) => { 
  localPricePerHour.value = String(newValue || '') 
})
watch(() => props.outcallPrice, (newValue) => { 
  localOutcallPrice.value = String(newValue || '') 
})
watch(() => props.minDuration, (newValue) => { 
  localMinDuration.value = String(newValue || '') 
})

// Отправка изменений родителю
watch(localPricePerHour, (newValue) => {
  emit('update:pricePerHour', newValue)
})

watch(localOutcallPrice, (newValue) => {
  emit('update:outcallPrice', newValue)
})

watch(localMinDuration, (newValue) => {
  emit('update:minDuration', newValue)
})
</script>