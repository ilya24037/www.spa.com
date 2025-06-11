<template>
  <div v-if="computedStats" class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-white p-4 rounded-md shadow text-center">
      <div class="text-2xl font-bold">{{ computedStats.total }}</div>
      <div class="text-gray-500">Всего проектов</div>
    </div>
    <div class="bg-white p-4 rounded-md shadow text-center">
      <div class="text-2xl font-bold">{{ computedStats.active }}</div>
      <div class="text-green-600">Активные</div>
    </div>
    <div class="bg-white p-4 rounded-md shadow text-center">
      <div class="text-2xl font-bold">{{ computedStats.completed }}</div>
      <div class="text-blue-600">Завершённые</div>
    </div>
  </div>
  <div v-else class="text-gray-400 text-center my-4">
    Нет данных для отображения статистики
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  stats: {
    type: Object,
    required: false,
    default: () => null,
  },
});

const computedStats = computed(() => {
  // Если stats не определён — возвращаем null, чтобы v-if скрыл блок
  if (!props.stats) return null;

  // Если есть поле data — это пагинация
  if (Array.isArray(props.stats.data)) {
    const list = props.stats.data;
    return {
      total: props.stats.meta?.total ?? list.length,
      active: list.filter((p) => p.status === 'active').length,
      completed: list.filter((p) => p.status === 'done').length,
    };
  }

  // Иначе считаем, что stats — уже объект { total, active, completed, on_hold }
  return {
    total: props.stats.total ?? 0,
    active: props.stats.active ?? 0,
    completed: props.stats.completed ?? 0,
  };
});
</script>
