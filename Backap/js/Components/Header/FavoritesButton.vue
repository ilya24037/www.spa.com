<!-- resources/js/Components/Header/FavoritesButton.vue -->
<template>
  <Link href="/favorites" class="flex items-center gap-1 text-gray-700 hover:text-gray-900 transition">
    <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
      <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
    </svg>
    <span v-if="count > 0" class="bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">
      {{ count }}
    </span>
    <span class="text-sm">Избранное</span>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const count = computed(() => usePage().props.favoritesCount || 0)
</script>