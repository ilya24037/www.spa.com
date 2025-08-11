<template>
  <div class="specialty-section">
    <h2 class="form-group-title">Специальность или сфера</h2>
    <select v-model="localSpecialty" @change="emitSpecialty" class="form-select">
      <option value="">Выберите специальность</option>
      <optgroup label="Массаж">
        <option value="erotic_massage">Эротический массаж</option>
        <option value="classic_massage">Классический массаж</option>
        <option value="relax_massage">Расслабляющий массаж</option>
        <option value="thai_massage">Тайский массаж</option>
        <option value="tantric_massage">Тантрический массаж</option>
        <option value="body_massage">Боди-массаж</option>
        <option value="nuru_massage">Нуру массаж</option>
      </optgroup>
      <optgroup label="Косметология">
        <option value="cosmetologist">Косметолог</option>
        <option value="beautician">Визажист</option>
        <option value="manicure">Мастер маникюра</option>
        <option value="pedicure">Мастер педикюра</option>
        <option value="eyebrows">Бровист</option>
        <option value="depilation">Мастер депиляции</option>
      </optgroup>
      <optgroup label="Развлечения">
        <option value="stripper">Стриптиз</option>
        <option value="gogo">Go-go танцы</option>
        <option value="escort">Эскорт</option>
        <option value="model">Модель</option>
        <option value="hostess">Хостес</option>
      </optgroup>
      <optgroup label="Другое">
        <option value="photographer">Фотограф</option>
        <option value="psychologist">Психолог</option>
        <option value="coach">Тренер</option>
        <option value="other">Другая специальность</option>
      </optgroup>
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
