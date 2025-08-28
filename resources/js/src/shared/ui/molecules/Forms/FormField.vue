<template>
  <div class="form-field" :class="{ 'has-error': hasError }">
    <!-- Р›РµР№Р±Р» РїРѕР»СЏ -->
    <label v-if="label" class="form-field-label" :for="fieldId">
      {{ label }}
      <span v-if="required" class="required-marker">*</span>
    </label>

    <!-- РџРѕРґСЃРєР°Р·РєР° Рє РїРѕР»СЋ -->
    <div v-if="hint && !hasError" class="form-field-hint">
      {{ hint }}
    </div>

    <!-- РџРѕР»Рµ РІРІРѕРґР° -->
    <div class="form-field-input">
      <slot />
    </div>

    <!-- РћС€РёР±РєР° РїРѕР»СЏ -->
    <div v-if="hasError" class="form-field-error">
      {{ error }}
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    label: {
        type: String,
        default: ''
    },
    hint: {
        type: String,
        default: ''
    },
    error: {
        type: String,
        default: ''
    },
    required: {
        type: Boolean,
        default: false
    },
    fieldId: {
        type: String,
        default: () => `field-${Math.random().toString(36).substr(2, 9)}`
    }
})

const hasError = computed(() => Boolean(props.error))
</script>

<style scoped>
.form-field {
  margin-bottom: 16px;
}

.form-field.has-error .form-field-input input,
.form-field.has-error .form-field-input textarea,
.form-field.has-error .form-field-input select {
  border-color: #ff4d4f;
}

.form-field-label {
  display: block;
  font-size: 16px;
  font-weight: 500;
  color: #1a1a1a;
  margin-bottom: 8px;
  line-height: 1.4;
}

.required-marker {
  color: #ff4d4f;
  margin-left: 4px;
}

.form-field-hint {
  font-size: 14px;
  color: #8c8c8c;
  line-height: 1.4;
  margin-bottom: 8px;
}

.form-field-input {
  /* РљРѕРЅС‚РµР№РЅРµСЂ РґР»СЏ РїРѕР»СЏ РІРІРѕРґР° */
}

.form-field-error {
  font-size: 14px;
  color: #ff4d4f;
  line-height: 1.4;
  margin-top: 8px;
}

/* Р‘Р°Р·РѕРІС‹Рµ СЃС‚РёР»Рё РґР»СЏ РїРѕР»РµР№ РІРІРѕРґР° РІРЅСѓС‚СЂРё РєРѕРјРїРѕРЅРµРЅС‚Р° */
.form-field-input input,
.form-field-input textarea,
.form-field-input select {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #d9d9d9;
  border-radius: 6px;
  font-size: 16px;
  line-height: 1.4;
  background: #fff;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-field-input input:focus,
.form-field-input textarea:focus,
.form-field-input select:focus {
  outline: none;
  border-color: #1890ff;
  box-shadow: 0 0 0 2px rgba(24, 144, 255, 0.2);
}

.form-field-input input:disabled,
.form-field-input textarea:disabled,
.form-field-input select:disabled {
  background: #f5f5f5;
  color: #bfbfbf;
  cursor: not-allowed;
}

.form-field-input textarea {
  resize: vertical;
  min-height: 80px;
}
</style>
