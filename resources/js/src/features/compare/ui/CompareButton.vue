<template>
  <Link 
    href="/compare" 
    class="flex items-center gap-1 text-gray-700 hover:text-gray-900 transition"
    :class="{ 'pointer-events-none opacity-50': loading }"
  >
    <!-- Иконка сравнения -->
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path 
        stroke-linecap="round" 
        stroke-linejoin="round" 
        stroke-width="2" 
        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
      />
    </svg>
    
    <!-- Счётчик -->
    <span 
      v-if="compareCount > 0" 
      class="bg-blue-500 text-white text-xs px-1.5 py-0.5 rounded-full min-w-[20px] text-center"
    >
      {{ compareCount > 99 ? '99+' : compareCount }}
    </span>
    
    <!-- Текст кнопки -->
    <span class="text-sm hidden sm:inline">Сравнить</span>
  </Link>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { useCompareStore } from '../model/compareStore'

// Store
const compareStore = useCompareStore()

// Computed
const compareCount = computed(() => compareStore.items.length)
const loading = computed(() => compareStore.loading)

// Загружаем данные сравнения при монтировании
compareStore.loadCompareItems()
</script>