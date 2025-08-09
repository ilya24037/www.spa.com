<template>
  <div class="clients-section">
    <h2 class="form-group-title">Ваши клиенты</h2>
    <div class="checkbox-group">
      <label class="checkbox-label">
        <input type="checkbox" value="men" v-model="localClients" @change="emitClients"> Мужчины
      </label>
      <label class="checkbox-label">
        <input type="checkbox" value="women" v-model="localClients" @change="emitClients"> Женщины
      </label>
      <label class="checkbox-label">
        <input type="checkbox" value="couples" v-model="localClients" @change="emitClients"> Пары
      </label>
    </div>
    <div v-if="errors.clients" class="error-message">{{ errors.clients }}</div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  clients: { type: Array, default: () => [] },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:clients'])
const localClients = ref([...props.clients])

watch(() => props.clients, (val) => { localClients.value = [...val] })

const emitClients = () => {
  emit('update:clients', localClients.value)
}
</script>

<style scoped>
.clients-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.checkbox-group { display: flex; flex-direction: column; gap: 12px; }
.checkbox-label { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.error-message { color: #dc3545; font-size: 0.875rem; margin-top: 0.5rem; }
</style>
