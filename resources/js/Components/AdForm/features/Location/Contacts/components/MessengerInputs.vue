<template>
  <Card variant="bordered" class="bg-slate-50">
    <div class="space-y-4">
      <div class="flex items-center space-x-2 mb-4">
        <span class="text-lg">💬</span>
        <span class="font-semibold text-gray-800">Дополнительные способы связи</span>
      </div>
      <p class="text-sm text-gray-600 mb-4">
        Добавьте альтернативные способы связи (необязательно)
      </p>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- WhatsApp -->
        <FormField
          label="WhatsApp"
          hint="Номер для WhatsApp (может отличаться от основного)"
          :error="errors.whatsapp"
        >
          <BaseInput
            v-model="localWhatsapp"
            type="tel"
            placeholder="+7 (999) 123-45-67"
            prefix="📱"
            @input="updateWhatsapp"
          />
        </FormField>

        <!-- Telegram -->
        <FormField
          label="Telegram"
          hint="Ваш никнейм в Telegram"
          :error="errors.telegram"
        >
          <BaseInput
            v-model="localTelegram"
            type="text"
            placeholder="@username"
            prefix="📲"
            @input="updateTelegram"
          />
        </FormField>
      </div>
    </div>
  </Card>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'
import Card from '@/Components/UI/Cards/Card.vue'

const props = defineProps({
  whatsapp: { type: String, default: '' },
  telegram: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:whatsapp', 'update:telegram'])

const localWhatsapp = ref(props.whatsapp)
const localTelegram = ref(props.telegram)

// Отслеживание изменений пропсов
watch(() => props.whatsapp, (newValue) => {
  localWhatsapp.value = newValue || ''
})

watch(() => props.telegram, (newValue) => {
  localTelegram.value = newValue || ''
})

// Методы обновления
const updateWhatsapp = () => {
  emit('update:whatsapp', localWhatsapp.value)
}

const updateTelegram = () => {
  // Автоматически добавляем @ если забыли
  if (localTelegram.value && !localTelegram.value.startsWith('@')) {
    localTelegram.value = '@' + localTelegram.value
  }
  emit('update:telegram', localTelegram.value)
}
</script>