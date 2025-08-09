<template>
  <div class="clients-type">
    <label class="block text-sm font-medium text-gray-500 mb-2">
      РўРёРї РєР»РёРµРЅС‚РѕРІ
    </label>
    <div class="space-y-2">
      <label class="flex items-center">
        <input
          v-model="localValue"
          type="checkbox"
          value="men"
          class="mr-2"
        >
        <span>РњСѓР¶С‡РёРЅС‹</span>
      </label>
      <label class="flex items-center">
        <input
          v-model="localValue"
          type="checkbox"
          value="women"
          class="mr-2"
        >
        <span>Р–РµРЅС‰РёРЅС‹</span>
      </label>
      <label class="flex items-center">
        <input
          v-model="localValue"
          type="checkbox"
          value="couples"
          class="mr-2"
        >
        <span>РџР°СЂС‹</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  modelValue?: string[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string[]]
}>()

const localValue = computed({
    get: () => props.modelValue || [],
    set: (value) => emit('update:modelValue', value)
})
</script>

