<template>
  <div :id="sectionId" :class="SECTION_CLASSES">
    <div v-if="title" :class="HEADER_CLASSES">
      <h2 :class="TITLE_CLASSES">
        {{ title }}
      </h2>
      <div v-if="subtitle" :class="SUBTITLE_CLASSES">
        {{ subtitle }}
      </div>
    </div>
    
    <div :class="CONTENT_CLASSES">
      <slot />
    </div>
    
    <div v-if="hasActions" :class="ACTIONS_CLASSES">
      <slot name="actions" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

// рџЋЇ РЎС‚РёР»Рё РІ СЃРѕРѕС‚РІРµС‚СЃС‚РІРёРё СЃ Tailwind CSS
const SECTION_CLASSES = 'mb-10 py-6 last:mb-0'
const HEADER_CLASSES = 'mb-6'
const TITLE_CLASSES = 'text-xl font-semibold text-gray-500 mb-2'
const SUBTITLE_CLASSES = 'text-sm text-gray-500'
const CONTENT_CLASSES = ''
const ACTIONS_CLASSES = 'mt-5 flex gap-3 items-center'

const props = defineProps({
    title: {
        type: String,
        default: null
    },
    subtitle: {
        type: String,
        default: null
    },
    id: {
        type: String,
        default: null
    }
})

const sectionId = computed(() => {
    return props.id || (props.title ? props.title.toLowerCase().replace(/\s+/g, '-') : null)
})

const hasActions = computed(() => {
    // Р’ Composition API РїСЂРѕРІРµСЂСЏРµРј РЅР°Р»РёС‡РёРµ СЃР»РѕС‚РѕРІ С‡РµСЂРµР· $slots
    return !!getCurrentInstance()?.slots.actions
})

// РџРѕР»СѓС‡Р°РµРј СЌРєР·РµРјРїР»СЏСЂ РєРѕРјРїРѕРЅРµРЅС‚Р° РґР»СЏ РґРѕСЃС‚СѓРїР° Рє СЃР»РѕС‚Р°Рј
import { getCurrentInstance } from 'vue'
</script>
