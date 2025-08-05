<template>
  <div>
    <label class="block text-sm font-medium text-gray-700">Цена</label>
    <input type="number" class="mt-1 block w-full rounded-md border-gray-300" />
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors?: any }>()
</script>