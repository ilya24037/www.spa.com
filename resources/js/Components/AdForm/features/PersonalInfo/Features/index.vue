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
        v-model="localFeatures"
        :error="errors.features"
      />

      <!-- Дополнительные особенности -->
      <AdditionalFeaturesInput
        v-model="localAdditionalFeatures"
        :error="errors.additional_features"
      />

      <!-- Опыт и образование в сетке -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Опыт работы -->
        <ExperienceSelect
          v-model="localExperience"
          :error="errors.experience"
        />

        <!-- Уровень образования -->
        <EducationSelect
          v-model="localEducationLevel"
          :error="errors.education_level"
        />
      </div>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'

// Микрокомпоненты
import FeaturesCheckboxes from './components/FeaturesCheckboxes.vue'
import AdditionalFeaturesInput from './components/AdditionalFeaturesInput.vue'
import ExperienceSelect from './components/ExperienceSelect.vue'
import EducationSelect from './components/EducationSelect.vue'

const props = defineProps({
  features: { type: Object, default: () => ({}) },
  additionalFeatures: { type: String, default: '' },
  experience: { type: String, default: '' },
  educationLevel: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:features', 
  'update:additionalFeatures', 
  'update:experience', 
  'update:educationLevel'
])

// Локальное состояние
const localFeatures = ref({ ...props.features })
const localAdditionalFeatures = ref(props.additionalFeatures)
const localExperience = ref(props.experience)
const localEducationLevel = ref(props.educationLevel)

// Отслеживание изменений пропсов
watch(() => props.features, (newValue) => {
  localFeatures.value = { ...newValue }
}, { deep: true })

watch(() => props.additionalFeatures, (newValue) => {
  localAdditionalFeatures.value = newValue
})

watch(() => props.experience, (newValue) => {
  localExperience.value = newValue
})

watch(() => props.educationLevel, (newValue) => {
  localEducationLevel.value = newValue
})

// Отправка изменений родителю
watch(localFeatures, (newValue) => {
  emit('update:features', { ...newValue })
}, { deep: true })

watch(localAdditionalFeatures, (newValue) => {
  emit('update:additionalFeatures', newValue)
})

watch(localExperience, (newValue) => {
  emit('update:experience', newValue)
})

watch(localEducationLevel, (newValue) => {
  emit('update:educationLevel', newValue)
})
</script>