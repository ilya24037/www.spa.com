<template>
  <div class="service-provider">
    <label class="block text-sm font-medium text-gray-500 mb-2">
      РўРёРї СѓСЃР»СѓРі
    </label>
    <div class="space-y-2">
      <label class="flex items-center">
        <input
          v-model="localValue"
          type="radio"
          :value="'individual'"
          class="mr-2"
        >
        <span>РРЅРґРёРІРёРґСѓР°Р»СЊРЅС‹Р№</span>
      </label>
      <label class="flex items-center">
        <input
          v-model="localValue"
          type="radio"
          :value="'salon'"
          class="mr-2"
        >
        <span>РЎР°Р»РѕРЅ</span>
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

