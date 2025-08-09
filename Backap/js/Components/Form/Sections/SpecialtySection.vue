<template>
  <div class="specialty-section">
    <h2 class="form-group-title">Специальность или сфера</h2>
    <div class="specialty-fields">
      <select v-model="localSpecialty" @change="emitSpecialty">
        <option value="">Выберите специальность</option>
        <option value="massage">Массажист</option>
        <option value="cosmetologist">Косметолог</option>
        <option value="stripper">Стриптизёр/Стриптизёрша</option>
        <option value="escort">Эскорт</option>
        <!-- Добавь другие опции по необходимости -->
      </select>
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
watch(() => props.specialty, val => { localSpecialty.value = val })
const emitSpecialty = () => {
  emit('update:specialty', localSpecialty.value)
}
</script>

<style scoped>
.specialty-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.specialty-fields { display: flex; gap: 16px; align-items: center; }
</style> 