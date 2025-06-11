<template>
  <div class="space-y-6">
    <h1 class="text-2xl font-bold">{{ project.name }}</h1>
    <div class="flex flex-wrap gap-4">
      <ProjectCard :project="project" />
      <div class="bg-white p-4 rounded-md shadow w-full md:w-1/2">
        <h2 class="font-semibold mb-2">Ключевые метрики</h2>
        <ul class="space-y-1 text-sm text-gray-700">
          <li>Прогресс: {{ project.progress }} %</li>
          <li>Health Score: {{ project.health_score }} %</li>
          <li>Бюджет: {{ project.spent_budget }} / {{ project.budget }}</li>
          <li>Начало: {{ formattedDate(project.start_date) }}</li>
          <li>Конец: {{ formattedDate(project.end_date) }}</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import ProjectCard from '@/Components/Projects/ProjectCard.vue'
import dayjs from 'dayjs'

const props = defineProps({
  project: {
    type: Object,
    required: true
  }
})

const formattedDate = (d) => d ? dayjs(d).format('DD.MM.YYYY') : '-'
</script>

<style scoped>
/* при желании можно добавить графики на canvas здесь */
</style>
