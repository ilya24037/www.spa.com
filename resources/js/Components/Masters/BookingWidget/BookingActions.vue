<!-- resources/js/Components/Masters/BookingWidget/BookingActions.vue -->
<template>
  <div class="booking-actions space-y-3">
    <!-- Кнопка записи -->
    <button 
      @click="$emit('book')"
      class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-indigo-700 transition-colors flex items-center justify-center gap-2"
      :class="{ 'animate-pulse': isAvailable }"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <span>Записаться на приём</span>
    </button>
    
    <!-- Кнопка телефона -->
    <button 
      @click="handleCall"
      class="w-full bg-green-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-600 transition-colors flex items-center justify-center gap-2"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
      </svg>
      <span v-if="!showPhone">Показать телефон</span>
      <span v-else class="font-mono">{{ formattedPhone }}</span>
    </button>
    
    <!-- Быстрая связь через мессенджеры -->
    <div v-if="hasMessengers" class="flex gap-2">
      <a 
        v-if="whatsapp"
        :href="`https://wa.me/${cleanPhone(whatsapp)}`"
        target="_blank"
        rel="noopener"
        class="flex-1 bg-green-500 text-white py-2.5 px-4 rounded-lg font-medium hover:bg-green-600 transition-colors flex items-center justify-center gap-2"
        @click="trackMessenger('whatsapp')"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
        </svg>
        <span>WhatsApp</span>
      </a>
      
      <a 
        v-if="telegram"
        :href="`https://t.me/${telegram.replace('@', '')}`"
        target="_blank"
        rel="noopener"
        class="flex-1 bg-blue-500 text-white py-2.5 px-4 rounded-lg font-medium hover:bg-blue-600 transition-colors flex items-center justify-center gap-2"
        @click="trackMessenger('telegram')"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.223-.548.223l.188-2.85 5.18-4.68c.223-.198-.054-.308-.346-.11l-6.4 4.02-2.76-.918c-.6-.187-.612-.6.125-.89l10.782-4.156c.5-.18.94.12.78.88z"/>
        </svg>
        <span>Telegram</span>
      </a>
    </div>
    
    <!-- Быстрый вопрос -->
    <button 
      v-if="!hasMessengers"
      @click="$emit('ask-question')"
      class="w-full border border-gray-300 text-gray-700 py-2.5 px-4 rounded-lg font-medium hover:bg-gray-50 transition-colors flex items-center justify-center gap-2"
    >
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
      </svg>
      <span>Задать вопрос</span>
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  phone: {
    type: String,
    required: true
  },
  whatsapp: {
    type: String,
    default: null
  },
  telegram: {
    type: String,
    default: null
  },
  isAvailable: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['book', 'call', 'ask-question'])

// Состояние
const showPhone = ref(false)

// Вычисляемые свойства
const hasMessengers = computed(() => props.whatsapp || props.telegram)

const formattedPhone = computed(() => {
  if (!props.phone) return ''
  const cleaned = props.phone.replace(/\D/g, '')
  return `+7 (${cleaned.slice(1, 4)}) ${cleaned.slice(4, 7)}-${cleaned.slice(7, 9)}-${cleaned.slice(9, 11)}`
})

// Методы
const handleCall = () => {
  if (!showPhone.value) {
    showPhone.value = true
    emit('call')
    
    // Копируем телефон в буфер обмена
    if (navigator.clipboard) {
      navigator.clipboard.writeText(props.phone)
    }
  } else {
    // Звоним
    window.location.href = `tel:${props.phone}`
  }
}

const cleanPhone = (phone) => {
  return phone.replace(/\D/g, '')
}

const trackMessenger = (type) => {
  // Отслеживание клика по мессенджеру
  if (window.gtag) {
    window.gtag('event', 'contact_messenger', {
      messenger_type: type
    })
  }
}
</script>