<template>
  <div class="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center">
    <p class="text-gray-500">Карта временно недоступна</p>
  </div>
</template>

<script setup lang="ts">
// Заглушка для компонента карты
defineProps<{
  center?: [number, number]
  zoom?: number
  markers?: any[]
}>()
</script>