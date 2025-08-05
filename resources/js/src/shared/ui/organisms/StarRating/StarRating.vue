<template>
  <div class="flex items-center">
    <div class="flex">
      <svg 
        v-for="i in 5" 
        :key="i"
        class="w-5 h-5"
        :class="i <= Math.floor(rating) ? 'text-yellow-400' : 'text-gray-300'"
        fill="currentColor"
        viewBox="0 0 20 20"
      >
        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
      </svg>
    </div>
    <span v-if="showText" class="ml-2 text-sm text-gray-600">
      {{ rating.toFixed(1) }}
    </span>
  </div>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  rating?: number
  showText?: boolean
}>(), {
  rating: 0,
  showText: true
})
</script>