<template>
  <div class="price-section">
    <!-- Экспресс 30 мин (перенесено в начало) -->
    <div class="express-section">
      <label class="express-label">Экспресс 30 мин</label>
      <input 
        v-model="expressPrice" 
        @input="emitAll"
        type="text" 
        class="express-input" 
        placeholder="Экспресс"
      />
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
          <input 
            v-model="prices.apartment.hour" 
            @input="emitAll"
            type="text" 
            class="price-input" 
            placeholder="За час"
          />
        </div>
        <div class="price-cell">
          <input 
            v-model="prices.apartment.two_hours" 
            @input="emitAll"
            type="text" 
            class="price-input" 
            placeholder="За два"
          />
        </div>
        <div class="price-cell">
          <input 
            v-model="prices.apartment.night" 
            @input="emitAll"
            type="text" 
            class="price-input" 
            placeholder="За ночь"
          />
        </div>
      </div>
      
      <div class="table-row">
        <div class="row-label">(выезд)</div>
        <div class="price-cell">
          <input 
            v-model="prices.outcall.hour" 
            @input="emitAll"
            type="text" 
            class="price-input" 
            placeholder="За час"
          />
        </div>
        <div class="price-cell">
          <input 
            v-model="prices.outcall.two_hours" 
            @input="emitAll"
            type="text" 
            class="price-input" 
            placeholder="За два"
          />
        </div>
        <div class="price-cell">
          <input 
            v-model="prices.outcall.night" 
            @input="emitAll"
            type="text" 
            class="price-input" 
            placeholder="За ночь"
          />
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
const contactsPerHour = ref(props.contactsPerHour || '')

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