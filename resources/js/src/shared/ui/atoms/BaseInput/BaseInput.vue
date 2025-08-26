<!-- Базовый инпут в стиле Авито -->
<template>
  <div class="w-full">
    <label v-if="label" :for="inputId" class="block text-base font-medium text-gray-900 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
    
    <div
      class="relative w-full"
      :class="{ 
        'has-error': error,
        'has-prefix': prefix,
        'has-suffix': suffix
      }"
    >
      <input
        :id="inputId"
        ref="inputRef"
        v-model="inputValue"
        :type="type"
        :name="inputName"
        :placeholder="placeholder"
        :aria-label="!label && placeholder ? placeholder : undefined"
        :aria-invalid="!!error"
        :aria-describedby="error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined"
        :disabled="disabled"
        :readonly="readonly"
        :maxlength="maxlength"
        :min="min"
        :max="max"
        :step="step"
        class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg bg-gray-50 text-base text-gray-900 transition-all duration-200 min-h-[48px] box-border focus:outline-none focus:border-blue-500 focus:bg-white hover:border-gray-400 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 readonly:cursor-default readonly:bg-gray-100 placeholder:text-gray-500"
        :class="{
          'pr-10': clearable && inputValue,
          'pl-12': prefix,
          'pr-12': suffix,
          'pr-20': suffix && clearable && inputValue,
          'border-red-500 focus:border-red-500': error,
          'border-gray-300': !error
        }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown.enter="$emit('enter')"
      >
      
      <!-- Кнопка очистки -->
      <button
        v-if="clearable && inputValue && !disabled && !readonly"
        type="button"
        class="absolute right-3 top-1/2 -translate-y-1/2 bg-transparent border-0 cursor-pointer p-1.5 text-gray-400 hover:text-gray-600 transition-colors"
        tabindex="-1"
        @click="clearInput"
      >
        <svg
          width="20"
          height="20"
          viewBox="0 0 16 16"
          fill="none"
        >
          <path
            d="M12 4L4 12M4 4L12 12"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
      </button>
      
      <!-- Префикс -->
      <div v-if="prefix" class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium">
        {{ prefix }}
      </div>
      
      <!-- Суффикс -->
      <div v-if="suffix" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-sm font-medium">
        {{ suffix }}
      </div>
    </div>
    
    <!-- Ошибка -->
    <div v-if="error" :id="`${inputId}-error`" class="mt-2 text-sm text-red-600" role="alert">
      {{ error }}
    </div>
    
    <!-- Подсказка -->
    <div v-if="hint && !error" :id="`${inputId}-hint`" class="mt-2 text-sm text-gray-500">
      {{ hint }}
    </div>
    
    <!-- Счетчик символов -->
    <div v-if="maxlength && showCounter" class="mt-2 text-xs text-gray-400 text-right">
      {{ inputValue.length }}/{{ maxlength }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue'
import { useId } from '@/src/shared/composables/useId'

// Props
const props = defineProps({
    modelValue: {
        type: [String, Number],
        default: ''
    },
    id: {
        type: String,
        default: ''
    },
    name: {
        type: String,
        default: '',
        validator: (value, props) => {
            // Предупреждение если нет name (но не блокируем работу)
            if (!value && !props.id) {
                // Убираем предупреждение - id генерируется автоматически при необходимости
            }
            return true
        }
    },
    type: {
        type: String,
        default: 'text',
        validator: (value) => [
            'text', 'email', 'password', 'number', 
            'tel', 'url', 'search'
        ].includes(value)
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
    clearable: {
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
    maxlength: {
        type: Number,
        default: null
    },
    showCounter: {
        type: Boolean,
        default: false
    },
    prefix: {
        type: String,
        default: ''
    },
    suffix: {
        type: String,
        default: ''
    },
    min: {
        type: [String, Number],
        default: null
    },
    max: {
        type: [String, Number],
        default: null
    },
    step: {
        type: [String, Number],
        default: null
    },
    required: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['update:modelValue', 'input', 'focus', 'blur', 'enter', 'clear'])

// Refs
const inputRef = ref(null)

// Computed
const inputId = computed(() => props.id || useId('input'))
const inputName = computed(() => props.name || inputId.value)
const inputValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// Methods
const handleInput = (event) => {
    const target = event.target
    const value = target.value
    
    // Для числовых полей конвертируем в число
    if (props.type === 'number') {
        const numValue = parseFloat(value)
        if (!isNaN(numValue)) {
            inputValue.value = numValue
        } else {
            inputValue.value = value
        }
    } else {
        inputValue.value = value
    }
    
    emit('input', inputValue.value)
}

const handleFocus = (event) => {
    emit('focus', event)
}

const handleBlur = (event) => {
    emit('blur', event)
}

const clearInput = () => {
    inputValue.value = ''
    emit('clear')
    
    // Фокусируемся на поле после очистки
    nextTick(() => {
        if (inputRef.value) {
            inputRef.value.focus()
        }
    })
}

const focus = () => {
    if (inputRef.value) {
        inputRef.value.focus()
    }
}

const blur = () => {
    if (inputRef.value) {
        inputRef.value.blur()
    }
}

// Expose methods
defineExpose({
    focus,
    blur
})
</script>

<!-- Все стили мигрированы на Tailwind CSS с полной адаптивностью -->























/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */

 

