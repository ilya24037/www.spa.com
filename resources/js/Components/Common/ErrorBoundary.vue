<template>
  <div class="error-boundary">
    <slot />
  </div>
</template>

<script setup lang="ts">
// Error boundary component stub
defineProps<{
  fallback?: any
}>()
</script>