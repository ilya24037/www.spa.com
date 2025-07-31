<template>
  <div class="space-y-4">
    <h4 class="text-lg font-medium text-gray-900">Основная услуга</h4>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Название услуги -->
      <FormField
        label="Название услуги"
        :error="errors.main_service_name"
      >
        <BaseInput
          :model-value="serviceName"
          @update:model-value="$emit('update:service-name', $event)"
          placeholder="Классический массаж"
        />
      </FormField>

      <!-- Цена -->
      <FormField
        label="Цена"
        :error="errors.main_service_price"
      >
        <div class="flex gap-2">
          <BaseInput
            :model-value="servicePrice"
            @update:model-value="$emit('update:service-price', $event)"
            type="number"
            min="0"
            step="100"
            placeholder="3000"
            suffix="₽"
            class="flex-1"
          />
          <BaseSelect
            :model-value="priceUnit"
            @update:model-value="$emit('update:price-unit', $event)"
            :options="priceUnitOptions"
            class="w-32"
          />
        </div>
      </FormField>
    </div>
  </div>
</template>

<script setup>
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'

const props = defineProps({
  serviceName: {
    type: String,
    default: ''
  },
  servicePrice: {
    type: [String, Number],
    default: ''
  },
  priceUnit: {
    type: String,
    default: 'час'
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

defineEmits(['update:service-name', 'update:service-price', 'update:price-unit'])

// Опции для единиц измерения цены
const priceUnitOptions = [
  { value: 'час', label: 'за час' },
  { value: 'сеанс', label: 'за сеанс' },
  { value: 'услуга', label: 'за услугу' },
  { value: 'программа', label: 'за программу' }
]
</script>