<template>
  <div class="p-6">
    <h3 class="text-lg font-medium mb-4">Финансы</h3>
    <div v-if="!loading" class="space-y-6">
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-green-50 p-4 rounded-lg">
          <div class="text-2xl font-bold text-green-600">{{ stats.income || 0 }} ₽</div>
          <div class="text-sm text-green-600">Доход</div>
        </div>
        <div class="bg-blue-50 p-4 rounded-lg">
          <div class="text-2xl font-bold text-blue-600">{{ stats.bookings || 0 }}</div>
          <div class="text-sm text-blue-600">Записей</div>
        </div>
        <div class="bg-purple-50 p-4 rounded-lg">
          <div class="text-2xl font-bold text-purple-600">{{ stats.avgPrice || 0 }} ₽</div>
          <div class="text-sm text-purple-600">Средний чек</div>
        </div>
      </div>
    </div>
    <div v-else class="text-center py-12">Загрузка...</div>
  </div>
</template>

<script setup>
defineProps({
  stats: { type: Object, default: () => ({}) },
  transactions: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})
</script>