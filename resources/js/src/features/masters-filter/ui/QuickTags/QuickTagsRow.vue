<!-- resources/js/Components/Filters/ServiceFilter.vue (fixed) -->
<template>
  <div>
    <h4 class="text-sm font-medium mb-2">Услуга</h4>

    <!-- Поиск по списку -->
    <input
      v-model="search"
      type="text"
      placeholder="Найти услугу…"
      class="mb-2 w-full border rounded px-2 py-1 text-sm"
    />

    <!-- Список чекбоксов -->
    <div class="max-h-60 overflow-y-auto pr-1 space-y-1">
      <label
        v-for="opt in filteredOptions"
        :key="opt.value"
        class="flex items-center gap-2 text-sm"
      >
        <input
          type="checkbox"
          :value="opt.value"
          v-model="localSelected"
          class="shrink-0 rounded border-gray-300 focus:ring-blue-500"
        />
        <span>{{ opt.label }}</span>
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

/* ─── props & emits ─── */
const emit = defineEmits(['update:modelValue'])
const props = defineProps({
  /** Выбранные услуги */
  modelValue: { type: Array, default: () => [] },
  /** Массив { value, label } всех доступных услуг */
  options:    { type: Array, default: () => [] },
})

/* ─── локальное состояние ─── */
const search        = ref('')
// всегда массив, даже если сверху пришёл null/undefined
const localSelected = ref(Array.isArray(props.modelValue) ? [...props.modelValue] : [])

/* синхронизация вниз → вверх */
watch(localSelected, v => emit('update:modelValue', v))
/* и наоборот (если родитель сбросил фильтр) */
watch(
  () => props.modelValue,
  v => {
    if (Array.isArray(v)) localSelected.value = [...v]
    else localSelected.value = []
  },
)

/* ─── поиск по списку ─── */
const filteredOptions = computed(() => {
  const q = search.value.trim().toLowerCase()
  return q
    ? props.options.filter(o => o.label.toLowerCase().includes(q))
    : props.options
})
</script>
