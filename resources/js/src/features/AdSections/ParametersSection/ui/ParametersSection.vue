<template>
  <div class="parameters-section">
    <h2 class="form-group-title">Физические параметры</h2>
    <div class="parameters-fields">
      <div class="parameter-field">
        <label>Рост (см):</label>
        <input type="number" min="100" max="250" v-model="localHeight" @input="emitAll" placeholder="170" />
      </div>
      <div class="parameter-field">
        <label>Вес (кг):</label>
        <input type="number" min="30" max="200" v-model="localWeight" @input="emitAll" placeholder="60" />
      </div>
      <div class="parameter-field">
        <label>Цвет волос:</label>
        <select v-model="localHairColor" @change="emitAll">
          <option value="">Выберите цвет</option>
          <option value="blonde">Блондинка</option>
          <option value="brunette">Брюнетка</option>
          <option value="redhead">Рыжая</option>
          <option value="black">Черные</option>
          <option value="brown">Каштановые</option>
          <option value="gray">Седые</option>
          <option value="colored">Цветные</option>
        </select>
      </div>
      <div class="parameter-field">
        <label>Цвет глаз:</label>
        <select v-model="localEyeColor" @change="emitAll">
          <option value="">Выберите цвет</option>
          <option value="blue">Голубые</option>
          <option value="green">Зеленые</option>
          <option value="brown">Карие</option>
          <option value="gray">Серые</option>
          <option value="black">Черные</option>
          <option value="hazel">Ореховые</option>
        </select>
      </div>
      <div class="parameter-field">
        <label>Национальность:</label>
        <select v-model="localNationality" @change="emitAll">
          <option value="">Выберите национальность</option>
          <option value="russian">Русская</option>
          <option value="ukrainian">Украинка</option>
          <option value="belarusian">Белоруска</option>
          <option value="kazakh">Казашка</option>
          <option value="uzbek">Узбечка</option>
          <option value="tajik">Таджичка</option>
          <option value="armenian">Армянка</option>
          <option value="georgian">Грузинка</option>
          <option value="azerbaijani">Азербайджанка</option>
          <option value="asian">Азиатка</option>
          <option value="european">Европейка</option>
          <option value="latin">Латиноамериканка</option>
          <option value="african">Африканка</option>
          <option value="mixed">Метиска</option>
          <option value="other">Другая</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  height: { type: [String, Number], default: '' },
  weight: { type: [String, Number], default: '' },
  hairColor: { type: String, default: '' },
  eyeColor: { type: String, default: '' },
  nationality: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:height', 'update:weight', 'update:hairColor', 'update:eyeColor', 'update:nationality'])
const localHeight = ref(props.height)
const localWeight = ref(props.weight)
const localHairColor = ref(props.hairColor)
const localEyeColor = ref(props.eyeColor)
const localNationality = ref(props.nationality)
watch(() => props.height, val => { localHeight.value = val })
watch(() => props.weight, val => { localWeight.value = val })
watch(() => props.hairColor, val => { localHairColor.value = val })
watch(() => props.eyeColor, val => { localEyeColor.value = val })
watch(() => props.nationality, val => { localNationality.value = val })
const emitAll = () => {
  emit('update:height', localHeight.value)
  emit('update:weight', localWeight.value)
  emit('update:hairColor', localHairColor.value)
  emit('update:eyeColor', localEyeColor.value)
  emit('update:nationality', localNationality.value)
}
</script>

<style scoped>
.parameters-section { 
  background: white; 
  border-radius: 8px; 
  padding: 20px; 
}

.form-group-title { 
  font-size: 18px; 
  font-weight: 600; 
  color: #333; 
  margin-bottom: 16px; 
}

.parameters-fields { 
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.parameter-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.parameter-field label {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.parameter-field input,
.parameter-field select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  background-color: #fff;
  transition: border-color 0.2s;
}

.parameter-field input:focus,
.parameter-field select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.parameter-field input[type="number"] {
  -moz-appearance: textfield;
}

.parameter-field input[type="number"]::-webkit-outer-spin-button,
.parameter-field input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style> 