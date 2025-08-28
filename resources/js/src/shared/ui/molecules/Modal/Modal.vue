<template>
  <Teleport to="body">
    <Transition
      name="modal"
      @enter="onEnter"
      @leave="onLeave"
    >
      <div
        v-show="modelValue || show"
        ref="modalRef"
        class="modal-backdrop"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="titleId"
        :aria-describedby="contentId"
        @click.self="handleBackdropClick"
        @keydown.esc="handleEscapeKey"
      >
        <div
          class="modal-container"
          :class="[
            sizeClasses,
            {
              'modal-container--centered': centered,
              'modal-container--fullscreen': fullscreen
            }
          ]"
          role="document"
        >
          <!-- Header -->
          <header
            v-if="showHeader && (title || $slots.header)"
            class="modal-header"
          >
            <div class="modal-header-content">
              <h2
                v-if="title"
                :id="titleId"
                class="modal-title"
              >
                {{ title }}
              </h2>
              <slot
                v-else
                name="header"
                :title-id="titleId"
              />
            </div>
            
            <button
              v-if="closeable || showClose"
              type="button"
              class="modal-close-button"
              :aria-label="closeLabel || closeAriaLabel || 'Закрыть'"
              @click="handleClose"
            >
              <svg
                class="w-6 h-6"
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
          </header>

          <!-- Body -->
          <main
            :id="contentId"
            class="modal-content"
            :class="{ 'modal-content--no-header': !showHeader || (!title && !$slots.header) }"
          >
            <slot
              :close="handleClose"
              :title-id="titleId"
              :content-id="contentId"
            >
              <p v-if="message" class="text-gray-600">
                {{ message }}
              </p>
            </slot>
          </main>

          <!-- Footer -->
          <footer
            v-if="showFooter || $slots.footer"
            class="modal-footer"
          >
            <slot
              name="footer"
              :close="handleClose"
            >
              <div class="modal-actions">
                <button
                  v-if="showCancelButton"
                  type="button"
                  class="modal-button modal-button--secondary"
                  @click="handleCancel"
                >
                  {{ cancelText }}
                </button>
                <button
                  v-if="showConfirmButton"
                  type="button"
                  class="modal-button modal-button--primary"
                  :class="{ 
                    [`modal-button--${confirmVariant}`]: confirmVariant && confirmVariant !== 'primary',
                    'modal-button--loading': loading
                  }"
                  :disabled="loading"
                  @click="handleConfirm"
                >
                  {{ confirmText }}
                </button>
              </div>
            </slot>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, onUnmounted, nextTick, watch } from 'vue'

// Types
export type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | '2xl' | 'full'
export type ModalVariant = 'primary' | 'danger' | 'warning' | 'success' | 'info'

export interface ModalProps {
  // Visibility - поддержка обоих вариантов
  modelValue?: boolean
  show?: boolean
  
  // Content
  title?: string
  message?: string
  
  // Size and layout - поддержка обоих вариантов
  size?: ModalSize
  maxWidth?: ModalSize
  centered?: boolean
  fullscreen?: boolean
  
  // Header/Footer
  showHeader?: boolean
  showFooter?: boolean
  showConfirmButton?: boolean
  showCancelButton?: boolean
  confirmText?: string
  cancelText?: string
  confirmVariant?: ModalVariant
  variant?: ModalVariant // для совместимости с ConfirmModal
  
  // Behavior - объединение всех вариантов
  closeable?: boolean
  showClose?: boolean // alias для closeable
  closeOnBackdrop?: boolean
  closeOnClickOutside?: boolean // alias для closeOnBackdrop
  closeOnEscape?: boolean
  persistent?: boolean
  loading?: boolean
  
  // Style
  zIndex?: number
  
  // Accessibility
  closeLabel?: string
  closeAriaLabel?: string // alias для closeLabel
}

