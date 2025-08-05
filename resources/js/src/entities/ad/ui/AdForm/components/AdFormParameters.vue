<template>
  <div class="space-y-4">
    <h4 class="text-lg font-medium text-gray-800">Параметры</h4>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium text-gray-700">Возраст</label>
        <input type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Рост</label>
        <input type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Вес</label>
        <input type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700">Размер груди</label>
        <input type="number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  errors?: any
}>()
</script>