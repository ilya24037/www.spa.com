<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="show" class="modal-overlay" @click="handleClose">
        <div class="modal-content" @click.stop>
          <!-- Заголовок -->
          <div class="modal-header">
            <h3 class="modal-title">Телефон мастера</h3>
            <button 
              @click="handleClose" 
              class="modal-close"
              aria-label="Закрыть"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
          
          <!-- Тело модалки -->
          <div class="modal-body">
            <!-- Отображение телефона -->
            <div class="phone-display">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
              </svg>
              <span class="phone-number">{{ formattedPhone }}</span>
            </div>
            
            <!-- Действия с телефоном -->
            <div class="phone-actions">
              <button 
                @click="handleCall" 
                class="call-btn"
                :disabled="!phone"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                </svg>
                Позвонить
              </button>
              
              <button 
                @click="handleCopy" 
                class="copy-btn"
                :disabled="!phone"
              >
                <svg v-if="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                </svg>
                <svg v-else class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ copied ? 'Скопировано!' : 'Скопировать' }}
              </button>
            </div>

            <!-- WhatsApp и Telegram если есть -->
            <div v-if="whatsapp || telegram" class="messengers">
              <h4 class="messengers-title">Также доступен в:</h4>
              <div class="messengers-buttons">
                <a 
                  v-if="whatsapp"
                  :href="`https://wa.me/${whatsapp.replace(/\D/g, '')}`"
                  target="_blank"
                  class="messenger-btn whatsapp"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.149-.67.149-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414-.074-.123-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                  </svg>
                  WhatsApp
                </a>
                
                <a 
                  v-if="telegram"
                  :href="`https://t.me/${telegram.replace('@', '')}`"
                  target="_blank"
                  class="messenger-btn telegram"
                >
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121L8.32 13.617l-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                  </svg>
                  Telegram
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { usePhoneModal } from '../composables/usePhoneModal'

// Props
interface Props {
  show: boolean
  phone: string
  whatsapp?: string
  telegram?: string
  masterName?: string
}

const props = defineProps<Props>()

// Emits
const emit = defineEmits<{
  close: []
}>()

// Composables
const { formatPhone, copyToClipboard } = usePhoneModal()

// State
const copied = ref(false)

// Computed
const formattedPhone = computed(() => formatPhone(props.phone))

// Methods
const handleClose = () => {
  emit('close')
  copied.value = false
}

const handleCall = () => {
  if (props.phone) {
    window.open(`tel:${props.phone}`)
  }
}

const handleCopy = async () => {
  if (props.phone) {
    const success = await copyToClipboard(props.phone)
    if (success) {
      copied.value = true
      setTimeout(() => {
        copied.value = false
      }, 2000)
    }
  }
}
</script>

<style scoped>
/* Анимация модалки */
.modal-enter-active,
.modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.9);
}

/* Основные стили */
.modal-overlay {
  @apply fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4;
}

.modal-content {
  @apply bg-white rounded-lg p-6 max-w-md w-full shadow-xl transition-transform;
}

.modal-header {
  @apply flex justify-between items-center mb-4;
}

.modal-title {
  @apply text-xl font-bold text-gray-900;
}

.modal-close {
  @apply text-gray-400 hover:text-gray-600 transition-colors;
}

.modal-body {
  @apply space-y-4;
}

.phone-display {
  @apply flex items-center gap-3 p-4 bg-gray-50 rounded-lg;
}

.phone-number {
  @apply text-2xl font-bold text-gray-900;
}

.phone-actions {
  @apply flex gap-3;
}

.call-btn, 
.copy-btn {
  @apply flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-lg font-medium transition-all;
}

.call-btn {
  @apply bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed;
}

.copy-btn {
  @apply bg-gray-600 text-white hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Мессенджеры */
.messengers {
  @apply pt-4 border-t border-gray-200;
}

.messengers-title {
  @apply text-sm text-gray-600 mb-2;
}

.messengers-buttons {
  @apply flex gap-2;
}

.messenger-btn {
  @apply flex-1 flex items-center justify-center gap-2 py-2 px-4 rounded-lg text-white font-medium transition-all;
}

.messenger-btn.whatsapp {
  @apply bg-green-500 hover:bg-green-600;
}

.messenger-btn.telegram {
  @apply bg-blue-500 hover:bg-blue-600;
}
</style>