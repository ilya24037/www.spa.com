<template>
  <div class="work-format-section">
    <h2 class="form-group-title">Формат работы</h2>
    <div class="work-format-fields">
      <BaseRadio
        v-for="option in workFormatOptions"
        :key="option.value"
        v-model="localWorkFormat"
        name="work_format"
        :value="option.value"
        :label="option.label"
        @update:modelValue="emitWorkFormat"
      />
    </div>
    <div v-if="errors.work_format" class="error-message">
      {{ errors.work_format }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'

const props = defineProps({
  workFormat: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:workFormat'])
const localWorkFormat = ref(props.workFormat)

// Опции для радио-кнопок
const workFormatOptions = computed(() => [
  { value: 'individual', label: 'Индивидуально' },
  { value: 'salon', label: 'Салон' }
])

watch(() => props.workFormat, val => { 
  localWorkFormat.value = val 
})

const emitWorkFormat = () => {
  emit('update:workFormat', localWorkFormat.value)
}
</script>

<style scoped>
.work-format-section { 
  background: white; 
  border-radius: 8px; 
  padding: 20px; 
}

.form-group-title { 
  font-size: 18px; 
  font-weight: 600; 
  color: #333; 
  margin-bottom: 16px; 
}

.work-format-fields { 
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