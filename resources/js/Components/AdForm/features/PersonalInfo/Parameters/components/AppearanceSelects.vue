<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Тип внешности -->
    <FormField
      label="Тип внешности"
      hint="Опишите тип внешности"
      :error="errors.appearance"
    >
      <BaseSelect
        v-model="localAppearance"
        :options="appearanceOptions"
        placeholder="Выберите тип"
        @update:modelValue="handleAppearanceChange"
      />
    </FormField>

    <!-- Национальность -->
    <FormField
      label="Национальность"
      hint="Укажите вашу национальность"
      :error="errors.nationality"
    >
      <BaseSelect
        v-model="localNationality"
        :options="nationalityOptions"
        placeholder="Выберите национальность"
        @update:modelValue="handleNationalityChange"
      />
    </FormField>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  appearance: { type: String, default: '' },
  nationality: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:appearance', 'update:nationality'])

const localAppearance = ref(props.appearance || '')
const localNationality = ref(props.nationality || '')

// Watchers
watch(() => props.appearance, (newValue) => { localAppearance.value = newValue || '' })
watch(() => props.nationality, (newValue) => { localNationality.value = newValue || '' })

// Options
const appearanceOptions = [
  { value: 'slavic', label: '🇷🇺 Славянская' },
  { value: 'european', label: '🇪🇺 Европейская' },
  { value: 'asian', label: '🌏 Азиатская' },
  { value: 'caucasian', label: '🏔️ Кавказская' },
  { value: 'mixed', label: '🌍 Смешанная' }
]

const nationalityOptions = [
  { value: 'russian', label: '🇷🇺 Русская' },
  { value: 'ukrainian', label: '🇺🇦 Украинка' },
  { value: 'belarusian', label: '🇧🇾 Белоруска' },
  { value: 'kazakh', label: '🇰🇿 Казашка' },
  { value: 'azerbaijani', label: '🇦🇿 Азербайджанка' },
  { value: 'armenian', label: '🇦🇲 Армянка' },
  { value: 'georgian', label: '🇬🇪 Грузинка' },
  { value: 'other', label: '🌍 Другая' }
]

// Methods
const handleAppearanceChange = (value) => {
  localAppearance.value = value
  emit('update:appearance', value)
}

const handleNationalityChange = (value) => {
  localNationality.value = value
  emit('update:nationality', value)
}
</script>