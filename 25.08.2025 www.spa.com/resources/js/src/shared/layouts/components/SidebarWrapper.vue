<template>
  <!-- Р”РµСЃРєС‚РѕРї РІРµСЂСЃРёСЏ -->
  <div 
    v-if="alwaysVisibleDesktop"
    :class="[
      DESKTOP_CONTAINER_CLASSES,
      desktopPositionClasses,
      widthClass || DESKTOP_WIDTH
    ]"
  >
    <div 
      :class="[
        BASE_CLASSES,
        stickyClass,
        contentClass
      ]"
    >
      <!-- Р—Р°РіРѕР»РѕРІРѕРє РґРµСЃРєС‚РѕРї -->
      <div v-if="showDesktopHeader && ($slots.header || title)" :class="HEADER_CLASSES">
        <slot name="header">
          <h2 v-if="title" :class="TITLE_CLASSES">
            {{ title }}
          </h2>
        </slot>
      </div>
      
      <!-- РљРѕРЅС‚РµРЅС‚ -->
      <slot />
      
      <!-- Р¤СѓС‚РµСЂ РґРµСЃРєС‚РѕРї -->
      <div v-if="$slots.footer" :class="FOOTER_CLASSES">
        <slot name="footer" />
      </div>
    </div>
  </div>

  <!-- РћР±С‹С‡РЅР°СЏ РІРµСЂСЃРёСЏ (СЃРєСЂС‹РІР°РµРјР°СЏ) -->
  <div 
    v-else
    :class="[
      DESKTOP_CONTAINER_CLASSES,
      desktopPositionClasses,
      widthClass || DESKTOP_WIDTH
    ]"
  >
    <div 
      :class="[
        BASE_CLASSES,
        stickyClass,
        contentClass
      ]"
    >
      <!-- Р—Р°РіРѕР»РѕРІРѕРє РґРµСЃРєС‚РѕРї -->
      <div v-if="showDesktopHeader && ($slots.header || title)" :class="HEADER_CLASSES">
        <slot name="header">
          <h2 v-if="title" :class="TITLE_CLASSES">
            {{ title }}
          </h2>
        </slot>
      </div>
      
      <!-- РљРѕРЅС‚РµРЅС‚ -->
      <slot />
      
      <!-- Р¤СѓС‚РµСЂ РґРµСЃРєС‚РѕРї -->
      <div v-if="$slots.footer" :class="FOOTER_CLASSES">
        <slot name="footer" />
      </div>
    </div>
  </div>

  <!-- РњРѕР±РёР»СЊРЅР°СЏ РІРµСЂСЃРёСЏ -->
  <Teleport to="body">
    <div 
      v-if="modelValue"
      :class="MOBILE_OVERLAY_CLASSES"
      @click="$emit('update:modelValue', false)"
    >
      <!-- Р—Р°С‚РµРјРЅРµРЅРЅС‹Р№ С„РѕРЅ -->
      <div :class="MOBILE_BACKDROP_CLASSES" />
      
      <!-- РџР°РЅРµР»СЊ -->
      <div 
        :class="[
          mobileMode === 'bottom-sheet' ? MOBILE_BOTTOM_SHEET_CLASSES : mobilePanelClasses,
          mobileWidthClass
        ]"
        @click.stop
      >
        <!-- Р—Р°РіРѕР»РѕРІРѕРє РјРѕР±РёР»СЊРЅС‹Р№ -->
        <div v-if="$slots.header || title" :class="mobileMode === 'bottom-sheet' ? MOBILE_BOTTOM_HEADER_CLASSES : MOBILE_HEADER_CLASSES">
          <div :class="MOBILE_HEADER_CONTENT_CLASSES">
            <slot name="header">
              <h2 v-if="title" :class="TITLE_CLASSES">
                {{ title }}
              </h2>
            </slot>
            <button 
              :class="MOBILE_CLOSE_BUTTON_CLASSES"
              @click="$emit('update:modelValue', false)"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- РљРѕРЅС‚РµРЅС‚ РјРѕР±РёР»СЊРЅС‹Р№ -->
        <div :class="[contentClass, mobileMode === 'bottom-sheet' ? 'max-h-[70vh] overflow-y-auto' : '']">
          <slot />
        </div>
        
        <!-- Р¤СѓС‚РµСЂ РјРѕР±РёР»СЊРЅС‹Р№ -->
        <div v-if="$slots.footer" :class="MOBILE_FOOTER_CLASSES">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'

// рџЋЇ Р’РЎР• РЎРўРР›Р Р’ РљРћРќРЎРўРђРќРўРђРҐ - РљРђРљ Р’ CONTENTCARD
// Р Р°Р·РјРµСЂС‹ Рё РїРѕР·РёС†РёРѕРЅРёСЂРѕРІР°РЅРёРµ
const DESKTOP_WIDTH = 'w-64'                    // 256px - С€РёСЂРёРЅР° Р±РѕРєРѕРІРѕР№ РїР°РЅРµР»Рё РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ
const MOBILE_WIDTH = 'w-80'                     // 320px - С€РёСЂРёРЅР° РјРѕР±РёР»СЊРЅРѕР№ РїР°РЅРµР»Рё

