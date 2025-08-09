<template>
  <div class="adformaddress">
    <label class="block text-sm font-medium text-gray-500 mb-2">
      {{ label }}
    </label>
    <input
      v-model="localValue"
      type="text"
      class="w-full px-3 py-2 border border-gray-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
      :placeholder="placeholder"
    >
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  modelValue?: string | number
  label?: string
  placeholder?: string
}>(), {
    label: 'Address',
    placeholder: 'Р’РІРµРґРёС‚Рµ Р·РЅР°С‡РµРЅРёРµ'
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const localValue = computed({
    get: () => props.modelValue || '',
    set: (value) => emit('update:modelValue', value)
})
</script>

