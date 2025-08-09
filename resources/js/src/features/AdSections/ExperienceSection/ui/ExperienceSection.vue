<template>
  <div class="experience-section">
    <h2 class="form-group-title">Опыт работы</h2>
    <div class="experience-fields">
      <label>Опыт (лет):
        <input type="number" min="0" max="50" v-model="localExperience" @input="emitExperience" />
      </label>
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
.experience-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.experience-fields { display: flex; gap: 16px; align-items: center; }
</style> 