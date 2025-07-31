<template>
  <FormField
    label="Особенности"
    hint="Выберите ваши особенности"
    :error="error"
  >
    <CheckboxGroup
      v-model="localValue"
      :options="featureOptions"
      class="grid grid-cols-1 md:grid-cols-2 gap-3"
      @update:modelValue="handleUpdate"
    />
    
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
import { ref, computed, watch } from 'vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

const props = defineProps({
  modelValue: { type: Object, default: () => ({}) },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(Array.from(Object.keys(props.modelValue || {})))

watch(() => props.modelValue, (newValue) => {
  const newKeys = Array.from(Object.keys(newValue || {}))
  // Обновляем только если есть реальные изменения
  if (JSON.stringify(newKeys.sort()) !== JSON.stringify(localValue.value.sort())) {
    localValue.value = newKeys
  }
}, { deep: true })

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

// Computed
const selectedCount = computed(() => localValue.value.length)

const selectedText = computed(() => {
  const count = selectedCount.value
  if (count === 1) return 'особенность выбрана'
  if (count >= 2 && count <= 4) return 'особенности выбраны'
  return 'особенностей выбрано'
})

// Методы
const handleUpdate = (selectedKeys) => {
  // Проверяем есть ли реальные изменения
  if (JSON.stringify(selectedKeys.sort()) === JSON.stringify(localValue.value.sort())) {
    return // Никаких изменений - не обновляем
  }
  
  // Преобразуем массив ключей в объект
  const featuresObject = {}
  selectedKeys.forEach(key => {
    featuresObject[key] = true
  })
  
  // Обновляем локальное состояние первым
  localValue.value = selectedKeys
  
  // Затем эмитим изменения
  emit('update:modelValue', featuresObject)
}
</script>