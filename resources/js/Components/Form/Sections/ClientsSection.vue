<template>
  <div class="clients-section">
    <h2 class="form-group-title">Ваши клиенты</h2>
    <CheckboxGroup 
      v-model="localClients"
      :options="clientOptions"
      @update:modelValue="emitClients"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

const props = defineProps({
  clients: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:clients'])

const localClients = ref([...props.clients])

watch(() => props.clients, (val) => {
  localClients.value = [...val]
})

// Опции для CheckboxGroup
const clientOptions = computed(() => [
  { value: 'Женщины', label: 'Женщины' },
  { value: 'Мужчины', label: 'Мужчины' }
])

const emitClients = () => {
  emit('update:clients', [...localClients.value])
}
</script>

<style scoped>
.clients-section {
  margin-bottom: 24px;
}

.form-group-title {
  font-size: 20px;
  font-weight: 500;
  color: #000000;
  margin: 0 0 20px 0;
  line-height: 1.3;
}
</style> 