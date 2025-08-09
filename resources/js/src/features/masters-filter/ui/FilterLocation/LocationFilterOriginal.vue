<!-- resources/js/Components/Filters/LocationFilter.vue -->
<template>
  <div>
    <h4 class="text-sm font-medium mb-2">
      Р“РѕСЂРѕРґ
    </h4>

    <!-- РџРѕР»Рµ РїРѕРёСЃРєР° -->
    <input
      v-model="search"
      type="text"
      placeholder="РќР°Р№С‚Рё РіРѕСЂРѕРґвЂ¦"
      class="mb-2 w-full border rounded px-2 py-1 text-sm"
    >

    <!-- РЎРїРёСЃРѕРє РіРѕСЂРѕРґРѕРІ -->
    <div class="max-h-60 overflow-y-auto pr-1 space-y-1">
      <label
        v-for="city in filteredCities"
        :key="city"
        class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-500 rounded px-2 py-1"
      >
        <input
          v-model="model"
          type="radio"
          :value="city"
          class="accent-blue-600"
        >
        <span>{{ city }}</span>
      </label>
    </div>

    <!-- РљРЅРѕРїРєР° СЃР±СЂРѕСЃР° -->
    <button
      v-if="model"
      class="mt-2 text-sm text-blue-600 hover:underline"
      @click="clear"
    >
      РЎР±СЂРѕСЃРёС‚СЊ РіРѕСЂРѕРґ
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'

/**
 * Props
 * - modelValue: string | null  (РІС‹Р±СЂР°РЅРЅС‹Р№ РіРѕСЂРѕРґ)
 * - cities: string[]            (СЃРїРёСЃРѕРє РґРѕСЃС‚СѓРїРЅС‹С… РіРѕСЂРѕРґРѕРІ)
 */
const props = withDefaults(defineProps<{
  modelValue?: string | null
  cities?: string[]
}>(), {
    modelValue: null,
    cities: () => [
        'РњРѕСЃРєРІР°', 'РЎР°РЅРєС‚вЂ‘РџРµС‚РµСЂР±СѓСЂРі', 'РќРѕРІРѕСЃРёР±РёСЂСЃРє', 'Р•РєР°С‚РµСЂРёРЅР±СѓСЂРі', 'РќРёР¶РЅРёР№ РќРѕРІРіРѕСЂРѕРґ',
        'РљР°Р·Р°РЅСЊ', 'Р§РµР»СЏР±РёРЅСЃРє', 'РЎР°РјР°СЂР°', 'РЈС„Р°', 'Р РѕСЃС‚РѕРІвЂ‘РЅР°вЂ‘Р”РѕРЅСѓ',
    ],
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string | null): void
}>()

// Р»РѕРєР°Р»СЊРЅРѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ
const search = ref('')

// 2вЂ‘way binding С‡РµСЂРµР· computed
const model = computed({
    get: () => props.modelValue,
    set: (val) => emit('update:modelValue', val),
})

// С„РёР»СЊС‚СЂР°С†РёСЏ РїРѕ РїРѕРёСЃРєСѓ (СЂРµРіРёСЃС‚СЂ РЅРµРІР°Р¶РµРЅ)
const filteredCities = computed(() => {
    if (!search.value) return props.cities
    const q = search.value.toLowerCase()
    return props.cities.filter((c) => c.toLowerCase().includes(q))
})

function clear() {
    emit('update:modelValue', null)
    search.value = ''
}

// РѕС‡РёСЃС‚РёС‚СЊ СЃС‚СЂРѕРєСѓ РїРѕРёСЃРєР°, РєРѕРіРґР° РІС‹Р±СЂР°РЅ РЅРѕРІС‹Р№ РіРѕСЂРѕРґ
watch(model, () => (search.value = ''))
</script>

<style scoped>
/* С‚РѕРЅРєРёР№ СЃРєСЂРѕР»Р»Р±Р°СЂ */
.max-h-60::-webkit-scrollbar {
  width: 6px;
}
.max-h-60::-webkit-scrollbar-thumb {
  background: #c3cbd1;
  border-radius: 3px;
}
</style>


