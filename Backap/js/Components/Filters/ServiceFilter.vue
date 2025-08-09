<!-- resources/js/Components/Filters/ServiceFilter.vue -->
<template>
  <div>
    <h4 class="text-sm font-medium mb-2 text-gray-700">Услуги</h4>
    
    <div class="space-y-2">
      <label 
        v-for="service in options"
        :key="service.id"
        class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-1 rounded transition-colors"
      >
        <input
          type="checkbox"
          :value="service.id"
          :checked="modelValue.includes(service.id)"
          @change="toggleService(service.id)"
          class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500"
        />
        <span class="flex-1 text-sm text-gray-700">{{ service.name }}</span>
        <span class="text-xs text-gray-500">({{ service.count || 0 }})</span>
      </label>
    </div>
  </div>
</template>