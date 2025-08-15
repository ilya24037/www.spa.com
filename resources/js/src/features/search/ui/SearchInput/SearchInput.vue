<!-- Поле ввода поиска -->
<template>
  <div class="flex-1 relative flex items-center">
    <label :for="inputId" class="sr-only">{{ placeholder || 'Поиск' }}</label>
    <input 
      :id="inputId"
      ref="inputRef"
      type="search"
      name="search"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :autofocus="autofocus"
      :autocomplete="autocomplete"
      :class="inputClasses"
      @input="handleInput"
      @keyup.enter="handleEnter"
      @focus="handleFocus"
      @blur="handleBlur"
      @keydown="handleKeyDown"
      aria-label="Поиск"
      :aria-invalid="hasError"
      :aria-describedby="ariaDescribedBy"
      :aria-expanded="isExpanded"
      aria-haspopup="listbox"
      role="combobox"
    >
    
    <!-- Индикатор загрузки -->
    <div 
      v-if="loading && !modelValue" 
      class="absolute left-3 top-1/2 -translate-y-1/2"
    >
      <svg 
        class="animate-spin h-4 w-4 text-gray-400" 
        xmlns="http://www.w3.org/2000/svg" 
        fill="none" 
        viewBox="0 0 24 24"
      >
        <circle 
          class="opacity-25" 
          cx="12" 
          cy="12" 
          r="10" 
          stroke="currentColor" 
          stroke-width="4"
        />
        <path 
          class="opacity-75" 
          fill="currentColor" 
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
    </div>
    
    <!-- Кнопка очистки -->
    <button 
      v-if="showClearButton && modelValue"
      type="button"
      @click.stop="handleClear"
      :class="clearButtonClasses"
      aria-label="Очистить поиск"
      tabindex="-1"
    >
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path 
          fill-rule="evenodd" 
          d="M10 8.586L4.707 3.293a1 1 0 00-1.414 1.414L8.586 10l-5.293 5.293a1 1 0 001.414 1.414L10 11.414l5.293 5.293a1 1 0 001.414-1.414L11.414 10l5.293-5.293a1 1 0 00-1.414-1.414L10 8.586z"
          clip-rule="evenodd"
        />
      </svg>
    </button>
    
    <!-- Индикатор загрузки рядом с кнопкой очистки -->
    <div 
      v-if="loading && modelValue" 
      class="absolute right-8 top-1/2 -translate-y-1/2"
    >
      <svg 
        class="animate-spin h-4 w-4 text-gray-400" 
        xmlns="http://www.w3.org/2000/svg" 
        fill="none" 
        viewBox="0 0 24 24"
      >
        <circle 
          class="opacity-25" 
          cx="12" 
          cy="12" 
          r="10" 
          stroke="currentColor" 
          stroke-width="4"
        />
        <path 
          class="opacity-75" 
          fill="currentColor" 
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        />
      </svg>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue'
import { useId } from '@/src/shared/composables/useId'
import type { SearchInputProps, SearchInputEmits } from '../../model/search.types'

// Props
const props = withDefaults(defineProps<SearchInputProps>(), {
  modelValue: '',
  placeholder: 'Искать на MASSAGIST',
  disabled: false,
  loading: false,
  autofocus: false,
  showClearButton: true,
  autocomplete: 'off',
  hasError: false,
  isExpanded: false,
  ariaDescribedBy: undefined
})

// Emits
const emit = defineEmits<SearchInputEmits>()

// Refs
const inputRef = ref<HTMLInputElement>()
const isFocused = ref(false)
const inputId = useId('search-input')

// Computed
const inputClasses = computed(() => {
  const classes = [
    'w-full h-full px-3',
    'bg-transparent border-none outline-none',
    'text-sm text-gray-900',
    'placeholder-gray-500',
    'appearance-none'
  ]

  if (props.disabled) {
    classes.push('cursor-not-allowed opacity-50')
  }

  if (props.loading && props.modelValue) {
    classes.push('pr-16') // Место для спиннера и кнопки очистки
  } else if (props.showClearButton && props.modelValue) {
    classes.push('pr-8') // Место для кнопки очистки
  }

  return classes.join(' ')
})

const clearButtonClasses = computed(() => {
  const classes = [
    'absolute right-2 p-1',
    'text-gray-400 transition-colors',
    'rounded-full',
    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1'
  ]

  if (!props.disabled) {
    classes.push('hover:text-gray-600 hover:bg-gray-100')
  }

  return classes.join(' ')
})

// Methods
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

const handleEnter = () => {
  emit('enter')
}

const handleClear = () => {
  emit('update:modelValue', '')
  emit('clear')
  
  // Возвращаем фокус на поле ввода
  nextTick(() => {
    inputRef.value?.focus()
  })
}

const handleFocus = (event: FocusEvent) => {
  isFocused.value = true
  emit('focus')
}

const handleBlur = (event: FocusEvent) => {
  isFocused.value = false
  emit('blur')
}

const handleKeyDown = (event: KeyboardEvent) => {
  // Предотвращаем закрытие dropdown при нажатии Escape в поле ввода
  if (event.key === 'Escape' && props.modelValue) {
    event.stopPropagation()
    handleClear()
  }
}

// Public methods
const focus = () => {
  inputRef.value?.focus()
}

const blur = () => {
  inputRef.value?.blur()
}

const select = () => {
  inputRef.value?.select()
}

// Auto-focus
onMounted(() => {
  if (props.autofocus) {
    nextTick(() => {
      focus()
    })
  }
})

// Expose public methods
defineExpose({
  focus,
  blur,
  select,
  inputRef
})
</script>

<style scoped>
/* Скрытие элементов визуально, но оставляя доступными для скринридеров */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}
/* Убираем стандартную кнопку очистки в Safari */
input[type="search"]::-webkit-search-cancel-button,
input[type="search"]::-webkit-search-decoration,
input[type="search"]::-webkit-search-results-button,
input[type="search"]::-webkit-search-results-decoration {
  -webkit-appearance: none;
  appearance: none;
}

/* Убираем стандартную кнопку очистки в Edge */
input[type="search"]::-ms-clear {
  display: none;
  width: 0;
  height: 0;
}

/* Убираем стандартную кнопку очистки в Firefox */
input[type="search"] {
  -moz-appearance: textfield;
}
</style>