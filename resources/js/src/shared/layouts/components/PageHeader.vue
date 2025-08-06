<template>
  <div class="mb-6">
    <h1 :class="TITLE_CLASSES">{{ title }}</h1>
    <p v-if="subtitle" :class="SUBTITLE_CLASSES">{{ subtitle }}</p>
    <div v-if="$slots.default" :class="ACTIONS_CLASSES">
      <slot />
    </div>
  </div>
</template>

<script setup>
// рџЋЇ РЎС‚РёР»Рё РІ СЃРѕРѕС‚РІРµС‚СЃС‚РІРёРё СЃ Tailwind CSS (РєР°Рє РІ РїСЂРѕРµРєС‚Рµ)
const TITLE_CLASSES = 'text-2xl lg:text-3xl font-bold text-gray-900 mb-2'
const SUBTITLE_CLASSES = 'text-gray-600'
const ACTIONS_CLASSES = 'flex justify-center gap-3 mt-4 flex-wrap'

const props = defineProps({
  title: {
    type: String,
    required: true
  },
  subtitle: {
    type: String,
    default: ''
  }
})
</script>
