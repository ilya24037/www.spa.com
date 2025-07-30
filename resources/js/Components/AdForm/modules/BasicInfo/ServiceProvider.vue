<template>
  <FormSection
    title="Кто оказывает услуги"
    hint="Выберите кто будет оказывать услуги"
    required
    :errors="errors"
    :error-keys="['service_provider']"
  >
    <FormField
      label="Тип исполнителя"
      required
      :error="errors.service_provider"
    >
      <div class="checkbox-group">
        <div
          v-for="option in providerOptions"
          :key="option.value"
          class="checkbox-item"
          @click="toggleProvider(option.value)"
        >
          <input
            type="checkbox"
            :checked="isSelected(option.value)"
            @click.stop
            @change="toggleProvider(option.value)"
          />
          <div class="checkbox-content">
            <div class="checkbox-title">{{ option.label }}</div>
            <div v-if="option.description" class="checkbox-description">
              {{ option.description }}
            </div>
          </div>
        </div>
      </div>
    </FormField>
  </FormSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  serviceProvider: {
    type: Array,
    default: () => []
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:serviceProvider'])

// Опции провайдеров
const providerOptions = [
  {
    value: 'woman',
    label: 'Женщина',
    description: 'Услуги оказывает женщина'
  },
  {
    value: 'man',
    label: 'Мужчина',
    description: 'Услуги оказывает мужчина'
  }
]

// Локальное состояние
const localServiceProvider = ref([...props.serviceProvider])

// Отслеживаем изменения пропсов
watch(() => props.serviceProvider, (newValue) => {
  localServiceProvider.value = [...newValue]
}, { deep: true })

// Методы
const isSelected = (value) => {
  return localServiceProvider.value.includes(value)
}

const toggleProvider = (value) => {
  const index = localServiceProvider.value.indexOf(value)
  
  if (index > -1) {
    // Убираем из массива
    localServiceProvider.value.splice(index, 1)
  } else {
    // Добавляем в массив
    localServiceProvider.value.push(value)
  }
  
  emit('update:serviceProvider', [...localServiceProvider.value])
}
</script>

<style scoped>
.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.checkbox-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  cursor: pointer;
  padding: 12px;
  border: 1px solid #e5e5e5;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.checkbox-item:hover {
  border-color: #1890ff;
  background: #f0f8ff;
}

.checkbox-item input[type="checkbox"] {
  width: 18px;
  height: 18px;
  margin: 0;
  margin-top: 2px;
  flex-shrink: 0;
}

.checkbox-content {
  flex: 1;
}

.checkbox-title {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
  line-height: 1.4;
}

.checkbox-description {
  font-size: 14px;
  color: #8c8c8c;
  line-height: 1.4;
  margin-top: 2px;
}
</style>