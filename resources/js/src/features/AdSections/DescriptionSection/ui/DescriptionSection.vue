<template>
  <div class="description-section">
    <h2 class="form-group-title">О себе</h2>
    <BaseTextarea
      v-model="localDescription"
      placeholder="Расскажите о себе, услугах, особенностях..."
      :rows="5"
      :error="errors.description"
      :maxlength="2000"
      :show-counter="true"
      @update:modelValue="emitDescription"
    />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseTextarea from '@/src/shared/ui/atoms/BaseTextarea/BaseTextarea.vue'

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

/* Стили для textarea теперь в компоненте BaseTextarea */
</style>
