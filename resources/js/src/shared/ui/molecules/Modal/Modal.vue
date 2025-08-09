<template>
  <!-- Teleport РґР»СЏ РїСЂР°РІРёР»СЊРЅРѕРіРѕ СЂРµРЅРґРµСЂРёРЅРіР° - CLAUDE.md вњ… -->
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="modelValue"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        :aria-modal="true"
        :aria-labelledby="titleId"
        @click.self="handleClose"
      >
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" />
        
        <!-- Modal -->
        <div class="flex min-h-full items-center justify-center p-4">
          <div
            :class="modalClasses"
            @click.stop
          >
            <!-- Header -->
            <header v-if="title || $slots.header" class="flex items-center justify-between p-4 sm:p-6 border-b">
              <slot name="header">
                <h2 :id="titleId" class="text-lg sm:text-xl font-semibold text-gray-500">
                  {{ title }}
                </h2>
              </slot>
              
              <button
                v-if="showClose"
                class="text-gray-500 hover:text-gray-500 transition-colors"
                :aria-label="closeAriaLabel"
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
            <main class="p-4 sm:p-6">
              <slot />
            </main>
            
            <!-- Footer -->
            <footer v-if="$slots.footer" class="p-4 sm:p-6 border-t bg-gray-500">
              <slot name="footer" />
            </footer>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, watch, onMounted, onUnmounted } from 'vue'

// TypeScript С‚РёРїРёР·Р°С†РёСЏ - Р§Р•Рљ-Р›РРЎРў CLAUDE.md вњ…
interface Props {
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  showClose?: boolean
  closeOnEscape?: boolean
  closeOnClickOutside?: boolean
  closeAriaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
    size: 'md',
    showClose: true,
    closeOnEscape: true,
    closeOnClickOutside: true,
    closeAriaLabel: 'Р—Р°РєСЂС‹С‚СЊ'
})

const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'close': []
}>()

// РЈРЅРёРєР°Р»СЊРЅС‹Р№ ID РґР»СЏ aria-labelledby
const titleId = `modal-title-${Math.random().toString(36).substr(2, 9)}`

// Р Р°Р·РјРµСЂС‹ РјРѕРґР°Р»СЊРЅРѕРіРѕ РѕРєРЅР° - РјРѕР±РёР»СЊРЅР°СЏ Р°РґР°РїС‚РёРІРЅРѕСЃС‚СЊ вњ…
const modalClasses = computed(() => [
    'relative bg-white rounded-lg shadow-xl w-full transition-all',
    {
        'max-w-sm': props.size === 'sm',
        'max-w-lg': props.size === 'md',
        'max-w-2xl': props.size === 'lg',
        'max-w-4xl': props.size === 'xl',
        'max-w-full mx-4': props.size === 'full'
    }
])

const handleClose = () => {
    if (props.closeOnClickOutside) {
        emit('update:modelValue', false)
        emit('close')
    }
}

// РћР±СЂР°Р±РѕС‚РєР° Escape
const handleEscape = (e: KeyboardEvent) => {
    if (props.closeOnEscape && e.key === 'Escape' && props.modelValue) {
        handleClose()
    }
}

// Р‘Р»РѕРєРёСЂРѕРІРєР° СЃРєСЂРѕР»Р»Р° body
watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden'
    } else {
        document.body.style.overflow = ''
    }
})

onMounted(() => {
    document.addEventListener('keydown', handleEscape)
})

onUnmounted(() => {
    document.removeEventListener('keydown', handleEscape)
    document.body.style.overflow = ''
})
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>

