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
        @update:age="$emit('update:age', $event)"
        @update:height="$emit('update:height', $event)"
        @update:weight="$emit('update:weight', $event)"
      />

      <!-- Особенности тела -->
      <BodyFeatures
        :hair-color="hairColor"
        :eye-color="eyeColor"
        :breast-size="breastSize"
        :errors="errors"
        @update:hairColor="$emit('update:hairColor', $event)"
        @update:eyeColor="$emit('update:eyeColor', $event)"
        @update:breastSize="$emit('update:breastSize', $event)"
      />

      <!-- Внешность и национальность -->
      <AppearanceSelects
        :appearance="appearance"
        :nationality="nationality"
        :errors="errors"
        @update:appearance="$emit('update:appearance', $event)"
        @update:nationality="$emit('update:nationality', $event)"
      />
    </div>
  </FormSection>
</template>

<script setup>
import FormSection from '@/Components/UI/Forms/FormSection.vue'

// Микрокомпоненты
import PhysicalMeasurements from './components/PhysicalMeasurements.vue'
import BodyFeatures from './components/BodyFeatures.vue'
import AppearanceSelects from './components/AppearanceSelects.vue'

defineProps({
  age: { type: [String, Number], default: '' },
  height: { type: [String, Number], default: '' },
  weight: { type: [String, Number], default: '' },
  breastSize: { type: [String, Number], default: '' },
  hairColor: { type: String, default: '' },
  eyeColor: { type: String, default: '' },
  appearance: { type: String, default: '' },
  nationality: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

defineEmits([
  'update:age', 'update:height', 'update:weight', 'update:breastSize',
  'update:hairColor', 'update:eyeColor', 'update:appearance', 'update:nationality'
])
</script>