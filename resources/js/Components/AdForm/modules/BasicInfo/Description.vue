<template>
  <FormSection
    title="О себе"
    hint="Расскажите о себе и своих услугах подробно"
    required
    :errors="errors"
    :error-keys="['description']"
  >
    <FormField
      label="Описание"
      hint="Минимум 50 символов. Расскажите о ваших услугах, опыте, подходе к работе"
      required
      :error="errors.description"
    >
      <BaseTextarea
        :model-value="description"
        @update:model-value="updateDescription"
        :rows="6"
        placeholder="Расскажите о себе, своих услугах, опыте работы..."
        :maxlength="2000"
        :show-counter="true"
        :min-length="50"
      />
    </FormField>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseTextarea from '@/Components/UI/BaseTextarea.vue'
import { useAdFormStore } from '../../stores/adFormStore'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const description = computed(() => store.formData.description || '')

// BaseTextarea имеет встроенный счетчик символов

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateDescription = (newValue) => {
  console.log('updateDescription called:', newValue)
  store.updateField('description', newValue)
}
</script>

