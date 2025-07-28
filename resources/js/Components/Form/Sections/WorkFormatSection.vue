<template>
  <div class="work-format-section">
    <h2 class="form-group-title">Формат работы</h2>
    <div class="work-format-fields">
      <label>
        <input type="radio" value="individual" v-model="localWorkFormat" @change="emitWorkFormat" /> Индивидуально
      </label>
      <label>
        <input type="radio" value="salon" v-model="localWorkFormat" @change="emitWorkFormat" /> Салон
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  workFormat: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:workFormat'])
const localWorkFormat = ref(props.workFormat)
watch(() => props.workFormat, val => { localWorkFormat.value = val })
const emitWorkFormat = () => {
  emit('update:workFormat', localWorkFormat.value)
}
</script>

<style scoped>
.work-format-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.work-format-fields { display: flex; gap: 16px; align-items: center; }
</style> 