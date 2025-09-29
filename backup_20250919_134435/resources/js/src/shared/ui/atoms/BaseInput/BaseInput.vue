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
        class="w-full border-gray-300 rounded-lg bg-gray-50 text-gray-900 transition-all duration-200 box-border focus:outline-none focus:border-blue-500 focus:bg-white hover:border-gray-400 disabled:cursor-not-allowed disabled:bg-gray-50 disabled:text-gray-500 readonly:cursor-default readonly:bg-gray-100 placeholder:text-gray-500"
        :class="{
          'pr-10': clearable && inputValue && !suffix,
          'pl-12': prefix,
          'pr-12': suffix && inputValue && !clearable,
          'pr-14': suffix && clearable && inputValue,
          'border-red-500 focus:border-red-500': error,
          'border-gray-300': !error,
          // Размеры
          'px-3 py-1.5 border-2 text-base min-h-[40px]': size === 'md',
          'px-2 py-1 border text-sm h-8': size === 'sm'
        }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown.enter="$emit('enter')"
      >
      
      <!-- Индикатор успешной валидации -->
      <div 
        v-if="showSuccess && !clearable"
        class="absolute top-1/2 -translate-y-1/2 pointer-events-none"
        :class="{
          'right-3': size === 'md',
          'right-2': size === 'sm'
        }"
      >
        <svg 
          class="text-green-500" 
          :width="size === 'sm' ? 16 : 20"
          :height="size === 'sm' ? 16 : 20"
          fill="currentColor" 
          viewBox="0 0 20 20"
        >
          <path 
            fill-rule="evenodd" 
            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" 
            clip-rule="evenodd"
          />
        </svg>
      </div>
      
      <!-- Кнопка очистки -->
      <button
        v-if="clearable && inputValue && !disabled && !readonly"
        type="button"
        class="absolute top-1/2 -translate-y-1/2 bg-transparent border-0 cursor-pointer text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors focus:outline-none"
        :class="{
          'right-3 p-1.5': size === 'md',
          'right-1 p-1': size === 'sm'
        }"
        tabindex="-1"
        @click="clearInput"
      >
        <svg
          :width="size === 'sm' ? 16 : 20"
          :height="size === 'sm' ? 16 : 20"
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
      <div 
        v-if="suffix && inputValue" 
        class="absolute top-1/2 -translate-y-1/2 text-gray-500 font-medium"
        :class="{
          'right-3 text-sm': size === 'md' && !clearable,
          'right-2 text-xs': size === 'sm' && !clearable,
          'right-10': clearable && inputValue && size === 'md',
          'right-7': clearable && inputValue && size === 'sm'
        }"
      >
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
    <div v-if="maxlength && showCounter" class="mt-0.5 text-xs text-gray-400 text-right">
      {{ inputValue.length }}/{{ maxlength }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
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
    size: {
        type: String,
        default: 'md',
        validator: (value) => ['sm', 'md'].includes(value)
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
const touched = ref(false)

// Computed
const inputId = computed(() => props.id || useId('input'))
const inputName = computed(() => props.name || inputId.value)
const inputValue = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
})

// Показывать галочку успеха при успешной валидации
const showSuccess = computed(() => {
    return !props.error && 
           props.modelValue && 
           props.modelValue.toString().length > 0 && 
           touched.value &&
           props.required // Только для обязательных полей
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
    touched.value = true
    emit('blur', event)
}

const clearInput = () => {
    inputValue.value = ''
    emit('clear')
    
    // Убираем автоматическую фокусировку после очистки
    // чтобы не появлялась синяя подсветка
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

<style scoped>
/* Удаляем стрелочки из числовых полей для Chrome, Safari, Edge */
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* Удаляем стрелочки из числовых полей для Firefox */
input[type="number"] {
  -moz-appearance: textfield;
}

</style>

<!-- Все стили мигрированы на Tailwind CSS с полной адаптивностью -->























/* РђРґР°РїС‚РёРІРЅРѕСЃС‚СЊ */

 

