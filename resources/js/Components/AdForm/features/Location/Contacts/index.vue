<template>
  <FormSection
    title="Контактная информация"
    hint="Укажите удобные способы связи с вами"
    required
    :error="errors.phone || errors.contact_method"
  >
    <div class="space-y-6">
      <!-- Основной телефон -->
      <PrimaryPhone
        v-model="localPhone"
        :error="errors.phone"
      />

      <!-- Способ связи -->
      <ContactMethods
        v-model="localContactMethod"
        :error="errors.contact_method"
      />

      <!-- Дополнительные мессенджеры -->
      <MessengerInputs
        v-model:whatsapp="localWhatsapp"
        v-model:telegram="localTelegram"
        :errors="errors"
      />

      <!-- Настройки конфиденциальности -->
      <PrivacySettings
        v-model:hide-phone="hidePhoneNumber"
        v-model:show-online="showOnlineStatus"
      />

      <!-- Предварительный просмотр -->
      <ContactsPreview
        :phone="localPhone"
        :contact-method="localContactMethod"
        :whatsapp="localWhatsapp"
        :telegram="localTelegram"
        :hide-phone="hidePhoneNumber"
      />

      <!-- Советы -->
      <ContactsTips />
    </div>
  </FormSection>
</template>

<script setup>
import { ref, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'

// Микрокомпоненты
import PrimaryPhone from './components/PrimaryPhone.vue'
import ContactMethods from './components/ContactMethods.vue'
import MessengerInputs from './components/MessengerInputs.vue'
import PrivacySettings from './components/PrivacySettings.vue'
import ContactsPreview from './components/ContactsPreview.vue'
import ContactsTips from './components/ContactsTips.vue'

const props = defineProps({
  phone: { type: String, default: '' },
  contactMethod: { type: String, default: '' },
  whatsapp: { type: String, default: '' },
  telegram: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits([
  'update:phone',
  'update:contactMethod',
  'update:whatsapp',
  'update:telegram'
])

// Локальное состояние
const localPhone = ref(props.phone || '')
const localContactMethod = ref(props.contactMethod || '')
const localWhatsapp = ref(props.whatsapp || '')
const localTelegram = ref(props.telegram || '')
const hidePhoneNumber = ref(false)
const showOnlineStatus = ref(true)

// Отслеживание изменений пропсов
watch(() => props.phone, (newValue) => { 
  localPhone.value = newValue || '' 
})
watch(() => props.contactMethod, (newValue) => { 
  localContactMethod.value = newValue || '' 
})
watch(() => props.whatsapp, (newValue) => { 
  localWhatsapp.value = newValue || '' 
})
watch(() => props.telegram, (newValue) => { 
  localTelegram.value = newValue || '' 
})

// Отправка изменений родителю
watch(localPhone, (newValue) => {
  emit('update:phone', newValue)
})
watch(localContactMethod, (newValue) => {
  emit('update:contactMethod', newValue)
})
watch(localWhatsapp, (newValue) => {
  emit('update:whatsapp', newValue)
})
watch(localTelegram, (newValue) => {
  emit('update:telegram', newValue)
})
</script>