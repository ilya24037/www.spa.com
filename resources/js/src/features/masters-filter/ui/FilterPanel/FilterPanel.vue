<template>
  <div class="filter-panel bg-white rounded-lg shadow-sm p-4">
    <h3 class="text-lg font-semibold mb-4">Фильтры</h3>
    
    <!-- Filters -->
    <div class="space-y-4">
      <slot />
    </div>
    
    <!-- Actions -->
    <div class="mt-6 flex gap-2">
      <button
        @click="$emit('apply')"
        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
      >
        Применить
      </button>
      <button
        @click="$emit('reset')"
        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50"
      >
        Сбросить
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineEmits<{
  apply: []
  reset: []
}>()
</script>