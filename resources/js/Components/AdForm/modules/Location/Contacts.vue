<template>
  <FormSection
    title="Контактная информация"
    hint="Укажите удобные способы связи с вами"
    required
    :error="errors.phone || errors.contact_method"
  >
    <div class="contacts-container">
      <!-- Основной телефон -->
      <FormField
        label="Номер телефона"
        hint="Основной номер для связи с клиентами"
        required
        :error="errors.phone"
      >
        <div class="phone-input-wrapper">
          <div class="phone-input-container">
            <span class="phone-prefix">+7</span>
            <input
              v-model="localPhone"
              @input="handlePhoneInput"
              @keypress="handlePhoneKeypress"
              type="tel"
              placeholder="(999) 123-45-67"
              maxlength="15"
              class="phone-input"
            />
          </div>
          
          <div v-if="phoneValidation.message" :class="[
            'phone-validation',
            { 'valid': phoneValidation.isValid, 'invalid': !phoneValidation.isValid }
          ]">
            <span class="validation-icon">
              {{ phoneValidation.isValid ? '✓' : '⚠️' }}
            </span>
            <span class="validation-text">{{ phoneValidation.message }}</span>
          </div>
        </div>
      </FormField>

      <!-- Способ связи -->
      <FormField
        label="Предпочтительный способ связи"
        hint="Как клиентам лучше с вами связываться"
        :error="errors.contact_method"
      >
        <div class="contact-methods">
          <label
            v-for="method in contactMethods"
            :key="method.value"
            class="contact-method-option"
          >
            <input
              type="radio"
              name="contactMethod"
              :value="method.value"
              :checked="localContactMethod === method.value"
              @change="updateContactMethod(method.value)"
              class="contact-method-radio"
            />
            <div class="contact-method-card">
              <div class="method-icon">{{ method.icon }}</div>
              <div class="method-content">
                <div class="method-title">{{ method.title }}</div>
                <div class="method-description">{{ method.description }}</div>
              </div>
            </div>
          </label>
        </div>
      </FormField>

      <!-- Дополнительные контакты -->
      <div class="additional-contacts">
        <h4 class="additional-title">Дополнительные способы связи</h4>
        <p class="additional-hint">Добавьте альтернативные способы связи (необязательно)</p>
        
        <div class="additional-grid">
          <!-- WhatsApp -->
          <FormField
            label="WhatsApp"
            hint="Номер для WhatsApp (может отличаться от основного)"
            :error="errors.whatsapp"
          >
            <div class="messenger-input-wrapper">
              <div class="messenger-icon">📱</div>
              <input
                v-model="localWhatsapp"
                @input="updateWhatsapp"
                type="tel"
                placeholder="+7 (999) 123-45-67"
                class="messenger-input"
              />
            </div>
          </FormField>

          <!-- Telegram -->
          <FormField
            label="Telegram"
            hint="Ваш никнейм в Telegram"
            :error="errors.telegram"
          >
            <div class="messenger-input-wrapper">
              <div class="messenger-icon">📲</div>
              <input
                v-model="localTelegram"
                @input="updateTelegram"
                type="text"
                placeholder="@username"
                class="messenger-input"
              />
            </div>
          </FormField>
        </div>
      </div>

      <!-- Настройки конфиденциальности -->
      <FormField
        label="Настройки конфиденциальности"
        hint="Как показывать ваши контакты в объявлении"
      >
        <div class="privacy-settings">
          <label class="privacy-checkbox-wrapper">
            <input
              type="checkbox"
              :checked="hidePhoneNumber"
              @change="updateHidePhoneNumber($event.target.checked)"
              class="privacy-checkbox"
            />
            <div class="privacy-content">
              <div class="privacy-title">Скрыть номер телефона</div>
              <div class="privacy-description">
                Номер будет показан только после запроса через сайт
              </div>
            </div>
          </label>
          
          <label class="privacy-checkbox-wrapper">
            <input
              type="checkbox"
              :checked="showOnlineStatus"
              @change="updateShowOnlineStatus($event.target.checked)"
              class="privacy-checkbox"
            />
            <div class="privacy-content">
              <div class="privacy-title">Показывать статус "онлайн"</div>
              <div class="privacy-description">
                Клиенты увидят, когда вы активны на сайте
              </div>
            </div>
          </label>
        </div>
      </FormField>

      <!-- Предварительный просмотр контактов -->
      <div v-if="hasContacts" class="contacts-preview">
        <div class="preview-header">
          <span class="preview-icon">👁️</span>
          <span class="preview-title">Как увидят клиенты:</span>
        </div>
        
        <div class="preview-contacts">
          <div class="preview-primary">
            <div class="preview-phone">
              <span class="preview-label">Телефон:</span>
              <span class="preview-value">
                {{ hidePhoneNumber ? 'Показать номер' : formatPhone(localPhone) }}
              </span>
            </div>
            
            <div v-if="localContactMethod" class="preview-method">
              <span class="preview-label">Способ связи:</span>
              <span class="preview-value">{{ getContactMethodTitle(localContactMethod) }}</span>
            </div>
          </div>
          
          <div v-if="hasMessengers" class="preview-messengers">
            <div class="preview-messengers-title">Также доступно:</div>
            <div class="preview-messengers-list">
              <span v-if="localWhatsapp" class="preview-messenger">📱 WhatsApp</span>
              <span v-if="localTelegram" class="preview-messenger">📲 {{ localTelegram }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Советы по контактам -->
      <div class="contacts-tips">
        <div class="tip-icon">💡</div>
        <div class="tip-content">
          <p class="tip-title">Советы по контактам</p>
          <ul class="tip-list">
            <li>Указание нескольких способов связи увеличивает отклик на 25%</li>
            <li>WhatsApp и Telegram удобны для быстрого общения</li>
            <li>Скрытие номера повышает безопасность и фильтрует серьёзных клиентов</li>
            <li>Статус "онлайн" показывает вашу активность</li>
          </ul>
        </div>
      </div>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

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
watch(() => props.phone, (newValue) => { localPhone.value = newValue || '' })
watch(() => props.contactMethod, (newValue) => { localContactMethod.value = newValue || '' })
watch(() => props.whatsapp, (newValue) => { localWhatsapp.value = newValue || '' })
watch(() => props.telegram, (newValue) => { localTelegram.value = newValue || '' })

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
    description: 'Общаение через WhatsApp',
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

// Валидация телефона
const phoneValidation = computed(() => {
  if (!localPhone.value) {
    return { isValid: false, message: 'Введите номер телефона' }
  }
  
  const cleanPhone = localPhone.value.replace(/\D/g, '')
  
  if (cleanPhone.length < 10) {
    return { isValid: false, message: 'Номер слишком короткий' }
  }
  
  if (cleanPhone.length > 11) {
    return { isValid: false, message: 'Номер слишком длинный' }
  }
  
  if (cleanPhone.length === 10 && !cleanPhone.startsWith('9')) {
    return { isValid: false, message: 'Номер должен начинаться с 9' }
  }
  
  if (cleanPhone.length === 11 && !cleanPhone.startsWith('7')) {
    return { isValid: false, message: 'Номер должен начинаться с 7' }
  }
  
  return { isValid: true, message: 'Номер корректный' }
})

// Методы
const handlePhoneInput = (event) => {
  let value = event.target.value.replace(/\D/g, '')
  
  // Ограничиваем длину
  if (value.length > 10) {
    value = value.substring(0, 10)
  }
  
  // Форматируем номер
  if (value.length >= 3) {
    value = `(${value.substring(0, 3)}) ${value.substring(3)}`
  }
  if (value.length >= 9) {
    value = `${value.substring(0, 9)}-${value.substring(9)}`
  }
  if (value.length >= 12) {
    value = `${value.substring(0, 12)}-${value.substring(12)}`
  }
  
  localPhone.value = value
  emit('update:phone', value)
}

const handlePhoneKeypress = (event) => {
  // Разрешаем только цифры
  if (!/\d/.test(event.key) && !['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab'].includes(event.key)) {
    event.preventDefault()
  }
}

const updateContactMethod = (method) => {
  localContactMethod.value = method
  emit('update:contactMethod', method)
}

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

const updateHidePhoneNumber = (value) => {
  hidePhoneNumber.value = value
}

const updateShowOnlineStatus = (value) => {
  showOnlineStatus.value = value
}

const formatPhone = (phone) => {
  const clean = phone.replace(/\D/g, '')
  if (clean.length === 10) {
    return `+7 (${clean.substring(0, 3)}) ${clean.substring(3, 6)}-${clean.substring(6, 8)}-${clean.substring(8)}`
  }
  return phone
}

const getContactMethodTitle = (method) => {
  const found = contactMethods.find(m => m.value === method)
  return found ? found.title : method
}

// Computed
const hasContacts = computed(() => {
  return localPhone.value || localContactMethod.value
})

const hasMessengers = computed(() => {
  return localWhatsapp.value || localTelegram.value
})
</script>

<style scoped>
.contacts-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.phone-input-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.phone-input-container {
  display: flex;
  align-items: center;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  overflow: hidden;
  transition: border-color 0.2s;
}

.phone-input-container:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.phone-prefix {
  display: flex;
  align-items: center;
  padding: 0.75rem 0.75rem 0.75rem 1rem;
  background: #f3f4f6;
  border-right: 1px solid #d1d5db;
  font-weight: 500;
  color: #374151;
}

.phone-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: none;
  outline: none;
  font-size: 1rem;
}

