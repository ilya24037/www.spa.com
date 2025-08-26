<template>
  <div class="features-section">
    <h2 class="form-group-title">Особенности мастера</h2>
    <div class="features-list">
      <BaseCheckbox
        v-for="feature in allFeatures"
        :key="feature.id"
        :model-value="isFeatureSelected(feature.id)"
        :label="feature.label"
        @update:modelValue="toggleFeature(feature.id)"
      />
    </div>
    <div class="additional-info">
      <BaseTextarea
        v-model="localAdditional"
        label="Дополнительные особенности"
        placeholder="Опишите другие особенности, если есть..."
        :rows="2"
        @update:modelValue="emitFeatures"
      />
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import BaseTextarea from '@/src/shared/ui/atoms/BaseTextarea/BaseTextarea.vue'

const props = defineProps({
  features: { type: Array, default: () => [] },
  additionalFeatures: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:features', 'update:additionalFeatures'])

const allFeatures = [
  { id: 'smokes', label: 'Курю' },
  { id: 'drinks', label: 'Выпиваю' },
  { id: 'kisses', label: 'Целуюсь' },
  { id: 'medical_certificate', label: 'Есть медсправка' },
  { id: 'works_during_period', label: 'Работаю в критические дни' },
  { id: 'tattoos', label: 'Есть татуировки' },
  { id: 'piercings', label: 'Есть пирсинг' },
  { id: 'speaks_english', label: 'Говорю по-английски' },
  { id: 'has_car', label: 'Есть автомобиль' },
  { id: 'works_weekends', label: 'Работаю в выходные' }
]

const localFeatures = ref([...props.features])
const localAdditional = ref(props.additionalFeatures || '')

watch(() => props.features, (val) => {
  localFeatures.value = [...(val || [])]
}, { deep: true })

watch(() => props.additionalFeatures, (val) => {
  localAdditional.value = val || ''
})

const isFeatureSelected = (featureId) => {
  return localFeatures.value.includes(featureId)
}

const toggleFeature = (featureId) => {
  const index = localFeatures.value.indexOf(featureId)
  if (index > -1) {
    localFeatures.value.splice(index, 1)
  } else {
    localFeatures.value.push(featureId)
  }
  emitFeatures()
}

const emitFeatures = () => {
  emit('update:features', [...localFeatures.value])
  // ВАЖНО: Всегда отправляем строку, не null
  emit('update:additionalFeatures', localAdditional.value || '')
}
</script>

<style scoped>
.features-section {
  background: white;
  border-radius: 8px;
  padding: 20px;
}
.form-group-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin-bottom: 16px;
}
.features-list {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
}
.additional-info {
  border-top: 1px solid #e5e5e5;
  padding-top: 20px;
  margin-top: 20px;
}
</style>
