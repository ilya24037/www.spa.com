<template>
  <div>
    <label class="block text-sm font-medium text-gray-700">Способы оплаты</label>
    <div class="space-y-2 mt-2">
      <label class="flex items-center">
        <input type="checkbox" class="mr-2" />
        <span>Наличные</span>
      </label>
      <label class="flex items-center">
        <input type="checkbox" class="mr-2" />
        <span>Карта</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ errors?: any }>()
</script>