.phone-validation {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.phone-validation.valid {
  color: #059669;
}

.phone-validation.invalid {
  color: #dc2626;
}

.validation-icon {
  flex-shrink: 0;
}

.contact-methods {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.contact-method-option {
  cursor: pointer;
  display: block;
}

.contact-method-radio {
  position: absolute;
  opacity: 0;
  pointer-events: none;
}

.contact-method-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 0.75rem;
  transition: all 0.2s;
  background: white;
}

.contact-method-radio:checked + .contact-method-card {
  border-color: #3b82f6;
  background: #eff6ff;
}

.contact-method-card:hover {
  border-color: #9ca3af;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.method-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.method-content {
  flex: 1;
}

.method-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.method-description {
  font-size: 0.875rem;
  color: #6b7280;
}

.additional-contacts {
  padding: 1.5rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 0.75rem;
}

.additional-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.additional-hint {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 1.5rem;
}

.additional-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.messenger-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  overflow: hidden;
}

.messenger-input-wrapper:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.messenger-icon {
  display: flex;
  align-items: center;
  padding: 0.75rem;
  background: #f3f4f6;
  border-right: 1px solid #d1d5db;
  font-size: 1.25rem;
}

.messenger-input {
  flex: 1;
  padding: 0.75rem 1rem;
  border: none;
  outline: none;
  font-size: 1rem;
}

