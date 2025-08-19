<template>
  <div class="bg-white rounded-lg p-5">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Контакты</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <BaseInput
        v-model="localPhone"
        type="tel"
        label="Телефон"
        placeholder="+7 (999) 999-99-99"
        hint="Основной номер для связи"
        pattern="[+]7\s?[\(]?[0-9]{3}[\)]?\s?[0-9]{3}[-]?[0-9]{2}[-]?[0-9]{2}"
        :error="errors.phone"
        @update:modelValue="emitAll"
      />
      
      <BaseInput
        v-model="localWhatsapp"
        v-maska="'+7 (###) ###-##-##'"
        type="tel"
        label="WhatsApp"
        placeholder="+7 (999) 999-99-99"
        hint="Оставьте пустым, если совпадает с телефоном"
        :error="errors.whatsapp"
        @update:modelValue="emitAll"
      />
      
      <BaseInput
        v-model="localTelegram"
        type="text"
        label="Telegram"
        placeholder="@username или +7 (999) 999-99-99"
        hint="Ник или номер телефона"
        :error="errors.telegram"
        @update:modelValue="emitAll"
      />
      
      <BaseSelect
        v-model="localContactMethod"
        label="Способ связи"
        :options="contactMethodOptions"
        hint="Как клиенты могут с вами связаться"
        :error="errors.contactMethod"
        @update:modelValue="emitAll"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { vMaska } from 'maska/vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseSelect from '@/src/shared/ui/atoms/BaseSelect/BaseSelect.vue'

const props = defineProps({
  phone: { type: String, default: '' },
  contactMethod: { type: String, default: 'any' },
  whatsapp: { type: String, default: '' },
  telegram: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:phone', 'update:contactMethod', 'update:whatsapp', 'update:telegram'])

const localPhone = ref(props.phone)
const localContactMethod = ref(props.contactMethod)
const localWhatsapp = ref(props.whatsapp)
const localTelegram = ref(props.telegram)

// Опции для способа связи
const contactMethodOptions = computed(() => [
  { value: 'phone', label: 'Только звонки' },
  { value: 'whatsapp', label: 'Только WhatsApp' },
  { value: 'telegram', label: 'Только Telegram' },
  { value: 'messages', label: 'Сообщения (любой мессенджер)' },
  { value: 'any', label: 'Любой способ' }
])

watch(() => props.phone, val => { localPhone.value = val })
watch(() => props.contactMethod, val => { localContactMethod.value = val })
watch(() => props.whatsapp, val => { localWhatsapp.value = val })
watch(() => props.telegram, val => { localTelegram.value = val })

const emitAll = () => {
  emit('update:phone', localPhone.value)
  emit('update:contactMethod', localContactMethod.value)
  emit('update:whatsapp', localWhatsapp.value)
  emit('update:telegram', localTelegram.value)
}
</script>

<!-- Все стили мигрированы на Tailwind CSS в template --> 