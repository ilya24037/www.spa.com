<template>
  <div class="parameters-section">
    <h2 class="form-group-title">Физические параметры</h2>
    <div class="parameters-fields">
      <div class="field-group">
        <label class="select-label">Возраст:</label>
        <select v-model="localAge" @change="handleAgeChange" class="age-select">
          <option value="">- Выбрать -</option>
          <option value="2007">18</option>
          <option value="2006">19</option>
          <option value="2005">20</option>
          <option value="2004">21</option>
          <option value="2003">22</option>
          <option value="2002">23</option>
          <option value="2001">24</option>
          <option value="2000">25</option>
          <option value="1999">26</option>
          <option value="1998">27</option>
          <option value="1997">28</option>
          <option value="1996">29</option>
          <option value="1995">30</option>
          <option value="1994">31</option>
          <option value="1993">32</option>
          <option value="1992">33</option>
          <option value="1991">34</option>
          <option value="1990">35</option>
          <option value="1989">36</option>
          <option value="1988">37</option>
          <option value="1987">38</option>
          <option value="1986">39</option>
          <option value="1985">40</option>
          <option value="1984">41</option>
          <option value="1983">42</option>
          <option value="1982">43</option>
          <option value="1981">44</option>
          <option value="1980">45</option>
          <option value="1979">46</option>
          <option value="1978">47</option>
          <option value="1977">48</option>
          <option value="1976">49</option>
          <option value="1975">50</option>
          <option value="1974">51</option>
          <option value="1973">52</option>
          <option value="1972">53</option>
          <option value="1971">54</option>
          <option value="1970">55</option>
          <option value="1969">56</option>
          <option value="1968">57</option>
          <option value="1967">58</option>
          <option value="1966">59</option>
          <option value="1965">60</option>
        </select>
      </div>
      
      <div class="field-group">
        <BaseInput
          v-model="localHeight"
          type="number"
          label="Рост (см):"
          placeholder="165"
          :min="100"
          :max="250"
          @update:modelValue="handleHeightChange"
        />
      </div>
      
      <div class="field-group">
        <BaseInput
          v-model="localWeight"
          type="number"
          label="Вес (кг):"
          placeholder="55"
          :min="30"
          :max="200"
          @update:modelValue="handleWeightChange"
        />
      </div>
      
      <div class="field-group">
        <label class="select-label">Размер груди:</label>
        <select v-model="localBreastSize" @change="handleBreastSizeChange" class="breast-select">
          <option value="">- Выбрать -</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option value="6">6</option>
          <option value="7">7</option>
        </select>
      </div>
      
      <div class="field-group">
        <label class="select-label">Цвет волос:</label>
        <select v-model="localHairColor" @change="handleHairColorChange" class="hair-select">
          <option value="">- Выбрать -</option>
          <option value="hair_blondinki">Блондинки</option>
          <option value="hair_brunetki">Брюнетки</option>
          <option value="hair_rusye">Русые</option>
          <option value="hair_ryzhie">Рыжие</option>
          <option value="hair_cvetnye">Цветные</option>
          <option value="hair_shatenki">Шатенки</option>
        </select>
      </div>
      
      <div class="field-group">
        <label class="select-label">Цвет глаз:</label>
        <select v-model="localEyeColor" @change="handleEyeColorChange" class="eyes-select">
          <option value="">- Выбрать -</option>
          <option value="eyes_bolotnyi">Болотный</option>
          <option value="eyes_golubye">Голубой</option>
          <option value="eyes_zheltye">Желтый</option>
          <option value="eyes_zelenye">Зеленый</option>
          <option value="eyes_karie">Карий</option>
          <option value="eyes_olivkovyi">Оливковый</option>
          <option value="eyes_serye">Серый</option>
          <option value="eyes_sinii">Синий</option>
          <option value="eyes_black">Черный</option>
          <option value="eyes_yantarnyi">Янтарный</option>
        </select>
      </div>
      
      <div class="field-group">
        <label class="select-label">Внешность:</label>
        <select v-model="localAppearance" @change="handleAppearanceChange" class="appearance-select">
          <option value="">---</option>
          <option value="Славянская">Славянская</option>
          <option value="Азиатская">Азиатская</option>
          <option value="Африканская">Африканская</option>
          <option value="Кавказская">Кавказская</option>
          <option value="Латино">Латино</option>
          <option value="Мулатка">Мулатка</option>
          <option value="Метиска">Метиска</option>
          <option value="Европейская">Европейская</option>
          <option value="Скандинавская">Скандинавская</option>
          <option value="Средиземноморская">Средиземноморская</option>
        </select>
      </div>
      
      <div class="field-group">
        <label class="select-label">Национальность:</label>
        <select v-model="localNationality" @change="handleNationalityChange" class="nationality-select">
          <option value="">---</option>
          <option value="Русские">Русские</option>
          <option value="Украинки">Украинки</option>
          <option value="Азиатки">Азиатки</option>
          <option value="Негритянки">Негритянки</option>
          <option value="Белоруски">Белоруски</option>
          <option value="Татарки">Татарки</option>
          <option value="Абхазки">Абхазки</option>
          <option value="Адыгейки">Адыгейки</option>
          <option value="Азербайджанки">Азербайджанки</option>
          <option value="Албанки">Албанки</option>
          <option value="Алтайки">Алтайки</option>
          <option value="Армянки">Армянки</option>
          <option value="Башкирки">Башкирки</option>
          <option value="Бурятки">Бурятки</option>
          <option value="Грузинки">Грузинки</option>
          <option value="Еврейки">Еврейки</option>
          <option value="Кабардинки">Кабардинки</option>
          <option value="Казашки">Казашки</option>
          <option value="Киргизки">Киргизки</option>
          <option value="Китаянки">Китаянки</option>
          <option value="Кореянки">Кореянки</option>
          <option value="Крымчанки">Крымчанки</option>
          <option value="Марийки">Марийки</option>
          <option value="Монголки">Монголки</option>
          <option value="Осетинки">Осетинки</option>
          <option value="Таджички">Таджички</option>
          <option value="Удмуртки">Удмуртки</option>
          <option value="Узбечки">Узбечки</option>
          <option value="Цыганки">Цыганки</option>
          <option value="Якутки">Якутки</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseInput from '@/src/shared/ui/atoms/TextInput/TextInput.vue'

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

