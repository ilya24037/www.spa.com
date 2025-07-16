<template>
  <div class="form-field">
    <label v-if="label" class="form-label">
      <h5 class="form-label-text">{{ label }}</h5>
    </label>
    <div class="form-checkbox-group">
      <label 
        v-for="option in options"
        :key="option.value"
        class="form-checkbox-item"
      >
        <div class="form-checkbox-wrapper">
          <input
            type="checkbox"
            :name="name"
            :value="option.value"
            :checked="modelValue.includes(option.value)"
            class="form-checkbox-input"
            @change="handleChange(option.value)"
          />
          <div class="form-checkbox-toggle">
            <span class="form-checkbox-icon">
              <svg width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
              </svg>
            </span>
          </div>
        </div>
        <div class="form-checkbox-label">
          <p class="form-checkbox-text">{{ option.label }}</p>
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
  name: 'FormCheckbox',
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
      type: Array,
      default: () => []
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
  emits: ['update:modelValue'],
  methods: {
    handleChange(value) {
      const newValue = [...this.modelValue];
      const index = newValue.indexOf(value);
      
      if (index > -1) {
        newValue.splice(index, 1);
      } else {
        newValue.push(value);
      }
      
      this.$emit('update:modelValue', newValue);
    }
  }
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

.form-checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.form-checkbox-item {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 10px 0;
}

.form-checkbox-wrapper {
  position: relative;
  margin-right: 12px;
}

.form-checkbox-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.form-checkbox-toggle {
  width: 20px;
  height: 20px;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  background: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.form-checkbox-input:checked + .form-checkbox-toggle {
  background: #0066cc;
  border-color: #0066cc;
}

.form-checkbox-icon {
  color: #fff;
  width: 12px;
  height: 12px;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.form-checkbox-input:checked + .form-checkbox-toggle .form-checkbox-icon {
  opacity: 1;
}

.form-checkbox-label {
  flex: 1;
}

.form-checkbox-text {
  font-size: 16px;
  color: #1a1a1a;
  margin: 0;
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