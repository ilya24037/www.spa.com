<template>
  <div class="price-section">
    <!-- Экспресс 30 мин (перенесено в начало) -->
    <div class="express-section">
      <label class="express-label">Экспресс 30 мин</label>
      <div class="price-input-wrapper">
        <input 
          v-model="expressDisplayValue" 
          @input="handleExpressInput"
          @keydown="handleExpressKeydown"
          @click="handleExpressClick"
          @keyup="handleExpressCursorPosition"
          @focus="handleExpressCursorPosition"
          @blur="validateExpressPrice"
          type="text" 
          class="express-input" 
          placeholder="₽"
        />
      </div>
    </div>
    
    <!-- Таблица расценок -->
    <div class="pricing-table">
      <div class="table-header">
        <div class="header-cell header-label">Расценки *</div>
        <div class="header-cell">За час</div>
        <div class="header-cell">За два</div>
        <div class="header-cell">За ночь</div>
      </div>
      
      <div class="table-row">
        <div class="row-label">(апартаменты)</div>
        <div class="price-cell">
          <div class="price-input-wrapper">
            <input 
              v-model="apartmentHourDisplay" 
              @input="(e) => handlePriceInput(e, 'apartment', 'hour')"
              @keydown="(e) => handlePriceKeydown(e, 'apartment', 'hour')"
              @click="(e) => handlePriceClick(e, 'apartment', 'hour')"
              @keyup="(e) => handlePriceCursorPosition(e, 'apartment', 'hour')"
              @focus="(e) => handlePriceCursorPosition(e, 'apartment', 'hour')"
              @blur="(e) => validatePrice(e, 'apartment', 'hour')"
              type="text" 
              class="price-input" 
              placeholder="₽"
            />
          </div>
        </div>
        <div class="price-cell">
          <div class="price-input-wrapper">
            <input 
              v-model="apartmentTwoHoursDisplay" 
              @input="(e) => handlePriceInput(e, 'apartment', 'two_hours')"
              @keydown="(e) => handlePriceKeydown(e, 'apartment', 'two_hours')"
              @click="(e) => handlePriceClick(e, 'apartment', 'two_hours')"
              @keyup="(e) => handlePriceCursorPosition(e, 'apartment', 'two_hours')"
              @focus="(e) => handlePriceCursorPosition(e, 'apartment', 'two_hours')"
              @blur="(e) => validatePrice(e, 'apartment', 'two_hours')"
              type="text" 
              class="price-input" 
              placeholder="₽"
            />
          </div>
        </div>
        <div class="price-cell">
          <div class="price-input-wrapper">
            <input 
              v-model="apartmentNightDisplay" 
              @input="(e) => handlePriceInput(e, 'apartment', 'night')"
              @keydown="(e) => handlePriceKeydown(e, 'apartment', 'night')"
              @click="(e) => handlePriceClick(e, 'apartment', 'night')"
              @keyup="(e) => handlePriceCursorPosition(e, 'apartment', 'night')"
              @focus="(e) => handlePriceCursorPosition(e, 'apartment', 'night')"
              @blur="(e) => validatePrice(e, 'apartment', 'night')"
              type="text" 
              class="price-input" 
              placeholder="₽"
            />
          </div>
        </div>
      </div>
      
      <div class="table-row">
        <div class="row-label">(выезд)</div>
        <div class="price-cell">
          <div class="price-input-wrapper">
            <input 
              v-model="outcallHourDisplay" 
              @input="(e) => handlePriceInput(e, 'outcall', 'hour')"
              @keydown="(e) => handlePriceKeydown(e, 'outcall', 'hour')"
              @click="(e) => handlePriceClick(e, 'outcall', 'hour')"
              @keyup="(e) => handlePriceCursorPosition(e, 'outcall', 'hour')"
              @focus="(e) => handlePriceCursorPosition(e, 'outcall', 'hour')"
              @blur="(e) => validatePrice(e, 'outcall', 'hour')"
              type="text" 
              class="price-input" 
              placeholder="₽"
            />
          </div>
        </div>
        <div class="price-cell">
          <div class="price-input-wrapper">
            <input 
              v-model="outcallTwoHoursDisplay" 
              @input="(e) => handlePriceInput(e, 'outcall', 'two_hours')"
              @keydown="(e) => handlePriceKeydown(e, 'outcall', 'two_hours')"
              @click="(e) => handlePriceClick(e, 'outcall', 'two_hours')"
              @keyup="(e) => handlePriceCursorPosition(e, 'outcall', 'two_hours')"
              @focus="(e) => handlePriceCursorPosition(e, 'outcall', 'two_hours')"
              @blur="(e) => validatePrice(e, 'outcall', 'two_hours')"
              type="text" 
              class="price-input" 
              placeholder="₽"
            />
          </div>
        </div>
        <div class="price-cell">
          <div class="price-input-wrapper">
            <input 
              v-model="outcallNightDisplay" 
              @input="(e) => handlePriceInput(e, 'outcall', 'night')"
              @keydown="(e) => handlePriceKeydown(e, 'outcall', 'night')"
              @click="(e) => handlePriceClick(e, 'outcall', 'night')"
              @keyup="(e) => handlePriceCursorPosition(e, 'outcall', 'night')"
              @focus="(e) => handlePriceCursorPosition(e, 'outcall', 'night')"
              @blur="(e) => validatePrice(e, 'outcall', 'night')"
              type="text" 
              class="price-input" 
              placeholder="₽"
            />
          </div>
        </div>
      </div>
    </div>
    
    <!-- Контактов в час -->
    <div class="contacts-section">
      <div class="contacts-label-container">
        <label class="contacts-label">Контактов в час</label>
        <div class="tooltip-container">
          <div class="tooltip-icon">?</div>
          <div class="tooltip-text">
            Количество окончаний клиента в час.<br>
            Например, минет + классика + анал = кончил = 1 контакт<br>
            минет = кончил и классика = кончил — 2 контакта
          </div>
        </div>
      </div>
      <select v-model="contactsPerHour" @change="emitAll" class="contacts-select">
        <option value="">- Выбрать -</option>
        <option value="1">1</option>
        <option value="2">до 2-х</option>
        <option value="3">до 3-х</option>
        <option value="4">до 4-х</option>
        <option value="5">до 5-ти</option>
        <option value="6">безгранично</option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'

