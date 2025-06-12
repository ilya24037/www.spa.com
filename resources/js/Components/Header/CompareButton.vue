<template>
  <Link 
    :href="route('compare.index')"
    class="relative flex flex-col items-center px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors group"
  >
    <div class="relative">
      <svg class="w-6 h-6 text-gray-600 group-hover:text-blue-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" 
        />
      </svg>
      <!-- Счётчик -->
      <span 
        v-if="count > 0"
        class="absolute -top-2 -right-2 w-5 h-5 bg-pink-500 text-white text-xs rounded-full flex items-center justify-center font-medium"
      >
        {{ count }}
      </span>
    </div>
    <span class="text-xs text-gray-600 mt-1">Сравнить</span>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const count = computed(() => usePage().props.compareCount || 0)
</script>