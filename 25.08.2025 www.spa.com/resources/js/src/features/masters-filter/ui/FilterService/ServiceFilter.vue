<!-- resources/js/Components/Filters/ServiceFilter.vue -->
<template>
  <div>
    <h4 class="text-sm font-medium mb-2 text-gray-500">
      Услуги
    </h4>
    
    <div class="space-y-2">
      <label 
        v-for="service in options"
        :key="service.id"
        class="flex items-center gap-2 cursor-pointer hover:bg-gray-500 p-1 rounded transition-colors"
      >
        <input
          type="checkbox"
          :value="service.id"
          :checked="modelValue.includes(service.id)"
          class="w-4 h-4 text-green-600 border-gray-500 rounded focus:ring-green-500"
          @change="toggleService(service.id)"
        >
        <span class="flex-1 text-sm text-gray-500">{{ service.name }}</span>
        <span class="text-xs text-gray-500">({{ service.count || 0 }})</span>
      </label>
    </div>
  </div>
</template>

<script setup lang="ts">
interface ServiceOption {
  id: number
  name: string
  count?: number
}

const props = defineProps<{
  options: ServiceOption[]
  modelValue: number[]
}>()

const emit = defineEmits<{
  'update:modelValue': [value: number[]]
}>()

const toggleService = (serviceId: number) => {
    const newValue = props.modelValue.includes(serviceId)
        ? props.modelValue.filter(id => id !== serviceId)
        : [...props.modelValue, serviceId]
  
    emit('update:modelValue', newValue)
}
</script>