.privacy-settings {
  display: grid;
  gap: 1rem;
}

.privacy-checkbox-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.privacy-checkbox-wrapper:hover {
  border-color: #9ca3af;
  background: #f9fafb;
}

.privacy-checkbox {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
}

.privacy-content {
  flex: 1;
}

.privacy-title {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.privacy-description {
  font-size: 0.875rem;
  color: #6b7280;
}

.contacts-preview {
  padding: 1rem;
  background: #f0fdf4;
  border: 1px solid #22c55e;
  border-radius: 0.5rem;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.preview-icon {
  font-size: 1rem;
}

.preview-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #166534;
}

.preview-contacts {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.preview-primary {
  display: grid;
  gap: 0.5rem;
}

.preview-phone,
.preview-method {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.preview-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #166534;
}

.preview-value {
  font-size: 0.875rem;
  color: #166534;
}

.preview-messengers {
  padding-top: 0.75rem;
  border-top: 1px solid #bbf7d0;
}

.preview-messengers-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #166534;
  margin-bottom: 0.5rem;
}

.preview-messengers-list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.preview-messenger {
  font-size: 0.875rem;
  color: #166534;
  background: #dcfce7;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
}

.contacts-tips {
  display: flex;
  gap: 0.75rem;
  padding: 1rem;
  background: #ecfdf5;
  border: 1px solid #10b981;
  border-radius: 0.5rem;
}

.tip-icon {
  font-size: 1.25rem;
  flex-shrink: 0;
}

.tip-content {
  flex: 1;
}

.tip-title {
  font-weight: 600;
  color: #065f46;
  margin-bottom: 0.5rem;
}

.tip-list {
  font-size: 0.875rem;
  color: #065f46;
  margin: 0;
  padding-left: 1.25rem;
}

.tip-list li {
  margin-bottom: 0.25rem;
}

@media (max-width: 768px) {
  .contact-methods,
  .additional-grid {
    grid-template-columns: 1fr;
  }
  
  .preview-phone,
  .preview-method {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
}
</style>