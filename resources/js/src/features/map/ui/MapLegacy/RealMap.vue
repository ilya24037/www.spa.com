<template>
  <div class="real-map">
    <div class="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center">
      <p class="text-gray-500">Карта будет здесь</p>
    </div>
  </div>
</template>

<script setup lang="ts">
// Real map component stub
defineProps<{
  markers?: any[]
  center?: { lat: number, lng: number }
  zoom?: number
}>()
</script>
