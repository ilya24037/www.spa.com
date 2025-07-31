<template>
  <FormSection
    title="Физические параметры"
    hint="Укажите ваши физические параметры для лучшего поиска"
    :errors="errors"
    :error-keys="['age', 'height', 'weight', 'breast_size', 'hair_color', 'eye_color', 'appearance', 'nationality']"
  >
    <div class="parameters-grid">
      <!-- Возраст -->
      <FormField
        label="Возраст"
        hint="Укажите ваш возраст"
        :error="errors.age"
      >
        <input
          v-model="localAge"
          @input="updateAge"
          type="number"
          min="18"
          max="65"
          placeholder="25"
        />
      </FormField>

      <!-- Рост -->
      <FormField
        label="Рост (см)"
        hint="Укажите ваш рост в сантиметрах"
        :error="errors.height"
      >
        <input
          v-model="localHeight"
          @input="updateHeight"
          type="number"
          min="140"
          max="200"
          placeholder="165"
        />
      </FormField>

      <!-- Вес -->
      <FormField
        label="Вес (кг)"
        hint="Укажите ваш вес в килограммах"
        :error="errors.weight"
      >
        <input
          v-model="localWeight"
          @input="updateWeight"
          type="number"
          min="40"
          max="150"
          placeholder="55"
        />
      </FormField>

      <!-- Размер груди -->
      <FormField
        label="Размер груди"
        hint="Укажите размер груди"
        :error="errors.breast_size"
      >
        <select v-model="localBreastSize" @change="updateBreastSize">
          <option value="">Выберите размер</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option value="6">6</option>
        </select>
      </FormField>

      <!-- Цвет волос -->
      <FormField
        label="Цвет волос"
        hint="Укажите цвет ваших волос"
        :error="errors.hair_color"
      >
        <select v-model="localHairColor" @change="updateHairColor">
          <option value="">Выберите цвет</option>
          <option value="blonde">Блондинка</option>
          <option value="brunette">Брюнетка</option>
          <option value="brown">Шатенка</option>
          <option value="red">Рыжая</option>
          <option value="gray">Седая</option>
          <option value="colored">Цветные</option>
        </select>
      </FormField>

      <!-- Цвет глаз -->
      <FormField
        label="Цвет глаз"
        hint="Укажите цвет ваших глаз"
        :error="errors.eye_color"
      >
        <select v-model="localEyeColor" @change="updateEyeColor">
          <option value="">Выберите цвет</option>
          <option value="blue">Голубой</option>
          <option value="green">Зеленый</option>
          <option value="brown">Карий</option>
          <option value="gray">Серый</option>
          <option value="hazel">Ореховый</option>
        </select>
      </FormField>

      <!-- Внешность -->
      <FormField
        label="Внешность"
        hint="Опишите тип внешности"
        :error="errors.appearance"
      >
        <select v-model="localAppearance" @change="updateAppearance">
          <option value="">Выберите тип</option>
          <option value="slavic">Славянская</option>
          <option value="european">Европейская</option>
          <option value="asian">Азиатская</option>
          <option value="caucasian">Кавказская</option>
          <option value="mixed">Смешанная</option>
        </select>
      </FormField>

      <!-- Национальность -->
      <FormField
        label="Национальность"
        hint="Укажите вашу национальность"
        :error="errors.nationality"
      >
        <select v-model="localNationality" @change="updateNationality">
          <option value="">Выберите национальность</option>
          <option value="russian">Русская</option>
          <option value="ukrainian">Украинка</option>
          <option value="belarusian">Белоруска</option>
          <option value="kazakh">Казашка</option>
          <option value="azerbaijani">Азербайджанка</option>
          <option value="armenian">Армянка</option>
          <option value="georgian">Грузинка</option>
          <option value="other">Другая</option>
        </select>
      </FormField>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  age: { type: [String, Number], default: '' },
  height: { type: [String, Number], default: '' },
  weight: { type: [String, Number], default: '' },
  breastSize: { type: [String, Number], default: '' },
  hairColor: { type: String, default: '' },
  eyeColor: { type: String, default: '' },
  appearance: { type: String, default: '' },
  nationality: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:age', 'update:height', 'update:weight', 'update:breastSize',
  'update:hairColor', 'update:eyeColor', 'update:appearance', 'update:nationality'
])

// Локальное состояние (конвертируем числа в строки для v-model)
const localAge = ref(String(props.age || ''))
const localHeight = ref(String(props.height || ''))
const localWeight = ref(String(props.weight || ''))
const localBreastSize = ref(String(props.breastSize || ''))
const localHairColor = ref(props.hairColor)
const localEyeColor = ref(props.eyeColor)
const localAppearance = ref(props.appearance)
const localNationality = ref(props.nationality)

// Отслеживание изменений пропсов (конвертируем числа в строки)
watch(() => props.age, (newValue) => { localAge.value = String(newValue || '') })
watch(() => props.height, (newValue) => { localHeight.value = String(newValue || '') })
watch(() => props.weight, (newValue) => { localWeight.value = String(newValue || '') })
watch(() => props.breastSize, (newValue) => { localBreastSize.value = String(newValue || '') })
watch(() => props.hairColor, (newValue) => { localHairColor.value = newValue })
watch(() => props.eyeColor, (newValue) => { localEyeColor.value = newValue })
watch(() => props.appearance, (newValue) => { localAppearance.value = newValue })
watch(() => props.nationality, (newValue) => { localNationality.value = newValue })

// Методы обновления
const updateAge = () => emit('update:age', localAge.value)
const updateHeight = () => emit('update:height', localHeight.value)
const updateWeight = () => emit('update:weight', localWeight.value)
const updateBreastSize = () => emit('update:breastSize', localBreastSize.value)
const updateHairColor = () => emit('update:hairColor', localHairColor.value)
const updateEyeColor = () => emit('update:eyeColor', localEyeColor.value)
const updateAppearance = () => emit('update:appearance', localAppearance.value)
const updateNationality = () => emit('update:nationality', localNationality.value)
</script>

<style scoped>
.parameters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 16px;
}

@media (max-width: 768px) {
  .parameters-grid {
    grid-template-columns: 1fr;
  }
}
</style>