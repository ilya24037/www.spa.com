<template>
  <div class="adstatusfilter">
    <!-- AdStatusFilter component -->
    <div v-if="items && items.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="item in items" :key="item.id" class="bg-white rounded-lg shadow p-4">
        <slot name="item" :item="item">
          <p>{{ item.name || item.title || 'Item' }}</p>
        </slot>
      </div>
    </div>
    <div v-else class="text-center py-8 text-gray-500">
      Нет данных для отображения
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  items?: any[]
  loading?: boolean
}>()
</script>