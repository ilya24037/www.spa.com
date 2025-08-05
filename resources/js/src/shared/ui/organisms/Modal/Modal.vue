<template>
  <teleport to="body">
    <transition
      enter-active-class="ease-out duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-show="show" 
        class="modal-backdrop"
        @click="handleBackdropClick"
      >
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" />
        
        <!-- Modal container -->
        <div class="modal-container">
          <transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-show="show" ref="modalRef"
              class="modal-content"
              :class="[maxWidthClass, customClass]"
              role="dialog"
              :aria-modal="true"
              :aria-labelledby="titleId"
              :aria-describedby="descriptionId"
              @click.stop
            >
              <!-- Header -->
              <div v-if="title || $slots.header || closeable" class="modal-header">
                <div class="modal-header-content">
                  <slot name="header">
                    <h3 v-if="title" :id="titleId" class="modal-title">
                      {{ title }}
                    </h3>
                  </slot>
                </div>
                
                <!-- Close button -->
                <button
                  v-if="closeable"
                  type="button"
                  class="modal-close"
                  :aria-label="closeLabel"
                  @click="close"
                >
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>

              <!-- Body -->
              <div 
                v-if="$slots.default || message" 
                :id="descriptionId"
                class="modal-body"
              >
                <slot>
                  <p v-if="message" class="text-gray-600">{{ message }}</p>
                </slot>
              </div>

              <!-- Footer -->
              <div v-if="$slots.footer || showFooter" class="modal-footer">
                <slot name="footer">
                  <div class="modal-footer-buttons">
                    <Button
                      v-if="showCancelButton"
                      variant="secondary"
                      :text="cancelText"
                      @click="cancel"
                    />
                    <Button
                      v-if="showConfirmButton"
                      :variant="confirmVariant"
                      :text="confirmText"
                      :loading="loading"
                      @click="confirm"
                    />
                  </div>
                </slot>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import { computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { Button } from '@/src/shared/ui/atoms/Button'
import { useId } from '@/shared/composables'
import type { ButtonVariant } from '@/src/shared/ui/atoms/Button'

// TypeScript типы
export interface ModalProps {
  // Видимость
  show?: boolean
  
  // Содержимое
  title?: string
  message?: string
  
  // Размеры
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | 'full'
  
  // Кнопки
  showFooter?: boolean
  showConfirmButton?: boolean
  showCancelButton?: boolean
  confirmText?: string
  cancelText?: string
  confirmVariant?: ButtonVariant
  
  // Поведение
  closeable?: boolean
  closeOnEscape?: boolean
  closeOnBackdrop?: boolean
  loading?: boolean
  
  // Стили
  customClass?: string
  
  // Доступность
  closeLabel?: string
}

// Props
const props = withDefaults(defineProps<ModalProps>(), {
  show: false,
  maxWidth: '2xl',
  showFooter: false,
  showConfirmButton: true,
  showCancelButton: true,
  confirmText: 'Подтвердить',
  cancelText: 'Отмена',
  confirmVariant: 'primary',
  closeable: true,
  closeOnEscape: true,
  closeOnBackdrop: true,
  loading: false,
  closeLabel: 'Закрыть окно'
})

// Emits
const emit = defineEmits<{
  'update:show': [value: boolean]
  'close': []
  'confirm': []
  'cancel': []
  'backdrop-click': []
}>()

// Refs
const modalRef = ref<HTMLElement>()

// IDs для доступности
const titleId = useId('modal-title')
const descriptionId = useId('modal-description')

// Computed
const maxWidthClass = computed(() => {
  const widths = {
    'sm': 'sm:max-w-sm',
    'md': 'sm:max-w-md',
    'lg': 'sm:max-w-lg',
    'xl': 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    '3xl': 'sm:max-w-3xl',
    '4xl': 'sm:max-w-4xl',
    '5xl': 'sm:max-w-5xl',
    'full': 'sm:max-w-full'
  }
  return widths[props.maxWidth] || widths['2xl']
})

// Методы
const close = () => {
  emit('update:show', false)
  emit('close')
}

const confirm = () => {
  if (!props.loading) {
    emit('confirm')
  }
}

const cancel = () => {
  emit('cancel')
  close()
}

const handleBackdropClick = () => {
  if (props.closeOnBackdrop && props.closeable) {
    emit('backdrop-click')
    close()
  }
}

// Обработка ESC
const handleEscape = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && props.show && props.closeOnEscape && props.closeable) {
    close()
  }
}

// Focus trap
const trapFocus = () => {
  if (!modalRef.value) return
  
  const focusableElements = modalRef.value.querySelectorAll(
    'button, [h], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )
  
  const firstFocusable = focusableElements[0] as HTMLElement
  const lastFocusable = focusableElements[focusableElements.length - 1] as HTMLElement
  
  const handleTab = (e: KeyboardEvent) => {
    if (e.key !== 'Tab') return
    
    if (e.shiftKey) {
      if (document.activeElement === firstFocusable) {
        e.preventDefault()
        lastFocusable?.focus()
      }
    } else {
      if (document.activeElement === lastFocusable) {
        e.preventDefault()
        firstFocusable?.focus()
      }
    }
  }
  
  modalRef.value.addEventListener('keydown', handleTab)
  
  // Focus первый элемент
  nextTick(() => {
    firstFocusable?.focus()
  })
  
  return () => {
    modalRef.value?.removeEventListener('keydown', handleTab)
  }
}

// Блокировка скролла body
const lockBodyScroll = () => {
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth
  document.body.style.paddingRight = `${scrollbarWidth}px`
  document.body.style.overflow = 'hidden'
  
  return () => {
    document.body.style.paddingRight = ''
    document.body.style.overflow = ''
  }
}

// Watch для show
watch(() => props.show, async (newValue) => {
  if (newValue) {
    await nextTick()
    const unlockScroll = lockBodyScroll()
    const removeFocusTrap = trapFocus()
    
    // Сохраняем функции очистки
    onUnmounted(() => {
      unlockScroll()
      removeFocusTrap?.()
    })
  }
})

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEscape)
})
</script>

<style scoped>
/* Backdrop */
.modal-backdrop {
  @apply fixed inset-0 z-50 overflow-y-auto;
  @apply flex min-h-screen items-center justify-center p-4;
}

/* Container */
.modal-container {
  @apply relative w-full;
}

/* Content */
.modal-content {
  @apply relative bg-white rounded-lg shadow-xl;
  @apply w-full mx-auto;
  @apply flex flex-col max-h-[90vh];
}

/* Header */
.modal-header {
  @apply flex items-start justify-between;
  @apply px-6 py-4 border-b border-gray-200;
}

.modal-header-content {
  @apply flex-1;
}

.modal-title {
  @apply text-lg font-semibold text-gray-900;
}

.modal-close {
  @apply ml-3 p-1 rounded-md;
  @apply text-gray-400 hover:text-gray-500;
  @apply hover:bg-gray-100 focus:bg-gray-100;
  @apply focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500;
  @apply transition-colors duration-200;
}

/* Body */
.modal-body {
  @apply flex-1 overflow-y-auto;
  @apply px-6 py-4;
}

/* Footer */
.modal-footer {
  @apply px-6 py-4 border-t border-gray-200;
  @apply bg-gray-50 rounded-b-lg;
}

.modal-footer-buttons {
  @apply flex justify-end gap-3;
}

/* Responsive */
@media (max-width: 640px) {
  .modal-content {
    @apply m-4;
  }
  
  .modal-body {
    @apply px-4;
  }
}
</style>