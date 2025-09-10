<template>
  <div class="work-format-section">
    <div class="work-format-fields">
      <BaseRadio
        v-for="option in workFormatOptions"
        :key="option.value"
        v-model="localWorkFormat"
        name="work_format"
        :value="option.value"
        :label="option.label"
        :description="option.description"
        :error="showError && !hasSelection"
        @update:modelValue="emitWorkFormat"
      />
    </div>
    <div v-if="showError && !hasSelection" class="text-sm text-red-600 mt-2">
      Выберите формат работы
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'

const props = defineProps({
  workFormat: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) },
  forceValidation: { type: Boolean, default: false }
})

const emit = defineEmits(['update:workFormat', 'clearForceValidation'])
const localWorkFormat = ref(props.workFormat)

// Состояние для валидации
const touched = ref(false)

// Проверка наличия выбора
const hasSelection = computed(() => !!localWorkFormat.value)

// Показывать ошибку если поле тронуто, есть ошибка от родителя или принудительная валидация
const showError = computed(() => touched.value || !!props.errors?.work_format || props.forceValidation)

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
  touched.value = true // Помечаем что поле тронуто
  emit('update:workFormat', localWorkFormat.value)
  
  // Сбрасываем флаг принудительной валидации если выбрано значение
  if (props.forceValidation && localWorkFormat.value) {
    emit('clearForceValidation')
  }
}
</script>

<style scoped>
.work-format-section {
  /* Убираем лишние стили, теперь это подсекция */
}

.work-format-fields { 
  display: flex; 
  flex-direction: column;
  gap: 8px; 
}

.error-message {
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}
</style>