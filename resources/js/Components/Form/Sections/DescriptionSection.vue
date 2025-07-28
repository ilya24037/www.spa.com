<template>
  <div class="description-section">
    <h2 class="form-group-title">О себе:</h2>
    <textarea 
      v-model="localDescription" 
      @input="emitDescription" 
      rows="8" 
      placeholder="Напишите подробное описание о себе и о своих услугах. Подробное, интересное, смысловое описание значительно увеличивает эффективность вашей анкеты." 
      class="description-textarea"
    ></textarea>
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

watch(() => props.description, val => { 
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
  min-height: 150px; 
  padding: 16px; 
  border: 1px solid #ddd; 
  border-radius: 8px; 
  font-size: 15px; 
  font-family: inherit; 
  resize: vertical; 
  line-height: 1.5;
  box-sizing: border-box;
  transition: border-color 0.2s ease;
}

.description-textarea:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.description-textarea::placeholder {
  color: #999;
  font-style: italic;
}
</style> 