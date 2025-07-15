<template>
  <div v-if="show" class="modal-overlay" @click="$emit('close')">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="modal-title">Телефон мастера</h3>
        <button @click="$emit('close')" class="modal-close">
          <XMarkIcon class="w-6 h-6" />
        </button>
      </div>
      
      <div class="modal-body">
        <div class="phone-display">
          <PhoneIcon class="w-8 h-8 text-blue-600" />
          <span class="phone-number">{{ phone }}</span>
        </div>
        
        <div class="phone-actions">
          <button @click="callPhone" class="call-btn">
            <PhoneIcon class="w-5 h-5" />
            Позвонить
          </button>
          
          <button @click="copyPhone" class="copy-btn">
            <ClipboardIcon class="w-5 h-5" />
            Скопировать
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { PhoneIcon, XMarkIcon, ClipboardIcon } from '@heroicons/vue/24/outline'

defineProps({
  show: Boolean,
  phone: String
})

defineEmits(['close'])

const callPhone = () => {
  window.open(`tel:${props.phone}`)
}

const copyPhone = () => {
  navigator.clipboard.writeText(props.phone)
  alert('Телефон скопирован!')
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