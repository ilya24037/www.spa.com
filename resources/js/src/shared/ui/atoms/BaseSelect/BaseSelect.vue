<!-- Базовый селект в стиле Авито -->
<template>
  <div class="w-full" ref="selectRef">
    <label v-if="label" :for="selectId" class="block text-base font-normal text-gray-900 mb-2">
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>
    
    <!-- Скрытый select для форм -->
    <select 
      :id="selectId"
      :value="modelValue"
      :disabled="disabled"
      class="sr-only"
      tabindex="-1"
      @change="$emit('update:modelValue', $event.target.value)"
    >
      <option v-if="placeholder" value="">{{ placeholder }}</option>
      <option 
        v-for="(option, index) in flatOptions" 
        :key="index"
        v-show="!option.isGroup"
        :value="option.value"
      >
        {{ option.label }}
      </option>
    </select>
    
    <div 
      class="relative w-full"
      :class="{ 'active': isOpen, 'disabled': disabled }"
    >
      <div 
        class="w-full px-3 py-1.5 pr-1 border-2 rounded-lg cursor-pointer flex items-center justify-between min-h-[40px] transition-all duration-200 hover:border-gray-400 focus:outline-none focus:border-blue-500 focus:bg-white disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-50"
        :class="[
          isOpen ? 'border-blue-500 bg-white rounded-b-none' : '',
          error && !isOpen ? 'border-red-300 bg-red-50' : '',
          !error && !isOpen ? 'border-gray-300 bg-gray-50' : ''
        ]"
        :tabindex="disabled ? -1 : 0"
        @click.stop="toggleDropdown"
        @keydown.enter="toggleDropdown"
        @keydown.space="toggleDropdown"
        @keydown.escape="isOpen = false"
      >
        <span class="text-base text-gray-900 font-normal text-left flex-1">
          {{ selectedOption?.label || placeholder }}
        </span>
        <svg 
          class="text-gray-500 transition-transform duration-200 flex-shrink-0" 
          :class="{ 'rotate-180': isOpen }"
          width="20" 
          height="20" 
          viewBox="0 0 20 20"
        >
          <path 
            d="M5 7.5L10 12.5L15 7.5" 
            stroke="currentColor" 
            stroke-width="2" 
            stroke-linecap="round" 
            stroke-linejoin="round"
          />
        </svg>
      </div>

      <Transition
        enter-active-class="transition ease-out duration-100"
        enter-from-class="transform opacity-0 scale-95"
        enter-to-class="transform opacity-100 scale-100"
        leave-active-class="transition ease-in duration-75"
        leave-from-class="transform opacity-100 scale-100"
        leave-to-class="transform opacity-0 scale-95"
      >
        <div v-if="isOpen" class="absolute top-full left-0 right-0 bg-white border-2 border-t-0 border-blue-500 rounded-b-lg shadow-lg z-50 max-h-60 overflow-y-auto">
          <template v-for="(option, index) in flatOptions" :key="index">
            <div v-if="option.isGroup" class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-50 border-b border-gray-200">
              {{ option.label }}
            </div>
            <div
              v-else
              class="px-4 py-3 cursor-pointer hover:bg-gray-50 transition-colors duration-150"
              :class="{ 
                'bg-blue-50 text-blue-900': option.value === modelValue,
                'pl-8': option.grouped 
              }"
              @click.stop="selectOption(option)"
            >
              {{ option.label }}
            </div>
          </template>
        </div>
      </Transition>
    </div>
    
    <div v-if="error" class="mt-2 text-sm text-red-600">
      {{ error }}
    </div>
    <div v-if="hint" class="mt-2 text-sm text-gray-500">
      {{ hint }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useId } from '@/src/shared/composables/useId'

// Props
const props = defineProps({
    modelValue: {
        type: [String, Number, Boolean],
        default: null
    },
    id: {
        type: String,
        default: ''
    },
    options: {
        type: Array,
        required: true,
        validator: (options) => {
            return options.every(option => {
                if (!option || typeof option !== 'object') return false
                // Проверяем обычную опцию или группу
                if (option.group) {
                    return 'label' in option && Array.isArray(option.options)
                } else {
                    return 'value' in option && 'label' in option
                }
            })
        }
    },
    placeholder: {
        type: String,
        default: 'Выберите...'
    },
    label: {
        type: String,
        default: ''
    },
    disabled: {
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
    required: {
        type: Boolean,
        default: false
    }
})

// Events
const emit = defineEmits(['update:modelValue', 'change'])

// Generate unique ID if not provided
const selectId = computed(() => props.id || useId('select'))

// State
const isOpen = ref(false)
const selectRef = ref(null)

// Computed
const flatOptions = computed(() => {
    const result = []
    props.options.forEach(option => {
        if (option.group) {
            // Добавляем заголовок группы
            result.push({ label: option.label, isGroup: true })
            // Добавляем опции группы с пометкой, что они в группе
            option.options?.forEach(subOption => {
                result.push({ ...subOption, grouped: true })
            })
        } else {
            // Обычная опция
            result.push(option)
        }
    })
    return result
})

const selectedOption = computed(() => {
    // Ищем во всех опциях, включая вложенные в группы
    for (const option of props.options) {
        if (option.group && option.options) {
            const found = option.options.find(o => o.value === props.modelValue)
            if (found) return found
        } else if (option.value === props.modelValue) {
            return option
        }
    }
    return null
})

// Methods  
const toggleDropdown = () => {
    if (props.disabled) return
    
    // Если открываем - сначала закрываем все другие
    if (!isOpen.value) {
        // Простой способ - кликаем по body чтобы закрыть все
        document.body.click()
        // Небольшая задержка чтобы другие успели закрыться
        nextTick(() => {
            isOpen.value = true
        })
    } else {
        isOpen.value = false
    }
}

const selectOption = (option) => {
    emit('update:modelValue', option.value)
    emit('change', option)
    isOpen.value = false
}

const handleClickOutside = (event) => {
    // Проверяем, был ли клик вне нашего select
    if (selectRef.value && !selectRef.value.contains(event.target)) {
        isOpen.value = false
    }
}

// Lifecycle
onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<style scoped>
/* Кастомные скроллбары для dropdown */
.custom-select-dropdown::-webkit-scrollbar {
  @apply w-1.5;
}

.custom-select-dropdown::-webkit-scrollbar-track {
  @apply bg-gray-100;
}

.custom-select-dropdown::-webkit-scrollbar-thumb {
  @apply bg-gray-400 rounded;
}

.custom-select-dropdown::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-500;
}
</style> 

