<!-- resources/js/src/entities/ad/ui/AdStatus/AdStatusFilter.vue -->
<template>
  <div :class="containerClasses">
    <label v-if="label" :class="labelClasses">{{ label }}</label>
    
    <div :class="filtersClasses">
      <!-- РљРЅРѕРїРєР° "Р’СЃРµ" -->
      <button
        @click="selectStatus(null)"
        :class="getFilterButtonClasses(null)"
      >
        <span>Р’СЃРµ</span>
        <span v-if="counts && counts.total" :class="countClasses">
          {{ counts.total }}
        </span>
      </button>
      
      <!-- РљРЅРѕРїРєРё СЃС‚Р°С‚СѓСЃРѕРІ -->
      <button
        v-for="(statusData, statusKey) in filteredStatuses"
        :key="statusKey"
        @click="selectStatus(statusKey)"
        :class="getFilterButtonClasses(statusKey)"
      >
        <AdStatus 
          :status="statusKey" 
          :show-icon="showIcons"
          size="xs"
        />
        <span v-if="counts && counts[statusKey]" :class="countClasses">
          {{ counts[statusKey] }}
        </span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import AdStatus from './AdStatus.vue'

// рџЋЇ РЎС‚РёР»Рё СЃРѕРіР»Р°СЃРЅРѕ РґРёР·Р°Р№РЅ-СЃРёСЃС‚РµРјРµ
const CONTAINER_CLASSES = 'space-y-2'
const LABEL_CLASSES = 'block text-sm font-medium text-gray-700'
const FILTERS_CLASSES = 'flex flex-wrap gap-2'
const BUTTON_BASE_CLASSES = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-colors'
const BUTTON_ACTIVE_CLASSES = 'bg-blue-100 text-blue-800 border border-blue-200'
const BUTTON_INACTIVE_CLASSES = 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50'
const COUNT_CLASSES = 'text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded-full min-w-[20px] text-center'

// рџ“Љ Р’СЃРµ РґРѕСЃС‚СѓРїРЅС‹Рµ СЃС‚Р°С‚СѓСЃС‹ (РёР· Laravel Enum)
const ALL_STATUSES = {
  draft: { label: 'Черновик', order: 1 },
  waiting_payment: { label: 'Р–РґРµС‚ РѕРїР»Р°С‚С‹', order: 2 },
  active: { label: 'РђРєС‚РёРІРЅРѕРµ', order: 3 },
  archived: { label: 'Р’ Р°СЂС…РёРІРµ', order: 4 },
  expired: { label: 'РСЃС‚РµРєР»Рѕ', order: 5 },
  rejected: { label: 'РћС‚РєР»РѕРЅРµРЅРѕ', order: 6 },
  blocked: { label: 'Р—Р°Р±Р»РѕРєРёСЂРѕРІР°РЅРѕ', order: 7 }
}

const props = defineProps({
  modelValue: {
    type: [String, null],
    default: null
  },
  label: {
    type: String,
    default: ''
  },
  showIcons: {
    type: Boolean,
    default: true
  },
  allowedStatuses: {
    type: Array,
    default: () => Object.keys(ALL_STATUSES)
  },
  counts: {
    type: Object,
    default: () => ({})
  },
  hideEmptyStatuses: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const containerClasses = computed(() => CONTAINER_CLASSES)
const labelClasses = computed(() => LABEL_CLASSES)
const filtersClasses = computed(() => FILTERS_CLASSES)
const countClasses = computed(() => COUNT_CLASSES)

const filteredStatuses = computed(() => {
  let statuses = {}
  
  // Р¤РёР»СЊС‚СЂСѓРµРј РїРѕ СЂР°Р·СЂРµС€РµРЅРЅС‹Рј СЃС‚Р°С‚СѓСЃР°Рј
  props.allowedStatuses.forEach(statusKey => {
    if (ALL_STATUSES[statusKey]) {
      statuses[statusKey] = ALL_STATUSES[statusKey]
    }
  })
  
  // РЎРєСЂС‹РІР°РµРј РїСѓСЃС‚С‹Рµ СЃС‚Р°С‚СѓСЃС‹ РµСЃР»Рё РЅСѓР¶РЅРѕ
  if (props.hideEmptyStatuses && props.counts) {
    statuses = Object.fromEntries(
      Object.entries(statuses).filter(([key]) => 
        props.counts[key] && props.counts[key] > 0
      )
    )
  }
  
  // РЎРѕСЂС‚РёСЂСѓРµРј РїРѕ РїРѕСЂСЏРґРєСѓ
  return Object.fromEntries(
    Object.entries(statuses).sort(([,a], [,b]) => a.order - b.order)
  )
})

// РњРµС‚РѕРґС‹
const selectStatus = (status) => {
  emit('update:modelValue', status)
  emit('change', status)
}

const getFilterButtonClasses = (status) => {
  const isActive = props.modelValue === status
  
  return [
    BUTTON_BASE_CLASSES,
    isActive ? BUTTON_ACTIVE_CLASSES : BUTTON_INACTIVE_CLASSES
  ].join(' ')
}
</script>

