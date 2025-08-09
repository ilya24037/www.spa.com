<template>
  <div class="space-y-4">
    <h4 class="text-lg font-medium text-gray-500">
      Особенности
    </h4>
    <div class="space-y-2">
      <label class="flex items-center">
        <input type="checkbox" class="rounded border-gray-500 text-blue-600 mr-2">
        <span class="text-sm text-gray-500">Выезд на дом</span>
      </label>
      <label class="flex items-center">
        <input type="checkbox" class="rounded border-gray-500 text-blue-600 mr-2">
        <span class="text-sm text-gray-500">Круглосуточно</span>
      </label>
      <label class="flex items-center">
        <input type="checkbox" class="rounded border-gray-500 text-blue-600 mr-2">
        <span class="text-sm text-gray-500">Пары</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  errors?: any
}>()
</script>