const emit = defineEmits(['update:age', 'update:height', 'update:weight', 'update:breastSize', 'update:hairColor', 'update:eyeColor', 'update:appearance', 'update:nationality'])

const localAge = ref(props.age || '')
const localHeight = ref(props.height || '')
const localWeight = ref(props.weight || '')
const localBreastSize = ref(props.breastSize || '')
const localHairColor = ref(props.hairColor || '')
const localEyeColor = ref(props.eyeColor || '')
const localAppearance = ref(props.appearance || '')
const localNationality = ref(props.nationality || '')

watch(() => props.age, val => { localAge.value = val || '' })
watch(() => props.height, val => { localHeight.value = val || '' })
watch(() => props.weight, val => { localWeight.value = val || '' })
watch(() => props.breastSize, val => { localBreastSize.value = val || '' })
watch(() => props.hairColor, val => { localHairColor.value = val || '' })
watch(() => props.eyeColor, val => { localEyeColor.value = val || '' })
watch(() => props.appearance, val => { localAppearance.value = val || '' })
watch(() => props.nationality, val => { localNationality.value = val || '' })

// Отдельные обработчики для каждого поля
const handleAgeChange = () => {
  emit('update:age', localAge.value)
}

const handleHeightChange = (value) => {
  localHeight.value = value
  emit('update:height', value)
}

const handleWeightChange = (value) => {
  localWeight.value = value
  emit('update:weight', value)
}

const handleBreastSizeChange = () => {
  emit('update:breastSize', localBreastSize.value)
}

const handleHairColorChange = () => {
  emit('update:hairColor', localHairColor.value)
}

const handleEyeColorChange = () => {
  emit('update:eyeColor', localEyeColor.value)
}

const handleAppearanceChange = () => {
  emit('update:appearance', localAppearance.value)
}

const handleNationalityChange = () => {
  emit('update:nationality', localNationality.value)
}

const emitAll = () => {
  emit('update:age', localAge.value)
  emit('update:height', localHeight.value)
  emit('update:weight', localWeight.value)
  emit('update:breastSize', localBreastSize.value)
  emit('update:hairColor', localHairColor.value)
  emit('update:eyeColor', localEyeColor.value)
  emit('update:appearance', localAppearance.value)
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

.field-group {
  width: 100%;
}

.select-label {
  display: block;
  font-size: 16px;
  font-weight: 500;
  color: #000000;
  margin-bottom: 8px;
}

.age-select {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e5e5;
  border-radius: 8px;
  background: #f5f5f5;
  font-size: 16px;
  color: #1a1a1a;
  transition: all 0.2s ease;
  min-height: 48px;
  box-sizing: border-box;
}

.age-select:hover {
  border-color: #d0d0d0;
}

.age-select:focus {
  outline: none;
  border-color: #2196f3;
  background: #fff;
}

/* Единые стили для всех select'ов */
.hair-select,
.eyes-select,
.appearance-select,
.nationality-select,
.breast-select {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e5e5;
  border-radius: 8px;
  background: #f5f5f5;
  font-size: 16px;
  color: #1a1a1a;
  transition: all 0.2s ease;
  min-height: 48px;
  box-sizing: border-box;
}

.hair-select:hover,
.eyes-select:hover,
.appearance-select:hover,
.nationality-select:hover,
.breast-select:hover {
  border-color: #d0d0d0;
}

.hair-select:focus,
.eyes-select:focus,
.appearance-select:focus,
.nationality-select:focus,
.breast-select:focus {
  outline: none;
  border-color: #2196f3;
  background: #fff;
}
</style> 

