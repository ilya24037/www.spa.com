<template>
  <div class="experience-section">
    <h2 class="form-group-title">Опыт работы</h2>
    <div class="experience-fields">
      <div class="field-wrapper">
        <label class="field-label">Опыт (лет):</label>
        <select v-model="localExperience" @change="emitExperience" class="experience-select">
          <option value="">Выберите опыт</option>
          <option value="0">Без опыта</option>
          <option value="1">1 год</option>
          <option value="2">2 года</option>
          <option value="3">3 года</option>
          <option value="4">4 года</option>
          <option value="5">5 лет</option>
          <option value="6">6 лет</option>
          <option value="7">7 лет</option>
          <option value="8">8 лет</option>
          <option value="9">9 лет</option>
          <option value="10">10 лет</option>
          <option value="11">11 лет</option>
          <option value="12">12 лет</option>
          <option value="13">13 лет</option>
          <option value="14">14 лет</option>
          <option value="15">15 лет</option>
          <option value="16">16 лет</option>
          <option value="17">17 лет</option>
          <option value="18">18 лет</option>
          <option value="19">19 лет</option>
          <option value="20">20 лет</option>
          <option value="20+">Более 20 лет</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  experience: { type: [String, Number], default: '' },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:experience'])
const localExperience = ref(props.experience)
watch(() => props.experience, val => { localExperience.value = val })
const emitExperience = () => {
  emit('update:experience', localExperience.value)
}
</script>

<style scoped>
.experience-section { 
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

.experience-fields { 
  display: flex; 
  gap: 16px; 
  align-items: center; 
}

.field-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
}

.field-label {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.experience-select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  background: white;
  min-width: 200px;
  cursor: pointer;
  transition: border-color 0.2s;
}

.experience-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.experience-select:hover {
  border-color: #999;
}
</style> 