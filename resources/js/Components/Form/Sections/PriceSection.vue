<template>
    <div class="price-section">
        <!-- Основные поля цены -->
        <div class="price-controls">
            <div class="price-input-container">
                <!-- АВИТО-СТИЛЬ: четкое разделение зон -->
                <div class="avito-price-wrapper">
                    <div class="price-prefix" aria-hidden="true">от</div>
                    
                    <div class="price-input-area">
                        <input
                            ref="priceInput"
                            v-model="displayValue"
                            type="text"
                            inputmode="numeric"
                            placeholder=" ₽"
                            class="price-input"
                            autocomplete="off"
                            @input="handleInput"
                            @keydown="handleKeydown"
                            @click="handleClick"
                            @focus="handleFocus"
                        />
                    </div>
                    
                    <button 
                        v-if="form.price" 
                        type="button" 
                        class="price-clear-btn"
                        @click="clearPrice"
                        aria-label="Очистить цену"
                    >
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                    
                    <div v-if="errors.price" class="price-error">{{ errors.price }}</div>
                </div>
            </div>
            
            <div class="price-unit-container">
                <BaseSelect
                    v-model="form.price_unit"
                    :options="unitOptions"
                    placeholder="за час"
                />
            </div>
        </div>
        
        <!-- Чекбокс начальной цены -->
        <div class="starting-price-section">
            <BaseCheckbox
                v-model="form.is_starting_price"
                label="это начальная цена"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import BaseSelect from '../../UI/BaseSelect.vue'  
import BaseCheckbox from '../../UI/BaseCheckbox.vue'

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

// Отображаемое значение с пробелом и ₽
const displayValue = computed({
    get() {
        const price = props.form.price || ''
        if (!price) return ''
        // Форматируем как "10000 ₽"
        return `${price} ₽`
    },
    set(value) {
        // Извлекаем только цифры из строки
        const cleanValue = value.replace(/[^\d]/g, '')
        props.form.price = cleanValue
    }
})

// Обработка ввода
const handleInput = (event) => {
    const input = event.target
    const value = input.value
    const cursorPos = input.selectionStart
    
    // Извлекаем только цифры
    const cleanValue = value.replace(/[^\d]/g, '')
    
    // Обновляем форму
    props.form.price = cleanValue
    
    // Восстанавливаем позицию курсора
    nextTick(() => {
        // Если есть цифры, курсор после них перед " ₽"
        if (cleanValue) {
            input.setSelectionRange(cleanValue.length, cleanValue.length)
        } else {
            // Если пусто - курсор в начало
            input.setSelectionRange(0, 0)
        }
    })
}

// Обработка клика - курсор в правильную позицию
const handleClick = (event) => {
    const input = event.target
    const clickPos = input.selectionStart
    
    setTimeout(() => {
        const price = props.form.price || ''
        if (price) {
            // Если кликнули до цифр или после " ₽" - ставим курсор после цифр
            if (clickPos <= 0 || clickPos > price.length) {
                input.setSelectionRange(price.length, price.length)
            }
        } else {
            // Если пусто - курсор в начало
            input.setSelectionRange(0, 0)
        }
    }, 0)
}

// Обработка фокуса
const handleFocus = (event) => {
    const input = event.target
    
    setTimeout(() => {
        const price = props.form.price || ''
        if (price) {
            // При фокусе - курсор после цифр перед " ₽"
            input.setSelectionRange(price.length, price.length)
        } else {
            // Если пусто - курсор в начало
            input.setSelectionRange(0, 0)
        }
    }, 0)
}

// Обработка клавиш
const handleKeydown = (event) => {
    // Разрешаем: Backspace, Delete, Tab, Escape, Enter, цифры
    const allowed = [
        'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
        'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
    ]
    
    // Разрешаем Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
    if (event.ctrlKey && ['a', 'c', 'v', 'x'].includes(event.key.toLowerCase())) {
        return
    }
    
    // Блокируем все остальные клавиши
    if (!allowed.includes(event.key)) {
        event.preventDefault()
        return
    }
}

// Очистка цены
const clearPrice = () => {
    props.form.price = ''
    
    // Фокус обратно на поле
    nextTick(() => {
        const input = document.querySelector('.price-input')
        if (input) {
            input.focus()
            input.setSelectionRange(0, 0)
        }
    })
}

