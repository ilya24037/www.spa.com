<!-- resources/js/Components/Filters/PriceFilter.vue -->
<template>
  <div>
    <h4 class="text-sm font-medium mb-2">Стоимость, ₽</h4>

    <!-- Диапазон «От» / «До» -->
    <div class="flex items-center gap-2">
      <input
        v-model.number="local.min"
        @input="emitChange"
        type="number"
        placeholder="От"
        min="0"
        class="w-24 border rounded px-2 py-1 text-sm"
      />
      <span class="text-gray-500">—</span>
      <input
        v-model.number="local.max"
        @input="emitChange"
        type="number"
        placeholder="До"
        min="0"
        class="w-24 border rounded px-2 py-1 text-sm"
      />
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, toRefs } from 'vue'

/**
 * v-model:price -> { min: Number|null, max: Number|null }
 */
const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ min: null, max: null })
  }
})
const emit = defineEmits(['update:modelValue'])

// локальное реактивное состояние, чтобы не трогать родителя до onInput
const local = reactive({
  min: props.modelValue.min ?? null,
  max: props.modelValue.max ?? null
})

watch(() => props.modelValue, v => {
  if (v.min !== local.min) local.min = v.min
  if (v.max !== local.max) local.max = v.max
})

function emitChange () {
  emit('update:modelValue', {
    min: local.min || null,
    max: local.max || null
  })
}
</script>

<style scoped>
/* небольшие правки, чтобы инпуты были одной высоты */
input::-webkit-inner-spin-button,
input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>
