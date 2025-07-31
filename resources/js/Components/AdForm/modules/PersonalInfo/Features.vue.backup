<template>
  <FormSection
    title="Особенности мастера"
    hint="Расскажите о ваших особенностях, опыте и образовании"
    :errors="errors"
    :error-keys="['features', 'additional_features', 'experience', 'education_level']"
  >
    <div class="features-content">
      <!-- Особенности -->
      <FormField
        label="Особенности"
        hint="Выберите ваши особенности"
        :error="errors.features"
      >
        <div class="features-grid">
          <div
            v-for="(feature, key) in availableFeatures"
            :key="key"
            class="feature-item"
            @click="toggleFeature(key)"
          >
            <input
              type="checkbox"
              :checked="isFeatureSelected(key)"
              @click.stop
              @change="toggleFeature(key)"
            />
            <span class="feature-label">{{ feature }}</span>
          </div>
        </div>
      </FormField>

      <!-- Дополнительные особенности -->
      <FormField
        label="Дополнительные особенности"
        hint="Опишите другие ваши особенности"
        :error="errors.additional_features"
      >
        <textarea
          v-model="localAdditionalFeatures"
          @input="updateAdditionalFeatures"
          rows="3"
          placeholder="Опишите дополнительные особенности..."
          maxlength="500"
        ></textarea>
        <div class="character-counter">
          {{ localAdditionalFeatures.length }}/500
        </div>
      </FormField>

      <!-- Опыт работы -->
      <FormField
        label="Опыт работы"
        hint="Укажите ваш опыт работы"
        required
        :error="errors.experience"
      >
        <select v-model="localExperience" @change="updateExperience">
          <option value="">Выберите опыт</option>
          <option value="3260137">Без опыта</option>
          <option value="3260142">До 1 года</option>
          <option value="3260146">1-3 года</option>
          <option value="3260149">3-6 лет</option>
          <option value="3260152">Более 6 лет</option>
        </select>
      </FormField>

      <!-- Уровень образования -->
      <FormField
        label="Уровень образования"
        hint="Укажите ваш уровень образования"
        :error="errors.education_level"
      >
        <select v-model="localEducationLevel" @change="updateEducationLevel">
          <option value="">Выберите уровень</option>
          <option value="2">Среднее</option>
          <option value="3">Среднее специальное</option>
          <option value="4">Неоконченное высшее</option>
          <option value="5">Высшее</option>
          <option value="6">Несколько высших</option>
          <option value="7">Кандидат наук</option>
        </select>
      </FormField>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  features: { type: Object, default: () => ({}) },
  additionalFeatures: { type: String, default: '' },
  experience: { type: String, default: '' },
  educationLevel: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:features', 'update:additionalFeatures', 
  'update:experience', 'update:educationLevel'
])

// Доступные особенности
const availableFeatures = {
  'new_in_city': 'Новенькая в городе',
  'apartment': 'Есть квартира',
  'outcall_available': 'Возможен выезд',
  'massage_table': 'Есть массажный стол',
  'shower_available': 'Есть душ',
  'parking_available': 'Есть парковка',
  'discreet_entrance': 'Отдельный вход',
  'air_conditioning': 'Кондиционер',
  'music': 'Приятная музыка',
  'drinks_offered': 'Угощаю напитками',
  'photos_verified': 'Фото проверены',
  'flexible_schedule': 'Гибкий график',
  'weekend_available': 'Работаю в выходные',
  'late_hours': 'Работаю допоздна'
}

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

// Методы
const isFeatureSelected = (featureKey) => {
  return Boolean(localFeatures.value[featureKey])
}

const toggleFeature = (featureKey) => {
  localFeatures.value[featureKey] = !localFeatures.value[featureKey]
  
  // Если особенность убрана, удаляем её из объекта
  if (!localFeatures.value[featureKey]) {
    delete localFeatures.value[featureKey]
  }
  
  emit('update:features', { ...localFeatures.value })
}

const updateAdditionalFeatures = () => {
  emit('update:additionalFeatures', localAdditionalFeatures.value)
}

const updateExperience = () => {
  emit('update:experience', localExperience.value)
}

const updateEducationLevel = () => {
  emit('update:educationLevel', localEducationLevel.value)
}
</script>

<style scoped>
.features-content {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 12px;
}

.feature-item {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  padding: 8px 12px;
  border: 1px solid #e5e5e5;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.feature-item:hover {
  border-color: #1890ff;
  background: #f0f8ff;
}

.feature-item input[type="checkbox"] {
  width: 16px;
  height: 16px;
  margin: 0;
  flex-shrink: 0;
}

.feature-label {
  font-size: 14px;
  color: #1a1a1a;
  line-height: 1.4;
}

.character-counter {
  text-align: right;
  font-size: 12px;
  color: #8c8c8c;
  margin-top: 4px;
}

@media (max-width: 768px) {
  .features-grid {
    grid-template-columns: 1fr;
  }
}
</style>