<template>
  <div class="absolute top-4 right-4 flex flex-col gap-2 z-20">
    <button
      :disabled="!canZoomIn"
      class="control-btn"
      title="Приблизить"
      @click="$emit('zoom-in')"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
      </svg>
    </button>

    <button
      :disabled="!canZoomOut"
      class="control-btn"
      title="Отдалить"
      @click="$emit('zoom-out')"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 12H6" />
      </svg>
    </button>

    <button
      v-if="showGeolocation"
      class="control-btn"
      title="Моя локация"
      @click="$emit('geolocation-click')"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
    </button>

    <button
      v-if="showFullscreen"
      class="control-btn"
      title="Полный экран"
      @click="$emit('fullscreen')"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
      </svg>
    </button>

    <div v-if="showZoomLevel" class="zoom-indicator">
      {{ zoom }}x
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  zoom?: number
  canZoomIn?: boolean
  canZoomOut?: boolean
  showGeolocation?: boolean
  showFullscreen?: boolean
  showZoomLevel?: boolean
  locationActive?: boolean
  geolocationLoading?: boolean
}

withDefaults(defineProps<Props>(), {
  zoom: 10,
  canZoomIn: true,
  canZoomOut: true,
  showGeolocation: false,
  showFullscreen: false,
  showZoomLevel: true,
  locationActive: false,
  geolocationLoading: false
})

defineEmits<{
  'zoom-in': []
  'zoom-out': []
  'geolocation-click': []
  fullscreen: []
}>()
</script>

<style scoped>
.control-btn {
  @apply bg-white p-2 rounded shadow hover:bg-gray-50 transition-colors;
  @apply disabled:opacity-50 disabled:cursor-not-allowed;
}

.zoom-indicator {
  @apply bg-white px-2 py-1 rounded shadow text-xs font-medium text-gray-500 text-center;
}
</style>
