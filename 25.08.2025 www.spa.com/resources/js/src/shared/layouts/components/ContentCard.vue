<!-- resources/js/src/shared/layouts/components/ContentCard.vue -->
<template>
  <component 
    :is="as"
    :class="[
      BASE_CLASSES,
      customClass
    ]"
  >
    <!-- Р—Р°РіРѕР»РѕРІРѕРє РєР°СЂС‚РѕС‡РєРё (РѕРїС†РёРѕРЅР°Р»СЊРЅРѕ) -->
    <div v-if="title || $slots.header" :class="['border-b', HEADER_PADDING]">
      <div class="flex items-center justify-between">
        <h2 v-if="title" :class="TITLE_CLASSES">
          {{ title }}
        </h2>
        <slot name="header" />
        <slot name="headerActions" />
      </div>
    </div>
    
    <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
    <div v-if="!noPadding" :class="CONTENT_PADDING">
      <slot />
    </div>
    <slot v-else />
    
    <!-- Р¤СѓС‚РµСЂ РєР°СЂС‚РѕС‡РєРё (РѕРїС†РёРѕРЅР°Р»СЊРЅРѕ) -->
    <div v-if="$slots.footer" :class="['border-t', FOOTER_PADDING]">
      <slot name="footer" />
    </div>
  </component>
</template>

<script setup>
// рџЋЇ Р¤РРљРЎРР РћР’РђРќРќР«Р• РЎРўРР›Р Р”Р›РЇ Р’РЎР•Р“Рћ РџР РћР•РљРўРђ
const BASE_CLASSES = 'bg-white rounded-lg shadow-sm overflow-hidden'
const CONTENT_PADDING = 'p-6'          // 24px - РІРµР·РґРµ РѕРґРёРЅР°РєРѕРІРѕ
const HEADER_PADDING = 'px-6 py-4'     // 24px/16px - РІРµР·РґРµ РѕРґРёРЅР°РєРѕРІРѕ  
const FOOTER_PADDING = 'px-6 py-4'     // 24px/16px - РІРµР·РґРµ РѕРґРёРЅР°РєРѕРІРѕ
const TITLE_CLASSES = 'text-lg font-semibold text-gray-500'

// РЈРїСЂРѕС‰РµРЅРЅС‹Рµ РїСЂРѕРїСЃС‹ (СѓР±СЂР°Р»Рё padding, noShadow, noRounded Рё С‚.Рґ.)
const props = defineProps({
    // HTML С‚РµРі
    as: {
        type: String,
        default: 'div'
    },
  
    // Р—Р°РіРѕР»РѕРІРѕРє
    title: String,
  
    // РЈР±СЂР°С‚СЊ РѕС‚СЃС‚СѓРїС‹ Сѓ РєРѕРЅС‚РµРЅС‚Р° (РґР»СЏ РѕСЃРѕР±С‹С… СЃР»СѓС‡Р°РµРІ)
    noPadding: Boolean,
  
    // РљР°СЃС‚РѕРјРЅС‹Рµ РєР»Р°СЃСЃС‹ (РґР»СЏ border, hover Рё С‚.Рґ.)
    customClass: String
})
</script>
