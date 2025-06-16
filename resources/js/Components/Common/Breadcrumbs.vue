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
        
        <ChevronRightIcon 
          v-if="index < items.length - 1"
          class="w-3 h-3 mx-2 text-gray-400"
        />
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { ChevronRightIcon } from '@heroicons/vue/24/solid'

defineProps({
  items: {
    type: Array,
    required: true
  }
})
</script>