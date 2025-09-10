<template>
  <div class="rounded-lg p-5">
    <div class="grid grid-cols-4 gap-3">
      <BaseInput
        v-model="localTitle"
        name="title"
        type="text"
        label="Имя"
        placeholder="Введите имя"
        :required="true"
        @update:modelValue="emitAll"
        @blur="validateField('title')"
        :error="fieldErrors.title || errors?.title || errors?.['parameters.title'] || (props.forceValidation?.title && !localTitle ? 'Имя обязательно' : '')"
        class="w-[170px]"
      />
      <BaseInput
        v-if="props.showFields.includes('age')"
        v-model="localAge"
        name="age"
        type="number"
        label="Возраст"
        placeholder="25"
        :required="true"
        :min="18"
        :max="65"
        @update:modelValue="emitAll"
        @blur="validateField('age')"
        :error="fieldErrors.age || errors?.age || errors?.['parameters.age'] || (props.forceValidation?.age && !localAge ? 'Возраст обязателен' : '')"
        class="w-[170px]"
      />
      <BaseInput
        v-model="localHeight"
        name="height"
        type="number"
        label="Рост (см)"
        placeholder="170"
        :required="true"
        :min="100"
        :max="250"
        @update:modelValue="emitAll"
        @blur="validateField('height')"
        :error="fieldErrors.height || errors?.height || errors?.['parameters.height'] || (props.forceValidation?.height && !localHeight ? 'Рост обязателен' : '')"
        class="w-[170px]"
      />
      <BaseInput
        v-model="localWeight"
        name="weight"
        type="number"
        label="Вес (кг)"
        placeholder="60"
        :required="true"
        :min="30"
        :max="200"
        @update:modelValue="emitAll"
        @blur="validateField('weight')"
        :error="fieldErrors.weight || errors?.weight || errors?.['parameters.weight'] || (props.forceValidation?.weight && !localWeight ? 'Вес обязателен' : '')"
        class="w-[170px]"
      />
      <BaseSelect
        v-if="props.showFields.includes('breast_size')"
        v-model="localBreastSize"
        label="Размер груди"
        placeholder="Не выбрано"
        :required="true"
        :options="breastSizeOptions"
        @update:modelValue="emitAll"
        @blur="validateField('breast_size')"
        :error="fieldErrors.breast_size || errors?.breast_size || errors?.['parameters.breast_size'] || (props.forceValidation?.breast_size && !localBreastSize ? 'Размер груди обязателен' : '')"
        class="w-[170px]"
      />
      <BaseSelect
        v-if="props.showFields.includes('hair_color')"
        v-model="localHairColor"
        label="Цвет волос"
        placeholder="Не выбрано"
        :required="true"
        :options="hairColorOptions"
        @update:modelValue="emitAll"
        @blur="validateField('hair_color')"
        :error="fieldErrors.hair_color || errors?.hair_color || errors?.['parameters.hair_color'] || (props.forceValidation?.hair_color && !localHairColor ? 'Цвет волос обязателен' : '')"
        class="w-[170px]"
      />
      <BaseSelect
        v-if="props.showFields.includes('eye_color')"
        v-model="localEyeColor"
        label="Цвет глаз"
        placeholder="Не выбрано"
        :options="eyeColorOptions"
        @update:modelValue="emitAll"
        :error="errors?.eye_color || errors?.['parameters.eye_color']"
        class="w-[170px]"
      />
      <BaseSelect
        v-if="props.showFields.includes('nationality')"
        v-model="localNationality"
        label="Национальность"
        placeholder="Не выбрано"
        :options="nationalityOptions"
        @update:modelValue="emitAll"
        :error="errors?.nationality || errors?.['parameters.nationality']"
        class="w-[170px]"
      />
      
      <BaseSelect
        v-if="props.showFields.includes('appearance')"
        v-model="localAppearance"
        label="Внешность"
        placeholder="Не выбрано"
        :options="appearanceOptions"
        @update:modelValue="emitAll"
        :error="errors?.appearance || errors?.['parameters.appearance']"
        class="w-[170px]"
      />
      
      <BaseSelect
        v-if="props.showFields.includes('bikini_zone')"
        v-model="localBikiniZone"
        label="Зона бикини"
        placeholder="Не выбрано"
        :options="bikiniZoneOptions"
        @update:modelValue="emitAll"
        :error="errors?.bikini_zone || errors?.['parameters.bikini_zone']"
        class="w-[170px]"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, reactive } from 'vue'
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
      nationality: '',
      appearance: '',
      bikini_zone: ''
    })
  },
  showFields: { 
    type: Array, 
    default: () => ['age', 'breast_size', 'hair_color', 'eye_color', 'nationality', 'bikini_zone'] 
  },
  errors: { type: Object, default: () => ({}) },
  forceValidation: {
    type: Object,
    default: () => ({
      title: false,
      age: false,
      height: false,
      weight: false,
      breast_size: false,
      hair_color: false
    })
  }
})
const emit = defineEmits(['update:parameters', 'clearForceValidation'])

// Локальная копия параметров
const localParameters = ref({ ...props.parameters })

// Локальные ошибки для валидации на лету
const fieldErrors = reactive({})

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

const localAppearance = computed({
  get: () => localParameters.value.appearance,
  set: (value) => updateParameter('appearance', value)
})

const localBikiniZone = computed({
  get: () => localParameters.value.bikini_zone,
  set: (value) => updateParameter('bikini_zone', value)
})

// Опции для селектов
const breastSizeOptions = computed(() => [
  { value: '', label: 'Не выбрано' },
  { value: '1', label: '1' },
  { value: '2', label: '2' },
  { value: '3', label: '3' },
  { value: '4', label: '4' },
  { value: '5', label: '5' },
  { value: '6', label: '6' }
])

