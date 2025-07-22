<template>
    <div class="form-field">
        
        <div class="price-controls">
            <!-- Поле цены "от X ₽" -->
            <div class="price-input-wrapper">
                <input 
                    v-model="formattedPrice"
                    @input="handlePriceInput"
                    type="text"
                    class="price-input-field"
                    placeholder="от 0 ₽"
                    required
                >
                <button 
                    v-if="form.price" 
                    type="button" 
                    @click="clearPrice" 
                    class="price-clear-button"
                >
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>
            </div>
            
            <!-- Выпадающий список единиц -->
            <div class="custom-select-wrapper unit-select" :class="{ 'active': isUnitOpen }">
                <div 
                    class="custom-select-trigger"
                    @click="toggleUnitDropdown"
                >
                    <span class="selected-text">{{ getUnitLabel(form.price_unit) }}</span>
                    <div class="select-arrow">
                        <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': isUnitOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
                
                <div v-if="isUnitOpen" class="custom-select-dropdown">
                    <div class="dropdown-option" 
                         v-for="option in unitOptions" 
                         :key="option.value"
                         @click="selectUnit(option.value)"
                         :class="{ 'selected': form.price_unit === option.value }">
                        {{ option.label }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Чекбокс "это начальная цена" -->
        <div class="mt-4">
            <div class="checkbox-item" @click="toggleStartingPrice">
                <div 
                    class="custom-checkbox"
                    :class="{ 'checked': isStartingPrice }"
                >
                    <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <span class="checkbox-label" @click="toggleStartingPrice">это начальная цена</span>
            </div>
        </div>
        
        <div v-if="errors.price" class="error-message mt-2">
            {{ errors.price }}
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

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

const isUnitOpen = ref(false)
const isStartingPrice = ref(false)

// Инициализация значений по умолчанию
if (!props.form.price_unit) {
    props.form.price_unit = 'hour'
}
if (props.form.is_starting_price === undefined || props.form.is_starting_price === null) {
    props.form.is_starting_price = false
}

// Синхронизируем локальный ref с формой
isStartingPrice.value = props.form.is_starting_price

const unitOptions = [
    { value: 'hour', label: 'за час' },
    { value: 'session', label: 'за сеанс' },
    { value: 'service', label: 'за услугу' },
    { value: 'day', label: 'за день' },
    { value: 'visit', label: 'за выезд' }
]

const formattedPrice = computed({
    get() {
        if (!props.form.price) return ''
        return `от ${props.form.price} ₽`
    },
    set(value) {
        // Обновляется через handlePriceInput
    }
})

const handlePriceInput = (event) => {
    let value = event.target.value
    // Убираем все кроме цифр
    value = value.replace(/[^\d]/g, '')
    props.form.price = value ? parseInt(value) : ''
    
    // Обновляем отображаемое значение
    if (value) {
        event.target.value = `от ${value} ₽`
    } else {
        event.target.value = ''
    }
}

const clearPrice = () => {
    props.form.price = ''
}

const toggleStartingPrice = (event) => {
    if (event) {
        event.preventDefault()
        event.stopPropagation()
    }
    
    console.log('Toggle clicked, current value:', isStartingPrice.value, props.form.is_starting_price)
    isStartingPrice.value = !isStartingPrice.value
    props.form.is_starting_price = isStartingPrice.value
    console.log('New value:', isStartingPrice.value, props.form.is_starting_price)
}

const toggleUnitDropdown = () => {
    isUnitOpen.value = !isUnitOpen.value
}

const selectUnit = (value) => {
    props.form.price_unit = value
    isUnitOpen.value = false
}

const getUnitLabel = (value) => {
    const option = unitOptions.find(opt => opt.value === value)
    return option ? option.label : 'за час'
}

// Закрытие при клике вне компонента
const handleClickOutside = (event) => {
    if (!event.target.closest('.unit-select')) {
        isUnitOpen.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    
    // Принудительная инициализация чекбокса
    if (props.form.is_starting_price === undefined || props.form.is_starting_price === null) {
        props.form.is_starting_price = false
    }
    isStartingPrice.value = props.form.is_starting_price
    console.log('Initial checkbox value:', isStartingPrice.value, props.form.is_starting_price)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
.price-controls {
    display: flex;
    gap: 12px;
    align-items: stretch;
}

.price-input-wrapper {
    flex: 1;
    position: relative;
}

.price-input-field {
    width: 100%;
    padding: 12px 40px 12px 16px;
    border: 2px solid #e5e5e5;
    border-radius: 8px;
    background: #f5f5f5;
    font-size: 16px;
    color: #1a1a1a;
    transition: all 0.2s ease;
    min-height: 48px;
}

.price-input-field:focus {
    outline: none;
    border-color: #2196f3;
    background: #fff;
}

.price-clear-button {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    color: #8c8c8c;
    transition: color 0.2s ease;
}

.price-clear-button:hover {
    color: #1a1a1a;
}

.unit-select {
    min-width: 120px;
}

.custom-select-wrapper {
    position: relative;
}

.custom-select-trigger {
    width: 100%;
    padding: 12px 32px 12px 16px;
    border: 2px solid #e5e5e5;
    border-radius: 8px;
    background: #f5f5f5;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 48px;
    transition: all 0.2s ease;
}

.custom-select-trigger:hover {
    border-color: #d0d0d0;
}

.custom-select-wrapper.active .custom-select-trigger {
    border-color: #2196f3;
    background: #fff;
    border-radius: 8px 8px 0 0;
}

.selected-text {
    font-size: 16px;
    color: #1a1a1a;
    font-weight: 400;
}

.select-arrow {
    color: #8c8c8c;
    transition: transform 0.2s ease;
}

.custom-select-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 2px solid #2196f3;
    border-top: none;
    border-radius: 0 0 8px 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
}

.dropdown-option {
    padding: 12px 16px;
    cursor: pointer;
    font-size: 16px;
    color: #1a1a1a;
    transition: background-color 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}

.dropdown-option:last-child {
    border-bottom: none;
}

.dropdown-option:hover {
    background-color: #f8f9fa;
}

.dropdown-option.selected {
    background-color: #e3f2fd;
    color: #2196f3;
    font-weight: 500;
}

.checkbox-item {
    display: flex;
    align-items: center;
    cursor: pointer;
    gap: 12px;
    padding: 8px 0;
    user-select: none;
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
    cursor: pointer;
}

.custom-checkbox:hover {
    border-color: #8c8c8c;
}

.custom-checkbox.checked {
    background: #000;
    border-color: #000;
}

.check-icon {
    width: 12px;
    height: 10px;
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
    line-height: 1.4;
    cursor: pointer;
    user-select: none;
}

.field-hint {
    font-size: 14px;
    color: #8c8c8c;
    line-height: 1.4;
}

.error-message {
    color: #ff4d4f;
    font-size: 14px;
    line-height: 1.4;
}
</style> 