<template>
  <div class="bg-white rounded-lg p-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <BaseInput
        v-model="localTitle"
        name="title"
        type="text"
        label="Имя"
        placeholder="Введите ваше имя"
        @update:modelValue="emitAll"
        :error="errors.title"
      />
      <BaseInput
        v-if="showAge"
        v-model="localAge"
        name="age"
        type="number"
        label="Возраст"
        placeholder="25"
        :min="18"
        :max="65"
        @update:modelValue="emitAll"
        :error="errors.age"
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
        :error="errors.height"
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
        :error="errors.weight"
      />
      <BaseSelect
        v-if="showBreastSize"
        v-model="localBreastSize"
        label="Размер груди"
        placeholder="Не указано"
        :options="breastSizeOptions"
        @update:modelValue="emitAll"
        :error="errors.breast_size"
      />
      <BaseSelect
        v-if="showHairColor"
        v-model="localHairColor"
        label="Цвет волос"
        placeholder="Выберите цвет"
        :options="hairColorOptions"
        @update:modelValue="emitAll"
        :error="errors.hair_color"
      />
      <BaseSelect
        v-if="showEyeColor"
        v-model="localEyeColor"
        label="Цвет глаз"
        placeholder="Выберите цвет"
        :options="eyeColorOptions"
        @update:modelValue="emitAll"
        :error="errors.eye_color"
      />
      <BaseSelect
        v-if="showNationality"
        v-model="localNationality"
        label="Национальность"
        placeholder="Выберите национальность"
        :options="nationalityOptions"
        @update:modelValue="emitAll"
        :error="errors.nationality"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'
const props = defineProps({
  title: { type: String, default: '' },
  age: { type: [String, Number], default: '' },
  height: { type: [String, Number], default: '' },
  weight: { type: [String, Number], default: '' },
  breast_size: { type: [String, Number], default: '' },
  hair_color: { type: String, default: '' },
  eye_color: { type: String, default: '' },
  nationality: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) },
  // Опциональные props для управления видимостью полей
  showAge: { type: Boolean, default: true },
  showBreastSize: { type: Boolean, default: false },
  showHairColor: { type: Boolean, default: true },
  showEyeColor: { type: Boolean, default: true },
  showNationality: { type: Boolean, default: true }
})
const emit = defineEmits(['update:title', 'update:age', 'update:height', 'update:weight', 'update:breast_size', 'update:hair_color', 'update:eye_color', 'update:nationality'])
const localTitle = ref(props.title)
const localAge = ref(props.age)
const localHeight = ref(props.height)
const localWeight = ref(props.weight)
const localBreastSize = ref(props.breast_size ? String(props.breast_size) : '')
const localHairColor = ref(props.hair_color)
const localEyeColor = ref(props.eye_color)
const localNationality = ref(props.nationality)

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

watch(() => props.title, val => { localTitle.value = val })
watch(() => props.age, val => { localAge.value = val })
watch(() => props.height, val => { localHeight.value = val })
watch(() => props.weight, val => { localWeight.value = val })
watch(() => props.breast_size, val => { localBreastSize.value = val ? String(val) : '' })
watch(() => props.hair_color, val => { localHairColor.value = val })
watch(() => props.eye_color, val => { localEyeColor.value = val })
watch(() => props.nationality, val => { localNationality.value = val })
const emitAll = () => {
  emit('update:title', localTitle.value)
  emit('update:age', localAge.value)
  emit('update:height', localHeight.value)
  emit('update:weight', localWeight.value)
  emit('update:breast_size', localBreastSize.value)
  emit('update:hair_color', localHairColor.value)
  emit('update:eye_color', localEyeColor.value)
  emit('update:nationality', localNationality.value)
}
</script>

<!-- Все стили мигрированы на Tailwind CSS в template --> 