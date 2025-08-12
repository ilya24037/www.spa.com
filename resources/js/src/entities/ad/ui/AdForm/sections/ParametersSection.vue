<template>
  <div class="parameters-section">
    <h2 class="form-group-title">Обо мне</h2>
    <div class="parameters-fields">
      <label>Рост (см):
        <input type="number" min="100" max="250" v-model="localHeight" @input="emitAll" />
      </label>
      <label>Вес (кг):
        <input type="number" min="30" max="200" v-model="localWeight" @input="emitAll" />
      </label>
      <label>Цвет волос:
        <input type="text" v-model="localHairColor" @input="emitAll" />
      </label>
      <label>Цвет глаз:
        <input type="text" v-model="localEyeColor" @input="emitAll" />
      </label>
      <label>Национальность:
        <input type="text" v-model="localNationality" @input="emitAll" />
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({
  height: { type: [String, Number], default: '' },
  weight: { type: [String, Number], default: '' },
  hairColor: { type: String, default: '' },
  eyeColor: { type: String, default: '' },
  nationality: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})
const emit = defineEmits(['update:height', 'update:weight', 'update:hairColor', 'update:eyeColor', 'update:nationality'])
const localHeight = ref(props.height)
const localWeight = ref(props.weight)
const localHairColor = ref(props.hairColor)
const localEyeColor = ref(props.eyeColor)
const localNationality = ref(props.nationality)
watch(() => props.height, val => { localHeight.value = val })
watch(() => props.weight, val => { localWeight.value = val })
watch(() => props.hairColor, val => { localHairColor.value = val })
watch(() => props.eyeColor, val => { localEyeColor.value = val })
watch(() => props.nationality, val => { localNationality.value = val })
const emitAll = () => {
  emit('update:height', localHeight.value)
  emit('update:weight', localWeight.value)
  emit('update:hairColor', localHairColor.value)
  emit('update:eyeColor', localEyeColor.value)
  emit('update:nationality', localNationality.value)
}
</script>

<style scoped>
.parameters-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.parameters-fields { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
</style> 