const props = defineProps({
  price: { type: String, default: '' },
  priceUnit: { type: String, default: 'hour' },
  isStartingPrice: { type: Boolean, default: false },
  pricingData: { type: Object, default: () => ({}) },
  contactsPerHour: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:price', 'update:priceUnit', 'update:isStartingPrice', 'update:pricingData', 'update:contactsPerHour'])

// Структура цен как на скриншоте
const prices = reactive({
  apartment: {
    hour: props.pricingData?.apartment?.hour || '',
    two_hours: props.pricingData?.apartment?.two_hours || '',
    night: props.pricingData?.apartment?.night || ''
  },
  outcall: {
    hour: props.pricingData?.outcall?.hour || '',
    two_hours: props.pricingData?.outcall?.two_hours || '',
    night: props.pricingData?.outcall?.night || ''
  }
})

const expressPrice = ref(props.pricingData?.express || '')
const expressDisplayValue = ref(props.pricingData?.express ? props.pricingData.express + ' ₽' : '')
const contactsPerHour = ref(props.contactsPerHour || '')

// Display values для всех полей цен
const apartmentHourDisplay = ref(props.pricingData?.apartment?.hour ? props.pricingData.apartment.hour + ' ₽' : '')
const apartmentTwoHoursDisplay = ref(props.pricingData?.apartment?.two_hours ? props.pricingData.apartment.two_hours + ' ₽' : '')
const apartmentNightDisplay = ref(props.pricingData?.apartment?.night ? props.pricingData.apartment.night + ' ₽' : '')
const outcallHourDisplay = ref(props.pricingData?.outcall?.hour ? props.pricingData.outcall.hour + ' ₽' : '')
const outcallTwoHoursDisplay = ref(props.pricingData?.outcall?.two_hours ? props.pricingData.outcall.two_hours + ' ₽' : '')
const outcallNightDisplay = ref(props.pricingData?.outcall?.night ? props.pricingData.outcall.night + ' ₽' : '')

// Совместимость со старой структурой
const localPrice = ref(props.price)
const localUnit = ref(props.priceUnit)
const localIsStartingPrice = ref(props.isStartingPrice)

watch(() => props.pricingData, (newVal) => {
  if (newVal?.apartment) {
    prices.apartment = { ...newVal.apartment }
  }
  if (newVal?.outcall) {
    prices.outcall = { ...newVal.outcall }
  }
  if (newVal?.express) {
    expressPrice.value = newVal.express
  }
}, { deep: true })

watch(() => props.contactsPerHour, (val) => {
  contactsPerHour.value = val || ''
})

// Универсальная функция для установки курсора перед ₽
const setPriceCursorPosition = (element) => {
  if (!element || !element.value) return
  
  let value = element.value
  let numericPart = value.replace(/[^\d]/g, '')
  let targetPos = numericPart.length
  
  element.setSelectionRange(targetPos, targetPos)
}

// Обработчики для поля "Экспресс"
const handleExpressInput = (event) => {
  let value = event.target.value
  let numericValue = value.replace(/[^\d]/g, '')
  
  if (numericValue === '') {
    expressDisplayValue.value = ''
    expressPrice.value = ''
  } else {
    let num = parseInt(numericValue)
    expressDisplayValue.value = num + ' ₽'
    expressPrice.value = num
  }
  
  setTimeout(() => {
    setPriceCursorPosition(event.target)
  }, 0)
  
  emitAll()
}

const handleExpressKeydown = (event) => {
  if (event.key === 'Backspace' || event.key === 'Delete') {
    let value = event.target.value
    let numericValue = value.replace(/[^\d]/g, '')
    
    if (event.key === 'Backspace' && numericValue.length > 0) {
      numericValue = numericValue.slice(0, -1)
    }
    
    if (numericValue === '') {
      expressDisplayValue.value = ''
      expressPrice.value = ''
    } else {
      let num = parseInt(numericValue)
      expressDisplayValue.value = num + ' ₽'
      expressPrice.value = num
    }
    
    event.preventDefault()
    
    setTimeout(() => {
      setPriceCursorPosition(event.target)
    }, 0)
    
    emitAll()
    return
  }
  
  const allowedKeys = ['Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown']
  if (!allowedKeys.includes(event.key) && !/\d/.test(event.key)) {
    event.preventDefault()
  }
}

const handleExpressClick = (event) => {
  setTimeout(() => {
    setPriceCursorPosition(event.target)
  }, 0)
}

const handleExpressCursorPosition = (event) => {
  if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
    setTimeout(() => {
      setPriceCursorPosition(event.target)
    }, 0)
  } else {
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

const validateExpressPrice = (event) => {
  let value = event.target.value.replace(/[^\d]/g, '')
  if (value === '') {
    expressDisplayValue.value = ''
    expressPrice.value = ''
  } else {
    let num = parseInt(value)
    expressDisplayValue.value = num + ' ₽'
    expressPrice.value = num
  }
  
  setTimeout(() => {
    setPriceCursorPosition(event.target)
  }, 0)
  
  emitAll()
}

// Универсальные обработчики для полей таблицы
const handlePriceInput = (event, location, period) => {
  let value = event.target.value
  let numericValue = value.replace(/[^\d]/g, '')
  
  const displayRefMap = {
    'apartment.hour': apartmentHourDisplay,
    'apartment.two_hours': apartmentTwoHoursDisplay,
    'apartment.night': apartmentNightDisplay,
    'outcall.hour': outcallHourDisplay,
    'outcall.two_hours': outcallTwoHoursDisplay,
    'outcall.night': outcallNightDisplay
  }
  
  const displayRef = displayRefMap[`${location}.${period}`]
  
  if (numericValue === '') {
    displayRef.value = ''
    prices[location][period] = ''
  } else {
    let num = parseInt(numericValue)
    displayRef.value = num + ' ₽'
    prices[location][period] = num
  }
  
  setTimeout(() => {
    setPriceCursorPosition(event.target)
  }, 0)
  
  emitAll()
}

const handlePriceKeydown = (event, location, period) => {
  if (event.key === 'Backspace' || event.key === 'Delete') {
    let value = event.target.value
    let numericValue = value.replace(/[^\d]/g, '')
    
    if (event.key === 'Backspace' && numericValue.length > 0) {
      numericValue = numericValue.slice(0, -1)
    }
    
    const displayRefMap = {
      'apartment.hour': apartmentHourDisplay,
      'apartment.two_hours': apartmentTwoHoursDisplay,
      'apartment.night': apartmentNightDisplay,
      'outcall.hour': outcallHourDisplay,
      'outcall.two_hours': outcallTwoHoursDisplay,
      'outcall.night': outcallNightDisplay
    }
    
    const displayRef = displayRefMap[`${location}.${period}`]
    
    if (numericValue === '') {
      displayRef.value = ''
      prices[location][period] = ''
    } else {
      let num = parseInt(numericValue)
      displayRef.value = num + ' ₽'
      prices[location][period] = num
    }
    
    event.preventDefault()
    
    setTimeout(() => {
      setPriceCursorPosition(event.target)
    }, 0)
    
    emitAll()
    return
  }
  
  const allowedKeys = ['Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown']
  if (!allowedKeys.includes(event.key) && !/\d/.test(event.key)) {
    event.preventDefault()
  }
}

const handlePriceClick = (event, location, period) => {
  setTimeout(() => {
    setPriceCursorPosition(event.target)
  }, 0)
}

const handlePriceCursorPosition = (event, location, period) => {
  if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
    setTimeout(() => {
      setPriceCursorPosition(event.target)
    }, 0)
  } else {
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

const validatePrice = (event, location, period) => {
  let value = event.target.value.replace(/[^\d]/g, '')
  
  const displayRefMap = {
    'apartment.hour': apartmentHourDisplay,
    'apartment.two_hours': apartmentTwoHoursDisplay,
    'apartment.night': apartmentNightDisplay,
    'outcall.hour': outcallHourDisplay,
    'outcall.two_hours': outcallTwoHoursDisplay,
    'outcall.night': outcallNightDisplay
  }
  
  const displayRef = displayRefMap[`${location}.${period}`]
  
  if (value === '') {
    displayRef.value = ''
    prices[location][period] = ''
  } else {
    let num = parseInt(value)
    displayRef.value = num + ' ₽'
    prices[location][period] = num
  }
  
  setTimeout(() => {
    setPriceCursorPosition(event.target)
  }, 0)
  
  emitAll()
}

const emitAll = () => {
  // Новая структура данных
  const pricingData = {
    apartment: { ...prices.apartment },
    outcall: { ...prices.outcall },
    express: expressPrice.value
  }
  
  emit('update:pricingData', pricingData)
  emit('update:contactsPerHour', contactsPerHour.value)
  
  // Совместимость со старой структурой - используем первую найденную цену как основную
  const firstPrice = prices.apartment.hour || prices.outcall.hour || expressPrice.value
  emit('update:price', firstPrice)
  emit('update:priceUnit', localUnit.value)
  emit('update:isStartingPrice', localIsStartingPrice.value)
}
</script>

<style scoped>
.price-section {
  background: white;
  border-radius: 8px;
  padding: 20px;
}

.pricing-table {
  margin-bottom: 20px;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid #e5e5e5;
}

.table-header {
  display: grid;
  grid-template-columns: 140px 1fr 1fr 1fr;
  gap: 12px;
  margin-bottom: 8px;
}

.header-cell {
  font-size: 14px;
  font-weight: 500;
  color: #333;
  text-align: center;
}

.header-label {
  text-align: left;
}

.table-row {
  display: grid;
  grid-template-columns: 140px 1fr 1fr 1fr;
  gap: 12px;
  margin-bottom: 8px;
  align-items: center;
}

.row-label {
  font-size: 14px;
  color: #666;
  text-align: left;
}

.price-cell {
  position: relative;
}

.price-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  background: white;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.price-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.express-section {
  margin-bottom: 0;
}

.express-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.express-input {
  width: 100%;
  max-width: 300px;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  background: #f8f9fa;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.express-input:focus {
  outline: none;
  border-color: #007bff;
  background: white;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.contacts-section {
  padding-top: 16px;
  border-top: 1px solid #e5e5e5;
  margin-top: 16px;
}

.contacts-label-container {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.contacts-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #333;
}

.tooltip-container {
  position: relative;
  display: flex;
  align-items: center;
  cursor: help;
}

.tooltip-icon {
  width: 20px;
  height: 20px;
  background-color: #007bff;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: bold;
  flex-shrink: 0;
}

.tooltip-text {
  position: absolute;
  bottom: 100%; /* Показываем сверху */
  left: 50%;
  transform: translateX(-50%);
  background-color: #333;
  color: white;
  padding: 10px;
  border-radius: 8px;
  font-size: 12px;
  line-height: 1.4;
  white-space: pre-wrap; /* Сохраняем переносы строк */
  z-index: 10;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  margin-bottom: 8px;
  min-width: 300px;
  max-width: 400px;
}

.tooltip-text::after {
  content: '';
  position: absolute;
  top: 100%;
  left: 50%;
  transform: translateX(-50%);
  border: 6px solid transparent;
  border-top-color: #333;
}

.tooltip-container:hover .tooltip-text {
  opacity: 1;
  visibility: visible;
}

.contacts-select {
  width: 100%;
  max-width: 300px;
  padding: 10px 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  font-family: inherit;
  background: white;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.contacts-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}
</style> 