<template>
  <div class="bg-white rounded-lg p-6 shadow-sm mt-6">
    <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ master.name }}</h1>
    
    <div class="flex items-center gap-4 mb-4">
      <div class="flex items-center gap-1">
        <StarIcon class="w-5 h-5 text-yellow-400" />
        <span class="font-medium">{{ master.rating || 4.5 }}</span>
        <span class="text-gray-500">({{ master.reviews_count || 12 }} отзывов)</span>
      </div>
      
      <div class="flex items-center gap-1">
        <MapPinIcon class="w-5 h-5 text-gray-400" />
        <span>{{ master.city }}{{ master.district ? ', ' + master.district : '' }}</span>
      </div>
    </div>
    
    <div v-if="master.description" class="text-gray-700 mb-4">
      {{ master.description }}
    </div>
  </div>
</template>

<script setup>
import { StarIcon, MapPinIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})
</script>

<style scoped>
/* Стили для основной информации */
</style> 