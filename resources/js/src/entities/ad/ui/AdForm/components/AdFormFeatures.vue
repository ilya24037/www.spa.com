<template>
  <div class="space-y-4">
    <h4 class="text-lg font-medium text-gray-800">Особенности</h4>
    <div class="space-y-2">
      <label class="flex items-center">
        <input type="checkbox" class="rounded border-gray-300 text-blue-600 mr-2" />
        <span class="text-sm text-gray-700">Выезд на дом</span>
      </label>
      <label class="flex items-center">
        <input type="checkbox" class="rounded border-gray-300 text-blue-600 mr-2" />
        <span class="text-sm text-gray-700">Круглосуточно</span>
      </label>
      <label class="flex items-center">
        <input type="checkbox" class="rounded border-gray-300 text-blue-600 mr-2" />
        <span class="text-sm text-gray-700">Пары</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  errors?: any
}>()
</script>