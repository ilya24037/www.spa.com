<template>
  <div>
    <label class="block text-sm font-medium text-gray-500">Местоположение</label>
    <input type="text" placeholder="Адрес" class="mt-1 block w-full rounded-md border-gray-500">
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors?: any }>()
</script>