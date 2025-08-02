<!-- resources/js/src/shared/ui/molecules/Breadcrumbs/Breadcrumbs.vue -->
<template>
  <nav :class="CONTAINER_CLASSES">
    <ol :class="LIST_CLASSES">
      <li v-for="(item, index) in items" :key="index" :class="ITEM_CLASSES">
        <a 
          v-if="index < items.length - 1"
          :href="item.href"
          :class="LINK_CLASSES"
        >
          {{ item.title }}
        </a>
        <span v-else :class="CURRENT_CLASSES">
          {{ item.title }}
        </span>
        
        <svg 
          v-if="index < items.length - 1"
          :class="SEPARATOR_CLASSES"
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
// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'text-xs text-gray-500 my-2'
const LIST_CLASSES = 'flex items-center space-x-2'
const ITEM_CLASSES = 'flex items-center'
const LINK_CLASSES = 'hover:text-gray-700 transition-colors'
const CURRENT_CLASSES = 'text-gray-700 font-medium'
const SEPARATOR_CLASSES = 'w-3 h-3 mx-2 text-gray-400'

defineProps({
  items: {
    type: Array,
    required: true
  }
})
</script>