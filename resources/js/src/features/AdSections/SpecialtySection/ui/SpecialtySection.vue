<template>
  <div class="specialty-section">
    <h2 class="form-group-title">Специальность</h2>
    <select v-model="localSpecialty" @change="emitSpecialty" class="form-select">
      <option value="">Выберите специальность</option>
      <option value="erotic_massage">Эротический массаж</option>
      <option value="classic_massage">Классический массаж</option>
      <option value="relax_massage">Расслабляющий массаж</option>
    </select>
    <div v-if="errors.specialty" class="error-message">
      {{ errors.specialty }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  specialty: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:specialty'])
const localSpecialty = ref(props.specialty)

watch(() => props.specialty, (val) => { localSpecialty.value = val })

const emitSpecialty = () => {
  emit('update:specialty', localSpecialty.value)
}
</script>

<style scoped>
.specialty-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.form-select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; }
.error-message { color: #dc3545; font-size: 0.875rem; margin-top: 0.5rem; }
</style>
