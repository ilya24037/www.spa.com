<!-- Заглушки для остальных компонентов -->
<template>
  <div class="p-6">
    <h3 class="text-lg font-medium mb-4">Избранные мастера</h3>
    <div v-if="!loading && favorites.length" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="favorite in favorites" :key="favorite.id" class="border rounded-lg p-4">
        <h4 class="font-medium">{{ favorite.name }}</h4>
        <p class="text-sm text-gray-600">{{ favorite.specialty }}</p>
        <div class="flex gap-2 mt-3">
          <button @click="$emit('go-to', favorite)" class="px-3 py-1 bg-blue-600 text-white text-sm rounded">
            Перейти
          </button>
          <button @click="$emit('remove', favorite)" class="px-3 py-1 border text-sm rounded">
            Удалить
          </button>
        </div>
      </div>
    </div>
    <div v-else-if="loading" class="text-center py-12">Загрузка...</div>
    <div v-else class="text-center py-12 text-gray-500">У вас нет избранных мастеров</div>
  </div>
</template>

<script setup>
defineProps({
  favorites: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false }
})
defineEmits(['remove', 'go-to'])
</script>