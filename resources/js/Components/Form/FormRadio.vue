<template>
  <div class="form-field">
    <label v-if="label" class="form-label">
      <h5 class="form-label-text">{{ label }}</h5>
    </label>
    <div class="form-radio-group">
      <label 
        v-for="option in options"
        :key="option.value"
        class="form-radio-item"
      >
        <div class="form-radio-wrapper">
          <input
            type="radio"
            :name="name"
            :value="option.value"
            :checked="modelValue === option.value"
            class="form-radio-input"
            @change="$emit('update:modelValue', option.value)"
          />
          <div class="form-radio-toggle">
            <span class="form-radio-circle"></span>
          </div>
        </div>
        <div class="form-radio-label">
          <div class="form-radio-title">{{ option.label }}</div>
          <div v-if="option.description" class="form-radio-description">
            {{ option.description }}
          </div>
        </div>
      </label>
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
  name: 'FormRadio',
  props: {
    label: {
      type: String,
      default: null
    },
    name: {
      type: String,
      required: true
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

.form-radio-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.form-radio-item {
  display: flex;
  align-items: flex-start;
  cursor: pointer;
  padding: 14px 0;
}

.form-radio-wrapper {
  position: relative;
  margin-right: 12px;
  margin-top: 2px;
}

.form-radio-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.form-radio-toggle {
  width: 20px;
  height: 20px;
  border: 1px solid #e0e0e0;
  border-radius: 50%;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.form-radio-input:checked + .form-radio-toggle {
  border-color: #0066cc;
}

.form-radio-circle {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #0066cc;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.form-radio-input:checked + .form-radio-toggle .form-radio-circle {
  opacity: 1;
}

.form-radio-label {
  flex: 1;
}

.form-radio-title {
  font-size: 16px;
  color: #1a1a1a;
  margin: 0 0 4px 0;
}

.form-radio-description {
  font-size: 14px;
  color: #757575;
  margin: 0;
  line-height: 1.4;
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