const props = withDefaults(defineProps<ModalProps>(), {
  modelValue: false,
  show: false,
  size: 'md',
  centered: true,
  fullscreen: false,
  showHeader: true,
  showFooter: false,
  showConfirmButton: true,
  showCancelButton: true,
  confirmText: 'Подтвердить',
  cancelText: 'Отмена',
  confirmVariant: 'primary',
  closeable: true,
  showClose: true,
  closeOnBackdrop: true,
  closeOnClickOutside: true,
  closeOnEscape: true,
  persistent: false,
  loading: false,
  zIndex: 50,
  closeLabel: 'Закрыть',
  closeAriaLabel: 'Закрыть'
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'update:show': [value: boolean]
  'open': []
  'close': []
  'confirm': []
  'cancel': []
  'backdrop': []
  'backdrop-click': []
  'escape': []
}>()

// Refs
const modalRef = ref<HTMLElement>()
const previousActiveElement = ref<HTMLElement | null>(null)

// Generate unique IDs for accessibility
let idCounter = 0
const generateId = (prefix: string) => `${prefix}-${++idCounter}-${Date.now()}`
const titleId = generateId('modal-title')
const contentId = generateId('modal-content')

// Computed
const sizeClasses = computed(() => {
  const effectiveSize = props.maxWidth || props.size
  const sizes: Record<ModalSize, string> = {
    'sm': 'modal-container--sm',
    'md': 'modal-container--md',
    'lg': 'modal-container--lg',
    'xl': 'modal-container--xl',
    '2xl': 'modal-container--2xl',
    'full': 'modal-container--full'
  }
  return sizes[effectiveSize]
})

// Methods
const handleClose = () => {
  if (!props.persistent) {
    emit('update:modelValue', false)
    emit('update:show', false)
    emit('close')
  }
}

const handleConfirm = () => {
  if (!props.loading) {
    emit('confirm')
    if (!props.persistent) {
      handleClose()
    }
  }
}

const handleCancel = () => {
  emit('cancel')
  handleClose()
}

const handleBackdropClick = () => {
  const canClose = props.closeOnBackdrop || props.closeOnClickOutside
  emit('backdrop')
  emit('backdrop-click')
  if (canClose && !props.persistent) {
    handleClose()
  }
}

const handleEscapeKey = () => {
  emit('escape')
  if (props.closeOnEscape && !props.persistent) {
    handleClose()
  }
}

// Focus management
const trapFocus = () => {
  if (!modalRef.value) return

  const focusableElements = modalRef.value.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )
  const firstElement = focusableElements[0] as HTMLElement
  const lastElement = focusableElements[focusableElements.length - 1] as HTMLElement

  const handleTabKey = (e: KeyboardEvent) => {
    if (e.key !== 'Tab') return

    if (e.shiftKey) {
      if (document.activeElement === firstElement) {
        e.preventDefault()
        lastElement?.focus()
      }
    } else {
      if (document.activeElement === lastElement) {
        e.preventDefault()
        firstElement?.focus()
      }
    }
  }

  modalRef.value.addEventListener('keydown', handleTabKey)
  
  return () => {
    modalRef.value?.removeEventListener('keydown', handleTabKey)
  }
}

// Lifecycle hooks
const onEnter = async () => {
  emit('open')
  
  // Save current active element
  previousActiveElement.value = document.activeElement as HTMLElement
  
  // Lock body scroll
  const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth
  document.body.style.paddingRight = `${scrollbarWidth}px`
  document.body.style.overflow = 'hidden'
  
  await nextTick()
  
  // Focus first focusable element
  const focusableElements = modalRef.value?.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )
  
  if (focusableElements && focusableElements.length > 0) {
    (focusableElements[0] as HTMLElement).focus()
  }
  
  // Set up focus trap
  trapFocus()
}

