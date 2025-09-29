<!-- SidebarWrapper - универсальная боковая панель с TypeScript -->
<template>
  <!-- Десктоп версия -->
  <div 
    v-if="alwaysVisibleDesktop"
    :class="[
      desktopContainerClasses,
      desktopPositionClasses,
      widthClass || defaultWidth
    ]"
  >
    <div 
      :class="[
        baseClasses,
        stickyClass,
        contentClass
      ]"
    >
      <!-- Заголовок десктоп -->
      <div v-if="showDesktopHeader && ($slots.header || title)" :class="headerClasses">
        <slot name="header">
          <h2 v-if="title" :class="titleClasses">{{ title }}</h2>
        </slot>
      </div>
      
      <!-- Контент -->
      <slot />
      
      <!-- Футер десктоп -->
      <div v-if="$slots.footer" :class="footerClasses">
        <slot name="footer" />
      </div>
    </div>
  </div>

  <!-- Обычная версия (скрываемая) -->
  <div 
    v-else
    :class="[
      desktopContainerClasses,
      desktopPositionClasses,
      widthClass || defaultWidth
    ]"
  >
    <div 
      :class="[
        baseClasses,
        stickyClass,
        contentClass
      ]"
    >
      <!-- Заголовок десктоп -->
      <div v-if="showDesktopHeader && ($slots.header || title)" :class="headerClasses">
        <slot name="header">
          <h2 v-if="title" :class="titleClasses">{{ title }}</h2>
        </slot>
      </div>
      
      <!-- Контент -->
      <slot />
      
      <!-- Футер десктоп -->
      <div v-if="$slots.footer" :class="footerClasses">
        <slot name="footer" />
      </div>
    </div>
  </div>

  <!-- Мобильная версия -->
  <Teleport to="body">
    <div 
      v-if="modelValue"
      :class="mobileOverlayClasses"
      @click="handleClose"
    >
      <!-- Затемненный фон -->
      <div :class="mobileBackdropClasses" />
      
      <!-- Панель -->
      <div 
        :class="[
          mobileMode === 'bottom-sheet' ? mobileBottomSheetClasses : mobilePanelClasses,
          mobileWidthClass
        ]"
        @click.stop
      >
        <!-- Заголовок мобильный -->
        <div v-if="$slots.header || title" :class="mobileMode === 'bottom-sheet' ? mobileBottomHeaderClasses : mobileHeaderClasses">
          <div :class="mobileHeaderContentClasses">
            <slot name="header">
              <h2 v-if="title" :class="titleClasses">{{ title }}</h2>
            </slot>
            <button 
              @click="handleClose"
              :class="mobileCloseButtonClasses"
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
        <div v-if="$slots.footer" :class="mobileFooterClasses">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue'

// TypeScript интерфейсы
interface Props {
  modelValue?: boolean
  title?: string
  contentClass?: string
  showDesktopHeader?: boolean
  alwaysVisibleDesktop?: boolean
  position?: 'left' | 'right'
  sticky?: boolean
  stickyTop?: number
  widthClass?: string
  mobileMode?: 'overlay' | 'bottom-sheet'
  show?: boolean
}

// Константы стилей
const DEFAULT_WIDTH = 'w-64' // 256px - ширина боковой панели по умолчанию
const MOBILE_WIDTH = 'w-80' // 320px - ширина мобильной панели

// Базовые стили компонента
const baseClasses = 'bg-white rounded-lg shadow-sm'
const desktopContainerClasses = 'hidden lg:block flex-shrink-0'

// Стили заголовка и футера
const headerClasses = 'px-6 py-4 border-b'
const footerClasses = 'border-t p-6'
const titleClasses = 'font-semibold text-lg'

// Мобильные стили - панель слева/справа
const mobileOverlayClasses = 'lg:hidden fixed inset-0 z-50 flex'
const mobileBackdropClasses = 'fixed inset-0 bg-black bg-opacity-50'
const mobileHeaderClasses = 'px-6 py-4 border-b lg:hidden'
const mobileHeaderContentClasses = 'flex items-center justify-between'
const mobileFooterClasses = 'border-t p-6'
const mobileCloseButtonClasses = 'p-2 hover:bg-gray-100 rounded-lg'

// Мобильные стили - bottom sheet
const mobileBottomSheetClasses = 'fixed bottom-0 left-0 right-0 bg-white rounded-t-2xl shadow-xl'
const mobileBottomHeaderClasses = 'px-6 py-4 border-b lg:hidden relative'

// Props
const props = withDefaults(defineProps<Props>(), {
  modelValue: false,
  contentClass: '',
  showDesktopHeader: false,
  alwaysVisibleDesktop: false,
  position: 'left',
  sticky: false,
  stickyTop: 64,
  mobileMode: 'overlay',
  show: false
})

// События
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
}>()

// Вычисляемые свойства
const defaultWidth = computed(() => DEFAULT_WIDTH)

const desktopPositionClasses = computed(() => {
  // Не добавляем order классы, позиционирование через родительский контейнер
  return ''
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

const mobilePanelClasses = computed(() => {
  const baseClass = 'relative bg-white h-full shadow-xl overflow-y-auto'
  if (props.position === 'right') {
    return baseClass + ' right-0'
  }
  return baseClass + ' left-0'
})

// Методы
const handleClose = () => {
  emit('update:modelValue', false)
}
</script>

<style scoped>
/* Плавные переходы для ховеров */
a {
  @apply transition-colors duration-150;
}
</style>