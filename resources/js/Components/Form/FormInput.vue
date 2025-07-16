<template>
  <div class="form-field">
    <label v-if="label" class="form-label" :for="id">
      <h5 class="form-label-text">{{ label }}</h5>
    </label>
    <div class="form-input-wrapper">
      <input
        :id="id"
        :type="type"
        :name="name"
        :placeholder="placeholder"
        :value="modelValue"
        :class="[
          'form-input',
          { 'form-input--error': error }
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur')"
        @focus="$emit('focus')"
      />
      <span v-if="postfix" class="form-input-postfix">{{ postfix }}</span>
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
  name: 'FormInput',
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
    type: {
      type: String,
      default: 'text'
    },
    placeholder: {
      type: String,
      default: ''
    },
    modelValue: {
      type: [String, Number],
      default: ''
    },
    postfix: {
      type: String,
      default: null
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
  emits: ['update:modelValue', 'blur', 'focus']
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

.form-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.form-input {
  width: 100%;
  padding: 14px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  color: #1a1a1a;
  background: #fff;
  transition: border-color 0.2s ease;
}

.form-input:focus {
  outline: none;
  border-color: #0066cc;
}

.form-input--error {
  border-color: #ff4d4f;
}

.form-input-postfix {
  position: absolute;
  right: 16px;
  color: #757575;
  font-size: 16px;
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