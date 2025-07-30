<template>
  <FormSection
    title="Ваши клиенты"
    hint="Выберите кто может воспользоваться вашими услугами"
    required
    :errors="errors"
    :error-keys="['clients']"
  >
    <FormField
      label="Тип клиентов"
      required
      :error="errors.clients"
    >
      <div class="checkbox-group">
        <div
          v-for="option in clientOptions"
          :key="option.value"
          class="checkbox-item"
          @click="toggleClient(option.value)"
        >
          <input
            type="checkbox"
            :checked="isSelected(option.value)"
            @click.stop
            @change="toggleClient(option.value)"
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
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  clients: {
    type: Array,
    default: () => []
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:clients'])

// Опции клиентов
const clientOptions = [
  {
    value: 'women',
    label: 'Женщины',
    description: 'Работаю с женщинами'
  },
  {
    value: 'men',
    label: 'Мужчины',
    description: 'Работаю с мужчинами'
  },
  {
    value: 'couples',
    label: 'Пары',
    description: 'Работаю с парами'
  },
  {
    value: 'groups',
    label: 'Группы',
    description: 'Работаю с группами'
  }
]

// Локальное состояние
const localClients = ref([...props.clients])

// Отслеживаем изменения пропсов
watch(() => props.clients, (newValue) => {
  localClients.value = [...newValue]
}, { deep: true })

// Методы
const isSelected = (value) => {
  return localClients.value.includes(value)
}

const toggleClient = (value) => {
  const index = localClients.value.indexOf(value)
  
  if (index > -1) {
    // Убираем из массива
    localClients.value.splice(index, 1)
  } else {
    // Добавляем в массив
    localClients.value.push(value)
  }
  
  emit('update:clients', [...localClients.value])
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