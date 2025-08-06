<!-- РњРѕРґР°Р»СЊРЅРѕРµ РѕРєРЅРѕ РІС‹Р±РѕСЂР° РіРѕСЂРѕРґР° - FSD Feature -->
<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="isOpen" 
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
        :aria-labelledby="modalTitleId"
      >
        <!-- РћРІРµСЂР»РµР№ -->
        <div 
          class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
          @click="handleClose"
          aria-hidden="true"
        ></div>
        
        <!-- РљРѕРЅС‚РµР№РЅРµСЂ РјРѕРґР°Р»СЊРЅРѕРіРѕ РѕРєРЅР° -->
        <div class="relative min-h-screen flex items-center justify-center p-4">
          <div 
            :class="modalClasses"
            @click.stop
          >
            <!-- Р—Р°РіРѕР»РѕРІРѕРє РјРѕРґР°Р»СЊРЅРѕРіРѕ РѕРєРЅР° -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
              <div>
                <h3 :id="modalTitleId" class="text-lg font-semibold text-gray-900">
                  Р’С‹Р±РµСЂРёС‚Рµ РіРѕСЂРѕРґ
                </h3>
                <p class="text-sm text-gray-500 mt-1">
                  {{ currentCity?.name ? `РўРµРєСѓС‰РёР№: ${currentCity.name}` : 'Р’С‹Р±РµСЂРёС‚Рµ РІР°С€ РіРѕСЂРѕРґ РґР»СЏ РїРµСЂСЃРѕРЅР°Р»РёР·Р°С†РёРё' }}
                </p>
              </div>
              
              <button 
                @click="handleClose"
                class="text-gray-400 hover:text-gray-600 transition-colors p-1 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                :aria-label="closeButtonAriaLabel"
              >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- РџРѕРёСЃРє -->
            <div class="p-6 border-b border-gray-200">
              <div class="relative">
                <input 
                  ref="searchInput"
                  v-model="searchQuery"
                  type="text"
                  :placeholder="searchPlaceholder"
                  :class="searchInputClasses"
                  :aria-label="searchAriaLabel"
                  @input="handleSearchInput"
                  @keydown="handleSearchKeydown"
                >
                
                <!-- РРєРѕРЅРєР° РїРѕРёСЃРєР° -->
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                  </svg>
                </div>

                <!-- РљРЅРѕРїРєР° РѕС‡РёСЃС‚РєРё -->
                <button
                  v-if="searchQuery"
                  @click="clearSearch"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                  aria-label="РћС‡РёСЃС‚РёС‚СЊ РїРѕРёСЃРє"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- РљРѕРЅС‚РµРЅС‚ -->
            <div :class="contentClasses">
              <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
              <div v-if="isLoading" class="p-6">
                <div class="animate-pulse space-y-4">
                  <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                  <div class="grid grid-cols-3 gap-2">
                    <div v-for="i in 9" :key="i" class="h-10 bg-gray-200 rounded"></div>
                  </div>
                </div>
              </div>

              <!-- Error СЃРѕСЃС‚РѕСЏРЅРёРµ -->
              <div v-else-if="error" class="p-6 text-center">
                <div class="text-red-600 mb-4">
                  <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                  <p class="font-medium">РћС€РёР±РєР° Р·Р°РіСЂСѓР·РєРё РіРѕСЂРѕРґРѕРІ</p>
                </div>
                <p class="text-gray-600 mb-4">{{ error }}</p>
                <button
                  @click="retryLoadCities"
                  class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
                >
                  РџРѕРїСЂРѕР±РѕРІР°С‚СЊ РµС‰Рµ СЂР°Р·
                </button>
              </div>

              <!-- РќРѕСЂРјР°Р»СЊРЅРѕРµ СЃРѕСЃС‚РѕСЏРЅРёРµ -->
              <div v-else>
                <!-- РџРѕРїСѓР»СЏСЂРЅС‹Рµ РіРѕСЂРѕРґР° (РµСЃР»Рё РЅРµС‚ РїРѕРёСЃРєР°) -->
                <div v-if="!searchQuery && popularCities.length > 0" class="p-6 border-b border-gray-100">
                  <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    РџРѕРїСѓР»СЏСЂРЅС‹Рµ РіРѕСЂРѕРґР°
                  </h4>
                  <div :class="citiesGridClasses">
                    <CityButton
                      v-for="city in popularCities"
                      :key="`popular-${city.id}`"
                      :city="city"
                      :is-selected="city.id === currentCity?.id"
                      @click="selectCity(city)"
                    />
                  </div>
                </div>

                <!-- Р РµР·СѓР»СЊС‚Р°С‚С‹ РїРѕРёСЃРєР° / Р’СЃРµ РіРѕСЂРѕРґР° -->
                <div class="p-6">
                  <h4 class="text-sm font-semibold text-gray-700 mb-3">
                    {{ searchQuery ? `Р РµР·СѓР»СЊС‚Р°С‚С‹ РїРѕРёСЃРєР° (${filteredCities.length})` : 'Р’СЃРµ РіРѕСЂРѕРґР°' }}
                  </h4>
                  
                  <!-- РЎРїРёСЃРѕРє РіРѕСЂРѕРґРѕРІ -->
                  <div v-if="hasResults" :class="resultsContainerClasses">
                    <!-- Р“СЂСѓРїРїРёСЂРѕРІРєР° РїРѕ СЂРµРіРёРѕРЅР°Рј -->
                    <template v-if="showGrouped && !searchQuery">
                      <div 
                        v-for="region in groupedCities" 
                        :key="region.name"
                        class="mb-6"
                      >
                        <h5 class="text-xs font-medium text-gray-500 mb-2 sticky top-0 bg-white py-1">
                          {{ region.name }}
                        </h5>
                        <div :class="citiesGridClasses">
                          <CityButton
                            v-for="city in region.cities"
                            :key="`region-${city.id}`"
                            :city="city"
                            :is-selected="city.id === currentCity?.id"
                            :show-region="false"
                            @click="selectCity(city)"
                          />
                        </div>
                      </div>
                    </template>

                    <!-- РџСЂРѕСЃС‚РѕР№ СЃРїРёСЃРѕРє -->
                    <template v-else>
                      <div :class="citiesGridClasses">
                        <CityButton
                          v-for="city in displayedCities"
                          :key="`simple-${city.id}`"
                          :city="city"
                          :is-selected="city.id === currentCity?.id"
                          :show-region="!!searchQuery"
                          :highlight-query="searchQuery"
                          @click="selectCity(city)"
                        />
                      </div>
                    </template>
                  </div>

                  <!-- РќРµС‚ СЂРµР·СѓР»СЊС‚Р°С‚РѕРІ -->
                  <div v-else class="text-center py-8">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-gray-500 mb-2">Р“РѕСЂРѕРґР° РЅРµ РЅР°Р№РґРµРЅС‹</p>
                    <p class="text-sm text-gray-400 mb-4">
                      РџРѕРїСЂРѕР±СѓР№С‚Рµ РёР·РјРµРЅРёС‚СЊ РїРѕРёСЃРєРѕРІС‹Р№ Р·Р°РїСЂРѕСЃ
                    </p>
                    <button
                      @click="clearSearch"
                      class="text-blue-600 hover:text-blue-700 text-sm font-medium"
                    >
                      РџРѕРєР°Р·Р°С‚СЊ РІСЃРµ РіРѕСЂРѕРґР°
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useCityStore } from '../../model/city.store'
import CityButton from '../CityButton/CityButton.vue'
import type { City } from '../../model/city.store'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface Props {
  maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl'
  showGrouped?: boolean
  searchPlaceholder?: string
  closeOnSelect?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  maxWidth: '2xl',
  showGrouped: true,
  searchPlaceholder: 'РќР°С‡РЅРёС‚Рµ РІРІРѕРґРёС‚СЊ РЅР°Р·РІР°РЅРёРµ РіРѕСЂРѕРґР°...',
  closeOnSelect: true
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'city-selected': [city: City]
  'closed': []
  'opened': []
}>()

