<template>
  <div class="contacts-section">
    <h2 class="form-group-title">Контакты</h2>
    <div class="contacts-fields">
      <div class="contact-field">
        <label>Телефон:</label>
        <input 
          type="tel" 
          v-model="localPhone" 
          @input="emitAll" 
          placeholder="+7 (999) 999-99-99"
          pattern="[+]7\s?[\(]?[0-9]{3}[\)]?\s?[0-9]{3}[-]?[0-9]{2}[-]?[0-9]{2}"
        />
        <span class="field-hint">Основной номер для связи</span>
      </div>
      
      <div class="contact-field">
        <label>WhatsApp:</label>
        <input 
          type="tel" 
          v-model="localWhatsapp" 
          @input="emitAll" 
          placeholder="+7 (999) 999-99-99"
        />
        <span class="field-hint">Оставьте пустым, если совпадает с телефоном</span>
      </div>
      
      <div class="contact-field">
        <label>Telegram:</label>
        <input 
          type="text" 
          v-model="localTelegram" 
          @input="emitAll" 
          placeholder="@username или +7 (999) 999-99-99"
        />
        <span class="field-hint">Ник или номер телефона</span>
      </div>
      
      <div class="contact-field">
        <label>Способ связи:</label>
        <select v-model="localContactMethod" @change="emitAll">
          <option value="phone">Только звонки</option>
          <option value="whatsapp">Только WhatsApp</option>
          <option value="telegram">Только Telegram</option>
          <option value="messages">Сообщения (любой мессенджер)</option>
          <option value="any">Любой способ</option>
        </select>
        <span class="field-hint">Как клиенты могут с вами связаться</span>
      </div>
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
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
}

.contact-field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.contact-field label {
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.contact-field input,
.contact-field select {
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  background-color: #fff;
  transition: border-color 0.2s;
}

.contact-field input:focus,
.contact-field select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.field-hint {
  font-size: 12px;
  color: #8c8c8c;
  margin-top: -4px;
}
</style> 