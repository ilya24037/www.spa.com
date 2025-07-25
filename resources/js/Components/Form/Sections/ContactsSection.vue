<template>
    <div class="form-field">
        <div class="contacts-fields">
            <div class="address-field">
                <label class="field-label">Адрес *</label>
                <input 
                    v-model="form.address"
                    type="text"
                    class="avito-input"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.address }"
                    placeholder="Введите адрес"
                    required
                >
                <div v-if="errors.address" class="error-message">
                    {{ errors.address }}
                </div>
            </div>

            <div class="phone-field">
                <label class="field-label">Телефон *</label>
                <input 
                    v-model="form.phone"
                    type="tel"
                    class="avito-input"
                    :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.phone }"
                    placeholder="+7 (___) ___-__-__"
                    required
                    @input="formatPhone"
                >
                <p class="field-hint">
                    Номер телефона для связи с клиентами
                </p>
                <div v-if="errors.phone" class="error-message">
                    {{ errors.phone }}
                </div>
            </div>

            <div class="contact-method-field">
                <label class="field-label">Способ связи</label>
                <div class="contact-methods">
                    <div class="checkbox-item" @click="toggleContactMethod('phone')">
                        <div 
                            class="custom-checkbox"
                            :class="{ 'checked': form.contact_method.includes('phone') }"
                        >
                            <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                            </svg>
                        </div>
                        <span class="checkbox-label">Телефонные звонки</span>
                    </div>
                    
                    <div class="checkbox-item" @click="toggleContactMethod('whatsapp')">
                        <div 
                            class="custom-checkbox"
                            :class="{ 'checked': form.contact_method.includes('whatsapp') }"
                        >
                            <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                            </svg>
                        </div>
                        <span class="checkbox-label">WhatsApp</span>
                    </div>
                    
                    <div class="checkbox-item" @click="toggleContactMethod('telegram')">
                        <div 
                            class="custom-checkbox"
                            :class="{ 'checked': form.contact_method.includes('telegram') }"
                        >
                            <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                            </svg>
                        </div>
                        <span class="checkbox-label">Telegram</span>
                    </div>
                </div>
                <p class="field-hint">
                    Выберите удобные способы связи
                </p>
                <div v-if="errors.contact_method" class="error-message">
                    {{ errors.contact_method }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

// Инициализируем contact_method как массив, если он не определен
if (!props.form.contact_method) {
    props.form.contact_method = []
}

const formatPhone = (event) => {
    let value = event.target.value.replace(/\D/g, '')
    
    if (value.startsWith('8')) {
        value = '7' + value.slice(1)
    }
    
    if (value.startsWith('7')) {
        value = value.slice(0, 11)
        const formatted = value.replace(/^7(\d{3})(\d{3})(\d{2})(\d{2})$/, '+7 ($1) $2-$3-$4')
        props.form.phone = formatted.length > 16 ? value : formatted
    } else {
        props.form.phone = value
    }
}

const toggleContactMethod = (method) => {
    if (!props.form.contact_method.includes(method)) {
        props.form.contact_method.push(method)
    } else {
        const index = props.form.contact_method.indexOf(method)
        props.form.contact_method.splice(index, 1)
    }
}
</script>

<style scoped>
.contacts-fields {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.address-field,
.phone-field,
.contact-method-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field-label {
    font-size: 16px;
    font-weight: 400;
    color: #1a1a1a;
    margin: 0;
}

.contact-methods {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
}

.custom-checkbox {
    width: 20px;
    height: 20px;
    border: 2px solid #d9d9d9;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    background: #fff;
    flex-shrink: 0;
}

.custom-checkbox.checked {
    background: #007bff;
    border-color: #007bff;
}

.check-icon {
    width: 10px;
    height: 8px;
    color: #fff;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.custom-checkbox.checked .check-icon {
    opacity: 1;
}

.checkbox-label {
    font-size: 16px;
    color: #1a1a1a;
    font-weight: 400;
}
</style> 