// Store
const cityStore = useCityStore()

// Refs
const searchInput = ref<HTMLInputElement>()
const modalTitleId = ref(`city-modal-title-${Date.now()}`)

// Computed
const isOpen = computed(() => cityStore.modalOpen)
const currentCity = computed(() => cityStore.currentCity)
const popularCities = computed(() => cityStore.popularCities)
const filteredCities = computed(() => cityStore.filteredCities)
const groupedCities = computed(() => cityStore.groupedCities)
const hasResults = computed(() => cityStore.hasResults)
const isLoading = computed(() => cityStore.isLoading)
const error = computed(() => cityStore.error)
const searchQuery = computed({
  get: () => cityStore.searchQuery,
  set: (value: string) => cityStore.updateSearchQuery(value)
})

const displayedCities = computed(() => {
  // РћРіСЂР°РЅРёС‡РёРІР°РµРј РєРѕР»РёС‡РµСЃС‚РІРѕ РґР»СЏ РїСЂРѕРёР·РІРѕРґРёС‚РµР»СЊРЅРѕСЃС‚Рё
  return filteredCities.value.slice(0, 100)
})

const modalClasses = computed(() => [
  'relative bg-white rounded-lg shadow-xl w-full',
  {
    'max-w-sm': props.maxWidth === 'sm',
    'max-w-md': props.maxWidth === 'md',
    'max-w-lg': props.maxWidth === 'lg',
    'max-w-xl': props.maxWidth === 'xl',
    'max-w-2xl': props.maxWidth === '2xl'
  }
])

