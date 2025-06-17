<template>
  <!-- Mobile: drawer via Teleport -->
  <Teleport to="body" v-if="isMobile">
    <Transition name="drawer">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex">
        <!-- Overlay с анимацией -->
        <div 
          class="flex-1 bg-black/50 backdrop-blur-sm" 
          @click="handleClose"
          :class="{ 'pointer-events-none': isAnimating }"
        />

        <!-- Sliding panel -->
        <aside
          ref="drawerRef"
          role="navigation"
          :aria-label="ariaLabel"
          :aria-hidden="!modelValue"
          class="relative w-72 bg-white h-full shadow-2xl overflow-hidden flex-shrink-0"
          :style="{ transform: `translateX(${dragOffset}px)` }"
          @touchstart="onTouchStart"
          @touchmove="onTouchMove"
          @touchend="onTouchEnd"
        >
          <!-- Индикатор свайпа -->
          <div 
            v-if="showSwipeHint" 
            class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-16 bg-gray-400/50 rounded-r-full"
          />
          
          <!-- Заголовок (опционально) -->
          <div v-if="$slots.header" class="sticky top-0 bg-white border-b border-gray-200 z-10">
            <div class="p-4 flex items-center justify-between">
              <slot name="header" />
              <button
                @click="handleClose"
                class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                :aria-label="closeAriaLabel"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Контент с кастомным скроллом -->
          <div 
            ref="contentRef"
            class="h-full overflow-y-auto overscroll-contain"
            :class="[
              contentClass,
              { 'pb-20': hasBottomNav }
            ]"
            @scroll="onContentScroll"
          >
            <div class="p-6">
              <slot />
            </div>
          </div>
          
          <!-- Футер (опционально) -->
          <div v-if="$slots.footer" class="sticky bottom-0 bg-white border-t border-gray-200">
            <div class="p-4">
              <slot name="footer" />
            </div>
          </div>
        </aside>
      </div>
    </Transition>
  </Teleport>

  <!-- Desktop / Tablet ≥1024px -->
  <aside 
    v-else 
    :class="[
      'shrink-0 hidden lg:block transition-all duration-300',
      desktopWidth,
      { 'lg:hidden': forceHideDesktop }
    ]"
  >
    <div
      ref="stickyRef"
      class="bg-white rounded-lg shadow-sm sticky overflow-hidden transition-all duration-300"
      :style="{ 
        top: `${effectiveTop}px`,
        maxHeight: `calc(100vh - ${effectiveTop + 20}px)`
      }"
      :class="[
        desktopClass,
        { 'ring-2 ring-primary-500': isHighlighted }
      ]"
    >
      <!-- Desktop заголовок -->
      <div v-if="$slots.header && showDesktopHeader" class="border-b border-gray-200">
        <div class="p-4">
          <slot name="header" />
        </div>
      </div>
      
      <!-- Desktop контент -->
      <div 
        class="overflow-y-auto overscroll-contain"
        :class="contentClass"
      >
        <div class="p-4">
          <slot />
        </div>
      </div>
      
      <!-- Desktop футер -->
      <div v-if="$slots.footer && showDesktopFooter" class="border-t border-gray-200 mt-auto">
        <div class="p-4">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch, inject, nextTick } from 'vue'
import { useSwipeGesture } from '@/Composables/useSwipeGesture'
import { useMediaQuery } from '@/Composables/useMediaQuery'
import { useLockScroll } from '@/Composables/useLockScroll'

// Props
const props = defineProps({
  /** v-model для контроля видимости на мобильных */
  modelValue: {
    type: Boolean,
    default: false,
  },
  /** Индивидуальный отступ от верха */
  stickyTop: {
    type: Number,
    default: undefined,
  },
  /** Ширина на десктопе */
  desktopWidth: {
    type: String,
    default: 'w-64',
  },
  /** Дополнительные классы для контента */
  contentClass: {
    type: String,
    default: '',
  },
  /** Дополнительные классы для десктопа */
  desktopClass: {
    type: String,
    default: '',
  },
  /** Показывать подсказку свайпа */
  showSwipeHint: {
    type: Boolean,
    default: true,
  },
  /** Есть ли нижняя навигация (для отступа) */
  hasBottomNav: {
    type: Boolean,
    default: false,
  },
  /** Принудительно скрыть на десктопе */
  forceHideDesktop: {
    type: Boolean,
    default: false,
  },
  /** Показывать заголовок на десктопе */
  showDesktopHeader: {
    type: Boolean,
    default: false,
  },
  /** Показывать футер на десктопе */
  showDesktopFooter: {
    type: Boolean,
    default: false,
  },
  /** Подсветить сайдбар */
  isHighlighted: {
    type: Boolean,
    default: false,
  },
  /** Aria-label для сайдбара */
  ariaLabel: {
    type: String,
    default: 'Боковая панель',
  },
  /** Aria-label для кнопки закрытия */
  closeAriaLabel: {
    type: String,
    default: 'Закрыть панель',
  },
})

// Emits
const emit = defineEmits(['update:modelValue', 'close', 'open', 'swipe', 'scroll'])

// Refs
const drawerRef = ref(null)
const contentRef = ref(null)
const stickyRef = ref(null)

// Composables
const isMobile = useMediaQuery('(max-width: 1023px)')
const { lockScroll, unlockScroll } = useLockScroll()

// Swipe gesture
const { dragOffset, isAnimating, onTouchStart, onTouchMove, onTouchEnd } = useSwipeGesture({
  threshold: 50,
  onSwipeLeft: () => {
    handleClose()
    emit('swipe', 'left')
  },
})

// Sticky offset
const injectedTop = inject('stickyTop', 80)
const effectiveTop = computed(() => props.stickyTop ?? injectedTop)

// Методы
function handleClose() {
  emit('update:modelValue', false)
  emit('close')
}

function handleOpen() {
  emit('update:modelValue', true)
  emit('open')
}

// Scroll tracking
let lastScrollTop = 0
function onContentScroll(e) {
  const scrollTop = e.target.scrollTop
  const scrollDirection = scrollTop > lastScrollTop ? 'down' : 'up'
  lastScrollTop = scrollTop
  
  emit('scroll', {
    scrollTop,
    scrollDirection,
    isAtTop: scrollTop === 0,
    isAtBottom: scrollTop + e.target.clientHeight >= e.target.scrollHeight - 1
  })
}

// Lock body scroll when mobile drawer is open
watch(
  () => props.modelValue && isMobile.value,
  async (shouldLock) => {
    await nextTick()
    if (shouldLock) {
      lockScroll()
    } else {
      unlockScroll()
    }
  }
)

// Cleanup
onBeforeUnmount(() => {
  unlockScroll()
})

// Expose methods
defineExpose({
  open: handleOpen,
  close: handleClose,
  scrollToTop: () => {
    if (contentRef.value) {
      contentRef.value.scrollTop = 0
    }
  }
})
</script>

<style scoped>
/* Анимация drawer */
.drawer-enter-active,
.drawer-leave-active {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.drawer-enter-from .bg-black\/50,
.drawer-leave-to .bg-black\/50 {
  opacity: 0;
}

.drawer-enter-from aside,
.drawer-leave-to aside {
  transform: translateX(-100%);
}

/* Кастомный скроллбар */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f3f4f6;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

/* Предотвращение прокрутки body на iOS */
.overscroll-contain {
  overscroll-behavior: contain;
  -webkit-overflow-scrolling: touch;
}
</style>