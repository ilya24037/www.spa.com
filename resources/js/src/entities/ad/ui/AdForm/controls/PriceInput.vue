<template>
  <div class="price-input-wrapper">
    <label v-if="label" :for="id" class="form-label">{{ label }}</label>
    
    <div class="price-input-container">
      <div class="price-field">
        <input
          :id="id"
          :name="name"
          :type="type"
          :value="modelValue"
          :placeholder="placeholder"
          :disabled="disabled"
          :class="['form-input', { 'error': error }]"
          @input="handleInput"
          @blur="handleBlur"
          @focus="handleFocus"
        />
        <span class="currency-symbol">₽</span>
      </div>
      
      <div v-if="showUnit" class="unit-selector">
        <select
          :value="unitValue"
          @change="handleUnitChange"
          class="unit-select"
        >
          <option value="service">За услугу</option>
          <option value="hour">За час</option>
          <option value="session">За сеанс</option>
          <option value="minute">За минуту</option>
        </select>
      </div>
    </div>
    
    <div v-if="error" class="error-message">{{ error }}</div>
    <div v-if="hint && !error" class="hint-text">{{ hint }}</div>
  </div>
</template>

<script>
export default {
  name: 'PriceInput',
  props: {
    modelValue: {
      type: [String, Number],
      default: ''
    },
    unitValue: {
      type: String,
      default: 'service'
    },
    id: {
      type: String,
      default: ''
    },
    name: {
      type: String,
      default: ''
    },
    label: {
      type: String,
      default: ''
    },
    placeholder: {
      type: String,
      default: '0'
    },
    type: {
      type: String,
      default: 'number'
    },
    disabled: {
      type: Boolean,
      default: false
    },
    error: {
      type: String,
      default: ''
    },
    hint: {
      type: String,
      default: ''
    },
    showUnit: {
      type: Boolean,
      default: true
    }
  },
  emits: ['update:modelValue', 'update:unitValue', 'blur', 'focus'],
  methods: {
    handleInput(event) {
      let value = event.target.value
      
      // Убираем все кроме цифр и точки
      value = value.replace(/[^\d.]/g, '')
      
      // Проверяем, что точка только одна
      const dotCount = (value.match(/\./g) || []).length
      if (dotCount > 1) {
        value = value.replace(/\.+$/, '')
      }
      
      // Ограничиваем до 2 знаков после точки
      if (value.includes('.')) {
        const parts = value.split('.')
        value = parts[0] + '.' + parts[1].slice(0, 2)
      }
      
      this.$emit('update:modelValue', value)
    },
    
    handleUnitChange(event) {
      this.$emit('update:unitValue', event.target.value)
    },
    
    handleBlur(event) {
      this.$emit('blur', event)
    },
    
    handleFocus(event) {
      this.$emit('focus', event)
    }
  }
}
</script>

<style scoped>
.price-input-wrapper {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.price-input-container {
  display: flex;
  gap: 12px;
  align-items: flex-end;
}

.price-field {
  position: relative;
  flex: 1;
}

.form-input {
  width: 100%;
  padding: 12px 40px 12px 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 16px;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-input.error {
  border-color: #dc3545;
}

.form-input:disabled {
  background-color: #f8f9fa;
  cursor: not-allowed;
}

.currency-symbol {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
  font-size: 16px;
  pointer-events: none;
}

.unit-selector {
  flex-shrink: 0;
}

.unit-select {
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 8px;
  font-size: 14px;
  background: white;
  cursor: pointer;
  min-width: 120px;
}

.unit-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.error-message {
  color: #dc3545;
  font-size: 12px;
  margin-top: 4px;
}

.hint-text {
  color: #666;
  font-size: 12px;
  margin-top: 4px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .price-input-container {
    flex-direction: column;
    gap: 8px;
  }
  
  .unit-select {
    min-width: 100%;
  }
}
</style>
