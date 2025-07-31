<template>
  <FormField
    label="Предпочтительный способ связи"
    hint="Как клиентам лучше с вами связываться"
    :error="error"
  >
    <!-- Используем готовые BaseRadio вместо кастомных карточек -->
    <div class="space-y-3">
      <BaseRadio
        v-for="method in contactMethods"
        :key="method.value"
        v-model="localValue"
        :value="method.value"
        :label="method.title"
        :description="method.description"
        :icon="method.icon"
      />
    </div>
  </FormField>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseRadio from '@/Components/UI/BaseRadio.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  error: { type: String, default: '' }
})

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue)

watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue || ''
})

watch(localValue, (newValue) => {
  emit('update:modelValue', newValue)
})

// Способы связи
const contactMethods = [
  {
    value: 'call',
    title: 'Звонок',
    description: 'Предпочитаю голосовое общение',
    icon: '📞'
  },
  {
    value: 'sms',
    title: 'SMS',
    description: 'Удобно получать текстовые сообщения',
    icon: '💬'
  },
  {
    value: 'whatsapp',
    title: 'WhatsApp',
    description: 'Общение через WhatsApp',
    icon: '📱'
  },
  {
    value: 'telegram',
    title: 'Telegram',
    description: 'Переписка в Telegram',
    icon: '📲'
  },
  {
    value: 'any',
    title: 'Любой способ',
    description: 'Подойдет любой удобный способ',
    icon: '🌐'
  }
]
</script>