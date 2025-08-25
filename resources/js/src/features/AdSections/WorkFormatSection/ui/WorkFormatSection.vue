<template>
  <div class="work-format-section">
    <div class="work-format-fields">
      <div
        v-for="option in workFormatOptions"
        :key="option.value"
        class="work-format-option"
      >
        <BaseRadio
          v-model="localWorkFormat"
          name="work_format"
          :value="option.value"
          :label="option.label"
          @update:modelValue="emitWorkFormat"
        />
        <p class="work-format-description">
          {{ option.description }}
        </p>
      </div>
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

// Опции для радио-кнопок (согласно скриншоту и backend валидации)
const workFormatOptions = computed(() => [
  { 
    value: 'individual', 
    label: 'Частный мастер',
    description: 'Работаете в одиночку'
  },
  { 
    value: 'duo', 
    label: 'Салон',
    description: 'У вас есть отдельное помещение и штат мастеров'
  },
  { 
    value: 'group', 
    label: 'Сеть салонов',
    description: 'У вас несколько филиалов с одним названием и концепцией'
  }
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
  /* Убираем лишние стили, теперь это подсекция */
}

.work-format-fields { 
  display: flex; 
  flex-direction: column;
  gap: 16px; 
}

.work-format-option {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.work-format-description {
  color: #6b7280;
  font-size: 0.875rem;
  line-height: 1.4;
  margin: 0;
  margin-left: 24px; /* Выравнивание с текстом радио-кнопки */
}

.error-message {
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}
</style>