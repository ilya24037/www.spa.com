<template>
  <div class="contacts-section">
    <h2 class="form-group-title">Контакты</h2>
    <div class="contacts-fields">
      <label>Телефон:
        <input type="text" v-model="localPhone" @input="emitAll" />
      </label>
      <label>WhatsApp:
        <input type="text" v-model="localWhatsapp" @input="emitAll" />
      </label>
      <label>Telegram:
        <input type="text" v-model="localTelegram" @input="emitAll" />
      </label>
      <label>Способ связи:
        <select v-model="localContactMethod" @change="emitAll">
          <option value="any">Любой</option>
          <option value="calls">Звонки</option>
          <option value="messages">Сообщения</option>
        </select>
      </label>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
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

<style scoped>
.contacts-section { background: white; border-radius: 8px; padding: 20px; }
.form-group-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 16px; }
.contacts-fields { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
</style> 