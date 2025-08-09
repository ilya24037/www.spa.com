<template>
  <div class="description-section">
    <h2 class="form-group-title">Описание</h2>
    <textarea 
      v-model="localDescription" 
      @input="emitDescription" 
      rows="5" 
      placeholder="Расскажите о себе, услугах, особенностях..." 
      class="description-textarea"
      :class="{ 'border-red-500': errors.description }"
    ></textarea>
    <div v-if="errors.description" class="error-message">
      {{ errors.description }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  description: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:description'])

const localDescription = ref(props.description)

watch(() => props.description, (val) => { 
  localDescription.value = val 
})

const emitDescription = () => {
  emit('update:description', localDescription.value)
}
</script>

<style scoped>
.description-section { 
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

.description-textarea { 
  width: 100%; 
  min-height: 120px; 
  padding: 12px; 
  border: 1px solid #ddd; 
  border-radius: 6px; 
  font-size: 15px; 
  font-family: inherit; 
  resize: vertical;
  transition: border-color 0.2s;
}

.description-textarea:focus {
  outline: none;
  border-color: #007bff;
}

.border-red-500 {
  border-color: #dc3545 !important;
}

.error-message {
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}
</style>
