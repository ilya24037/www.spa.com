<!-- resources/js/Components/Header/CitySelector.vue -->
<template>
  <div>
    <button 
      @click="$emit('open-modal')"
      class="font-medium text-gray-900 hover:text-blue-600 transition flex items-center gap-1"
    >
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      {{ currentCity }}
    </button>
  </div>
</template>

<script setup>
import { } from 'vue'

defineProps({
  currentCity: {
    type: String,
    default: 'Москва'
  }
})

defineEmits(['open-modal'])
</script>