const onLeave = () => {
  // Restore body scroll
  document.body.style.paddingRight = ''
  document.body.style.overflow = ''
  
  // Return focus to previous element
  if (previousActiveElement.value) {
    previousActiveElement.value.focus()
  }
}

// Watch для поддержки обоих props: modelValue и show
const isVisible = computed(() => props.modelValue || props.show)

watch(isVisible, (newValue) => {
  if (newValue) {
    nextTick(() => onEnter())
  } else {
    onLeave()
  }
})

// Cleanup on unmount
onUnmounted(() => {
  if (isVisible.value) {
    document.body.style.overflow = ''
    document.body.style.paddingRight = ''
  }
})
</script>

<style scoped>
.modal-backdrop {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4;
  z-index: v-bind(zIndex);
}

.modal-container {
  @apply bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-hidden flex flex-col;
}

.modal-container--centered {
  @apply mx-auto;
}

.modal-container--fullscreen {
  @apply max-w-none max-h-none h-full w-full rounded-none;
}

/* Sizes */
.modal-container--sm {
  @apply max-w-sm;
}

.modal-container--md {
  @apply max-w-md;
}

.modal-container--lg {
  @apply max-w-lg;
}

.modal-container--xl {
  @apply max-w-xl;
}

.modal-container--2xl {
  @apply max-w-2xl;
}

.modal-container--full {
  @apply max-w-4xl;
}

/* Header */
.modal-header {
  @apply flex items-center justify-between p-4 sm:p-6 border-b border-gray-200;
}

.modal-header-content {
  @apply flex-1;
}

.modal-title {
  @apply text-lg sm:text-xl font-semibold text-gray-900;
}

.modal-close-button {
  @apply p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500;
}

/* Content */
.modal-content {
  @apply flex-1 overflow-y-auto p-4 sm:p-6;
}

.modal-content--no-header {
  @apply pt-8;
}

/* Footer */
.modal-footer {
  @apply px-4 sm:px-6 py-4 border-t border-gray-200 bg-gray-50;
}

.modal-actions {
  @apply flex justify-end space-x-3;
}

/* Buttons */
.modal-button {
  @apply px-4 py-2 text-sm font-medium rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
}

.modal-button--secondary {
  @apply text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 focus:ring-gray-500;
}

.modal-button--primary {
  @apply text-white bg-blue-600 hover:bg-blue-700 focus:ring-blue-500;
}

.modal-button--danger {
  @apply text-white bg-red-600 hover:bg-red-700 focus:ring-red-500;
}

.modal-button--warning {
  @apply text-white bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500;
}

.modal-button--success {
  @apply text-white bg-green-600 hover:bg-green-700 focus:ring-green-500;
}

.modal-button--info {
  @apply text-white bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500;
}

.modal-button--loading {
  @apply opacity-50 cursor-not-allowed;
}

/* Animations */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.9) translateY(-20px);
}

/* Responsive */
@media (max-width: 640px) {
  .modal-container {
    @apply max-w-none mx-2 max-h-[95vh];
  }
  
  .modal-header,
  .modal-content,
  .modal-footer {
    @apply px-4;
  }
  
  .modal-content {
    @apply py-4;
  }
  
  .modal-actions {
    @apply flex-col space-x-0 space-y-2;
  }
  
  .modal-button {
    @apply w-full justify-center;
  }
}

/* High contrast for accessibility */
@media (prefers-contrast: high) {
  .modal-backdrop {
    @apply bg-black bg-opacity-80;
  }
  
  .modal-container {
    @apply border-2 border-gray-900;
  }
}

/* Reduced motion for accessibility */
@media (prefers-reduced-motion: reduce) {
  .modal-enter-active,
  .modal-leave-active {
    transition: opacity 0.1s ease;
  }
  
  .modal-enter-active .modal-container,
  .modal-leave-active .modal-container {
    transition: none;
  }
  
  .modal-enter-from .modal-container,
  .modal-leave-to .modal-container {
    transform: none;
  }
}
</style>