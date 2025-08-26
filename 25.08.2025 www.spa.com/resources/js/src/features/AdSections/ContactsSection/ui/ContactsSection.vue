<template>
  <div class="bg-white rounded-lg p-5">
    <h2 class="text-lg font-semibold text-gray-900 mb-4">Контакты</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <!-- Все телефонные поля используют единую маску +7 (###) ###-##-## -->
      <BaseInput
        v-model="localPhone"
        v-maska="'+7 (###) ###-##-##'"
        type="tel"
        label="Телефон"
        placeholder="+7 (999) 999-99-99"
        hint="Основной номер для связи"
        pattern="^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$"
        :error="errors?.phone"
        @update:modelValue="emitAll"
      />
      
      <BaseInput
        v-model="localWhatsapp"
        v-maska="'+7 (###) ###-##-##'"
        type="tel"
        label="WhatsApp"
        placeholder="+7 (999) 999-99-99"
        hint="Оставьте пустым, если совпадает с телефоном"
        pattern="^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$"
        :error="errors?.whatsapp"
        @update:modelValue="emitAll"
      />
      
      <BaseInput
        v-model="localTelegram"
        type="text"
        label="Telegram"
        placeholder="@username или +7 (999) 999-99-99"
        hint="Ник или номер телефона"
        :error="errors?.telegram"
        @update:modelValue="emitAll"
      />
      
      <BaseSelect
        v-model="localContactMethod"
        label="Способ связи"
        :options="contactMethodOptions"
        hint="Как клиенты могут с вами связаться"
        :error="errors?.contact_method"
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
  contacts: { 
    type: Object, 
    default: () => ({
      phone: '',
      contact_method: 'any',
      whatsapp: '',
      telegram: ''
    })
  },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:contacts'])

// Локальная копия контактов для работы
const localContacts = ref({ ...props.contacts })

// Computed свойства для удобного доступа к полям
const localPhone = computed({
  get: () => localContacts.value.phone,
  set: (value) => updateContact('phone', value)
})

const localContactMethod = computed({
  get: () => localContacts.value.contact_method,
  set: (value) => updateContact('contact_method', value)
})

const localWhatsapp = computed({
  get: () => localContacts.value.whatsapp,
  set: (value) => updateContact('whatsapp', value)
})

const localTelegram = computed({
  get: () => localContacts.value.telegram,
  set: (value) => updateContact('telegram', value)
})

// Опции для способа связи
const contactMethodOptions = computed(() => [
  { value: 'phone', label: 'Только звонки' },
  { value: 'whatsapp', label: 'Только WhatsApp' },
  { value: 'telegram', label: 'Только Telegram' },
  { value: 'messages', label: 'Сообщения (любой мессенджер)' },
  { value: 'any', label: 'Любой способ' }
])

// Универсальная функция обновления контактов
const updateContact = (field, value) => {
  localContacts.value[field] = value
  emit('update:contacts', { ...localContacts.value })
}

// Функция для обновления всех контактов
const emitAll = () => {
  emit('update:contacts', { ...localContacts.value })
}

// Watcher для синхронизации с внешними изменениями
watch(() => props.contacts, (newContacts) => {
  localContacts.value = { ...newContacts }
}, { deep: true })
</script>

<!-- Все стили мигрированы на Tailwind CSS в template --> 