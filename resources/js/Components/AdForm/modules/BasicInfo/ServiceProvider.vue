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
      <div class="flex flex-col gap-3">
        <BaseCheckbox
          v-for="option in providerOptions"
          :key="option.value"
          :model-value="isSelected(option.value)"
          :label="option.label"
          :description="option.description"
          @update:model-value="() => toggleProvider(option.value)"
        />
      </div>
    </FormField>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseCheckbox from '@/Components/UI/BaseCheckbox.vue'
import { useAdFormStore } from '../../stores/adFormStore'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
})

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
  },
  {
    value: 'couple',
    label: 'Пара',
    description: 'Услуги оказывает пара (мужчина + женщина)'
  },
  {
    value: 'team',
    label: 'Команда',
    description: 'Услуги оказывает команда специалистов'
  }
]

// Читаем данные ТОЛЬКО из store (как на Avito)
const serviceProvider = computed(() => store.formData.service_provider || [])

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const isSelected = (value) => {
  return serviceProvider.value.includes(value)
}

const toggleProvider = (value) => {
  const currentValues = [...serviceProvider.value]
  const index = currentValues.indexOf(value)
  
  if (index > -1) {
    // Убираем из массива
    currentValues.splice(index, 1)
  } else {
    // Добавляем в массив
    currentValues.push(value)
  }
  
  console.log('toggleProvider called:', value, 'new array:', currentValues)
  store.updateField('service_provider', currentValues)
}
</script>

