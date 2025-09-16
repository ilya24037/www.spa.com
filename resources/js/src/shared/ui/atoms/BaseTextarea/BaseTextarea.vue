<!-- Р'Р°Р·РѕРІС‹Р№ textarea РІ СЃС‚РёР»Рµ РђРІРёС‚Рѕ -->
<template>
  <div class="flex flex-col gap-1.5 w-full">
    <label v-if="label" :for="textareaId" class="text-sm font-medium text-gray-900 leading-tight">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
    
    <div class="relative flex items-start">
      <textarea
        :id="textareaId"
        ref="textareaRef"
        v-model="textareaValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :readonly="readonly"
        :rows="rows"
        :maxlength="maxlength"
        class="w-full min-h-[80px] px-3 py-1.5 text-sm leading-relaxed text-gray-900 bg-white border-2 border-gray-300 rounded-lg outline-none transition-all duration-200 font-inherit placeholder-gray-400"
        :class="{
          'bg-gray-50 text-gray-400 cursor-not-allowed border-gray-200': disabled,
          'bg-gray-50 cursor-default border-gray-200': readonly && !disabled,
          'resize-y': resizable,
          'resize-none': !resizable,
          'border-red-500 focus:border-red-500 focus:ring-2 focus:ring-red-100': error,
          'hover:border-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100': !error && !disabled && !readonly
        }"
        @input="handleInput"
        @focus="handleFocus"
        @blur="handleBlur"
        @keydown.enter="handleEnter"
      />
    </div>
    
    <!-- Ошибка -->
    <div v-if="error" class="text-xs text-red-500 leading-snug mt-0.5">
      {{ error }}
    </div>
    
    <!-- Подсказка -->
    <div v-if="hint && !error" class="text-xs text-gray-600 leading-snug mt-0.5">
      {{ hint }}
    </div>
    
    <!-- Счетчик символов -->
    <div v-if="maxlength && showCounter" class="text-xs text-gray-400 text-right mt-1">
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
    },
    required: {
        type: Boolean,
        default: false
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

<!-- Компонент полностью мигрирован на Tailwind CSS --> 

