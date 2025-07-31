<template>
  <FormSection
    title="Формат работы"
    hint="Выберите как вы предпочитаете работать"
    required
    :errors="errors"
    :error-keys="['work_format']"
  >
    <div class="flex flex-col gap-4">
      <FormField
        label="Формат работы"
        required
        :error="errors.work_format"
      >
        <div class="flex flex-col gap-3">
          <BaseRadio
            :model-value="workFormat"
            value="individual"
            label="Индивидуально"
            description="Работаю только одна"
            @update:model-value="selectFormat"
          />
          
          <BaseRadio
            :model-value="workFormat"
            value="salon"
            label="Салон"
            description="Работаю в команде"
            @update:model-value="selectFormat"
          />
        </div>
      </FormField>

      <!-- Подруга (только для индивидуального формата) -->
      <FormField
        v-if="workFormat === 'individual'"
        label="Есть подруга?"
        hint="Можете ли предложить услуги с подругой"
      >
        <BaseCheckbox
          :model-value="hasGirlfriend"
          label="Есть подруга"
          @update:model-value="toggleGirlfriend"
        />
      </FormField>
    </div>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseCheckbox from '@/Components/UI/BaseCheckbox.vue'
import BaseRadio from '@/Components/UI/BaseRadio.vue'
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
const workFormat = computed(() => store.formData.work_format)
const hasGirlfriend = computed(() => store.formData.has_girlfriend)

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const selectFormat = (format) => {
  console.log('selectFormat called:', format)
  store.updateField('work_format', format)

  // Если выбрали салон, сбрасываем подругу
  if (format === 'salon') {
    store.updateField('has_girlfriend', false)
  }
}

const toggleGirlfriend = (newValue) => {
  console.log('toggleGirlfriend called:', newValue)
  store.updateField('has_girlfriend', newValue)
}
</script>

