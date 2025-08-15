<!-- Р'Р°Р·РѕРІС‹Р№ textarea РІ СЃС‚РёР»Рµ РђРІРёС‚Рѕ -->
<template>
  <div class="textarea-container">
    <label v-if="label" :for="textareaId" class="textarea-label">{{ label }}</label>
    
    <div class="textarea-wrapper" :class="{ 'has-error': error }">
      <textarea
        :id="textareaId"
        ref="textareaRef"
        v-model="textareaValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :rows="rows"
        :maxlength="maxlength"
        class="base-textarea"
        :class="{
          'disabled': disabled,
          'readonly': readonly,
          'resizable': resizable
        }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown.enter="handleEnter"
      />
    </div>
    
    <!-- РћС€РёР±РєР° -->
    <div v-if="error" class="textarea-error">
      {{ error }}
    </div>
    
    <!-- РџРѕРґСЃРєР°Р·РєР° -->
    <div v-if="hint && !error" class="textarea-hint">
      {{ hint }}
    </div>
    
    <!-- РЎС‡РµС‚С‡РёРє СЃРёРјРІРѕР»РѕРІ -->
    <div v-if="maxlength && showCounter" class="textarea-counter">
      {{ textareaValue.length }}/{{ maxlength }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { useId } from '@/src/shared/composables/useId'

// Props
const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    id: {
        type: String,
        default: ''
    },
    label: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    }, readonly: {
        type: Boolean,
        default: false
    },
    rows: {
        type: Number,
        default: 4
    },
    error: {
        type: String,
        default: ''
    },
    hint: {
        type: String,
        default: ''
    },
    maxlength: {
        type: Number,
        default: null
    },
    showCounter: {
        type: Boolean,
        default: false
    },
    resizable: {
        type: Boolean,
        default: true
    }
})

// Events
const emit = defineEmits(['update:modelValue', 'focus', 'blur', 'enter'])

// Generate unique ID if not provided
const textareaId = computed(() => props.id || useId('textarea'))

// Refs
const textareaRef = ref(null)

// Computed
const textareaValue = computed({
    get() {
        return props.modelValue
    },
    set(value) {
        emit('update:modelValue', value)
    }
})

// Methods
const handleInput = (event) => {
    emit('update:modelValue', event.target.value)
}

const handleFocus = (event) => {
    emit('focus', event)
}

const handleBlur = (event) => {
    emit('blur', event)
}

const handleEnter = (event) => {
    emit('enter', event)
}

// Public methods
const focus = () => {
    nextTick(() => {
        textareaRef.value?.focus()
    })
}

const blur = () => {
    textareaRef.value?.blur()
}

// Expose public methods
defineExpose({
    focus,
    blur
})
</script>

<style scoped>
/* РљРѕРЅС‚РµР№РЅРµСЂ */
.textarea-container {
  display: flex;
  flex-direction: column;
  gap: 6px;
  width: 100%;
}

/* Р›РµР№Р±Р» */
.textarea-label {
  font-size: 14px;
  font-weight: 500;
  color: #1a1a1a;
  line-height: 1.2;
}

/* РћР±РµСЂС‚РєР° textarea */
.textarea-wrapper {
  position: relative;
  display: flex;
  align-items: flex-start;
}

.textarea-wrapper.has-error {
  border-color: #ff4d4f;
}

/* Р‘Р°Р·РѕРІС‹Р№ textarea */
.base-textarea {
  width: 100%;
  min-height: 80px;
  padding: 12px;
  font-size: 16px;
  line-height: 1.4;
  color: #1a1a1a;
  background-color: #ffffff;
  border: 1px solid #d6dae0;
  border-radius: 8px;
  outline: none;
  transition: all 0.2s ease;
  font-family: inherit;
}

.base-textarea:focus {
  border-color: #0066ff;
  box-shadow: 0 0 0 2px rgba(0, 102, 255, 0.1);
}

.base-textarea:hover:not(:focus):not(.disabled):not(.readonly) {
  border-color: #bec5cc;
}

.base-textarea.disabled {
  background-color: #f5f5f5;
  color: #999999;
  cursor: not-allowed;
  border-color: #e1e5ea;
}

.base-textarea.readonly {
  background-color: #f8f9fa;
  cursor: default;
  border-color: #e1e5ea;
}

.base-textarea.resizable {
  resize: vertical;
}

.base-textarea:not(.resizable) {
  resize: none;
}

.base-textarea::placeholder {
  color: #999999;
  font-size: 16px;
}

/* РЎРѕСЃС‚РѕСЏРЅРёРµ РѕС€РёР±РєРё */
.textarea-wrapper.has-error .base-textarea {
  border-color: #ff4d4f;
}

.textarea-wrapper.has-error .base-textarea:focus {
  border-color: #ff4d4f;
  box-shadow: 0 0 0 2px rgba(255, 77, 79, 0.1);
}

/* РЎРѕРѕР±С‰РµРЅРёРµ РѕР± РѕС€РёР±РєРµ */
.textarea-error {
  font-size: 13px;
  color: #ff4d4f;
  line-height: 1.3;
  margin-top: 2px;
}

/* РџРѕРґСЃРєР°Р·РєР° */
.textarea-hint {
  font-size: 13px;
  color: #666666;
  line-height: 1.3;
  margin-top: 2px;
}

/* РЎС‡РµС‚С‡РёРє СЃРёРјРІРѕР»РѕРІ */
.textarea-counter {
  font-size: 12px;
  color: #999999;
  text-align: right;
  margin-top: 4px;
}

/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */
@media (max-width: 768px) {
  .base-textarea {
    font-size: 16px; /* РџСЂРµРґРѕС‚РІСЂР°С‰Р°РµС‚ Р·СѓРј РЅР° iOS */
  }
}

/* РўРµРјРЅР°СЏ С‚РµРјР° (РѕРїС†РёРѕРЅР°Р»СЊРЅРѕ) */
@media (prefers-color-scheme: dark) {
  .textarea-label {
    color: #e1e5ea;
  }
  
  .base-textarea {
    background-color: #2a2a2a;
    border-color: #4a4a4a;
    color: #e1e5ea;
  }
  
  .base-textarea:focus {
    border-color: #0066ff;
    box-shadow: 0 0 0 2px rgba(0, 102, 255, 0.2);
  }
  
  .base-textarea.disabled {
    background-color: #1a1a1a;
    color: #666666;
    border-color: #333333;
  }
  
  .base-textarea.readonly {
    background-color: #1f1f1f;
    border-color: #333333;
  }
  
  .base-textarea::placeholder {
    color: #666666;
  }
  
  .textarea-hint {
    color: #999999;
  }
  
  .textarea-counter {
    color: #666666;
  }
}
</style> 

