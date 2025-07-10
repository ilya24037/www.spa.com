<template>
  <!-- Десктоп версия -->
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
        :class="[
          mobileMode === 'bottom-sheet' ? MOBILE_BOTTOM_SHEET_CLASSES : mobilePanelClasses,
          mobileWidthClass
        ]"
        @click.stop
      >
        <!-- Заголовок мобильный -->
        <div v-if="$slots.header || title" :class="mobileMode === 'bottom-sheet' ? MOBILE_BOTTOM_HEADER_CLASSES : MOBILE_HEADER_CLASSES">
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
        <div :class="[contentClass, mobileMode === 'bottom-sheet' ? 'max-h-[70vh] overflow-y-auto' : '']">
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
import { computed } from 'vue'

// 🎯 ВСЕ СТИЛИ В КОНСТАНТАХ - КАК В CONTENTCARD
// Размеры и позиционирование
const DESKTOP_WIDTH = 'w-64'                    // 256px - ширина боковой панели по умолчанию
const MOBILE_WIDTH = 'w-80'                     // 320px - ширина мобильной панели

// Базовые стили компонента
const BASE_CLASSES = 'bg-white rounded-lg shadow-sm'
const DESKTOP_CONTAINER_CLASSES = 'hidden lg:block flex-shrink-0'

// Стили заголовка и футера
const HEADER_CLASSES = 'px-6 py-4 border-b'
const FOOTER_CLASSES = 'border-t p-6'
const TITLE_CLASSES = 'font-semibold text-lg'

// Мобильные стили - панель слева/справа
const MOBILE_OVERLAY_CLASSES = 'lg:hidden fixed inset-0 z-50 flex'
const MOBILE_BACKDROP_CLASSES = 'fixed inset-0 bg-black bg-opacity-50'
const MOBILE_PANEL_CLASSES = 'relative bg-white h-full shadow-xl overflow-y-auto'
const MOBILE_HEADER_CLASSES = 'px-6 py-4 border-b lg:hidden'
const MOBILE_HEADER_CONTENT_CLASSES = 'flex items-center justify-between'
const MOBILE_FOOTER_CLASSES = 'border-t p-6'
const MOBILE_CLOSE_BUTTON_CLASSES = 'p-2 hover:bg-gray-100 rounded-lg'

// Мобильные стили - bottom sheet
const MOBILE_BOTTOM_SHEET_CLASSES = 'fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-xl'
const MOBILE_BOTTOM_HEADER_CLASSES = 'px-6 py-4 border-b lg:hidden relative'

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
  },
  
  // НОВЫЕ ПРОПСЫ
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
    default: null // если null, используем DESKTOP_WIDTH
  },
  
  mobileMode: {
    type: String,
    default: 'overlay', // 'overlay' | 'bottom-sheet'
    validator: (value) => ['overlay', 'bottom-sheet'].includes(value)
  }
})

// События
defineEmits(['update:modelValue'])

// Вычисляемые свойства
const desktopPositionClasses = computed(() => {
  // ВАЖНО: НЕ добавляем order классы по умолчанию!
  // Это позволит существующим использованиям работать как раньше
  if (props.position === 'right') {
    return '' // Не добавляем order, позиционирование через родительский контейнер
  }
  return '' // По умолчанию тоже без order
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

// Вычисляемое свойство для мобильной панели с учетом позиции
const mobilePanelClasses = computed(() => {
  if (props.position === 'right') {
    return MOBILE_PANEL_CLASSES + ' right-0'
  }
  return MOBILE_PANEL_CLASSES + ' left-0'
})
</script>