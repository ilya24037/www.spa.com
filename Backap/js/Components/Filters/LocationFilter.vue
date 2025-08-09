<!-- resources/js/Components/Filters/LocationFilter.vue -->
<template>
  <div>
    <h4 class="text-sm font-medium mb-2">Город</h4>

    <!-- Поле поиска -->
    <input
      v-model="search"
      type="text"
      placeholder="Найти город…"
      class="mb-2 w-full border rounded px-2 py-1 text-sm"
    />

    <!-- Список городов -->
    <div class="max-h-60 overflow-y-auto pr-1 space-y-1">
      <label
        v-for="city in filteredCities"
        :key="city"
        class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 rounded px-2 py-1"
      >
        <input
          type="radio"
          :value="city"
          v-model="model"
          class="accent-blue-600"
        />
        <span>{{ city }}</span>
      </label>
    </div>

    <!-- Кнопка сброса -->
    <button
      v-if="model"
      @click="clear"
      class="mt-2 text-sm text-blue-600 hover:underline"
    >
      Сбросить город
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

/**
 * Props
 * - modelValue: string | null  (выбранный город)
 * - cities: string[]            (список доступных городов)
 */
const props = withDefaults(defineProps<{
  modelValue?: string | null
  cities?: string[]
}>(), {
  modelValue: null,
  cities: () => [
    'Москва', 'Санкт‑Петербург', 'Новосибирск', 'Екатеринбург', 'Нижний Новгород',
    'Казань', 'Челябинск', 'Самара', 'Уфа', 'Ростов‑на‑Дону',
  ],
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

// локальное состояние
const search = ref('')

// 2‑way binding через computed
const model = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val),
})

// фильтрация по поиску (регистр неважен)
const filteredCities = computed(() => {
  if (!search.value) return props.cities
  const q = search.value.toLowerCase()
  return props.cities.filter((c) => c.toLowerCase().includes(q))
})

function clear() {
  emit('update:modelValue', null)
  search.value = ''
}

// очистить строку поиска, когда выбран новый город
watch(model, () => (search.value = ''))
</script>

<style scoped>
/* тонкий скроллбар */
.max-h-60::-webkit-scrollbar {
  width: 6px;
}
.max-h-60::-webkit-scrollbar-thumb {
  background: #c3cbd1;
  border-radius: 3px;
}
</style>
