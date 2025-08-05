<template>
  <div class="input-wrapper">
    <!-- Label -->
    <label 
      v-if="label"
      :for="inputId"
      class="input-label"
      :class="{ 'input-label--required': required }"
    >
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1" aria-label="обязательное поле">*</span>
    </label>

    <!-- Input container -->
    <div class="input-container" :class="containerClasses">
      <!-- Prefix icon/text -->
      <span v-if="prefix || prefixIcon" class="input-prefix">
        <component v-if="prefixIcon" :is="prefixIcon" class="w-5 h-5" />
        <span v-else>{{ prefix }}</span>
      </span>

      <!-- Actual input -->
      <input
        :id="inputId"
        ref="inputRef"
        v-model="localValue"
        :type="inputType"
        :name="name"
        :placeholder="placeholder"
        :disabled="disabled"
        :Readonly="Readonly"
        :required="required"
        :autofocus="autofocus"
        :autocomplete="autocomplete"
        :min="min"
        :max="max"
        :minlength="minlength"
        :maxlength="maxlength"
        :pattern="pattern"
        :class="inputClasses"
        :aria-invalid="hasError"
        :aria-describedby="errorId"
        @input="handleInput"
        @change="handleChange"
        @blur="handleBlur"
        @focus="handleFocus"
        @keydown="handleKeydown"
      />

      <!-- Show/hide password button -->
      <button
        v-if="type === 'password' && localValue"
        type="button"
        class="input-password-toggle"
        :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
        @click="togglePassword"
      >
        <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
        </svg>
        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
        </svg>
      </button>

      <!-- Clear button -->
      <button
        v-if="clearable && localValue && !disabled && !Readonly"
        type="button"
        class="input-clear"
        aria-label="Очистить поле"
        @click="clearInput"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Suffix icon/text -->
      <span v-if="suffix || suffixIcon" class="input-suffix">
        <component v-if="suffixIcon" :is="suffixIcon" class="w-5 h-5" />
        <span v-else>{{ suffix }}</span>
      </span>

      <!-- Loading spinner -->
      <span v-if="loading" class="input-loading">
        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
      </span>
    </div>

    <!-- Helper text -->
    <p v-if="helperText && !hasError" class="input-helper">
      {{ helperText }}
    </p>

    <!-- Error message -->
    <p v-if="hasError" :id="errorId" class="input-error" role="alert">
      {{ error }}
    </p>

    <!-- Character counter -->
    <p v-if="showCounter && maxlength" class="input-counter">
      {{ localValue?.length || 0 }} / {{ maxlength }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, type Component } from 'vue'
import { useId } from '@/shared/composables/useId'

// TypeScript типы
export interface InputProps {
  // Основные
  modelValue?: string | number | null
  type?: 'text' | 'email' | 'password' | 'tel' | 'url' | 'number' | 'search' | 'date' | 'time'
  name?: string
  label?: string
  placeholder?: string
  
  // Валидация
  required?: boolean
  error?: string
  pattern?: string
  min?: string | number
  max?: string | number
  minlength?: number
  maxlength?: number
  
  // Состояния
  disabled?: boolean,
  readonly?: boolean
  loading?: boolean
  autofocus?: boolean
  
  // Дополнительно
  prefix?: string
  suffix?: string
  prefixIcon?: Component | string
  suffixIcon?: Component | string
  helperText?: string
  clearable?: boolean
  showCounter?: boolean
  autocomplete?: string
  
  // Стили
  size?: 'sm' | 'md' | 'lg'
  variant?: 'outline' | 'filled' | 'underline'
}

// Props
const props = withDefaults(defineProps<InputProps>(), {
  type: 'text',
  size: 'md',
  variant: 'outline',
  clearable: false,
  showCounter: false,
  autocomplete: 'off'
})

// Emits
const emit = defineEmits<{
  'update:modelValue': [value: string | number | null]
  'input': [value: string | number | null]
  'change': [value: string | number | null]
  'blur': [event: FocusEvent]
  'focus': [event: FocusEvent]
  'clear': []
  'keydown': [event: KeyboardEvent]
}>()

