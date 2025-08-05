<template>
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
      География услуг
    </label>
    <div class="space-y-3">
      <div>
        <label class="block text-sm text-gray-600 mb-1">Город</label>
        <input 
          type="text" 
          placeholder="Введите город"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
      </div>
      <div>
        <label class="block text-sm text-gray-600 mb-1">Районы (необязательно)</label>
        <textarea 
          placeholder="Укажите районы через запятую"
          rows="2"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        ></textarea>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{ 
  errors?: any 
}>()
</script>