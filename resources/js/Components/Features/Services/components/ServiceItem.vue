<template>
  <li class="list-checkbox__item">
    <label class="list-checkbox__label">
      <div class="jq-checkbox js-styled">
        <input 
          type="checkbox" 
          class="js-styled" 
          :name="`xfield[${service.id}]`" 
          value="1"
          v-model="enabled"
        >
        <div class="jq-checkbox__div"></div>
      </div>
      <span class="label-text">{{ serviceLabel }}</span>
    </label>
    <div class="additional-price">
      <button 
        type="button" 
        class="js-add-val button"
        @click="addPrice"
      ></button>
      <input 
        type="number" 
        class="additional-price__input" 
        :name="`service_price[${service.id}]`" 
        min="0" 
        max="99999" 
        step="100" 
        placeholder="0"
        v-model="serviceData.price"
        @input="emitUpdate"
      >
    </div>
    <div class="service-comment-field">
      <input 
        type="text" 
        :name="`uslugi_comm[${service.id}]`" 
        placeholder="(макс 100 символов)" 
        maxlength="100"
        v-model="serviceData.price_comment"
        @input="emitUpdate"
      >
    </div>
  </li>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  service: {
    type: Object,
    required: true
  },
  modelValue: {
    type: Object,
    default: () => ({ enabled: false, price: '', price_comment: '' })
  }
})

const emit = defineEmits(['update:modelValue'])

// Локальное состояние
const serviceData = ref({
  enabled: props.modelValue?.enabled || false,
  price: props.modelValue?.price || '',
  price_comment: props.modelValue?.price_comment || ''
})

// Computed для enabled
const enabled = computed({
  get: () => serviceData.value.enabled,
  set: (value) => {
    serviceData.value.enabled = value
    if (!value) {
      // Очищаем данные при отключении
      serviceData.value.price = ''
      serviceData.value.price_comment = ''
    }
    emitUpdate()
  }
})

// Лейбл для чекбокса с популярностью
const serviceLabel = computed(() => {
  let label = props.service.name
  if (props.service.popular) {
    label += ' (Популярно)'
  }
  return label
})

// Функция добавления цены (кнопка +)
const addPrice = () => {
  if (!serviceData.value.price) {
    serviceData.value.price = '1000'
  } else {
    serviceData.value.price = String(parseInt(serviceData.value.price) + 500)
  }
  emitUpdate()
}

// Предотвращение избыточных обновлений с проверкой изменений
let updateTimeout = null
let lastEmittedData = null

const emitUpdate = () => {
  if (updateTimeout) clearTimeout(updateTimeout)
  updateTimeout = setTimeout(() => {
    const currentData = JSON.stringify(serviceData.value)
    
    // Emit только если данные действительно изменились
    if (currentData !== lastEmittedData) {
      lastEmittedData = currentData
      emit('update:modelValue', { ...serviceData.value })
      // console.log('ServiceItem updated:', props.service.name, serviceData.value.enabled)
    }
  }, 10) // Уменьшил delay для быстрой реакции
}

// Отслеживаем изменения из родителя
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    serviceData.value = { ...newValue }
  }
}, { deep: true })
</script>

<style scoped>
.list-checkbox__item {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  gap: 15px;
  padding: 12px 0;
  border-bottom: 1px solid #f0f0f0;
  align-items: center;
}

.list-checkbox__label {
  display: flex;
  align-items: center;
  cursor: pointer;
  min-width: 0;
}

.jq-checkbox {
  display: inline-flex;
  align-items: center;
  position: relative;
  margin-right: 10px;
  flex-shrink: 0;
}

.jq-checkbox input[type="checkbox"] {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  width: 0;
  height: 0;
}

.jq-checkbox__div {
  width: 18px;
  height: 18px;
  border: 2px solid #ddd;
  border-radius: 3px;
  background: #fff;
  position: relative;
  transition: all 0.2s ease;
}

.jq-checkbox input:checked + .jq-checkbox__div {
  background: #007bff;
  border-color: #007bff;
}

.jq-checkbox input:checked + .jq-checkbox__div::after {
  content: '';
  position: absolute;
  left: 5px;
  top: 2px;
  width: 6px;
  height: 10px;
  border: solid white;
  border-width: 0 2px 2px 0;
  transform: rotate(45deg);
}

.label-text {
  font-size: 14px;
  line-height: 1.4;
  color: #333;
  word-break: break-word;
}

.additional-price {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 120px;
  justify-content: center;
}

.js-add-val.button {
  width: 24px;
  height: 24px;
  border: 1px solid #007bff;
  background: #fff;
  border-radius: 3px;
  position: relative;
  cursor: pointer;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.js-add-val.button:hover {
  background: #007bff;
}

.js-add-val.button::before,
.js-add-val.button::after {
  content: '';
  position: absolute;
  background: #007bff;
  transition: all 0.2s ease;
}

.js-add-val.button:hover::before,
.js-add-val.button:hover::after {
  background: #fff;
}

.js-add-val.button::before {
  width: 12px;
  height: 2px;
  top: 11px;
  left: 6px;
}

.js-add-val.button::after {
  width: 2px;
  height: 12px;
  top: 6px;
  left: 11px;
}

.additional-price__input {
  width: 70px;
  padding: 6px 8px;
  border: 1px solid #ddd;
  border-radius: 3px;
  font-size: 14px;
  text-align: center;
  background: #fff;
  transition: border-color 0.2s ease;
}

.additional-price__input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.service-comment-field {
  min-width: 0;
}

.service-comment-field input {
  width: 100%;
  padding: 6px 10px;
  border: 1px solid #ddd;
  border-radius: 3px;
  font-size: 14px;
  background: #fff;
  transition: border-color 0.2s ease;
}

.service-comment-field input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.service-comment-field input::placeholder {
  color: #999;
  font-size: 13px;
}

/* Адаптивность для мобильных */
@media (max-width: 768px) {
  .list-checkbox__item {
    grid-template-columns: 1fr;
    gap: 12px;
  }
  
  .additional-price {
    justify-content: flex-start;
    margin-left: 28px;
  }
  
  .service-comment-field {
    margin-left: 28px;
  }
}
</style> 