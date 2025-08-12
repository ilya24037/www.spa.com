<template>
  <div class="specialty-section">
    <BaseSelect
      v-model="localSpecialty"
      label="Специальность или сфера"
      placeholder="Выберите специальность"
      :options="specialtyOptions"
      :error="errors.specialty"
      @update:modelValue="emitSpecialty"
    />
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'

const props = defineProps({
  specialty: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:specialty'])
const localSpecialty = ref(props.specialty)

// Опции для селекта с группировкой
const specialtyOptions = computed(() => [
  { value: '', label: 'Выберите специальность' },
  { 
    label: 'Массаж',
    group: true,
    options: [
      { value: 'erotic_massage', label: 'Эротический массаж' },
      { value: 'classic_massage', label: 'Классический массаж' },
      { value: 'relax_massage', label: 'Расслабляющий массаж' },
      { value: 'thai_massage', label: 'Тайский массаж' },
      { value: 'tantric_massage', label: 'Тантрический массаж' },
      { value: 'body_massage', label: 'Боди-массаж' },
      { value: 'nuru_massage', label: 'Нуру массаж' }
    ]
  },
  {
    label: 'Косметология',
    group: true,
    options: [
      { value: 'cosmetologist', label: 'Косметолог' },
      { value: 'beautician', label: 'Визажист' },
      { value: 'manicure', label: 'Мастер маникюра' },
      { value: 'pedicure', label: 'Мастер педикюра' },
      { value: 'eyebrows', label: 'Бровист' },
      { value: 'depilation', label: 'Мастер депиляции' }
    ]
  },
  {
    label: 'Развлечения',
    group: true,
    options: [
      { value: 'stripper', label: 'Стриптиз' },
      { value: 'gogo', label: 'Go-go танцы' },
      { value: 'escort', label: 'Эскорт' },
      { value: 'model', label: 'Модель' },
      { value: 'hostess', label: 'Хостес' }
    ]
  },
  {
    label: 'Другое',
    group: true,
    options: [
      { value: 'photographer', label: 'Фотограф' },
      { value: 'psychologist', label: 'Психолог' },
      { value: 'coach', label: 'Тренер' },
      { value: 'other', label: 'Другая специальность' }
    ]
  }
])

watch(() => props.specialty, (val) => { 
  localSpecialty.value = val 
})

const emitSpecialty = () => {
  emit('update:specialty', localSpecialty.value)
}
</script>

<style scoped>
.specialty-section { 
  background: white; 
  border-radius: 8px; 
  padding: 20px; 
}
</style>