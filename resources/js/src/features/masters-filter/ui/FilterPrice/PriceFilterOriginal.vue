<!-- resources/js/Components/Filters/PriceFilter.vue -->
<template>
  <div>
    <h4 class="text-sm font-medium mb-2">
      РЎС‚РѕРёРјРѕСЃС‚СЊ, в‚Ѕ
    </h4>

    <!-- Р”РёР°РїР°Р·РѕРЅ В«РћС‚В» / В«Р”РѕВ» -->
    <div class="flex items-center gap-2">
      <input
        v-model.number="local.min"
        type="number"
        placeholder="РћС‚"
        min="0"
        class="w-24 border rounded px-2 py-1 text-sm"
        @input="emitChange"
      >
      <span class="text-gray-500">вЂ”</span>
      <input
        v-model.number="local.max"
        type="number"
        placeholder="Р”Рѕ"
        min="0"
        class="w-24 border rounded px-2 py-1 text-sm"
        @input="emitChange"
      >
    </div>
  </div>
</template>

<script setup>
import { reactive, watch, toRefs } from 'vue'

/**
 * v-model:price -> { min: Number|null, max: Number|null }
 */
const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ min: null, max: null })
    }
})
const emit = defineEmits(['update:modelValue'])

// Р»РѕРєР°Р»СЊРЅРѕРµ СЂРµР°РєС‚РёРІРЅРѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ, С‡С‚РѕР±С‹ РЅРµ С‚СЂРѕРіР°С‚СЊ СЂРѕРґРёС‚РµР»СЏ РґРѕ onInput
const local = reactive({
    min: props.modelValue.min ?? null,
    max: props.modelValue.max ?? null
})

watch(() => props.modelValue, v => {
    if (v.min !== local.min) local.min = v.min
    if (v.max !== local.max) local.max = v.max
})

function emitChange () {
    emit('update:modelValue', {
        min: local.min || null,
        max: local.max || null
    })
}
</script>

<style scoped>
/* РЅРµР±РѕР»СЊС€РёРµ РїСЂР°РІРєРё, С‡С‚РѕР±С‹ РёРЅРїСѓС‚С‹ Р±С‹Р»Рё РѕРґРЅРѕР№ РІС‹СЃРѕС‚С‹ */
input::-webkit-inner-spin-button,
input::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>


