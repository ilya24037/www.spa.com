<template>
  <div class="promo-section">
    <p class="section-description">Клиенты увидят информацию о скидках и подарках в объявлении.</p>
    <div class="promo-fields">
      <div class="field-group">
        <div class="discount-field">
          <label class="field-label">Скидка новым клиентам:</label>
          <div class="discount-input-wrapper">
                          <input
                v-model="displayValue"
                type="text"
                placeholder="%"
                @input="handleDiscountInput"
                @blur="validateDiscount"
                @keydown="handleKeydown"
                @click="handleClick"
                @keyup="handleCursorPosition"
                @focus="handleCursorPosition"
                class="discount-input"
              />
          </div>
        </div>
      </div>
      
      <div class="field-group">
        <BaseInput
          v-model="localGift"
          type="text"
          label="Подарок:"
          placeholder="Что и на каких условиях дарите"
          hint="Можно не заполнять, если у вас нет такой акции"
          @update:modelValue="emitGift"
          class="gift-input"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import BaseInput from '@/Components/UI/BaseInput.vue'

const props = defineProps({
  newClientDiscount: { type: String, default: '' },
  gift: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:newClientDiscount', 'update:gift'])

const localDiscount = ref(props.newClientDiscount)
const localGift = ref(props.gift)
const displayValue = ref(props.newClientDiscount ? props.newClientDiscount + ' %' : '')

watch(() => props.newClientDiscount, val => { 
  localDiscount.value = val
  displayValue.value = val ? val + ' %' : ''
})
watch(() => props.gift, val => { localGift.value = val })

const handleDiscountInput = (event) => {
  let value = event.target.value
  let cursorPos = event.target.selectionStart
  
  // Убираем все кроме цифр
  let numericValue = value.replace(/[^\d]/g, '')
  
  if (numericValue === '') {
    displayValue.value = ''
    localDiscount.value = ''
    emit('update:newClientDiscount', '')
    return
  }
  
  let num = parseInt(numericValue)
  if (num > 100) {
    num = 100
  }
  
  displayValue.value = num + ' %'
  localDiscount.value = num
  emit('update:newClientDiscount', num)
  
  // Устанавливаем курсор перед знаком %
  setTimeout(() => {
    setCursorPosition(event.target)
  }, 0)
}

const handleKeydown = (event) => {
  // Для Backspace и Delete особая обработка
  if (event.key === 'Backspace' || event.key === 'Delete') {
    let value = event.target.value
    let numericValue = value.replace(/[^\d]/g, '')
    
    if (event.key === 'Backspace' && numericValue.length > 0) {
      numericValue = numericValue.slice(0, -1)
    }
    
    if (numericValue === '') {
      displayValue.value = ''
      localDiscount.value = ''
      emit('update:newClientDiscount', '')
    } else {
      let num = parseInt(numericValue)
      displayValue.value = num + ' %'
      localDiscount.value = num
      emit('update:newClientDiscount', num)
    }
    
    event.preventDefault()
    
    // Устанавливаем курсор
    setTimeout(() => {
      setCursorPosition(event.target)
    }, 0)
    return
  }
  
  // Разрешаем только цифры и служебные клавиши
  const allowedKeys = ['Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown']
  if (!allowedKeys.includes(event.key) && !/\d/.test(event.key)) {
    event.preventDefault()
  }
}

const validateDiscount = (event) => {
  let value = event.target.value.replace(/[^\d]/g, '')
  if (value === '') {
    displayValue.value = ''
    localDiscount.value = ''
    emit('update:newClientDiscount', '')
    return
  }
  
  let num = parseInt(value)
  if (num > 100) {
    num = 100
  }
  
  displayValue.value = num + ' %'
  localDiscount.value = num
  emit('update:newClientDiscount', num)
  
  // Устанавливаем курсор перед знаком %
  setTimeout(() => {
    setCursorPosition(event.target)
  }, 0)
}

const emitGift = () => {
  emit('update:gift', localGift.value)
}

// Функция для установки правильной позиции курсора
const setCursorPosition = (element) => {
  if (!element || !element.value) return
  
  // Находим позицию перед знаком %
  let value = element.value
  let numericPart = value.replace(/[^\d]/g, '')
  let targetPos = numericPart.length
  
  // Устанавливаем курсор перед пробелом и знаком %
  element.setSelectionRange(targetPos, targetPos)
}

const handleClick = (event) => {
  // При клике всегда ставим курсор перед знаком %
  setTimeout(() => {
    setCursorPosition(event.target)
  }, 0)
}

const handleCursorPosition = (event) => {
  // При любом движении курсора возвращаем его перед знаком %
  if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
    setTimeout(() => {
      setCursorPosition(event.target)
    }, 0)
  } else {
    // Даже при стрелках не даем курсору уйти за цифры
    setTimeout(() => {
      let value = event.target.value
      let numericPart = value.replace(/[^\d]/g, '')
      let maxPos = numericPart.length
      
      if (event.target.selectionStart > maxPos) {
        event.target.setSelectionRange(maxPos, maxPos)
      }
    }, 0)
  }
}
</script>

<style scoped>
.promo-section { 
  background: white; 
  border-radius: 8px; 
  padding: 0; 
}

.section-description {
  font-size: 14px;
  color: #666;
  margin: 0 0 24px 0;
  line-height: 1.4;
}

.promo-fields { 
  display: flex;
  flex-direction: column;
  gap: 24px;
  margin-bottom: 0;
}

.field-group {
  width: 100%;
}

.field-label {
  display: block;
  font-size: 14px;
  font-weight: 600;
  color: #222;
  margin-bottom: 8px;
}

.discount-field {
  width: 100%;
}

.discount-input-wrapper {
  position: relative;
}

.discount-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #d4d4d8;
  border-radius: 8px;
  font-size: 14px;
  font-family: inherit;
  background: #f4f4f5;
  transition: all 0.2s ease;
  box-sizing: border-box;
}

.discount-input:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Убираем стрелки у number input */
.discount-input::-webkit-outer-spin-button,
.discount-input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

.discount-input[type=number] {
  -moz-appearance: textfield;
}

  /* Стили для поля подарка */
  .gift-input :deep(.input-field) {
    padding: 12px 16px;
    border: 1px solid #d4d4d8;
    border-radius: 8px;
    background: #f4f4f5;
    transition: all 0.2s ease;
  }

  .gift-input :deep(.input-field:focus) {
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }

  .gift-input :deep(.input-label) {
    font-weight: 600;
    color: #222;
    margin-bottom: 8px;
  }
</style> 