<!-- resources/js/src/features/masters-filter/ui/FilterLocation/FilterLocation.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <h4 :class="TITLE_CLASSES">РњРµСЃС‚РѕРїРѕР»РѕР¶РµРЅРёРµ</h4>
    
    <div :class="FIELDS_CONTAINER_CLASSES">
      <!-- Р“РѕСЂРѕРґ -->
      <div>
        <label :class="LABEL_CLASSES">Р“РѕСЂРѕРґ</label>
        <select
          :value="city"
          @change="$emit('update:city', $event.target.value || null)"
          :class="SELECT_CLASSES"
        >
          <option value="">Р›СЋР±РѕР№ РіРѕСЂРѕРґ</option>
          <option
            v-for="cityOption in availableCities"
            :key="cityOption.value"
            :value="cityOption.value"
          >
            {{ cityOption.label }}
          </option>
        </select>
      </div>

      <!-- Р Р°Р№РѕРЅ -->
      <div v-if="city">
        <label :class="LABEL_CLASSES">Р Р°Р№РѕРЅ</label>
        <select
          :value="district"
          @change="$emit('update:district', $event.target.value || null)"
          :class="SELECT_CLASSES"
        >
          <option value="">Р›СЋР±РѕР№ СЂР°Р№РѕРЅ</option>
          <option
            v-for="districtOption in availableDistricts"
            :key="districtOption.value"
            :value="districtOption.value"
          >
            {{ districtOption.label }}
          </option>
        </select>
      </div>

      <!-- РњРµС‚СЂРѕ -->
      <div v-if="city === 'moscow' || city === 'spb'">
        <label :class="LABEL_CLASSES">РњРµС‚СЂРѕ</label>
        <select
          :value="metro"
          @change="$emit('update:metro', $event.target.value || null)"
          :class="SELECT_CLASSES"
        >
          <option value="">Р›СЋР±РѕРµ РјРµС‚СЂРѕ</option>
          <option
            v-for="metroOption in availableMetro"
            :key="metroOption.value"
            :value="metroOption.value"
          >
            {{ metroOption.label }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CONTAINER_CLASSES = 'space-y-3'
const TITLE_CLASSES = 'font-medium text-gray-900'
const FIELDS_CONTAINER_CLASSES = 'space-y-3'
const LABEL_CLASSES = 'text-xs text-gray-600 mb-1 block'
const SELECT_CLASSES = 'w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm'

const props = defineProps({
  city: {
    type: String,
    default: null
  },
  district: {
    type: String,
    default: null
  },
  metro: {
    type: String,
    default: null
  }
})

defineEmits(['update:city', 'update:district', 'update:metro'])

// РЎС‚Р°С‚РёС‡РЅС‹Рµ РґР°РЅРЅС‹Рµ (РІ СЂРµР°Р»СЊРЅРѕРј РїСЂРѕРµРєС‚Рµ РјРѕР¶РЅРѕ Р·Р°РіСЂСѓР¶Р°С‚СЊ СЃ API)
const availableCities = [
  { value: 'moscow', label: 'РњРѕСЃРєРІР°' },
  { value: 'spb', label: 'РЎР°РЅРєС‚-РџРµС‚РµСЂР±СѓСЂРі' },
  { value: 'ekaterinburg', label: 'Р•РєР°С‚РµСЂРёРЅР±СѓСЂРі' },
  { value: 'novosibirsk', label: 'РќРѕРІРѕСЃРёР±РёСЂСЃРє' },
  { value: 'kazan', label: 'РљР°Р·Р°РЅСЊ' }
]

const availableDistricts = computed(() => {
  const districts = {
    moscow: [
      { value: 'center', label: 'Р¦РµРЅС‚СЂ' },
      { value: 'north', label: 'РЎРµРІРµСЂ' },
      { value: 'south', label: 'Р®Рі' },
      { value: 'east', label: 'Р’РѕСЃС‚РѕРє' },
      { value: 'west', label: 'Р—Р°РїР°Рґ' }
    ],
    spb: [
      { value: 'center', label: 'Р¦РµРЅС‚СЂР°Р»СЊРЅС‹Р№' },
      { value: 'vasilievsky', label: 'Р’Р°СЃРёР»СЊРµРІСЃРєРёР№ РѕСЃС‚СЂРѕРІ' },
      { value: 'petrograd', label: 'РџРµС‚СЂРѕРіСЂР°РґСЃРєРёР№' },
      { value: 'admiralty', label: 'РђРґРјРёСЂР°Р»С‚РµР№СЃРєРёР№' }
    ]
  }
  
  return districts[props.city] || []
})

const availableMetro = computed(() => {
  const metro = {
    moscow: [
      { value: 'sokolnicheskaya', label: 'РЎРѕРєРѕР»СЊРЅРёС‡РµСЃРєР°СЏ Р»РёРЅРёСЏ' },
      { value: 'zamoskvoretskaya', label: 'Р—Р°РјРѕСЃРєРІРѕСЂРµС†РєР°СЏ Р»РёРЅРёСЏ' },
      { value: 'arbatsko-pokrovskaya', label: 'РђСЂР±Р°С‚СЃРєРѕ-РџРѕРєСЂРѕРІСЃРєР°СЏ Р»РёРЅРёСЏ' }
    ],
    spb: [
      { value: 'kirovsko-vyborgskaya', label: 'РљРёСЂРѕРІСЃРєРѕ-Р’С‹Р±РѕСЂРіСЃРєР°СЏ Р»РёРЅРёСЏ' },
      { value: 'moskovsko-petrogradskaya', label: 'РњРѕСЃРєРѕРІСЃРєРѕ-РџРµС‚СЂРѕРіСЂР°РґСЃРєР°СЏ Р»РёРЅРёСЏ' },
      { value: 'nevsko-vasileostrovskaya', label: 'РќРµРІСЃРєРѕ-Р’Р°СЃРёР»РµРѕСЃС‚СЂРѕРІСЃРєР°СЏ Р»РёРЅРёСЏ' }
    ]
  }
  
  return metro[props.city] || []
})
</script>
