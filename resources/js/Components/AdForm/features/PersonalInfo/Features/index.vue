<template>
  <FormSection
    title="Особенности мастера"
    hint="Расскажите о ваших особенностях, опыте и образовании"
    :errors="errors"
    :error-keys="['features', 'additional_features', 'experience', 'education_level']"
  >
    <div class="space-y-6">
      <!-- Особенности -->
      <FeaturesCheckboxes
        :model-value="features"
        @update:model-value="updateFeatures"
        :error="errors.features"
      />

      <!-- Дополнительные особенности -->
      <AdditionalFeaturesInput
        :model-value="additionalFeatures"
        @update:model-value="updateAdditionalFeatures"
        :error="errors.additional_features"
      />

      <!-- Опыт и образование в сетке -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Опыт работы -->
        <ExperienceSelect
          :model-value="experience"
          @update:model-value="updateExperience"
          :error="errors.experience"
        />

        <!-- Уровень образования -->
        <EducationSelect
          :model-value="educationLevel"
          @update:model-value="updateEducationLevel"
          :error="errors.education_level"
        />
      </div>
    </div>
  </FormSection>
</template>

<script setup>
import { computed } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Микрокомпоненты
import FeaturesCheckboxes from './components/FeaturesCheckboxes.vue'
import AdditionalFeaturesInput from './components/AdditionalFeaturesInput.vue'
import ExperienceSelect from './components/ExperienceSelect.vue'
import EducationSelect from './components/EducationSelect.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: { type: Object, default: () => ({}) }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const features = computed(() => store.formData.features || {})
const additionalFeatures = computed(() => store.formData.additional_features || '')
const experience = computed(() => store.formData.experience || '')
const educationLevel = computed(() => store.formData.education_level || '')

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updateFeatures = (newFeatures) => {
  console.log('updateFeatures called:', newFeatures)
  store.updateField('features', newFeatures)
}

const updateAdditionalFeatures = (value) => {
  console.log('updateAdditionalFeatures called:', value)
  store.updateField('additional_features', value)
}

const updateExperience = (value) => {
  console.log('updateExperience called:', value)
  store.updateField('experience', value)
}

const updateEducationLevel = (value) => {
  console.log('updateEducationLevel called:', value)
  store.updateField('education_level', value)
}
</script>