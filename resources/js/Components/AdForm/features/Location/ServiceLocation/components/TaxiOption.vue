<template>
  <FormField
    v-if="showTaxiOption"
    label="Дополнительные услуги"
    :error="error"
  >
    <!-- Используем готовый BaseCheckbox -->
    <BaseCheckbox
      v-model="localValue"
      label="🚗 Встречаю на такси"
      description="Могу встретить клиента и довезти до места"
    />
    
    <!-- Информация об услуге -->
    <Card v-if="localValue" variant="bordered" class="mt-4 bg-amber-50 border-amber-200">
      <div class="flex space-x-2">
        <span class="text-amber-600">ℹ️</span>
        <div class="text-sm text-amber-800">
          <p class="font-medium mb-1">Услуга такси:</p>
          <p>Эта опция будет отображена в вашем объявлении как дополнительное удобство для клиентов.</p>
        </div>
      </div>
    </Card>
  </FormField>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseCheckbox from '@/Components/UI/BaseCheckbox.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  selectedServiceTypes: { type: Array, default: () => [] },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue)

watch(() => props.modelValue, (newValue) => {
  if (newValue !== localValue.value) {
    localValue.value = newValue
  }
})

watch(localValue, (newValue) => {
  if (newValue !== props.modelValue) {
    emit('update:modelValue', newValue)
  }
})

// Показывать только если выбран выезд
const showTaxiOption = computed(() => {
  return props.selectedServiceTypes.includes('outcall')
})
</script>