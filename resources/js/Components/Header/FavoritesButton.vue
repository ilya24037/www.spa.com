<template>
  <Link 
    :href="route('favorites.index')"
    class="relative flex flex-col items-center px-3 py-2 rounded-lg hover:bg-gray-100 transition-colors group"
  >
    <div class="relative">
      <svg class="w-6 h-6 text-gray-600 group-hover:text-blue-600 transition-colors" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
          d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" 
        />
      </svg>
      <!-- Счётчик -->
      <span 
        v-if="count > 0"
        class="absolute -top-2 -right-2 w-5 h-5 bg-pink-500 text-white text-xs rounded-full flex items-center justify-center font-medium"
      >
        {{ count > 99 ? '99+' : count }}
      </span>
    </div>
    <span class="text-xs text-gray-600 mt-1">Избранное</span>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const count = computed(() => usePage().props.favoritesCount || 0)
</script>