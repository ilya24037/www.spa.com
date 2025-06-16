<!-- resources/js/Components/Common/Breadcrumbs.vue -->
<template>
  <nav class="text-xs text-gray-500 my-2">
    <ol class="flex items-center space-x-2">
      <li v-for="(item, index) in items" :key="index" class="flex items-center">
        <Link 
          v-if="index < items.length - 1"
          :href="item.href"
          class="hover:text-gray-700 transition-colors"
        >
          {{ item.title }}
        </Link>
        <span v-else class="text-gray-700 font-medium">
          {{ item.title }}
        </span>
        
        <svg 
          v-if="index < items.length - 1"
          class="w-3 h-3 mx-2 text-gray-400"
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  items: {
    type: Array,
    required: true
  }
})
</script>