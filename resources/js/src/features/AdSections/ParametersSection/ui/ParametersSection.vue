<template>
  <div class="bg-white rounded-lg p-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <BaseInput
        v-model="localTitle"
        name="title"
        type="text"
        label="Название объявления"
        placeholder="Введите название объявления"
        @update:modelValue="emitAll"
        :error="errors?.title || errors?.['parameters.title']"
      />
      <BaseInput
        v-if="props.showFields.includes('age')"
        v-model="localAge"
        name="age"
        type="number"
        label="Возраст"
        placeholder="25"
        :min="18"
        :max="65"
        @update:modelValue="emitAll"
        :error="errors?.age || errors?.['parameters.age']"
      />
      <BaseInput
        v-model="localHeight"
        name="height"
        type="number"
        label="Рост (см)"
        placeholder="170"
        :min="100"
        :max="250"
        @update:modelValue="emitAll"
        :error="errors?.height || errors?.['parameters.height']"
      />
      <BaseInput
        v-model="localWeight"
        name="weight"
        type="number"
        label="Вес (кг)"
        placeholder="60"
        :min="30"
        :max="200"
        @update:modelValue="emitAll"
        :error="errors?.weight || errors?.['parameters.weight']"
      />
      <BaseSelect
        v-if="props.showFields.includes('breast_size')"
        v-model="localBreastSize"
        label="Размер груди"
        placeholder="Не указано"
        :options="breastSizeOptions"
        @update:modelValue="emitAll"
        :error="errors?.breast_size || errors?.['parameters.breast_size']"
      />
      <BaseSelect
        v-if="props.showFields.includes('hair_color')"
        v-model="localHairColor"
        label="Цвет волос"
        placeholder="Выберите цвет"
        :options="hairColorOptions"
        @update:modelValue="emitAll"
        :error="errors?.hair_color || errors?.['parameters.hair_color']"
      />
      <BaseSelect
        v-if="props.showFields.includes('eye_color')"
        v-model="localEyeColor"
        label="Цвет глаз"
        placeholder="Выберите цвет"
        :options="eyeColorOptions"
        @update:modelValue="emitAll"
        :error="errors?.eye_color || errors?.['parameters.eye_color']"
      />
      <BaseSelect
        v-if="props.showFields.includes('nationality')"
        v-model="localNationality"
        label="Национальность"
        placeholder="Выберите национальность"
        :options="nationalityOptions"
        @update:modelValue="emitAll"
        :error="errors?.nationality || errors?.['parameters.nationality']"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'
const props = defineProps({
  parameters: { 
    type: Object, 
    default: () => ({
      title: '',
      age: '',
      height: '',
      weight: '',
      breast_size: '',
      hair_color: '',
      eye_color: '',
      nationality: ''
    })
  },
  showFields: { 
    type: Array, 
    default: () => ['age', 'breast_size', 'hair_color', 'eye_color', 'nationality'] 
  },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:parameters'])
// Локальная копия параметров
const localParameters = ref({ ...props.parameters })

// Computed свойства для удобства доступа к полям
const localTitle = computed({
  get: () => localParameters.value.title,
  set: (value) => updateParameter('title', value)
})

const localAge = computed({
  get: () => localParameters.value.age,
  set: (value) => updateParameter('age', value)
})

const localHeight = computed({
  get: () => localParameters.value.height,
  set: (value) => updateParameter('height', value)
})

const localWeight = computed({
  get: () => localParameters.value.weight,
  set: (value) => updateParameter('weight', value)
})

const localBreastSize = computed({
  get: () => localParameters.value.breast_size ? String(localParameters.value.breast_size) : '',
  set: (value) => updateParameter('breast_size', value)
})

const localHairColor = computed({
  get: () => localParameters.value.hair_color,
  set: (value) => updateParameter('hair_color', value)
})

const localEyeColor = computed({
  get: () => localParameters.value.eye_color,
  set: (value) => updateParameter('eye_color', value)
})

const localNationality = computed({
  get: () => localParameters.value.nationality,
  set: (value) => updateParameter('nationality', value)
})

// Опции для селектов
const breastSizeOptions = computed(() => [
  { value: '', label: 'Не указано' },
  { value: '1', label: '1' },
  { value: '2', label: '2' },
  { value: '3', label: '3' },
  { value: '4', label: '4' },
  { value: '5', label: '5' },
  { value: '6', label: '6' }
])

const hairColorOptions = computed(() => [
  { value: '', label: 'Выберите цвет' },
  { value: 'blonde', label: 'Блондинка' },
  { value: 'brunette', label: 'Брюнетка' },
  { value: 'redhead', label: 'Рыжая' },
  { value: 'black', label: 'Черные' },
  { value: 'brown', label: 'Каштановые' },
  { value: 'gray', label: 'Седые' },
  { value: 'colored', label: 'Цветные' }
])

const eyeColorOptions = computed(() => [
  { value: '', label: 'Выберите цвет' },
  { value: 'blue', label: 'Голубые' },
  { value: 'green', label: 'Зеленые' },
  { value: 'brown', label: 'Карие' },
  { value: 'gray', label: 'Серые' },
  { value: 'black', label: 'Черные' },
  { value: 'hazel', label: 'Ореховые' }
])

const nationalityOptions = computed(() => [
  { value: '', label: 'Выберите национальность' },
  { value: 'russian', label: 'Русская' },
  { value: 'ukrainian', label: 'Украинка' },
  { value: 'belarusian', label: 'Белоруска' },
  { value: 'kazakh', label: 'Казашка' },
  { value: 'uzbek', label: 'Узбечка' },
  { value: 'tajik', label: 'Таджичка' },
  { value: 'armenian', label: 'Армянка' },
  { value: 'georgian', label: 'Грузинка' },
  { value: 'azerbaijani', label: 'Азербайджанка' },
  { value: 'asian', label: 'Азиатка' },
  { value: 'european', label: 'Европейка' },
  { value: 'latin', label: 'Латиноамериканка' },
  { value: 'african', label: 'Африканка' },
  { value: 'mixed', label: 'Метиска' },
  { value: 'other', label: 'Другая' }
])

// Универсальная функция обновления параметра
const updateParameter = (field, value) => {
  localParameters.value[field] = value
  emit('update:parameters', { ...localParameters.value })
}

// Слежение за изменениями props.parameters
watch(() => props.parameters, (newParams) => {
  localParameters.value = { ...newParams }
}, { deep: true })
// Функция для отправки всех параметров
const emitAll = () => {
  emit('update:parameters', { ...localParameters.value })
}
</script>

<!-- Все стили мигрированы на Tailwind CSS в template --> 