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
      <div class="flex flex-col gap-3">
        <BaseCheckbox
          v-for="option in clientOptions"
          :key="option.value"
          :model-value="isSelected(option.value)"
          :label="option.label"
          :description="option.description"
          @update:model-value="() => toggleClient(option.value)"
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

// Читаем данные ТОЛЬКО из store (как на Avito)
const clients = computed(() => store.formData.clients || [])

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const isSelected = (value) => {
  return clients.value.includes(value)
}

const toggleClient = (value) => {
  const currentValues = [...clients.value]
  const index = currentValues.indexOf(value)
  
  if (index > -1) {
    // Убираем из массива
    currentValues.splice(index, 1)
  } else {
    // Добавляем в массив
    currentValues.push(value)
  }
  
  console.log('toggleClient called:', value, 'new array:', currentValues)
  store.updateField('clients', currentValues)
}
</script>

