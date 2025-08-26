<!-- РЎРµР»РµРєС‚РѕСЂ РіРѕСЂРѕРґР° - FSD Feature -->
<template>
  <div class="relative">
    <button 
      :disabled="disabled || isLoading"
      :class="buttonClasses"
      :aria-label="buttonAriaLabel"
      :aria-expanded="dropdownOpen"
      :aria-haspopup="showDropdown"
      @click="handleClick"
    >
      <!-- РРєРѕРЅРєР° РјРµСЃС‚РѕРїРѕР»РѕР¶РµРЅРёСЏ -->
      <svg 
        class="w-4 h-4 flex-shrink-0" 
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path 
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
        />
        <path 
          stroke-linecap="round" 
          stroke-linejoin="round" 
          stroke-width="2" 
          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
        />
      </svg>
      
      <!-- РќР°Р·РІР°РЅРёРµ РіРѕСЂРѕРґР° -->
      <span :class="cityNameClasses">
        {{ displayCityName }}
      </span>
      
      <!-- РРЅРґРёРєР°С‚РѕСЂ Р·Р°РіСЂСѓР·РєРё -->
      <div 
        v-if="isLoading"
        class="w-3 h-3 border border-gray-500 border-t-blue-600 rounded-full animate-spin ml-1"
        aria-hidden="true"
      />
      
      <!-- РЎС‚СЂРµР»РєР° (РµСЃР»Рё РµСЃС‚СЊ dropdown) -->
      <svg 
        v-else-if="showDropdown"
        class="w-3 h-3 ml-1 transition-transform duration-200"
        :class="{ 'rotate-180': dropdownOpen }"
        fill="none" 
        stroke="currentColor" 
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>

    <!-- Р’С‹РїР°РґР°СЋС‰РёР№ СЃРїРёСЃРѕРє (РјРёРЅРё-РІРµСЂСЃРёСЏ) -->
    <Transition
      v-if="showDropdown"
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="dropdownOpen && !modalMode"
        :class="dropdownClasses"
        role="listbox"
        :aria-label="dropdownAriaLabel"
      >
        <!-- РџРѕРїСѓР»СЏСЂРЅС‹Рµ РіРѕСЂРѕРґР° -->
        <div class="p-2">
          <div class="text-xs font-medium text-gray-500 mb-2">
            РџРѕРїСѓР»СЏСЂРЅС‹Рµ
          </div>
          <button
            v-for="city in popularCities.slice(0, maxDropdownItems)"
            :key="city.id"
            :class="dropdownItemClasses"
            :aria-selected="city.id === currentCity?.id"
            role="option"
            @click="selectCity(city)"
          >
            <span class="truncate">{{ city.name }}</span>
            <svg
              v-if="city.id === currentCity?.id"
              class="w-4 h-4 text-blue-600 flex-shrink-0"
              fill="currentColor"
              viewBox="0 0 20 20"
              aria-hidden="true"
            >
              <path
                fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>

        <!-- РљРЅРѕРїРєР° "Р’СЃРµ РіРѕСЂРѕРґР°" -->
        <div class="border-t border-gray-500 p-2">
          <button
            class="w-full px-3 py-2 text-left text-sm text-blue-600 hover:bg-blue-50 rounded transition-colors flex items-center"
            @click="openModal"
          >
            <svg
              class="w-4 h-4 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
            Р’СЃРµ РіРѕСЂРѕРґР°
          </button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useCityStore } from '../../model/city.store'
import type { City } from '../../model/city.store'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface Props {
  variant?: 'default' | 'compact' | 'minimal'
  disabled?: boolean
  modalMode?: boolean
  showDropdown?: boolean
  maxDropdownItems?: number
  customClass?: string
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'default',
    disabled: false,
    modalMode: false,
    showDropdown: true,
    maxDropdownItems: 6,
    customClass: ''
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'city-selected': [city: City]
  'modal-requested': []
  'dropdown-opened': []
  'dropdown-closed': []
}>()

// Store
const cityStore = useCityStore()

// Local state
const dropdownRef = ref<HTMLElement>()
const dropdownOpen = ref(false)

// Computed
const currentCity = computed(() => cityStore.currentCity)
const popularCities = computed(() => cityStore.popularCities)
const isLoading = computed(() => cityStore.isLoading)

const displayCityName = computed(() => {
    if (isLoading.value) return 'Р—Р°РіСЂСѓР·РєР°...'
    return cityStore.currentCityName
})

