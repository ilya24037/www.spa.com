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

// Логирование убрано - проблема решена

// Состояние для валидации
const touched = ref(false)

// Проверка наличия выбора
const hasSelection = computed(() => !!localWorkFormat.value)

// Показывать ошибку если поле тронуто, есть ошибка от родителя или принудительная валидация
const showError = computed(() => touched.value || !!props.errors?.work_format || props.forceValidation)

// Опции для радио-кнопок (согласно backend enum WorkFormat.php)
const workFormatOptions = computed(() => [
  {
    value: 'individual',
    label: 'Частный мастер',
    description: 'Работаете в одиночку'
  },
  {
    value: 'salon',  // Исправлено: salon для салона
    label: 'Салон',
    description: 'У вас есть отдельное помещение и штат мастеров'
  },
  {
    value: 'duo',    // duo для работы в паре
    label: 'В паре',
    description: 'Работаете вдвоем с другим специалистом'
  }
])

watch(() => props.workFormat, val => {
  localWorkFormat.value = val
})

const emitWorkFormat = (value) => {
  touched.value = true // Помечаем что поле тронуто
  localWorkFormat.value = value // Обновляем локальное значение
  emit('update:workFormat', value) // Эмитим новое значение

  // Сбрасываем флаг принудительной валидации если выбрано значение
  if (props.forceValidation && value) {
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