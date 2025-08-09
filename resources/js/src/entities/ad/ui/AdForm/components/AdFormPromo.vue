<template>
  <div>
    <label class="block text-sm font-medium text-gray-500">Промо</label>
    <textarea class="mt-1 block w-full rounded-md border-gray-500" rows="3" />
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors?: any }>()
</script>