<template>
  <div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-4 rounded-md shadow text-center">
      <div class="text-2xl font-bold">{{ stats.total }}</div>
      <div class="text-gray-500">Всего проектов</div>
    </div>
    <div class="bg-white p-4 rounded-md shadow text-center">
      <div class="text-2xl font-bold">{{ stats.active }}</div>
      <div class="text-green-600">Активные</div>
    </div>
    <div class="bg-white p-4 rounded-md shadow text-center">
      <div class="text-2xl font-bold">{{ stats.completed }}</div>
      <div class="text-blue-600">Завершённые</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  projects: {
    type: Object,    // Inertia-пагинация с { data: [...], meta: { total, ... } }
    required: true
  }
})

const stats = computed(() => {
  const arr = props.projects.data
  return {
    total: props.projects.meta.total,
    active: arr.filter(p => p.status === 'active').length,
    completed: arr.filter(p => p.status === 'done').length,
  }
})
</script>

<style scoped>
/* адаптивная сетка уже через Tailwind */
</style>
