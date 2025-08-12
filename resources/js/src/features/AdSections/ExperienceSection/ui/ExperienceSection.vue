<template>
  <div class="experience-section">
    <h2 class="form-group-title">Опыт работы</h2>
    <BaseSelect
      v-model="localExperience"
      label="Опыт (лет)"
      placeholder="Выберите опыт"
      :options="experienceOptions"
      :error="errors.experience"
      @update:modelValue="emitExperience"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'

const props = defineProps({
  experience: { type: [String, Number], default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:experience'])
const localExperience = ref(props.experience)

// Генерируем опции для опыта
const experienceOptions = computed(() => {
  const options = [
    { value: '', label: 'Выберите опыт' },
    { value: '0', label: 'Без опыта' }
  ]
  
  // Добавляем годы от 1 до 20
  for (let i = 1; i <= 20; i++) {
    const yearLabel = i === 1 ? 'год' : i >= 2 && i <= 4 ? 'года' : 'лет'
    options.push({ value: String(i), label: `${i} ${yearLabel}` })
  }
  
  options.push({ value: '20+', label: 'Более 20 лет' })
  
  return options
})

watch(() => props.experience, val => { 
  localExperience.value = val 
})

const emitExperience = () => {
  emit('update:experience', localExperience.value)
}
</script>

<style scoped>
.experience-section { 
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
</style>