const hairColorOptions = computed(() => [
  { value: '', label: 'Не выбрано' },
  { value: 'blonde', label: 'Блондинка' },
  { value: 'brunette', label: 'Брюнетка' },
  { value: 'redhead', label: 'Рыжая' },
  { value: 'black', label: 'Черные' },
  { value: 'brown', label: 'Каштановые' },
  { value: 'gray', label: 'Седые' },
  { value: 'colored', label: 'Цветные' }
])

const eyeColorOptions = computed(() => [
  { value: '', label: 'Не выбрано' },
  { value: 'blue', label: 'Голубые' },
  { value: 'green', label: 'Зеленые' },
  { value: 'brown', label: 'Карие' },
  { value: 'gray', label: 'Серые' },
  { value: 'black', label: 'Черные' },
  { value: 'hazel', label: 'Ореховые' }
])

const nationalityOptions = computed(() => [
  { value: '', label: 'Не выбрано' },
  { value: 'russian', label: 'Русская' },
  { value: 'ukrainian', label: 'Украинка' },
  { value: 'belarusian', label: 'Белоруска' },
  { value: 'kazakh', label: 'Казашка' },
  { value: 'uzbek', label: 'Узбечка' },
  { value: 'tajik', label: 'Таджичка' },
  { value: 'armenian', label: 'Армянка' },
  { value: 'georgian', label: 'Грузинка' },
  { value: 'azerbaijani', label: 'Азербайджанка' },
  { value: 'other', label: 'Другая' }
])

const appearanceOptions = computed(() => [
  { value: '', label: 'Не выбрано' },
  { value: 'slavic', label: 'Славянская' },
  { value: 'caucasian', label: 'Кавказская' },
  { value: 'central_asian', label: 'Среднеазиатская' },
  { value: 'east_asian', label: 'Восточноазиатская' },
  { value: 'west_european', label: 'Западноевропейская' },
  { value: 'scandinavian', label: 'Скандинавская' },
  { value: 'mediterranean', label: 'Средиземноморская' },
  { value: 'oriental', label: 'Восточная' },
  { value: 'latin', label: 'Латинская' },
  { value: 'mixed', label: 'Смешанная' },
  { value: 'african', label: 'Африканская' },
  { value: 'indian', label: 'Индийская' },
  { value: 'mestiza', label: 'Метиска' },
  { value: 'mulatto', label: 'Мулатка' }
])

const bikiniZoneOptions = computed(() => [
  { value: '', label: 'Не выбрано' },
  { value: 'natural', label: 'Натуральная' },
  { value: 'bikini_line', label: 'Линия бикини' },
  { value: 'triangle', label: 'Треугольник' },
  { value: 'thin_strip', label: 'Тонкая полоска' },
  { value: 'smooth', label: 'Гладкая' }
])

// Простая валидация на blur (KISS принцип)
const validateField = (field) => {
  let error = null
  
  switch(field) {
    case 'title':
      if (!localTitle.value) {
        error = 'Имя обязательно'
      } else if (localTitle.value.length < 2) {
        error = 'Минимум 2 символа'
      }
      break
    
    case 'age':
      if (!localAge.value) {
        error = 'Возраст обязателен'
      } else if (localAge.value < 18) {
        error = 'Минимум 18 лет'
      } else if (localAge.value > 99) {
        error = 'Максимум 99 лет'
      }
      break
    
    case 'height':
      if (!localHeight.value) {
        error = 'Рост обязателен'
      } else if (localHeight.value < 140) {
        error = 'Минимум 140 см'
      } else if (localHeight.value > 220) {
        error = 'Максимум 220 см'
      }
      break
    
    case 'weight':
      if (!localWeight.value) {
        error = 'Вес обязателен'
      } else if (localWeight.value < 40) {
        error = 'Минимум 40 кг'
      } else if (localWeight.value > 200) {
        error = 'Максимум 200 кг'
      }
      break
    
    case 'breast_size':
      if (!localBreastSize.value) {
        error = 'Размер груди обязателен'
      }
      break
    
    case 'hair_color':
      if (!localHairColor.value) {
        error = 'Цвет волос обязателен'
      }
      break
  }
  
  // Обновляем или удаляем ошибку
  if (error) {
    fieldErrors[field] = error
  } else {
    delete fieldErrors[field]
  }
}

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

// Следим за изменениями локальных полей и сбрасываем флаг принудительной валидации
watch(() => localTitle.value, (value) => {
  if (value && props.forceValidation?.title) {
    emit('clearForceValidation', 'title')
  }
})

watch(() => localAge.value, (value) => {
  if (value && props.forceValidation?.age) {
    emit('clearForceValidation', 'age')
  }
})

watch(() => localHeight.value, (value) => {
  if (value && props.forceValidation?.height) {
    emit('clearForceValidation', 'height')
  }
})

watch(() => localWeight.value, (value) => {
  if (value && props.forceValidation?.weight) {
    emit('clearForceValidation', 'weight')
  }
})

watch(() => localBreastSize.value, (value) => {
  if (value && props.forceValidation?.breast_size) {
    emit('clearForceValidation', 'breast_size')
  }
})

watch(() => localHairColor.value, (value) => {
  if (value && props.forceValidation?.hair_color) {
    emit('clearForceValidation', 'hair_color')
  }
})
</script>

<style scoped>
/* Мобильная адаптация для 4-колоночной сетки */
@media (max-width: 1024px) {
  .grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .grid {
    grid-template-columns: 1fr;
  }
}
</style> 