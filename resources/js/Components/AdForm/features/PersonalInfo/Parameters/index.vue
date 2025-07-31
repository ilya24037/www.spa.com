<template>
  <FormSection
    title="Физические параметры"
    hint="Укажите ваши физические параметры для лучшего поиска"
    :errors="errors"
    :error-keys="['age', 'height', 'weight', 'breast_size', 'hair_color', 'eye_color', 'appearance', 'nationality']"
  >
    <div class="space-y-6">
      <!-- Основные измерения -->
      <PhysicalMeasurements
        :age="age"
        :height="height"  
        :weight="weight"
        :errors="errors"
        @update:age="(value) => updateField('age', value)"
        @update:height="(value) => updateField('height', value)"
        @update:weight="(value) => updateField('weight', value)"
      />

      <!-- Особенности тела -->
      <BodyFeatures
        :hair-color="hairColor"
        :eye-color="eyeColor"
        :breast-size="breastSize"
        :errors="errors"
        @update:hairColor="(value) => updateField('hair_color', value)"
        @update:eyeColor="(value) => updateField('eye_color', value)"
        @update:breastSize="(value) => updateField('breast_size', value)"
      />

      <!-- Внешность и национальность -->
      <AppearanceSelects
        :appearance="appearance"
        :nationality="nationality"
        :errors="errors"
        @update:appearance="(value) => updateField('appearance', value)"
        @update:nationality="(value) => updateField('nationality', value)"
      />
    </div>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Микрокомпоненты
import PhysicalMeasurements from './components/PhysicalMeasurements.vue'
import BodyFeatures from './components/BodyFeatures.vue'
import AppearanceSelects from './components/AppearanceSelects.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: { type: Object, default: () => ({}) }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const age = computed(() => store.formData.age || '')
const height = computed(() => store.formData.height || '')
const weight = computed(() => store.formData.weight || '')
const breastSize = computed(() => store.formData.breast_size || '')
const hairColor = computed(() => store.formData.hair_color || '')
const eyeColor = computed(() => store.formData.eye_color || '')
const appearance = computed(() => store.formData.appearance || '')
const nationality = computed(() => store.formData.nationality || '')

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateField = (field, value) => {
  console.log('updateField called:', field, value)
  store.updateField(field, value)
}
</script>