<template>
  <div class="form-field">
    <label v-if="label" class="form-label" :for="id">
      <h5 class="form-label-text">{{ label }}</h5>
    </label>
    <div class="form-textarea-wrapper">
      <textarea
        :id="id"
        :name="name"
        :placeholder="placeholder"
        :rows="rows"
        :value="modelValue"
        :class="[
          'form-textarea',
          { 'form-textarea--error': error }
        ]"
        @input="$emit('update:modelValue', $event.target.value)"
        @blur="$emit('blur')"
        @focus="$emit('focus')"
      ></textarea>
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
  name: 'FormTextarea',
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
      default: ''
    },
    rows: {
      type: Number,
      default: 6
    },
    modelValue: {
      type: String,
      default: ''
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
  margin-bottom: 28px;
}

.form-label {
  display: block;
  margin-bottom: 8px;
}

.form-label-text {
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
  margin: 0;
}

.form-textarea-wrapper {
  position: relative;
}

.form-textarea {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 16px;
  color: #1a1a1a;
  background: #fff;
  resize: vertical;
  min-height: 120px;
  transition: border-color 0.2s ease;
  font-family: inherit;
}

.form-textarea:focus {
  outline: none;
  border-color: #0066cc;
}

.form-textarea--error {
  border-color: #ff4d4f;
}

.form-hint {
  margin-top: 8px;
}

.form-hint-text {
  font-size: 14px;
  color: #757575;
  margin: 0;
  line-height: 1.4;
}

.form-error {
  margin-top: 8px;
}

.form-error-text {
  font-size: 14px;
  color: #ff4d4f;
  margin: 0;
  line-height: 1.4;
}
</style> 