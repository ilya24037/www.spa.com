<template>
  <!-- Десктоп версия -->
  <div 
    v-if="alwaysVisibleDesktop"
    :class="[
      DESKTOP_CONTAINER_CLASSES,
      DESKTOP_WIDTH
    ]"
  >
    <div 
      :class="[
        BASE_CLASSES,
        `sticky top-[${STICKY_TOP}px]`,
        contentClass
      ]"
    >
      <!-- Заголовок десктоп -->
      <div v-if="showDesktopHeader && ($slots.header || title)" :class="HEADER_CLASSES">
        <slot name="header">
          <h2 v-if="title" :class="TITLE_CLASSES">{{ title }}</h2>
        </slot>
      </div>
      
      <!-- Контент -->
      <slot />
      
      <!-- Футер десктоп -->
      <div v-if="$slots.footer" :class="FOOTER_CLASSES">
        <slot name="footer" />
      </div>
    </div>
  </div>

  <!-- Обычная версия (скрываемая) -->
  <div 
    v-else
    :class="[
      DESKTOP_CONTAINER_CLASSES,
      DESKTOP_WIDTH
    ]"
  >
    <div 
      :class="[
        BASE_CLASSES,
        `sticky top-[${STICKY_TOP}px]`,
        contentClass
      ]"
    >
      <!-- Заголовок десктоп -->
      <div v-if="showDesktopHeader && ($slots.header || title)" :class="HEADER_CLASSES">
        <slot name="header">
          <h2 v-if="title" :class="TITLE_CLASSES">{{ title }}</h2>
        </slot>
      </div>
      
      <!-- Контент -->
      <slot />
      
      <!-- Футер десктоп -->
      <div v-if="$slots.footer" :class="FOOTER_CLASSES">
        <slot name="footer" />
      </div>
    </div>
  </div>

  <!-- Мобильная версия -->
  <Teleport to="body">
    <div 
      v-if="modelValue"
      :class="MOBILE_OVERLAY_CLASSES"
      @click="$emit('update:modelValue', false)"
    >
      <!-- Затемненный фон -->
      <div :class="MOBILE_BACKDROP_CLASSES" />
      
      <!-- Панель -->
      <div 
        :class="[MOBILE_PANEL_CLASSES, MOBILE_WIDTH]"
        @click.stop
      >
        <!-- Заголовок мобильный -->
        <div v-if="$slots.header || title" :class="MOBILE_HEADER_CLASSES">
          <div :class="MOBILE_HEADER_CONTENT_CLASSES">
            <slot name="header">
              <h2 v-if="title" :class="TITLE_CLASSES">{{ title }}</h2>
            </slot>
            <button 
              @click="$emit('update:modelValue', false)"
              :class="MOBILE_CLOSE_BUTTON_CLASSES"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Контент мобильный -->
        <div :class="contentClass">
          <slot />
        </div>
        
        <!-- Футер мобильный -->
        <div v-if="$slots.footer" :class="MOBILE_FOOTER_CLASSES">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
// 🎯 ВСЕ СТИЛИ В КОНСТАНТАХ - КАК В CONTENTCARD
// Размеры и позиционирование
const DESKTOP_WIDTH = 'w-64'                    // 256px - ширина боковой панели
const MOBILE_WIDTH = 'w-80'                     // 320px - ширина мобильной панели
const STICKY_TOP = 120                          // 120px - отступ сверху при прилипании

// Базовые стили компонента
const BASE_CLASSES = 'bg-white rounded-lg shadow-sm'
const DESKTOP_CONTAINER_CLASSES = 'hidden lg:block flex-shrink-0'

// Стили заголовка и футера
const HEADER_CLASSES = 'px-6 py-4 border-b'
const FOOTER_CLASSES = 'border-t p-6'
const TITLE_CLASSES = 'font-semibold text-lg'

// Мобильные стили
const MOBILE_OVERLAY_CLASSES = 'lg:hidden fixed inset-0 z-50 flex'
const MOBILE_BACKDROP_CLASSES = 'fixed inset-0 bg-black bg-opacity-50'
const MOBILE_PANEL_CLASSES = 'relative bg-white h-full shadow-xl overflow-y-auto'
const MOBILE_HEADER_CLASSES = 'px-6 py-4 border-b lg:hidden'
const MOBILE_HEADER_CONTENT_CLASSES = 'flex items-center justify-between'
const MOBILE_FOOTER_CLASSES = 'border-t p-6'
const MOBILE_CLOSE_BUTTON_CLASSES = 'p-2 hover:bg-gray-100 rounded-lg'

// Упрощенные пропсы
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
  }
})

// События
defineEmits(['update:modelValue'])
</script>