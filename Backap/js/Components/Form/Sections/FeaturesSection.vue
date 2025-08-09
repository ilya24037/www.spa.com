<template>
  <div class="features-section">
    <h2 class="form-group-title">Особенности мастера</h2>
    <div class="features-list">
      <div v-for="feature in allFeatures" :key="feature.id" class="feature-item">
        <label>
          <input type="checkbox" v-model="localFeatures[feature.id]" @change="emitFeatures" />
          {{ feature.label }}
        </label>
      </div>
    </div>
    <div class="additional-info" style="margin-top: 20px;">
      <label class="additional-label">Дополнительные особенности:</label>
      <textarea v-model="localAdditional" @input="emitFeatures" placeholder="Опишите другие особенности, если есть..." class="additional-textarea" rows="2"></textarea>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue'

const props = defineProps({
  features: { type: Object, default: () => ({}) },
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
  // ...добавь остальные особенности по необходимости
]

const localFeatures = reactive({ ...props.features })
const localAdditional = ref(props.additionalFeatures || '')

watch(() => props.features, (val) => {
  Object.assign(localFeatures, val || {})
}, { deep: true })

watch(() => props.additionalFeatures, (val) => {
  localAdditional.value = val || ''
})

const emitFeatures = () => {
  emit('update:features', { ...localFeatures })
  emit('update:additionalFeatures', localAdditional.value)
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
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
}
.feature-item {
  min-width: 160px;
}
.additional-info {
  border-top: 1px solid #e5e5e5;
  padding-top: 16px;
}
.additional-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}
.additional-textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  resize: vertical;
  min-height: 50px;
}
</style> 