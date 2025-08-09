<template>
  <div class="form-field">
    <label class="field-label">Название объявления</label>
    <div class="input-wrapper">
      <input
        v-model="localTitle"
        @input="emitTitle"
        type="text"
        name="title"
        id="title"
        data-field="title"
        class="avito-input"
        :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.title }"
        placeholder=""
        maxlength="60"
        required
      >
      <button 
        v-if="localTitle" 
        type="button" 
        @click="clearTitle" 
        class="clear-button"
      >
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M9.00001 10.8428L16.15 17.9928L17.5642 16.5786L10.4142 9.42857L17.5643 2.27851L16.1501 0.864296L9.00001 8.01436L1.84994 0.864296L0.43573 2.27851L7.58579 9.42857L0.435787 16.5786L1.85 17.9928L9.00001 10.8428Z" fill="currentColor"></path>
        </svg>
      </button>
    </div>
    
    <p class="field-hint">
      Например, «Маникюр, педикюр и наращивание ногтей» или «Ремонт квартир под ключ»
    </p>
    
    <div v-if="errors.title" class="error-message">
      {{ errors.title }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  title: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:title'])

const localTitle = ref(props.title)

watch(() => props.title, (newValue) => {
  localTitle.value = newValue
})

const emitTitle = () => {
  emit('update:title', localTitle.value)
}

const clearTitle = () => {
  localTitle.value = ''
  emitTitle()
}
</script>

<style scoped>
.form-field {
  margin-bottom: 1.5rem;
}

.field-label {
  display: block;
  font-size: 1rem;
  font-weight: 600;
  color: #333;
  margin-bottom: 0.5rem;
}

.input-wrapper {
  position: relative;
}

.avito-input {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 0.75rem;
  border: 2px solid #ddd;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.avito-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.clear-button {
  position: absolute;
  right: 0.5rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
}

.clear-button:hover {
  background: #f5f5f5;
  color: #333;
}

.field-hint {
  font-size: 0.875rem;
  color: #666;
  margin-top: 0.5rem;
  margin-bottom: 0;
}

.error-message {
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}
</style>
