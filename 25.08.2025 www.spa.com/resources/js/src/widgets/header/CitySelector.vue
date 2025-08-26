<!-- resources/js/src/widgets/header/CitySelector.vue -->
<template>
  <div class="city-selector relative">
    <button class="flex items-center space-x-1 text-sm text-gray-500 hover:text-blue-600" @click="showCityModal = true">
      <svg
        class="w-4 h-4"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
        />
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
        />
      </svg>
      <span>{{ currentCity }}</span>
      <svg
        class="w-3 h-3"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const showCityModal = ref(false)
const currentCity = ref('Москва')
</script> 