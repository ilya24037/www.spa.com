<template>
  <Teleport to="body">
    <Transition
      name="modal"
      @enter="onEnter"
      @leave="onLeave"
    >
      <div
        v-show="modelValue"
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
          <!-- Р—Р°РіРѕР»РѕРІРѕРє РјРѕРґР°Р»СЊРЅРѕРіРѕ РѕРєРЅР° -->
          <header
            v-if="showHeader"
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
              v-if="closable"
              type="button"
              class="modal-close-button"
              aria-label="Р—Р°РєСЂС‹С‚СЊ РјРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ"
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

          <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
          <main
            :id="contentId"
            class="modal-content"
            :class="{ 'modal-content--no-header': !showHeader }"
          >
            <slot
              :close="handleClose"
              :title-id="titleId"
              :content-id="contentId"
            />
          </main>

          <!-- Р¤СѓС‚РµСЂ СЃ РєРЅРѕРїРєР°РјРё -->
          <footer
            v-if="showFooter"
            class="modal-footer"
          >
            <slot
              name="footer"
              :close="handleClose"
            >
              <!-- РљРЅРѕРїРєРё РїРѕ СѓРјРѕР»С‡Р°РЅРёСЋ -->
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
                  :class="{ [`modal-button--${variant}`]: variant !== 'primary' }"
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
import { generateUniqueId } from '@/src/shared/lib/utils'

interface Props {
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  variant?: 'primary' | 'danger' | 'warning' | 'success'
  centered?: boolean
  fullscreen?: boolean
  closable?: boolean
  closeOnBackdrop?: boolean
  closeOnEscape?: boolean
  showHeader?: boolean
  showFooter?: boolean
  showCancelButton?: boolean
  showConfirmButton?: boolean
  confirmText?: string
  cancelText?: string
  persistent?: boolean
  zIndex?: number
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  variant: 'primary',
  centered: true,
  fullscreen: false,
  closable: true,
  closeOnBackdrop: true,
  closeOnEscape: true,
  showHeader: true,
  showFooter: false,
  showCancelButton: false,
  showConfirmButton: false,
  confirmText: 'РџРѕРґС‚РІРµСЂРґРёС‚СЊ',
  cancelText: 'РћС‚РјРµРЅР°',
  persistent: false,
  zIndex: 50
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  open: []
  close: []
  confirm: []
  cancel: []
  backdrop: []
  escape: []
}>()

// Refs
const modalRef = ref<HTMLElement>()
const previousActiveElement = ref<HTMLElement | null>(null)

// РЈРЅРёРєР°Р»СЊРЅС‹Рµ ID РґР»СЏ accessibility
const titleId = generateUniqueId('modal-title')
const contentId = generateUniqueId('modal-content')

// Computed
const sizeClasses = computed(() => {
  const sizes = {
    sm: 'modal-container--sm',
    md: 'modal-container--md',
    lg: 'modal-container--lg',
    xl: 'modal-container--xl',
    full: 'modal-container--full'
  }
  return sizes[props.size]
})

// РњРµС‚РѕРґС‹
const handleClose = () => {
  if (!props.persistent) {
    emit('update:modelValue', false)
    emit('close')
  }
}

const handleConfirm = () => {
  emit('confirm')
  if (!props.persistent) {
    handleClose()
  }
}

const handleCancel = () => {
  emit('cancel')
  handleClose()
}

const handleBackdropClick = () => {
  emit('backdrop')
  if (props.closeOnBackdrop) {
    handleClose()
  }
}

const handleEscapeKey = () => {
  emit('escape')
  if (props.closeOnEscape) {
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

const onEnter = async () => {
  emit('open')
  
  // РЎРѕС…СЂР°РЅСЏРµРј С‚РµРєСѓС‰РёР№ Р°РєС‚РёРІРЅС‹Р№ СЌР»РµРјРµРЅС‚
  previousActiveElement.value = document.activeElement as HTMLElement
  
  // РћС‚РєР»СЋС‡Р°РµРј СЃРєСЂРѕР»Р» body
  document.body.style.overflow = 'hidden'
  
  await nextTick()
  
  // Р¤РѕРєСѓСЃРёСЂСѓРµРјСЃСЏ РЅР° РїРµСЂРІС‹Р№ СЌР»РµРјРµРЅС‚ РјРѕРґР°Р»РєРё
  const focusableElements = modalRef.value?.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )
  
  if (focusableElements && focusableElements.length > 0) {
    (focusableElements[0] as HTMLElement).focus()
  }
  
  // РЈСЃС‚Р°РЅР°РІР»РёРІР°РµРј trap focus
  trapFocus()
}

const onLeave = () => {
  // Р’РѕСЃСЃС‚Р°РЅР°РІР»РёРІР°РµРј СЃРєСЂРѕР»Р» body
  document.body.style.overflow = ''
  
  // Р’РѕР·РІСЂР°С‰Р°РµРј С„РѕРєСѓСЃ РЅР° РїСЂРµРґС‹РґСѓС‰РёР№ СЌР»РµРјРµРЅС‚
  if (previousActiveElement.value) {
    previousActiveElement.value.focus()
  }
}

// РћС‚СЃР»РµР¶РёРІР°РµРј РёР·РјРµРЅРµРЅРёСЏ modelValue РґР»СЏ СѓРїСЂР°РІР»РµРЅРёСЏ С„РѕРєСѓСЃРѕРј
watch(() => props.modelValue, (isOpen) => {
  if (isOpen) {
    nextTick(() => onEnter())
  } else {
    onLeave()
  }
})

// Cleanup РїСЂРё СЂР°Р·РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё
onUnmounted(() => {
  if (props.modelValue) {
    document.body.style.overflow = ''
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

/* Р Р°Р·РјРµСЂС‹ РјРѕРґР°Р»СЊРЅС‹С… РѕРєРѕРЅ */
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
  @apply max-w-2xl;
}

.modal-container--full {
  @apply max-w-4xl;
}

/* Р—Р°РіРѕР»РѕРІРѕРє */
.modal-header {
  @apply flex items-center justify-between p-6 border-b border-gray-200;
}

.modal-header-content {
  @apply flex-1;
}

.modal-title {
  @apply text-lg font-semibold text-gray-900;
}

.modal-close-button {
  @apply p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500;
}

/* РљРѕРЅС‚РµРЅС‚ */
.modal-content {
  @apply flex-1 overflow-y-auto p-6;
}

.modal-content--no-header {
  @apply pt-8;
}

/* Р¤СѓС‚РµСЂ */
.modal-footer {
  @apply px-6 py-4 border-t border-gray-200 bg-gray-50;
}

.modal-actions {
  @apply flex justify-end space-x-3;
}

/* РљРЅРѕРїРєРё */
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

/* РђРЅРёРјР°С†РёРё */
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

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
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

/* Р’С‹СЃРѕРєРёР№ РєРѕРЅС‚СЂР°СЃС‚ РґР»СЏ accessibility */
@media (prefers-contrast: high) {
  .modal-backdrop {
    @apply bg-black bg-opacity-80;
  }
  
  .modal-container {
    @apply border-2 border-gray-800;
  }
}

/* РЈРјРµРЅСЊС€РµРЅРЅРѕРµ РґРІРёР¶РµРЅРёРµ РґР»СЏ accessibility */
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