// Refs
const inputRef = ref<HTMLInputElement>()
const localValue = ref(props.modelValue)
const isFocused = ref(false)
const showPassword = ref(false)

// IDs для доступности
const inputId = useId('input')
const errorId = useId('error')

// Computed
const hasError = computed(() => !!props.error)

const inputType = computed(() => {
  if (props.type === 'password' && showPassword.value) {
    return 'text'
  }
  return props.type
})

const containerClasses = computed(() => [
  'input-container',
  `input-container--${props.size}`,
  `input-container--${props.variant}`,
  {
    'input-container--focused': isFocused.value,
    'input-container--error': hasError.value,
    'input-container--disabled': props.disabled,
    'input-container--loading': props.loading
  }
])

const inputClasses = computed(() => [
  'input-field',
  {
    'pl-10': props.prefix || props.prefixIcon,
    'pr-10': props.suffix || props.suffixIcon || props.clearable || props.loading
  }
])

// Methods
const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  const value = props.type === 'number' ? Number(target.value) : target.value
  localValue.value = value
  emit('update:modelValue', value)
  emit('input', value)
}

const handleChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const value = props.type === 'number' ? Number(target.value) : target.value
  emit('change', value)
}

const handleBlur = (event: FocusEvent) => {
  isFocused.value = false
  emit('blur', event)
}

const handleFocus = (event: FocusEvent) => {
  isFocused.value = true
  emit('focus', event)
}

const handleKeydown = (event: KeyboardEvent) => {
  emit('keydown', event)
}

const clearInput = () => {
  localValue.value = ''
  emit('update:modelValue', '')
  emit('clear')
  inputRef.value?.focus()
}

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

// Focus method для внешнего использования
const focus = () => {
  inputRef.value?.focus()
}

// Blur method для внешнего использования
const blur = () => {
  inputRef.value?.blur()
}

// Watchers
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue
})

// Lifecycle
onMounted(() => {
  if (props.autofocus) {
    focus()
  }
})

// Expose для родительских компонентов
defineExpose({
  focus,
  blur,
  inputRef
})
</script>

<style scoped>
/* Wrapper */
.input-wrapper {
  @apply w-full;
}

/* Label */
.input-label {
  @apply block mb-1 text-sm font-medium text-gray-700;
}

.input-label--required {
  @apply font-semibold;
}

/* Container */
.input-container {
  @apply relative flex items-center w-full transition-all duration-200;
}

/* Sizes */
.input-container--sm {
  @apply text-sm;
}

.input-container--md {
  @apply text-base;
}

.input-container--lg {
  @apply text-lg;
}

/* Variants */
.input-container--outline {
  @apply border rounded-md bg-white;
}

.input-container--filled {
  @apply border-0 bg-gray-100 rounded-md;
}

.input-container--underline {
  @apply border-b bg-transparent rounded-none;
}

/* States */
.input-container--focused {
  @apply ring-2 ring-blue-500 border-blue-500;
}

.input-container--error {
  @apply border-red-500;
}

.input-container--disabled {
  @apply opacity-50 cursor-not-allowed bg-gray-50;
}

/* Input field */
.input-field {
  @apply w-full px-3 py-2 bg-transparent border-0 focus:outline-none;
  @apply placeholder-gray-400;
}

/* Size variations for input */
.input-container--sm .input-field {
  @apply px-2 py-1 text-sm;
}

.input-container--lg .input-field {
  @apply px-4 py-3 text-lg;
}

/* Prefix/Suffix */
.input-prefix,
.input-suffix {
  @apply flex items-center px-3 text-gray-500;
}

/* Buttons */
.input-clear,
.input-password-toggle {
  @apply p-1 text-gray-400 hover:text-gray-600 focus:outline-none;
}

/* Loading */
.input-loading {
  @apply absolute right-3 text-gray-400;
}

/* Helper text */
.input-helper {
  @apply mt-1 text-sm text-gray-500;
}

/* Error message */
.input-error {
  @apply mt-1 text-sm text-red-600;
}

/* Counter */
.input-counter {
  @apply mt-1 text-xs text-gray-400 text-right;
}

/* Disabled state */
.input-container--disabled .input-field {
  @apply cursor-not-allowed;
}
</style>