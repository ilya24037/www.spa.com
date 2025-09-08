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
          :error="showError && !hasSelection"
          @update:modelValue="emitWorkFormat"
        />
        <p class="work-format-description">
          {{ option.description }}
        </p>
      </div>
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