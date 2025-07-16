<template>
  <div class="form-field">
    <label v-if="label" class="form-label" :for="id">
      <h5 class="form-label-text">{{ label }}</h5>
    </label>
    <div class="form-select-wrapper">
      <select
        :id="id"
        :name="name"
        :value="modelValue"
        :class="[
          'form-select',
          { 'form-select--error': error }
        ]"
        @change="$emit('update:modelValue', $event.target.value)"
      >
        <option value="" disabled>{{ placeholder }}</option>
        <option
          v-for="option in options"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
      <div class="form-select-icon">
        <svg width="20" height="20" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
          <path d="M2.86 5.2c.27-.27.7-.27.96 0l4.26 4.2 4.26-4.2c.27-.27.7-.27.96 0 .27.26.27.68 0 .94L8.56 10.8a.68.68 0 0 1-.96 0L2.86 6.14a.66.66 0 0 1 0-.94Z" fill="currentColor"/>
        </svg>
      </div>
    </div>
    <div v-if="hint" class="form-hint">
      <p class="form-hint-text">{{ hint }}</p>
    </div>
    <div v-if="error" class="form-error">
      <p class="form-error-text">{{ error }}</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'FormSelect',
  props: {
    id: {
      type: String,
      required: true
    },
    label: {
      type: String,
      default: null
    },
    name: {
      type: String,
      required: true
    },
    placeholder: {
      type: String,
      default: 'Выберите вариант'
    },
    modelValue: {
      type: [String, Number],
      default: ''
    },
    options: {
      type: Array,
      required: true
    },
    hint: {
      type: String,
      default: null
    },
    error: {
      type: String,
      default: null
    }
  },
  emits: ['update:modelValue']
}
</script>

<style scoped>
.form-field {
  margin-bottom: 32px;
}

.form-label {
  display: block;
  margin-bottom: 12px;
}

.form-label-text {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
  margin: 0;
}

.form-select-wrapper {
  position: relative;
}

.form-select {
  width: 100%;
  padding: 14px 40px 14px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  color: #1a1a1a;
  background: #fff;
  appearance: none;
  transition: border-color 0.2s ease;
  cursor: pointer;
}

.form-select:focus {
  outline: none;
  border-color: #0066cc;
}

.form-select--error {
  border-color: #ff4d4f;
}

.form-select-icon {
  position: absolute;
  right: 16px;
  top: 50%;
  transform: translateY(-50%);
  color: #757575;
  pointer-events: none;
}

.form-hint {
  margin-top: 10px;
}

.form-hint-text {
  font-size: 14px;
  color: #757575;
  margin: 0;
  line-height: 1.4;
}

.form-error {
  margin-top: 10px;
}

.form-error-text {
  font-size: 14px;
  color: #ff4d4f;
  margin: 0;
  line-height: 1.4;
}
</style> 