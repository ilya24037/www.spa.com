<template>
  <div class="profilesidebar">
    <slot />
  </div>
</template>

<script setup lang="ts">
// ProfileSidebar stub component
defineProps<{
  [key: string]: any
}>()
</script>

<style scoped>
.profilesidebar {
  width: 100%;
}
</style>