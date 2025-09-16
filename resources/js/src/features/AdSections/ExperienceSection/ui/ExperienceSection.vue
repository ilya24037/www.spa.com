<template>
  <div class="w-[170px]">
    <BaseSelect
      v-model="localExperience"
      placeholder="Не выбрано"
      :options="experienceOptions"
      :required="false"
      :error="errors.experience"
      @update:modelValue="emitExperience"
      class="w-[170px]"
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

// Опции для опыта (согласно backend валидации)
const experienceOptions = computed(() => [
  { value: '', label: 'Не выбрано' },
  { value: '3260137', label: 'Без опыта' },
  { value: '3260142', label: '1-2 года' },
  { value: '3260146', label: '3-5 лет' },
  { value: '3260149', label: '6-10 лет' },
  { value: '3260152', label: 'Более 10 лет' }
])

watch(() => props.experience, val => { 
  localExperience.value = val 
})

const emitExperience = () => {
  emit('update:experience', localExperience.value)
}
</script>

<style scoped>
/* Стили убраны, используется только Tailwind класс w-[170px] */
</style>