// Р‘Р°Р·РѕРІС‹Рµ СЃС‚РёР»Рё РєРѕРјРїРѕРЅРµРЅС‚Р°
const BASE_CLASSES = 'bg-white rounded-lg shadow-sm'
const DESKTOP_CONTAINER_CLASSES = 'hidden lg:block flex-shrink-0'

// РЎС‚РёР»Рё Р·Р°РіРѕР»РѕРІРєР° Рё С„СѓС‚РµСЂР°
const HEADER_CLASSES = 'px-6 py-4 border-b'
const FOOTER_CLASSES = 'border-t p-6'
const TITLE_CLASSES = 'font-semibold text-lg'

// РњРѕР±РёР»СЊРЅС‹Рµ СЃС‚РёР»Рё - РїР°РЅРµР»СЊ СЃР»РµРІР°/СЃРїСЂР°РІР°
const MOBILE_OVERLAY_CLASSES = 'lg:hidden fixed inset-0 z-50 flex'
const MOBILE_BACKDROP_CLASSES = 'fixed inset-0 bg-black bg-opacity-50'
const MOBILE_PANEL_CLASSES = 'relative bg-white h-full shadow-xl overflow-y-auto'
const MOBILE_HEADER_CLASSES = 'px-6 py-4 border-b lg:hidden'
const MOBILE_HEADER_CONTENT_CLASSES = 'flex items-center justify-between'
const MOBILE_FOOTER_CLASSES = 'border-t p-6'
const MOBILE_CLOSE_BUTTON_CLASSES = 'p-2 hover:bg-gray-500 rounded-lg'

// РњРѕР±РёР»СЊРЅС‹Рµ СЃС‚РёР»Рё - bottom sheet
const MOBILE_BOTTOM_SHEET_CLASSES = 'fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-xl'
const MOBILE_BOTTOM_HEADER_CLASSES = 'px-6 py-4 border-b lg:hidden relative'

// РЈРїСЂРѕС‰РµРЅРЅС‹Рµ РїСЂРѕРїСЃС‹
const props = defineProps({
    modelValue: {
        type: Boolean,
        default: false
    },
  
    title: String,
  
    contentClass: {
        type: String,
        default: ''
    },
  
    showDesktopHeader: {
        type: Boolean,
        default: false
    },
  
    alwaysVisibleDesktop: {
        type: Boolean,
        default: false
    },
  
    // РќРћР’Р«Р• РџР РћРџРЎР«
    position: {
        type: String,
        default: 'left',
        validator: (value) => ['left', 'right'].includes(value)
    },
  
    sticky: {
        type: Boolean,
        default: false
    },
  
    stickyTop: {
        type: Number,
        default: 64
    },
  
    widthClass: {
        type: String,
        default: null // РµСЃР»Рё null, РёСЃРїРѕР»СЊР·СѓРµРј DESKTOP_WIDTH
    },
  
    mobileMode: {
        type: String,
        default: 'overlay', // 'overlay' | 'bottom-sheet'
        validator: (value) => ['overlay', 'bottom-sheet'].includes(value)
    },

    show: {
        type: Boolean,
        default: false
    }
})

// РЎРѕР±С‹С‚РёСЏ
defineEmits(['update:modelValue'])

// Р’С‹С‡РёСЃР»СЏРµРјС‹Рµ СЃРІРѕР№СЃС‚РІР°
const desktopPositionClasses = computed(() => {
    // Р’РђР–РќРћ: РќР• РґРѕР±Р°РІР»СЏРµРј order РєР»Р°СЃСЃС‹ РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ!
    // Р­С‚Рѕ РїРѕР·РІРѕР»РёС‚ СЃСѓС‰РµСЃС‚РІСѓСЋС‰РёРј РёСЃРїРѕР»СЊР·РѕРІР°РЅРёСЏРј СЂР°Р±РѕС‚Р°С‚СЊ РєР°Рє СЂР°РЅСЊС€Рµ
    if (props.position === 'right') {
        return '' // РќРµ РґРѕР±Р°РІР»СЏРµРј order, РїРѕР·РёС†РёРѕРЅРёСЂРѕРІР°РЅРёРµ С‡РµСЂРµР· СЂРѕРґРёС‚РµР»СЊСЃРєРёР№ РєРѕРЅС‚РµР№РЅРµСЂ
    }
    return '' // РџРѕ СѓРјРѕР»С‡Р°РЅРёСЋ С‚РѕР¶Рµ Р±РµР· order
})

const stickyClass = computed(() => {
    if (props.sticky) {
        return `sticky top-[${props.stickyTop}px]`
    }
    return ''
})

const mobileWidthClass = computed(() => {
    if (props.mobileMode === 'bottom-sheet') {
        return 'w-full'
    }
    return props.widthClass || MOBILE_WIDTH
})

// Р’С‹С‡РёСЃР»СЏРµРјРѕРµ СЃРІРѕР№СЃС‚РІРѕ РґР»СЏ РјРѕР±РёР»СЊРЅРѕР№ РїР°РЅРµР»Рё СЃ СѓС‡РµС‚РѕРј РїРѕР·РёС†РёРё
const mobilePanelClasses = computed(() => {
    if (props.position === 'right') {
        return MOBILE_PANEL_CLASSES + ' right-0'
    }
    return MOBILE_PANEL_CLASSES + ' left-0'
})
</script>
