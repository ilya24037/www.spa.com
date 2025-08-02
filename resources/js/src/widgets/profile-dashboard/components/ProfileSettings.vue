<template>
  <div class="p-6">
    <h3 class="text-lg font-medium mb-4">Настройки профиля</h3>
    <div v-if="!loading" class="space-y-6">
      <div>
        <label class="block text-sm font-medium mb-2">Имя</label>
        <input type="text" :value="user.name" class="w-full px-3 py-2 border rounded-lg">
      </div>
      <div>
        <label class="block text-sm font-medium mb-2">Email</label>
        <input type="email" :value="user.email" class="w-full px-3 py-2 border rounded-lg">
      </div>
      <button @click="$emit('update', {})" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
        Сохранить
      </button>
    </div>
    <div v-else class="text-center py-12">Загрузка...</div>
  </div>
</template>

<script setup>
defineProps({
  user: { type: Object, required: true },
  loading: { type: Boolean, default: false }
})
defineEmits(['update'])
</script>