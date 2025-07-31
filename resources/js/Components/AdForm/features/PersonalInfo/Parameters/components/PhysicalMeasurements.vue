<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Возраст -->
    <FormField
      label="Возраст"
      hint="Укажите ваш возраст"
      :error="errors.age"
    >
      <BaseInput
        v-model="localAge"
        type="number"
        placeholder="25"
        suffix="лет"
        min="18"
        max="65"
        class="w-full max-w-24"
      />
    </FormField>

    <!-- Рост -->
    <FormField
      label="Рост"
      hint="Рост в сантиметрах"
      :error="errors.height"
    >
      <BaseInput
        v-model="localHeight"
        type="number"
        placeholder="165"
        suffix="см"
        min="140"
        max="200"
        class="w-full max-w-24"
      />
    </FormField>

    <!-- Вес -->
    <FormField
      label="Вес"
      hint="Вес в килограммах"
      :error="errors.weight"
    >
      <BaseInput
        v-model="localWeight"
        type="number"
        placeholder="55"
        suffix="кг"
        min="40"
        max="150"
        class="w-full max-w-24"
      />
    </FormField>
  </div>

  <!-- BMI индикатор -->
  <div v-if="bmiValue" class="mt-4 flex items-center space-x-2 text-sm">
    <span :class="bmiIndicator.class">{{ bmiIndicator.icon }}</span>
    <span class="text-gray-600">ИМТ: {{ bmiValue }} ({{ bmiIndicator.text }})</span>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  age: { type: [String, Number], default: '' },
  height: { type: [String, Number], default: '' },
  weight: { type: [String, Number], default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:age', 'update:height', 'update:weight'])

const localAge = ref(String(props.age || ''))
const localHeight = ref(String(props.height || ''))
const localWeight = ref(String(props.weight || ''))

// Watchers для синхронизации props -> local
watch(() => props.age, (newValue) => { localAge.value = String(newValue || '') })
watch(() => props.height, (newValue) => { localHeight.value = String(newValue || '') })
watch(() => props.weight, (newValue) => { localWeight.value = String(newValue || '') })

// Watchers для отправки local -> parent
watch(localAge, (newValue) => { emit('update:age', newValue) })
watch(localHeight, (newValue) => { emit('update:height', newValue) })
watch(localWeight, (newValue) => { emit('update:weight', newValue) })

// BMI калькулятор
const bmiValue = computed(() => {
  const height = parseFloat(localHeight.value)
  const weight = parseFloat(localWeight.value)
  
  if (height && weight && height > 0) {
    return ((weight / ((height / 100) ** 2)).toFixed(1))
  }
  return null
})

const bmiIndicator = computed(() => {
  const bmi = parseFloat(bmiValue.value)
  if (!bmi) return { icon: '', text: '', class: '' }
  
  if (bmi < 18.5) {
    return { icon: '📐', text: 'недостаточный', class: 'text-blue-600' }
  } else if (bmi <= 24.9) {
    return { icon: '✅', text: 'нормальный', class: 'text-green-600' }
  } else if (bmi <= 29.9) {
    return { icon: '⚖️', text: 'избыточный', class: 'text-yellow-600' }
  } else {
    return { icon: '⚠️', text: 'ожирение', class: 'text-red-600' }
  }
})

// Methods (больше не нужны - используем watchers)
</script>