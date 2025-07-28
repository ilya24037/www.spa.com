<template>
  <div class="features-section">
    <h2 class="form-group-title">Особенности мастера</h2>
    
    <!-- Опыт работы и образование -->
    <div class="experience-education-row">
      <div class="field-group">
        <label class="field-label">Опыт работы:</label>
        <select v-model="localExperience" @change="emitExperience" class="experience-select">
          <option value="">- Выбрать -</option>
          <option value="3260137">Меньше года</option>
          <option value="3260142">1–3 года</option>
          <option value="3260146">4–7 лет</option>
          <option value="3260149">8–10 лет</option>
          <option value="3260152">Больше 10 лет</option>
        </select>
      </div>
      
      <div class="field-group">
        <label class="field-label">Образование:</label>
        <select v-model="localEducation" @change="emitEducation" class="education-select">
          <option value="">- Выбрать -</option>
          <option value="2">Среднее</option>
          <option value="3">Среднее (медицинское)</option>
          <option value="4">Высшее</option>
          <option value="5">Высшее (медицинское)</option>
          <option value="6">Курсы</option>
          <option value="7">Самоучка</option>
        </select>
      </div>
    </div>
    
    <!-- Чекбоксы особенностей -->
    <div class="features-checkboxes">
      <CheckboxGroup
        v-model="selectedFeatures"
        :options="allFeatures"
        direction="row"
        @update:modelValue="handleFeaturesChange"
      />
    </div>
    
    <div class="additional-info">
      <label class="additional-label">Дополнительные особенности:</label>
      <textarea 
        v-model="localAdditional" 
        @input="emitAdditionalFeatures" 
        placeholder="Опишите другие особенности, если есть..." 
        class="additional-textarea" 
        rows="2"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

const props = defineProps({
  features: { type: Object, default: () => ({}) },
  additionalFeatures: { type: String, default: '' },
  experience: { type: [String, Number], default: '' },
  educationLevel: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:features', 
  'update:additionalFeatures',
  'update:experience',
  'update:educationLevel'
])

// Опции для CheckboxGroup (используем value для ID, label для отображения)
const allFeatures = [
  { value: 'smokes', label: 'Курю' },
  { value: 'drinks', label: 'Выпиваю' },
  { value: 'kisses', label: 'Целуюсь' },
  { value: 'medical_certificate', label: 'Есть медсправка' },
  { value: 'works_during_period', label: 'Работаю в критические дни' }
]

const localAdditional = ref(props.additionalFeatures || '')
const localExperience = ref(props.experience || '')
const localEducation = ref(props.educationLevel || '')

// Преобразуем объект features в массив для CheckboxGroup
const selectedFeatures = computed({
  get: () => {
    // Возвращаем массив выбранных ID
    return Object.keys(props.features || {}).filter(key => props.features[key])
  },
  set: (newValue) => {
    // Преобразуем массив обратно в объект
    const featuresObject = {}
    allFeatures.forEach(feature => {
      featuresObject[feature.value] = newValue.includes(feature.value)
    })
    emit('update:features', featuresObject)
  }
})

// Обработчики
const handleFeaturesChange = (newFeatures) => {
  selectedFeatures.value = newFeatures
}

const emitAdditionalFeatures = () => {
  emit('update:additionalFeatures', localAdditional.value)
}

const emitExperience = () => {
  emit('update:experience', localExperience.value)
}

const emitEducation = () => {
  emit('update:educationLevel', localEducation.value)
}

// Отслеживаем изменения
watch(() => props.additionalFeatures, (val) => {
  localAdditional.value = val || ''
})

watch(() => props.experience, (val) => {
  localExperience.value = val || ''
})

watch(() => props.educationLevel, (val) => {
  localEducation.value = val || ''
})
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

.experience-education-row {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.field-group {
  flex: 1;
  min-width: 200px;
}

.field-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 6px;
}

.experience-select,
.education-select {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  background: white;
  transition: border-color 0.2s ease;
}

.experience-select:focus,
.education-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.features-checkboxes {
  border-top: 1px solid #e5e5e5;
  border-bottom: 1px solid #e5e5e5;
  padding: 16px 0;
  margin-bottom: 16px;
}

.additional-info {
  padding-top: 0;
  margin-top: 0;
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
  transition: border-color 0.2s ease;
}

.additional-textarea:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}
</style> 