<template>
  <FormField
    label="Особенности"
    hint="Выберите ваши особенности"
    :error="error"
  >
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
      <BaseCheckbox
        v-for="option in featureOptions"
        :key="option.value"
        :model-value="isSelected(option.value)"
        :label="option.label"
        @update:model-value="() => toggleFeature(option.value)"
      />
    </div>
    
    <!-- Счетчик выбранных -->
    <div v-if="selectedCount > 0" class="mt-3 flex items-center space-x-2 text-sm text-blue-600">
      <span class="flex items-center justify-center w-5 h-5 bg-blue-100 rounded-full text-xs font-medium">
        {{ selectedCount }}
      </span>
      <span>{{ selectedText }}</span>
    </div>
  </FormField>
</template>

<script setup>
import { computed } from 'vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import BaseCheckbox from '@/Components/UI/BaseCheckbox.vue'
import { useAdFormStore } from '../../../../stores/adFormStore'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

// Читаем данные ТОЛЬКО из store (как на Avito)
const features = computed(() => store.formData.features || {})

// Группы особенностей с иконками
const featureOptions = [
  { value: 'new_in_city', label: '🆕 Новенькая в городе' },
  { value: 'apartment', label: '🏠 Есть квартира' },
  { value: 'outcall_available', label: '🚗 Возможен выезд' },
  { value: 'massage_table', label: '🛏️ Есть массажный стол' },
  { value: 'shower_available', label: '🚿 Есть душ' },
  { value: 'parking_available', label: '🅿️ Есть парковка' },
  { value: 'discreet_entrance', label: '🚪 Отдельный вход' },
  { value: 'air_conditioning', label: '❄️ Кондиционер' },
  { value: 'music', label: '🎵 Приятная музыка' },
  { value: 'drinks_offered', label: '☕ Угощаю напитками' },
  { value: 'photos_verified', label: '✅ Фото проверены' },
  { value: 'flexible_schedule', label: '⏰ Гибкий график' },
  { value: 'weekend_available', label: '📅 Работаю в выходные' },
  { value: 'late_hours', label: '🌙 Работаю допоздна' }
]

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const isSelected = (value) => {
  return features.value[value] === true
}

const toggleFeature = (value) => {
  const currentFeatures = { ...features.value }
  
  if (currentFeatures[value]) {
    // Убираем особенность
    delete currentFeatures[value]
  } else {
    // Добавляем особенность
    currentFeatures[value] = true
  }
  
  console.log('toggleFeature called:', value, 'new features:', currentFeatures)
  store.updateField('features', currentFeatures)
  
  // Также эмитим для совместимости
  emit('update:modelValue', currentFeatures)
}

// Computed
const selectedCount = computed(() => {
  return Object.keys(features.value).filter(key => features.value[key]).length
})

const selectedText = computed(() => {
  const count = selectedCount.value
  if (count === 1) return 'особенность выбрана'
  if (count >= 2 && count <= 4) return 'особенности выбраны'
  return 'особенностей выбрано'
})
</script>