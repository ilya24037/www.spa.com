<!-- resources/js/Components/Filters/ServiceFilter.vue (fixed) -->
<template>
  <div>
    <h4 class="text-sm font-medium mb-2">
      РЈСЃР»СѓРіР°
    </h4>

    <!-- РџРѕРёСЃРє РїРѕ СЃРїРёСЃРєСѓ -->
    <input
      v-model="search"
      type="text"
      placeholder="РќР°Р№С‚Рё СѓСЃР»СѓРіСѓвЂ¦"
      class="mb-2 w-full border rounded px-2 py-1 text-sm"
    >

    <!-- РЎРїРёСЃРѕРє С‡РµРєР±РѕРєСЃРѕРІ -->
    <div class="max-h-60 overflow-y-auto pr-1 space-y-1">
      <label
        v-for="opt in filteredOptions"
        :key="opt.value"
        class="flex items-center gap-2 text-sm"
      >
        <input
          v-model="localSelected"
          type="checkbox"
          :value="opt.value"
          class="shrink-0 rounded border-gray-500 focus:ring-blue-500"
        >
        <span>{{ opt.label }}</span>
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

/* в”Ђв”Ђв”Ђ props & emits в”Ђв”Ђв”Ђ */
const emit = defineEmits(['update:modelValue'])
const props = defineProps({
    /** Р’С‹Р±СЂР°РЅРЅС‹Рµ СѓСЃР»СѓРіРё */
    modelValue: { type: Array, default: () => [] },
    /** РњР°СЃСЃРёРІ { value, label } РІСЃРµС… РґРѕСЃС‚СѓРїРЅС‹С… СѓСЃР»СѓРі */
    options:    { type: Array, default: () => [] },
})

/* в”Ђв”Ђв”Ђ Р»РѕРєР°Р»СЊРЅРѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ в”Ђв”Ђв”Ђ */
const search        = ref('')
// РІСЃРµРіРґР° РјР°СЃСЃРёРІ, РґР°Р¶Рµ РµСЃР»Рё СЃРІРµСЂС…Сѓ РїСЂРёС€С‘Р» null/undefined
const localSelected = ref(Array.isArray(props.modelValue) ? [...props.modelValue] : [])

/* СЃРёРЅС…СЂРѕРЅРёР·Р°С†РёСЏ РІРЅРёР· в†’ РІРІРµСЂС… */
watch(localSelected, v => emit('update:modelValue', v))
/* Рё РЅР°РѕР±РѕСЂРѕС‚ (РµСЃР»Рё СЂРѕРґРёС‚РµР»СЊ СЃР±СЂРѕСЃРёР» С„РёР»СЊС‚СЂ) */
watch(
    () => props.modelValue,
    v => {
        if (Array.isArray(v)) localSelected.value = [...v]
        else localSelected.value = []
    },
)

/* в”Ђв”Ђв”Ђ РїРѕРёСЃРє РїРѕ СЃРїРёСЃРєСѓ в”Ђв”Ђв”Ђ */
const filteredOptions = computed(() => {
    const q = search.value.trim().toLowerCase()
    return q
        ? props.options.filter(o => o.label.toLowerCase().includes(q))
        : props.options
})
</script>


