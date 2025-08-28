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
        @update:modelValue="handleProviderChange"
      />
    </div>
    <div v-if="errors.service_provider" class="error-message">
      {{ errors.service_provider }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseRadio from '@/src/shared/ui/atoms/BaseRadio/BaseRadio.vue'

const props = defineProps({
  serviceProvider: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:serviceProvider'])

// Функция безопасного получения первого значения
const getFirstValue = (value) => {
  if (Array.isArray(value) && value.length > 0) {
    return value[0]
  }
  if (typeof value === 'string') {
    // Если пришла JSON строка, пробуем декодировать
    try {
      const parsed = JSON.parse(value)
      return Array.isArray(parsed) && parsed.length > 0 ? parsed[0] : 'women'
    } catch {
      return 'women'
    }
  }
  return 'women'
}

// Для радиокнопок используем строку вместо массива
const selectedProvider = ref(getFirstValue(props.serviceProvider))

watch(() => props.serviceProvider, (val) => {
  selectedProvider.value = getFirstValue(val)
})

// Опции для радиокнопок
const providerOptions = computed(() => [
  { value: 'women', label: 'Женщина' },
  { value: 'men', label: 'Мужчина' },
  { value: 'couple', label: 'Пара' }
])

const handleProviderChange = (value) => {
  selectedProvider.value = value
  // Отправляем массив с одним элементом для совместимости
  emit('update:serviceProvider', [value])
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