const searchInputClasses = computed(() => [
  'w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg text-sm',
  'focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors',
  'placeholder-gray-500'
])

const contentClasses = computed(() => [
  'max-h-96 overflow-y-auto'
])

const resultsContainerClasses = computed(() => [
  'max-h-80 overflow-y-auto'
])

const citiesGridClasses = computed(() => [
  'grid gap-2',
  {
    'grid-cols-2 sm:grid-cols-3': props.maxWidth === 'sm' || props.maxWidth === 'md',
    'grid-cols-3 sm:grid-cols-4': props.maxWidth === 'lg' || props.maxWidth === 'xl' || props.maxWidth === '2xl'
  }
])

const closeButtonAriaLabel = computed(() => 'Р—Р°РєСЂС‹С‚СЊ РѕРєРЅРѕ РІС‹Р±РѕСЂР° РіРѕСЂРѕРґР°')
const searchAriaLabel = computed(() => 'РџРѕРёСЃРє РіРѕСЂРѕРґР° РїРѕ РЅР°Р·РІР°РЅРёСЋ')

// Methods
const handleClose = (): void => {
  cityStore.closeModal()
  emit('closed')
}

const selectCity = (city: City): void => {
  cityStore.selectCity(city)
  emit('city-selected', city)
  
  if (props.closeOnSelect) {
    handleClose()
  }
}

const handleSearchInput = (): void => {
  // Р”РµР±Р°СѓРЅСЃ СѓР¶Рµ СЂРµР°Р»РёР·РѕРІР°РЅ РІ store С‡РµСЂРµР· computed
}

const handleSearchKeydown = (event: KeyboardEvent): void => {
  if (event.key === 'Escape') {
    if (searchQuery.value) {
      clearSearch()
    } else {
      handleClose()
    }
  } else if (event.key === 'Enter') {
    // Р’С‹Р±СЂР°С‚СЊ РїРµСЂРІС‹Р№ СЂРµР·СѓР»СЊС‚Р°С‚
    if (displayedCities.value.length > 0) {
      if (displayedCities.value[0]) {
        selectCity(displayedCities.value[0])
      }
    }
  }
}

const clearSearch = (): void => {
  cityStore.clearSearch()
  searchInput.value?.focus()
}

const retryLoadCities = async (): Promise<void> => {
  await cityStore.loadCities()
}

// Р—Р°РєСЂС‹С‚РёРµ РїСЂРё РЅР°Р¶Р°С‚РёРё Escape
const handleGlobalKeydown = (event: KeyboardEvent): void => {
  if (event.key === 'Escape' && isOpen.value) {
    handleClose()
  }
}

// Р¤РѕРєСѓСЃ РЅР° РїРѕР»Рµ РїРѕРёСЃРєР° РїСЂРё РѕС‚РєСЂС‹С‚РёРё
watch(isOpen, async (newValue) => {
  if (newValue) {
    emit('opened')
    await nextTick()
    searchInput.value?.focus()
  }
})

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleGlobalKeydown)
  
  // Р—Р°РіСЂСѓР·РёС‚СЊ РіРѕСЂРѕРґР° РµСЃР»Рё РѕРЅРё РЅРµ Р·Р°РіСЂСѓР¶РµРЅС‹
  if (cityStore.availableCities.length === 0 && !cityStore.isLoading) {
    cityStore.loadCities()
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleGlobalKeydown)
})
</script>

<style scoped>
/* РљР°СЃС‚РѕРјРЅС‹Рµ СЃС‚РёР»Рё РґР»СЏ СЃРєСЂРѕР»Р»Р° */
.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Sticky Р·Р°РіРѕР»РѕРІРєРё СЂРµРіРёРѕРЅРѕРІ */
.sticky {
  position: sticky;
  top: 0;
  z-index: 1;
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    transition: none !important;
    animation: none !important;
  }
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .grid-cols-3 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors.gray.800');
  }
  
  .text-gray-900 {
    color: theme('colors.gray.100');
  }
  
  .border-gray-200 {
    border-color: theme('colors.gray.600');
  }
}
</style>

