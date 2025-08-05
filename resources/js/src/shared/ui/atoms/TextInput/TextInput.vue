<template>
  <input
    :value="modelValue"
    @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
    ref="input"
  />
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  modelValue?: string | number
}

interface Emits {
  'update:modelValue': [value: string]
}

defineProps<Props>()
defineEmits<Emits>()

const input = ref<HTMLInputElement>()

defineExpose({
  focus: () => input.value?.focus()
})
</script>