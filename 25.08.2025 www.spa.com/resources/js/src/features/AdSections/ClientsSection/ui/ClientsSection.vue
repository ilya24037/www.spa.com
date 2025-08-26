<template>
  <div class="clients-section">
    <div class="checkbox-group">
      <BaseCheckbox
        v-for="option in clientOptions"
        :key="option.value"
        :model-value="localClients.includes(option.value)"
        :label="option.label"
        @update:modelValue="toggleClient(option.value, $event)"
      />
    </div>
    <div v-if="errors.clients" class="error-message">{{ errors.clients }}</div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'

const props = defineProps({
  clients: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:clients'])
const localClients = ref([...props.clients])

// Опции для чекбоксов
const clientOptions = computed(() => [
  { value: 'men', label: 'Мужчины' },
  { value: 'women', label: 'Женщины' },
  { value: 'couples', label: 'Пары' }
])

watch(() => props.clients, (val) => { 
  localClients.value = [...val] 
})

const toggleClient = (value, checked) => {
  if (checked) {
    if (!localClients.value.includes(value)) {
      localClients.value.push(value)
    }
  } else {
    const index = localClients.value.indexOf(value)
    if (index > -1) {
      localClients.value.splice(index, 1)
    }
  }
  emit('update:clients', localClients.value)
}
</script>

<style scoped>
.clients-section {
  /* Убираем лишние стили, теперь это подсекция */
}

.checkbox-group { 
  display: flex; 
  flex-direction: column; 
  gap: 12px; 
}

.error-message { 
  color: #dc3545; 
  font-size: 0.875rem; 
  margin-top: 0.5rem; 
}
</style>