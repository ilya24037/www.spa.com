<template>
  <div class="contacts-section">
    <h2 class="form-group-title">Контакты</h2>
    <div class="contacts-fields">
      <div class="field-group">
        <BaseInput
          v-model="localPhone"
          type="tel"
          label="Телефон:"
          placeholder="+7 (XXX) XXX-XX-XX"
          @update:modelValue="emitAll"
        />
      </div>
      
      <div class="field-group">
        <BaseInput
          v-model="localWhatsapp"
          type="tel"
          label="WhatsApp:"
          placeholder="+7 (XXX) XXX-XX-XX"
          @update:modelValue="emitAll"
        />
      </div>
      
      <div class="field-group">
        <BaseInput
          v-model="localTelegram"
          type="text"
          label="Telegram:"
          placeholder="@username"
          @update:modelValue="emitAll"
        />
      </div>
      
      <div class="field-group">
        <label class="select-label">Способ связи:</label>
        <select v-model="localContactMethod" @change="emitAll" class="contact-select">
          <option value="any">Любой</option>
          <option value="calls">Звонки</option>
          <option value="messages">Сообщения</option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseInput from '@/Components/UI/BaseInput.vue'

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
.contacts-section { 
  background: white; 
  border-radius: 8px; 
  padding: 20px; 
}

.form-group-title { 
  font-size: 18px; 
  font-weight: 600; 
  color: #333; 
  margin-bottom: 16px; 
}

.contacts-fields { 
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}

.field-group {
  width: 100%;
}

.select-label {
  display: block;
  font-size: 16px;
  font-weight: 500;
  color: #000000;
  margin-bottom: 8px;
}

.contact-select {
  width: 100%;
  padding: 12px 16px;
  border: 2px solid #e5e5e5;
  border-radius: 8px;
  background: #f5f5f5;
  font-size: 16px;
  color: #1a1a1a;
  transition: all 0.2s ease;
  min-height: 48px;
  box-sizing: border-box;
}

.contact-select:hover {
  border-color: #d0d0d0;
}

.contact-select:focus {
  outline: none;
  border-color: #2196f3;
  background: #fff;
}
</style> 