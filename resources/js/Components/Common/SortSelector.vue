<script setup>
import { ref, watch } from 'vue'

/**
 * Список доступных вариантов сортировки
 */
const options = [
  { value: 'date_desc', label: 'Сначала новые' },
  { value: 'date_asc', label: 'Сначала старые' },
  { value: 'price_asc', label: 'Сначала дешёвые' },
  { value: 'price_desc', label: 'Сначала дорогие' },
  { value: 'rating_desc', label: 'Высокий рейтинг' }
]

// Значение по умолчанию (можно принимать через props)
const selected = ref(options[0].value)

// Отправлять выбранный вариант наружу
const emit = defineEmits(['update:modelValue'])

watch(selected, value => {
  emit('update:modelValue', value)
})
</script>

<template>
  <div class="flex items-center gap-2">
    <span class="text-gray-500 text-sm">Сортировать:</span>
    <select
      v-model="selected"
      class="rounded-lg border-gray-300 focus:border-blue-400 focus:ring-2 focus:ring-blue-200 text-sm py-2 px-3 transition"
    >
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
  </div>
</template>

<style scoped>
/* Можно добавить доп. стили под дизайн */
</style>
