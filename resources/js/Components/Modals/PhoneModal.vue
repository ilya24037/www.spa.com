<template>
  <div 
    v-if="show" 
    class="modal-overlay" 
    data-testid="phone-modal-overlay"
    role="dialog"
    aria-modal="true"
    aria-labelledby="phone-modal-title"
    @click="handleOverlayClick"
    @keydown="handleKeydown"
  >
    <div 
      class="modal-content" 
      data-testid="phone-modal-content"
      @click.stop
    >
      <div class="modal-header">
        <h3 
          id="phone-modal-title"
          class="modal-title"
          data-testid="phone-modal-title"
        >
          Телефон мастера
        </h3>
        <button 
          @click="closeModal" 
          class="modal-close"
          data-testid="phone-modal-close"
          aria-label="Закрыть модальное окно"
        >
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>
      
      <div class="modal-body">
        <div class="phone-display" data-testid="phone-display">
          <PhoneIcon class="w-8 h-8 text-blue-600" aria-hidden="true" />
          <span 
            class="phone-number"
            data-testid="phone-number"
            :class="{ 'text-red-600': !phoneInfo.isValid }"
          >
            {{ phoneInfo.formatted || phoneInfo.original }}
          </span>
          <span 
            v-if="!phoneInfo.isValid && phoneInfo.original"
            class="text-sm text-red-500 ml-2"
            data-testid="phone-validation-error"
          >
            Некорректный номер
          </span>
        </div>
        
        <div class="phone-actions" data-testid="phone-actions">
          <button 
            @click="callPhone" 
            class="call-btn"
            data-testid="call-button"
            :disabled="!phoneInfo.isValid"
            :aria-label="`Позвонить по номеру ${phoneInfo.formatted || phoneInfo.original}`"
          >
            <PhoneIcon class="w-5 h-5" aria-hidden="true" />
            <span>Позвонить</span>
          </button>
          
          <button 
            @click="copyPhone" 
            class="copy-btn"
            data-testid="copy-button"
            :disabled="state.isCopying || !phoneInfo.original"
            :class="{
              'bg-green-600 hover:bg-green-700': state.copySuccess,
              'opacity-50 cursor-not-allowed': state.isCopying || !phoneInfo.original
            }"
            :aria-label="`Скопировать номер ${phoneInfo.formatted || phoneInfo.original}`"
          >
            <ClipboardIcon 
              v-if="!state.copySuccess" 
              class="w-5 h-5" 
              aria-hidden="true" 
            />
            <svg 
              v-else
              class="w-5 h-5" 
              fill="none" 
              stroke="currentColor" 
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M5 13l4 4L19 7"
              />
            </svg>
            <span>
              {{ state.isCopying ? 'Копирование...' : state.copySuccess ? 'Скопировано!' : 'Скопировать' }}
            </span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { PhoneIcon, XMarkIcon, ClipboardIcon } from '@heroicons/vue/24/outline'
import { useToast } from '@/src/shared/composables/useToast'
import type { 
  PhoneModalProps, 
  PhoneModalEmits, 
  PhoneModalState, 
  PhoneInfo, 
  PhoneModalError 
} from './PhoneModal.types'

// Props и emits с TypeScript типизацией
const props = defineProps<PhoneModalProps>()
const emit = defineEmits<PhoneModalEmits>()

// Toast для замены alert()
const toast = useToast()

// Реактивное состояние компонента
const state = ref<PhoneModalState>({
  isCopying: false,
  copySuccess: false
})

// Computed свойство для обработки телефона
const phoneInfo = computed<PhoneInfo>(() => {
  const phone = props.phone || ''
  const cleaned = phone.replace(/\D/g, '')
  const formatted = formatPhoneNumber(phone)
  const isValid = validatePhoneNumber(cleaned)
  
  return {
    original: phone,
    formatted,
    cleaned,
    isValid
  }
})

// Форматирование телефона для отображения
const formatPhoneNumber = (phone: string): string => {
  if (!phone) return ''
  
  const cleaned = phone.replace(/\D/g, '')
  
  if (cleaned.length === 11 && cleaned.startsWith('7')) {
    return `+7 (${cleaned.slice(1, 4)}) ${cleaned.slice(4, 7)}-${cleaned.slice(7, 9)}-${cleaned.slice(9)}`
  }
  
  return phone
}

// Валидация номера телефона
const validatePhoneNumber = (phone: string): boolean => {
  return /^[78]\d{10}$/.test(phone)
}

// Обработка вызова
const callPhone = async (): Promise<void> => {
  try {
    if (!phoneInfo.value.isValid) {
      toast.error('Некорректный номер телефона')
      return
    }
    
    const telUrl = `tel:${phoneInfo.value.cleaned}`
    window.open(telUrl)
    emit('called', phoneInfo.value.original)
    toast.info('Открываю приложение для звонка...')
  } catch (error: unknown) {
    const phoneError: PhoneModalError = {
      type: 'tel',
      message: 'Не удалось открыть приложение для звонка',
      originalError: error
    }
    toast.error(phoneError.message)
  }
}

// Обработка копирования в буфер обмена
const copyPhone = async (): Promise<void> => {
  try {
    state.value.isCopying = true
    state.value.copySuccess = false
    
    if (!navigator.clipboard) {
      throw new Error('Clipboard API не поддерживается')
    }
    
    await navigator.clipboard.writeText(phoneInfo.value.original)
    
    state.value.copySuccess = true
    emit('copied', phoneInfo.value.original)
    toast.success('Телефон скопирован в буфер обмена!')
    
    // Сброс состояния через 2 секунды
    setTimeout(() => {
      state.value.copySuccess = false
    }, 2000)
    
  } catch (error: unknown) {
    const phoneError: PhoneModalError = {
      type: 'clipboard',
      message: 'Не удалось скопировать телефон',
      originalError: error
    }
    toast.error(phoneError.message)
  } finally {
    state.value.isCopying = false
  }
}

// Закрытие модального окна
const closeModal = (): void => {
  emit('close')
}

// Обработка клика по оверлею
const handleOverlayClick = (event: MouseEvent): void => {
  if (event.target === event.currentTarget) {
    closeModal()
  }
}

// Обработка Escape клавиши
const handleKeydown = (event: KeyboardEvent): void => {
  if (event.key === 'Escape') {
    closeModal()
  }
}
</script>

<style scoped>
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50;
}

.modal-content {
  @apply bg-white rounded-lg p-6 max-w-md w-full mx-4;
}

.modal-header {
  @apply flex justify-between items-center mb-4;
}

.modal-title {
  @apply text-xl font-bold text-gray-900;
}

.modal-close {
  @apply text-gray-400 hover:text-gray-600;
}

.phone-display {
  @apply flex items-center gap-3 mb-6 p-4 bg-gray-50 rounded-lg;
}

.phone-number {
  @apply text-2xl font-bold text-gray-900;
}

.phone-actions {
  @apply flex gap-3;
}

.call-btn, .copy-btn {
  @apply flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-lg font-medium transition-colors;
}

.call-btn {
  @apply bg-blue-600 text-white hover:bg-blue-700;
}

.copy-btn {
  @apply bg-gray-600 text-white hover:bg-gray-700;
}
</style> 