<template>
  <Link
    :href="route('projects.show', project.id)"
    class="block hover:shadow-lg transition-shadow"
  >
    <div class="bg-white p-4 rounded-md shadow-sm">
      <h3 class="text-lg font-semibold mb-2">{{ project.name }}</h3>
      <div class="flex items-center space-x-4 text-sm text-gray-500 mb-4">
        <span class="capitalize">{{ project.status }}</span>
        <span>•</span>
        <span>{{ formattedDate(project.start_date) }} – {{ formattedDate(project.end_date) }}</span>
      </div>
      <div class="mb-1 text-sm text-gray-600">Прогресс: {{ project.progress }} %</div>
      <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
        <div
          class="h-full bg-blue-500"
          :style="{ width: project.progress + '%' }"
        ></div>
      </div>
      <div class="mt-2 text-sm text-gray-600">Health: {{ project.health_score }} %</div>
    </div>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
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
/* никаких дополнительных стилей пока не нужно */
</style>
