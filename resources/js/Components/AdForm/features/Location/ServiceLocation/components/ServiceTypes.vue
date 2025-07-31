<template>
  <FormField
    label="Место оказания услуг"
    :error="error"
  >
    <!-- Используем готовый CheckboxGroup вместо кастомных карточек -->
    <CheckboxGroup 
      v-model="localValue"
      :options="locationOptions"
      direction="column"
      variant="cards"
    />
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref([...props.modelValue])

watch(() => props.modelValue, (newValue) => {
  const normalizedNew = Array.isArray(newValue) ? newValue : []
  if (JSON.stringify(normalizedNew.sort()) !== JSON.stringify(localValue.value.sort())) {
    localValue.value = [...normalizedNew]
  }
})

watch(localValue, (newValue) => {
  const normalizedProps = Array.isArray(props.modelValue) ? props.modelValue : []
  if (JSON.stringify(newValue.sort()) !== JSON.stringify(normalizedProps.sort())) {
    emit('update:modelValue', newValue)
  }
}, { deep: true })

// Варианты местоположения с иконками и описаниями
const locationOptions = [
  {
    value: 'incall',
    label: 'У себя',
    description: 'Клиенты приезжают ко мне',
    icon: '🏠'
  },
  {
    value: 'outcall',
    label: 'Выезд к клиенту',
    description: 'Выезжаю к клиенту домой или в отель',
    icon: '🚗'
  },
  {
    value: 'salon',
    label: 'В салоне',
    description: 'Работаю в массажном салоне',
    icon: '🏢'
  },
  {
    value: 'hotel',
    label: 'В отеле',
    description: 'Встречи в отелях',
    icon: '🏨'
  }
]
</script>