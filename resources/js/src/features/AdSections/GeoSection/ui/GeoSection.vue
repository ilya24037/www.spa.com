<template>
  <div class="geo-section">
    <h2 class="form-group-title">География</h2>
    <input type="text" v-model="localGeo" @input="emitGeo" placeholder="Введите город, район или метро..." class="geo-input" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  geo: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:geo'])
const localGeo = ref(props.geo)
watch(() => props.geo, val => { localGeo.value = val })
const emitGeo = () => {
  emit('update:geo', localGeo.value)
}
</script>

<style scoped>
.geo-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.geo-input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; font-family: inherit; }
</style>
