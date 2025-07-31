<template>
  <Card v-if="hasContacts" variant="elevated" class="bg-green-50 border-green-200">
    <div class="flex items-center space-x-2 mb-3">
      <span class="text-lg">👁️</span>
      <span class="text-sm font-medium text-green-800">
        Как увидят клиенты:
      </span>
    </div>
    
    <!-- Основные контакты -->
    <div class="space-y-2 mb-3">
      <div class="flex justify-between items-center">
        <span class="text-sm font-medium text-green-700">Телефон:</span>
        <span class="text-sm text-green-700">
          {{ hidePhone ? 'Показать номер' : formatPhone(phone) }}
        </span>
      </div>
      
      <div v-if="contactMethod" class="flex justify-between items-center">
        <span class="text-sm font-medium text-green-700">Способ связи:</span>
        <span class="text-sm text-green-700">{{ getMethodTitle(contactMethod) }}</span>
      </div>
    </div>
    
    <!-- Мессенджеры -->
    <div v-if="hasMessengers" class="pt-3 border-t border-green-200">
      <div class="text-sm font-medium text-green-700 mb-2">Также доступно:</div>
      <div class="flex flex-wrap gap-2">
        <span v-if="whatsapp" class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
          📱 WhatsApp
        </span>
        <span v-if="telegram" class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
          📲 {{ telegram }}
        </span>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  phone: { type: String, default: '' },
  contactMethod: { type: String, default: '' },
  whatsapp: { type: String, default: '' },
  telegram: { type: String, default: '' },
  hidePhone: { type: Boolean, default: false }
})

// Computed
const hasContacts = computed(() => props.phone || props.contactMethod)
const hasMessengers = computed(() => props.whatsapp || props.telegram)

// Методы
const formatPhone = (phone) => {
  const clean = phone.replace(/\D/g, '')
  if (clean.length === 10) {
    return `+7 (${clean.substring(0, 3)}) ${clean.substring(3, 6)}-${clean.substring(6, 8)}-${clean.substring(8)}`
  }
  return phone
}

const getMethodTitle = (method) => {
  const methods = {
    call: 'Звонок',
    sms: 'SMS',
    whatsapp: 'WhatsApp',
    telegram: 'Telegram',
    any: 'Любой способ'
  }
  return methods[method] || method
}
</script>