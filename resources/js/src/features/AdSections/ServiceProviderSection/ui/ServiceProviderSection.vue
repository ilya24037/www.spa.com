<template>
  <div class="service-provider-section">
    <div class="radio-group">
      <BaseRadio
        v-for="option in providerOptions"
        :key="option.value"
        v-model="selectedProvider"
        :value="option.value"
        :label="option.label"
        name="service_provider"
        :error="showError && !hasSelection"
        @update:modelValue="handleProviderChange"
      />
    </div>
    <div v-if="showError && !hasSelection" class="text-sm text-red-600 mt-2">
      Выберите, кто будет оказывать услуги
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'

const props = defineProps({
  serviceProvider: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) },
  forceValidation: { type: Boolean, default: false }
})

const emit = defineEmits(['update:serviceProvider', 'clearForceValidation'])

// Для радиокнопок используем строку вместо массива
const selectedProvider = ref(props.serviceProvider[0] || '')

// Состояние для валидации
const touched = ref(false)

// Проверка наличия выбора
const hasSelection = computed(() => !!selectedProvider.value)

// Показывать ошибку если поле тронуто, есть ошибка от родителя или принудительная валидация
const showError = computed(() => touched.value || !!props.errors?.service_provider || props.forceValidation)

watch(() => props.serviceProvider, (val) => {
  selectedProvider.value = val[0] || ''
})

// Опции для радиокнопок
const providerOptions = computed(() => [
  { value: 'women', label: 'Женщина' },
  { value: 'men', label: 'Мужчина' },
  { value: 'couple', label: 'Пара' }
])

const handleProviderChange = (value) => {
  touched.value = true // Помечаем что поле тронуто
  selectedProvider.value = value
  // Отправляем массив с одним элементом для совместимости
  emit('update:serviceProvider', [value])
  
  // Сбрасываем флаг принудительной валидации если выбрано значение
  if (props.forceValidation && value) {
    emit('clearForceValidation')
  }
}
</script>

<style scoped>
.service-provider-section {
  /* Убираем лишние стили, теперь это подсекция */
}

.radio-group {
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