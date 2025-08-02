<template>
  <div class="p-6">
    <h3 class="text-lg font-medium mb-4">Настройки</h3>
    <div v-if="!loading" class="space-y-6">
      <div>
        <label class="flex items-center gap-2">
          <input type="checkbox" :checked="settings.emailNotifications" class="rounded">
          <span>Email уведомления</span>
        </label>
      </div>
      <div>
        <label class="flex items-center gap-2">
          <input type="checkbox" :checked="settings.smsNotifications" class="rounded">
          <span>SMS уведомления</span>
        </label>
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
  settings: { type: Object, default: () => ({}) },
  loading: { type: Boolean, default: false }
})
defineEmits(['update'])
</script>