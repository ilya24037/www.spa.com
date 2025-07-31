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
        :model-value="phone"
        @update:model-value="updatePhone"
        :error="errors.phone"
      />

      <!-- Способ связи -->
      <ContactMethods
        :model-value="contactMethod"
        @update:model-value="updateContactMethod"
        :error="errors.contact_method"
      />

      <!-- Дополнительные мессенджеры -->
      <MessengerInputs
        :whatsapp="whatsapp"
        :telegram="telegram"
        @update:whatsapp="updateWhatsapp"
        @update:telegram="updateTelegram"
        :errors="errors"
      />

      <!-- Настройки конфиденциальности -->
      <PrivacySettings
        v-model:hide-phone="hidePhoneNumber"
        v-model:show-online="showOnlineStatus"
      />

      <!-- Предварительный просмотр -->
      <ContactsPreview
        :phone="phone"
        :contact-method="contactMethod"
        :whatsapp="whatsapp"
        :telegram="telegram"
        :hide-phone="hidePhoneNumber"
      />

      <!-- Советы -->
      <ContactsTips />
    </div>
  </FormSection>
</template>

<script setup>
import { computed, ref } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import { useAdFormStore } from '../../../stores/adFormStore'

// Микрокомпоненты
import PrimaryPhone from './components/PrimaryPhone.vue'
import ContactMethods from './components/ContactMethods.vue'
import MessengerInputs from './components/MessengerInputs.vue'
import PrivacySettings from './components/PrivacySettings.vue'
import ContactsPreview from './components/ContactsPreview.vue'
import ContactsTips from './components/ContactsTips.vue'

// AVITO-STYLE: Используем централизованный store
const store = useAdFormStore()

const props = defineProps({
  errors: { type: Object, default: () => ({}) }
})

// Читаем данные ТОЛЬКО из store (как на Avito)
const phone = computed(() => store.formData.phone || '')
const contactMethod = computed(() => store.formData.contact_method || '')
const whatsapp = computed(() => store.formData.whatsapp || '')
const telegram = computed(() => store.formData.telegram || '')

// Локальные настройки (не нужно сохранять в store)
const hidePhoneNumber = ref(false)
const showOnlineStatus = ref(true)

// Методы обновляют ТОЛЬКО store (как на Avito/Ozon)
const updatePhone = (value) => {
  console.log('updatePhone called:', value)
  store.updateField('phone', value)
}

const updateContactMethod = (value) => {
  console.log('updateContactMethod called:', value)
  store.updateField('contact_method', value)
}

const updateWhatsapp = (value) => {
  console.log('updateWhatsapp called:', value)
  store.updateField('whatsapp', value)
}

const updateTelegram = (value) => {
  console.log('updateTelegram called:', value)
  store.updateField('telegram', value)
}
</script>