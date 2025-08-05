<template>
  <div>
    <label class="block text-sm font-medium text-gray-700">Продвижение</label>
    <select class="mt-1 block w-full rounded-md border-gray-300">
      <option>Без продвижения</option>
      <option>VIP</option>
      <option>Premium</option>
    </select>
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors?: any }>()
</script>