const buttonClasses = computed(() => [
    'inline-flex items-center gap-1.5 font-medium transition-colors duration-200',
    'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md',
    {
    // Default variant
        'text-gray-500 hover:text-blue-600 px-2 py-1': props.variant === 'default',
    
        // Compact variant
        'text-gray-500 hover:text-blue-600 px-1.5 py-0.5 text-sm': props.variant === 'compact',
    
        // Minimal variant
        'text-gray-500 hover:text-gray-500 py-0.5': props.variant === 'minimal',
    
        // Disabled state
        'opacity-50 cursor-not-allowed': props.disabled || isLoading.value,
    
        // Active state
        'text-blue-600': dropdownOpen.value && !props.disabled
    },
    props.customClass
])

const cityNameClasses = computed(() => [
    'truncate',
    {
        'max-w-32': props.variant === 'default',
        'max-w-24': props.variant === 'compact',
        'max-w-20': props.variant === 'minimal'
    }
])

const dropdownClasses = computed(() => [
    'absolute top-full left-0 mt-1 bg-white rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 z-50',
    'min-w-48 max-w-64'
])

const dropdownItemClasses = computed(() => [
    'w-full px-3 py-2 text-left text-sm text-gray-500 hover:bg-gray-500 transition-colors',
    'flex items-center justify-between rounded'
])

const buttonAriaLabel = computed(() => {
    if (props.modalMode) {
        return `Р’С‹Р±СЂР°С‚СЊ РіРѕСЂРѕРґ, С‚РµРєСѓС‰РёР№: ${displayCityName.value}`
    }
    return `Р“РѕСЂРѕРґ ${displayCityName.value}, ${dropdownOpen.value ? 'Р·Р°РєСЂС‹С‚СЊ' : 'РѕС‚РєСЂС‹С‚СЊ'} СЃРїРёСЃРѕРє`
})

const dropdownAriaLabel = computed(() => 
    'РЎРїРёСЃРѕРє РїРѕРїСѓР»СЏСЂРЅС‹С… РіРѕСЂРѕРґРѕРІ РґР»СЏ Р±С‹СЃС‚СЂРѕРіРѕ РІС‹Р±РѕСЂР°'
)

// Methods
const handleClick = (): void => {
    if (props.disabled || isLoading.value) return
  
    if (props.modalMode) {
        openModal()
    } else if (props.showDropdown) {
        toggleDropdown()
    }
}

const toggleDropdown = (): void => {
    if (dropdownOpen.value) {
        closeDropdown()
    } else {
        openDropdown()
    }
}

const openDropdown = (): void => {
    dropdownOpen.value = true
    emit('dropdown-opened')
}

const closeDropdown = (): void => {
    dropdownOpen.value = false
    emit('dropdown-closed')
}

const selectCity = (city: City): void => {
    cityStore.selectCity(city)
    closeDropdown()
    emit('city-selected', city)
}

const openModal = (): void => {
    closeDropdown()
    cityStore.openModal()
    emit('modal-requested')
}

// Р—Р°РєСЂС‹С‚РёРµ РїСЂРё РєР»РёРєРµ РІРЅРµ РєРѕРјРїРѕРЅРµРЅС‚Р°
const handleClickOutside = (event: MouseEvent): void => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        closeDropdown()
    }
}

// Р—Р°РєСЂС‹С‚РёРµ РїСЂРё РЅР°Р¶Р°С‚РёРё Escape
const handleEscapeKey = (event: KeyboardEvent): void => {
    if (event.key === 'Escape' && dropdownOpen.value) {
        closeDropdown()
    }
}

// Lifecycle
onMounted(() => {
    document.addEventListener('click', handleClickOutside)
    document.addEventListener('keydown', handleEscapeKey)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    document.removeEventListener('keydown', handleEscapeKey)
})

// Expose РґР»СЏ template ref
defineExpose({
    openModal,
    closeDropdown,
    selectCity
})
</script>

<style scoped>
/* РђРЅРёРјР°С†РёСЏ РґР»СЏ РёРЅРґРёРєР°С‚РѕСЂР° Р·Р°РіСЂСѓР·РєРё */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Hover СЌС„С„РµРєС‚С‹ */
.city-hover-effect {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

.city-hover-effect:hover {
  transform: translateY(-1px);
}

/* Focus styles РґР»СЏ СѓР»СѓС‡С€РµРЅРЅРѕР№ РґРѕСЃС‚СѓРїРЅРѕСЃС‚Рё */
.focus-ring {
  transition: box-shadow 0.15s ease-in-out;
}

.focus-ring:focus-visible {
  outline: 2px solid theme('colors.blue.500');
  outline-offset: 2px;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .max-w-32 {
    max-width: 5rem;
  }
  
  .max-w-24 {
    max-width: 4rem;
  }
  
  .max-w-20 {
    max-width: 3rem;
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors.gray.800');
  }
  
  .text-gray-500 {
    color: theme('colors.gray.100');
  }
  
  .text-gray-500 {
    color: theme('colors.gray.200');
  }
  
  .ring-black {
    --tw-ring-color: theme('colors.gray.600');
  }
}
</style>

