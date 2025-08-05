<template>
  <div class="service-provider">
    <label class="block text-sm font-medium text-gray-700 mb-2">
      Тип услуг
    </label>
    <div class="space-y-2">
      <label class="flex items-center">
        <input type="radio" :value="'individual'" v-model="localValue" class="mr-2">
        <span>Индивидуальный</span>
      </label>
      <label class="flex items-center">
        <input type="radio" :value="'salon'" v-model="localValue" class="mr-2">
        <span>Салон</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  modelValue?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const localValue = computed({
  get: () => props.modelValue || 'individual',
  set: (value) => emit('update:modelValue', value)
})
</script>