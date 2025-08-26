<template>
  <div class="mapmarkers relative w-full h-full min-h-[400px] bg-gray-500 rounded-lg overflow-hidden">
    <!-- Loading -->
    <div v-if="loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-75">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4" />
        <p class="text-gray-500">
          Загрузка карты...
        </p>
      </div>
    </div>
    
    <!-- Map placeholder -->
    <div v-else class="w-full h-full flex items-center justify-center">
      <p class="text-gray-500">
        Карта временно недоступна
      </p>
    </div>
    
    <slot />
  </div>
</template>

<script setup lang="ts">
interface Props {
  loading?: boolean
  center?: [number, number]
  zoom?: number
}

withDefaults(defineProps<Props>(), {
    loading: false,
    center: () => [55.7558, 37.6173],
    zoom: 10
})
</script>