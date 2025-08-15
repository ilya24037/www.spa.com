<template>
  <input
    :id="textInputId"
    ref="input"
    :value="modelValue"
    class="w-full px-3 py-2 border border-gray-500 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" 
    @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
  >
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useId } from '@/src/shared/composables/useId'

interface Props {
  modelValue?: string | number
  id?: string
}

interface Emits {
  'update:modelValue': [value: string]
}

const props = withDefaults(defineProps<Props>(), {
  id: ''
})
defineEmits<Emits>()

// Generate unique ID if not provided
const textInputId = computed(() => props.id || useId('text-input'))

const input = ref<HTMLInputElement>()

defineExpose({
    focus: () => input.value?.focus()
})
</script>