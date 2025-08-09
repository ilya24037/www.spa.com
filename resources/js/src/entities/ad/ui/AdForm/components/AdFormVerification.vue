<template>
  <div>
    <label class="block text-sm font-medium text-gray-500">Верификация</label>
    <div class="mt-2 space-y-2">
      <label class="flex items-center">
        <input type="checkbox" class="mr-2">
        <span>Подтвердить фото</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors?: any }>()
</script>