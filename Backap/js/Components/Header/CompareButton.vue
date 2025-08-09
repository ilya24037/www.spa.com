<!-- resources/js/Components/Header/CompareButton.vue -->
<template>
  <Link href="/compare" class="flex items-center gap-1 text-gray-700 hover:text-gray-900 transition">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>
    <span v-if="count > 0" class="text-red-500 text-xs font-bold">
      {{ count }}
    </span>
    <span class="text-sm">Сравнить</span>
  </Link>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const count = computed(() => usePage().props.compareCount || 0)
</script>