<template>
  <div class="contentcard">
    <slot />
  </div>
</template>

<script setup lang="ts">
// ContentCard stub component
defineProps<{
  [key: string]: any
}>()
</script>

<style scoped>
.contentcard {
  width: 100%;
}
</style>