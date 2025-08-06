<!-- Р‘Р°Р·РѕРІС‹Р№ СЃРµР»РµРєС‚ РІ СЃС‚РёР»Рµ РђРІРёС‚Рѕ -->
<template>
  <div class="select-container">
    <label v-if="label" class="select-label">{{ label }}</label>
    
    <div 
      class="custom-select-wrapper" 
      :class="{ 'active': isOpen, 'disabled': disabled }"
    >
      <div 
        class="custom-select-trigger"
        @click="toggleDropdown"
        :tabindex="disabled ? -1 : 0"
        @keydown.enter="toggleDropdown"
        @keydown.space="toggleDropdown"
        @keydown.escape="isOpen = false"
      >
        <span class="selected-text">
          {{ selectedOption?.label || placeholder }}
        </span>
        <svg 
          class="select-arrow" 
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
        <div v-if="isOpen" class="custom-select-dropdown">
          <div
            v-for="option in options"
            :key="option.value"
            class="dropdown-option"
            :class="{ 'selected': option.value === modelValue }"
            @click="selectOption(option)"
          >
            {{ option.label }}
          </div>
        </div>
      </Transition>
    </div>
    
    <div v-if="error" class="select-error">{{ error }}</div>
    <div v-if="hint" class="select-hint">{{ hint }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

// Props
const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean],
    default: null
  },
  options: {
    type: Array,
    required: true,
    validator: (options) => {
      return options.every(option => 
        option && typeof option === 'object' && 'value' in option && 'label' in option
      )
    }
  },
  placeholder: {
    type: String,
    default: 'Р’С‹Р±РµСЂРёС‚Рµ...'
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
  }
})

// Events
const emit = defineEmits(['update:modelValue', 'change'])

// State
const isOpen = ref(false)

// Computed
const selectedOption = computed(() => {
  return props.options.find(option => option.value === props.modelValue)
})

// Methods
const toggleDropdown = () => {
  if (props.disabled) return
  isOpen.value = !isOpen.value
}

const selectOption = (option) => {
  emit('update:modelValue', option.value)
  emit('change', option)
  isOpen.value = false
}

const handleClickOutside = (event) => {
  if (!event.target.closest('.custom-select-wrapper')) {
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
.select-container {
  width: 100%;
}

.select-label {
  display: block;
  font-size: 16px;
  font-weight: 400;
  color: #1a1a1a;
  margin-bottom: 8px;
}

.custom-select-wrapper {
  position: relative;
  width: 100%;
}

.custom-select-wrapper.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.custom-select-trigger {
  width: 100%;
  padding: 12px 40px 12px 16px;
  border: 2px solid #e5e5e5;
  border-radius: 8px;
  background: #f5f5f5;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 48px;
  transition: all 0.2s ease;
}

.custom-select-trigger:hover:not(.disabled) {
  border-color: #d0d0d0;
}

.custom-select-trigger:focus {
  outline: none;
  border-color: #2196f3;
  background: #fff;
}

.custom-select-wrapper.active .custom-select-trigger {
  border-color: #2196f3;
  background: #fff;
  border-radius: 8px 8px 0 0;
}

.custom-select-wrapper.disabled .custom-select-trigger {
  cursor: not-allowed;
  background: #f5f5f5;
}

.selected-text {
  font-size: 16px;
  color: #1a1a1a;
  font-weight: 400;
  flex: 1;
  text-align: left;
}

.custom-select-wrapper.disabled .selected-text {
  color: #8c8c8c;
}

.select-arrow {
  color: #8c8c8c;
  transition: transform 0.2s ease;
  flex-shrink: 0;
}

.select-arrow.rotate-180 {
  transform: rotate(180deg);
}

.custom-select-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #fff;
  border: 2px solid #2196f3;
  border-top: none;
  border-radius: 0 0 8px 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  max-height: 300px;
  overflow-y: auto;
}

.dropdown-option {
  padding: 12px 16px;
  cursor: pointer;
  font-size: 16px;
  color: #1a1a1a;
  transition: background-color 0.2s ease;
  border-bottom: 1px solid #f0f0f0;
}

.dropdown-option:last-child {
  border-bottom: none;
}

.dropdown-option:hover {
  background-color: #f8f9fa;
}

.dropdown-option.selected {
  background-color: #e3f2fd;
  color: #2196f3;
  font-weight: 500;
}

.select-error {
  margin-top: 4px;
  font-size: 14px;
  color: #ff4d4f;
  line-height: 1.4;
}

.select-hint {
  margin-top: 4px;
  font-size: 14px;
  color: #8c8c8c;
  line-height: 1.4;
}

/* РЎРєСЂРѕР»Р»Р±Р°СЂ РґР»СЏ dropdown */
.custom-select-dropdown::-webkit-scrollbar {
  width: 6px;
}

.custom-select-dropdown::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.custom-select-dropdown::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 3px;
}

.custom-select-dropdown::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}
</style> 

