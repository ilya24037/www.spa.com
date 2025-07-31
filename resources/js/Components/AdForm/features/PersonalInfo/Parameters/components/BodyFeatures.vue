<template>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Цвет волос -->
    <FormField
      label="Цвет волос"
      hint="Цвет ваших волос"
      :error="errors.hair_color"
    >
      <BaseSelect
        v-model="localHairColor"
        :options="hairColorOptions"
        placeholder="Выберите цвет"
        @update:modelValue="handleHairColorChange"
      />
    </FormField>

    <!-- Цвет глаз -->
    <FormField
      label="Цвет глаз"
      hint="Цвет ваших глаз"
      :error="errors.eye_color"
    >
      <BaseSelect
        v-model="localEyeColor"
        :options="eyeColorOptions"
        placeholder="Выберите цвет"
        @update:modelValue="handleEyeColorChange"
      />
    </FormField>

    <!-- Размер груди -->
    <FormField
      label="Размер груди"
      hint="Размер груди"
      :error="errors.breast_size"
    >
      <BaseSelect
        v-model="localBreastSize"
        :options="breastSizeOptions"
        placeholder="Выберите размер"
        @update:modelValue="handleBreastSizeChange"
      />
    </FormField>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  hairColor: { type: String, default: '' },
  eyeColor: { type: String, default: '' },
  breastSize: { type: [String, Number], default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:hairColor', 'update:eyeColor', 'update:breastSize'])

const localHairColor = ref(props.hairColor || '')
const localEyeColor = ref(props.eyeColor || '')
const localBreastSize = ref(String(props.breastSize || ''))

// Watchers
watch(() => props.hairColor, (newValue) => { localHairColor.value = newValue || '' })
watch(() => props.eyeColor, (newValue) => { localEyeColor.value = newValue || '' })
watch(() => props.breastSize, (newValue) => { localBreastSize.value = String(newValue || '') })

// Options with emojis
const hairColorOptions = [
  { value: 'blonde', label: '👱‍♀️ Блондинка' },
  { value: 'brunette', label: '👩‍🦳 Брюнетка' },
  { value: 'brown', label: '👩‍🦰 Шатенка' },
  { value: 'red', label: '🦰 Рыжая' },
  { value: 'gray', label: '👵 Седая' },
  { value: 'colored', label: '🌈 Цветные' }
]

const eyeColorOptions = [
  { value: 'blue', label: '💙 Голубые' },
  { value: 'green', label: '💚 Зеленые' },
  { value: 'brown', label: '🤎 Карие' },
  { value: 'gray', label: '🩶 Серые' },
  { value: 'hazel', label: '🌰 Ореховые' }
]

const breastSizeOptions = [
  { value: '1', label: '1️⃣ Первый' },
  { value: '2', label: '2️⃣ Второй' },
  { value: '3', label: '3️⃣ Третий' },
  { value: '4', label: '4️⃣ Четвертый' },
  { value: '5', label: '5️⃣ Пятый' },
  { value: '6', label: '6️⃣ Шестой' }
]

// Methods
const handleHairColorChange = (value) => {
  localHairColor.value = value
  emit('update:hairColor', value)
}

const handleEyeColorChange = (value) => {
  localEyeColor.value = value
  emit('update:eyeColor', value)
}

const handleBreastSizeChange = (value) => {
  localBreastSize.value = value
  emit('update:breastSize', value)
}
</script>