// Инициализация значений по умолчанию
if (!props.form.price_unit) {
    props.form.price_unit = 'hour'
}
if (props.form.is_starting_price === undefined || props.form.is_starting_price === null) {
    props.form.is_starting_price = false
}
if (props.form.price === undefined || props.form.price === null) {
    props.form.price = ''
}

const unitOptions = [
    { value: 'hour', label: 'за час' },
    { value: 'session', label: 'за сеанс' },
    { value: 'service', label: 'за услугу' },
    { value: 'day', label: 'за день' },
    { value: 'visit', label: 'за выезд' }
]
</script>

<style scoped>
/* Основной контейнер секции */
.price-section {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* Контейнер для полей цены */
.price-controls {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    width: 100%;
}

/* Поле ввода цены - 50% как на Авито */
.price-input-container {
    width: calc(50% - 6px);
    flex-shrink: 0;
}

/* АВИТО-СТИЛЬ: Основной контейнер БЕЗ ВИДИМЫХ РАЗДЕЛИТЕЛЕЙ */
.avito-price-wrapper {
    position: relative;
    display: flex;
    align-items: center;
    border: 2px solid #e5e5e5;
    border-radius: 8px;
    background: #fff;
    transition: all 0.2s ease;
    min-height: 48px;
    overflow: hidden;
}

.avito-price-wrapper:hover {
    border-color: #d0d0d0;
}

.avito-price-wrapper:focus-within {
    border-color: #2196f3;
    box-shadow: none; /* Убираем дополнительную тень */
}

/* Префикс "от" - БЕЗ видимых границ */
.price-prefix {
    padding-left: 16px;
    padding-right: 0;
    color: #666;
    font-size: 16px;
    font-weight: 400;
    pointer-events: none;
    user-select: none;
    flex-shrink: 0;
    background: transparent;
    height: 100%;
    display: flex;
    align-items: center;
}



/* Область ввода */
.price-input-area {
    flex: 1;
    display: flex;
    align-items: center;
    min-width: 0;
    padding-right: 40px; /* Место только для кнопки X */
    position: relative;
}

/* Поле ввода цифр */
.price-input {
    width: 100%;
    border: none !important;
    background: transparent !important;
    font-size: 16px;
    color: #1a1a1a;
    outline: none !important;
    box-shadow: none !important;
    padding: 12px 0 12px 4px; /* Минимальный отступ слева */
    box-sizing: border-box;
    font-family: inherit;
    text-align: left;
}

.price-input::placeholder {
    color: #666;
    text-align: left;
    padding-left: 0;
    font-weight: 400;
    /* Placeholder начинается сразу, без дополнительных отступов */
}

.price-input:focus {
    outline: none !important;
    border: none !important;
    box-shadow: none !important;
}



/* Кнопка очистки - дальше от ₽ */
.price-clear-btn {
    position: absolute;
    right: 12px; /* Больше отступ от края */
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 6px;
    color: #999;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 4px;
    z-index: 10;
}

.price-clear-btn:hover {
    color: #333;
    background: #f0f0f0;
}

/* Ошибка */
.price-error {
    position: absolute;
    top: 100%;
    left: 0;
    margin-top: 4px;
    font-size: 14px;
    color: #ff4d4f;
    line-height: 1.4;
}

/* Поле единиц измерения */
.price-unit-container {
    width: calc(45% - 6px);
    flex-shrink: 0;
}

/* Секция чекбокса */
.starting-price-section {
    margin-top: 8px;
}

/* Адаптивность для мобильных устройств */
@media (max-width: 768px) {
    .price-controls {
        flex-direction: column;
        gap: 16px;
    }
    
    .price-input-container,
    .price-unit-container {
        width: 100%;
    }
    
    .price-prefix,
    .price-suffix {
        padding: 0 10px;
        font-size: 15px;
    }
    
    .price-input {
        padding: 10px;
        font-size: 16px;
    }
}

/* Состояние с ошибкой */
.avito-price-wrapper.error {
    border-color: #ff4d4f;
}

.avito-price-wrapper.error:focus-within {
    border-color: #ff4d4f;
    box-shadow: 0 0 0 1px #ff4d4f;
}
</style> 