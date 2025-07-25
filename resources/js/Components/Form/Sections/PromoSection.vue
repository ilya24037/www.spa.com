<template>
    <div class="form-field">
        <div class="promo-fields">
            <div class="discount-field">
                <label class="field-label">Скидка новым клиентам</label>
                <div class="discount-input-container">
                    <!-- АВИТО-СТИЛЬ: четкое разделение зон -->
                    <div class="avito-discount-wrapper">
                        <div class="discount-input-area">
                            <input
                                ref="discountInput"
                                v-model="displayValue"
                                type="text"
                                inputmode="numeric"
                                placeholder=" %"
                                class="discount-input"
                                autocomplete="off"
                                @input="handleInput"
                                @keydown="handleKeydown"
                                @click="handleClick"
                                @focus="handleFocus"
                            />
                        </div>
                        
                        <button 
                            v-if="form.new_client_discount" 
                            type="button" 
                            class="discount-clear-btn"
                            @click="clearDiscount"
                            aria-label="Очистить скидку"
                        >
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M12 4L4 12M4 4l8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="gift-field">
                <BaseInput
                    v-model="form.gift"
                    label="Подарок"
                    placeholder="Что вы можете подарить? Например: дарим чай"
                    :maxlength="100"
                    hint="Можно не заполнять, если подарка нет"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import BaseInput from '../../UI/BaseInput.vue'

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

// Отображаемое значение с пробелом и %
const displayValue = computed({
    get() {
        const discount = props.form.new_client_discount || ''
        if (!discount) return ''
        // Форматируем как "50 %"
        return `${discount} %`
    },
    set(value) {
        // Извлекаем только цифры из строки
        const cleanValue = value.replace(/[^\d]/g, '')
        props.form.new_client_discount = cleanValue
    }
})

// Обработка ввода
const handleInput = (event) => {
    const input = event.target
    const value = input.value
    const cursorPos = input.selectionStart
    
    // Извлекаем только цифры
    let cleanValue = value.replace(/[^\d]/g, '')
    
    // Ограничиваем от 1 до 100
    if (cleanValue) {
        const numValue = Number(cleanValue)
        if (numValue < 1) {
            cleanValue = '1'
        } else if (numValue > 100) {
            cleanValue = '100'
        }
    }
    
    // Обновляем форму
    props.form.new_client_discount = cleanValue
    
    // Восстанавливаем позицию курсора
    nextTick(() => {
        // Если есть цифры, курсор после них перед " %"
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
        const discount = props.form.new_client_discount || ''
        if (discount) {
            // Если кликнули до цифр или после " %" - ставим курсор после цифр
            if (clickPos <= 0 || clickPos > discount.length) {
                input.setSelectionRange(discount.length, discount.length)
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
        const discount = props.form.new_client_discount || ''
        if (discount) {
            // При фокусе - курсор после цифр перед " %"
            input.setSelectionRange(discount.length, discount.length)
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

// Очистка скидки
const clearDiscount = () => {
    props.form.new_client_discount = ''
    
    // Фокус обратно на поле
    nextTick(() => {
        const input = document.querySelector('.discount-input')
        if (input) {
            input.focus()
            input.setSelectionRange(0, 0)
        }
    })
}
</script>

<style scoped>
.promo-fields {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.discount-field,
.gift-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Стили для поля скидки - как в PriceSection */
.field-label {
    display: block;
    font-size: 16px;
    font-weight: 500;
    color: #000000;
    margin-bottom: 8px;
}

.discount-input-container {
    width: 100%;
}

/* АВИТО-СТИЛЬ: Основной контейнер БЕЗ ВИДИМЫХ РАЗДЕЛИТЕЛЕЙ */
.avito-discount-wrapper {
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

.avito-discount-wrapper:hover {
    border-color: #d0d0d0;
}

.avito-discount-wrapper:focus-within {
    border-color: #2196f3;
    box-shadow: none; /* Убираем дополнительную тень */
}

/* Область ввода */
.discount-input-area {
    flex: 1;
    display: flex;
    align-items: center;
    min-width: 0;
    padding-right: 40px; /* Место только для кнопки X */
    position: relative;
}

/* Поле ввода цифр */
.discount-input {
    width: 100%;
    border: none !important;
    background: transparent !important;
    font-size: 16px;
    color: #1a1a1a;
    outline: none !important;
    box-shadow: none !important;
    padding: 12px 0 12px 16px; /* Нормальный отступ слева */
    box-sizing: border-box;
    font-family: inherit;
    text-align: left;
}

.discount-input::placeholder {
    color: #666;
    text-align: left;
    padding-left: 0;
    font-weight: 400;
    /* Placeholder начинается сразу, без дополнительных отступов */
}

.discount-input:focus {
    outline: none !important;
    border: none !important;
    box-shadow: none !important;
}

/* Кнопка очистки - дальше от % */
.discount-clear-btn {
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

.discount-clear-btn:hover {
    color: #333;
    background: #f0f0f0;
}
</style> 