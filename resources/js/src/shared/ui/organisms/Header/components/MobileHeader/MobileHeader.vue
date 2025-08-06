<!-- РњРѕР±РёР»СЊРЅР°СЏ РІРµСЂСЃРёСЏ Header - FSD Shared Organism Component -->
<template>
  <div class="lg:hidden relative">
    <!-- РњРѕР±РёР»СЊРЅР°СЏ С€Р°РїРєР° -->
    <header class="bg-white border-b border-gray-200 px-4 py-3 relative z-40">
      <div class="flex items-center justify-between">
        <!-- Р›РѕРіРѕС‚РёРї Рё РіРѕСЂРѕРґ -->
        <div class="flex items-center gap-3">
          <slot name="logo">
            <!-- Fallback logo РµСЃР»Рё СЃР»РѕС‚ РЅРµ Р·Р°РїРѕР»РЅРµРЅ -->
            <div class="text-lg font-bold text-blue-600">SPA</div>
          </slot>
          
          <button 
            v-if="showCitySelector"
            @click="handleCityClick"
            :class="cityButtonClasses"
            :disabled="loading || cityLoading"
            type="button"
            :aria-label="`РўРµРєСѓС‰РёР№ РіРѕСЂРѕРґ: ${displayCity}`"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            </svg>
            <span class="truncate max-w-20">{{ displayCity }}</span>
            <div
              v-if="cityLoading"
              class="w-3 h-3 border border-gray-300 border-t-blue-500 rounded-full animate-spin ml-1"
              aria-hidden="true"
            />
          </button>
        </div>

        <!-- РџСЂР°РІР°СЏ С‡Р°СЃС‚СЊ: РґРµР№СЃС‚РІРёСЏ Рё РјРµРЅСЋ -->
        <div class="flex items-center gap-2">
          <!-- РљРЅРѕРїРєРё РґРµР№СЃС‚РІРёР№ (РёР·Р±СЂР°РЅРЅРѕРµ, СЃСЂР°РІРЅРµРЅРёРµ) -->
          <div v-if="showActionButtons" class="flex items-center gap-1">
            <slot name="favorites">
              <!-- Fallback РєРЅРѕРїРєР° РёР·Р±СЂР°РЅРЅРѕРіРѕ -->
              <button
                type="button"
                class="p-2 text-gray-600 hover:text-red-500 transition-colors rounded-lg"
                :aria-label="`РР·Р±СЂР°РЅРЅРѕРµ${favoritesCount > 0 ? ` (${favoritesCount})` : ''}`"
                @click="$emit('favorites-click')"
              >
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                  <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                </svg>
                <span v-if="favoritesCount > 0" class="sr-only">{{ favoritesCount }}</span>
              </button>
            </slot>
            
            <slot name="compare">
              <!-- Fallback РєРЅРѕРїРєР° СЃСЂР°РІРЅРµРЅРёСЏ -->
              <button
                type="button"
                class="p-2 text-gray-600 hover:text-blue-500 transition-colors rounded-lg"
                :aria-label="`РЎСЂР°РІРЅРµРЅРёРµ${compareCount > 0 ? ` (${compareCount})` : ''}`"
                @click="$emit('compare-click')"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span v-if="compareCount > 0" class="sr-only">{{ compareCount }}</span>
              </button>
            </slot>
          </div>

          <!-- User menu РёР»Рё auth buttons -->
          <div class="flex items-center">
            <slot name="user-menu">
              <!-- Fallback auth РєРЅРѕРїРєРё -->
              <button
                v-if="!isAuthenticated"
                @click="$emit('show-login')"
                class="text-sm text-blue-600 font-medium px-2 py-1"
                type="button"
              >
                Р’РѕР№С‚Рё
              </button>
            </slot>
          </div>
          
          <!-- РњРµРЅСЋ Р±СѓСЂРіРµСЂ -->
          <button 
            @click="toggleMenu"
            :class="burgerButtonClasses"
            type="button"
            :aria-expanded="showMenu"
            aria-label="РћС‚РєСЂС‹С‚СЊ РјРµРЅСЋ РЅР°РІРёРіР°С†РёРё"
            :disabled="loading"
          >
            <svg class="w-6 h-6 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path 
                v-if="!showMenu" 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M4 6h16M4 12h16M4 18h16"
              />
              <path 
                v-else 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>

      <!-- РџРѕРёСЃРє -->
      <div v-if="showSearch" class="mt-3 flex gap-2">
        <button 
          v-if="showCatalogButton"
          @click="handleCatalogToggle"
          :class="catalogButtonClasses"
          type="button"
          :disabled="loading"
          :aria-expanded="catalogOpen"
          aria-label="РљР°С‚Р°Р»РѕРі СѓСЃР»СѓРі"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
          <span>РљР°С‚Р°Р»РѕРі</span>
        </button>
        
        <div class="flex-1 relative">
          <slot name="search">
            <!-- Fallback РїРѕРёСЃРє -->
            <input 
              v-model="localSearchQuery"
              type="search"
              :placeholder="searchPlaceholder"
              :class="searchInputClasses"
              @keyup.enter="handleSearch"
              @input="handleSearchInput"
              :disabled="loading"
              autocomplete="off"
              role="searchbox"
              :aria-label="searchPlaceholder"
            />
            <button 
              @click="handleSearch"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-500 transition-colors p-1 rounded"
              type="button"
              :disabled="loading || !localSearchQuery.trim()"
              aria-label="РџРѕРёСЃРє"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
              </svg>
            </button>
          </slot>
        </div>
      </div>

      <!-- Loading overlay -->
      <div
        v-if="loading"
        class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10"
        aria-hidden="true"
      >
        <div class="w-6 h-6 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
      </div>
    </header>

    <!-- Р’С‹РїР°РґР°СЋС‰РµРµ РјРµРЅСЋ -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0 -translate-y-2"
      enter-to-class="opacity-100 translate-y-0"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100 translate-y-0"
      leave-to-class="opacity-0 -translate-y-2"
    >
      <div 
        v-if="showMenu" 
        class="absolute top-full left-0 right-0 bg-white shadow-lg border-t z-50"
        role="menu"
        :aria-hidden="!showMenu"
      >
        <div class="p-4 space-y-4">
          <!-- Р”Р»СЏ Р°РІС‚РѕСЂРёР·РѕРІР°РЅРЅС‹С… -->
          <template v-if="isAuthenticated">
            <Link 
              href="/additem"
              class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              @click="closeMenu"
              role="menuitem"
            >
              {{ postAdText }}
            </Link>
            
            <!-- Р”РѕРїРѕР»РЅРёС‚РµР»СЊРЅС‹Рµ РґРµР№СЃС‚РІРёСЏ Р°РІС‚РѕСЂРёР·РѕРІР°РЅРЅРѕРіРѕ -->
            <div class="grid grid-cols-2 gap-2">
              <Link 
                href="/profile"
                class="flex items-center justify-center gap-2 p-3 border rounded-lg hover:bg-gray-50 transition-colors text-sm"
                @click="closeMenu"
                role="menuitem"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                РџСЂРѕС„РёР»СЊ
              </Link>
              <Link 
                href="/orders"
                class="flex items-center justify-center gap-2 p-3 border rounded-lg hover:bg-gray-50 transition-colors text-sm"
                @click="closeMenu"
                role="menuitem"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Р—Р°РєР°Р·С‹
              </Link>
            </div>
          </template>
          
          <!-- Р”Р»СЏ РЅРµР°РІС‚РѕСЂРёР·РѕРІР°РЅРЅС‹С… -->
          <template v-else>
            <div class="space-y-2">
              <button 
                @click="handleLoginClick"
                class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                type="button"
                role="menuitem"
              >
                {{ loginText }}
              </button>
              <button 
                @click="handleRegisterClick"
                class="block w-full border border-blue-600 text-blue-600 text-center py-3 rounded-lg font-medium hover:bg-blue-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                type="button"
                role="menuitem"
              >
                {{ registerText }}
              </button>
            </div>
          </template>

          <!-- РћР±С‰Р°СЏ РЅР°РІРёРіР°С†РёСЏ -->
          <nav class="space-y-2" role="navigation">
            <div class="border-t pt-3">
              <div class="grid grid-cols-2 gap-2">
                <Link 
                  href="/favorites" 
                  class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors"
                  @click="closeMenu"
                  role="menuitem"
                >
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                      <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"/>
                    </svg>
                    <span class="text-sm">РР·Р±СЂР°РЅРЅРѕРµ</span>
                  </div>
                  <span v-if="favoritesCount > 0" class="text-xs bg-red-100 text-red-600 px-2 py-1 rounded-full">
                    {{ favoritesCount }}
                  </span>
                </Link>
                
                <Link 
                  href="/compare" 
                  class="flex items-center justify-between py-2 px-3 rounded-lg hover:bg-gray-50 transition-colors"
                  @click="closeMenu"
                  role="menuitem"
                >
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span class="text-sm">РЎСЂР°РІРЅРµРЅРёРµ</span>
                  </div>
                  <span v-if="compareCount > 0" class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-full">
                    {{ compareCount }}
                  </span>
                </Link>
              </div>
              
              <!-- Quick Links -->
              <div v-if="showQuickLinks && quickLinks.length > 0" class="mt-3 space-y-1">
                <div class="text-xs text-gray-500 font-medium px-3 mb-2">РЈСЃР»СѓРіРё</div>
                <Link
                  v-for="link in quickLinks"
                  :key="link.href"
                  :href="link.href"
                  class="block py-2 px-3 text-sm text-gray-700 hover:bg-gray-50 rounded-lg transition-colors"
                  @click="closeMenu"
                  role="menuitem"
                >
                  {{ link.text }}
                </Link>
              </div>
            </div>
          </nav>
        </div>
      </div>
    </Transition>

    <!-- Overlay РґР»СЏ Р·Р°РєСЂС‹С‚РёСЏ РјРµРЅСЋ -->
    <div
      v-if="showMenu"
      class="fixed inset-0 bg-black bg-opacity-25 z-40"
      @click="closeMenu"
      aria-hidden="true"
    ></div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Link } from '@inertiajs/vue3'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
export interface QuickLink {
  href: string
  text: string
  icon?: any
}

interface Props {
  currentCity?: string
  isAuthenticated?: boolean
  showCitySelector?: boolean
  showActionButtons?: boolean
  showSearch?: boolean
  showCatalogButton?: boolean
  showQuickLinks?: boolean
  loading?: boolean
  cityLoading?: boolean
  catalogOpen?: boolean
  searchPlaceholder?: string
  postAdText?: string
  loginText?: string
  registerText?: string
  favoritesCount?: number
  compareCount?: number
  quickLinks?: QuickLink[]
  searchQuery?: string
}

const props = withDefaults(defineProps<Props>(), {
  currentCity: 'Р’С‹Р±РµСЂРёС‚Рµ РіРѕСЂРѕРґ',
  isAuthenticated: false,
  showCitySelector: true,
  showActionButtons: true,
  showSearch: true,
  showCatalogButton: true,
  showQuickLinks: true,
  loading: false,
  cityLoading: false,
  catalogOpen: false,
  searchPlaceholder: 'РќР°Р№С‚Рё РјР°СЃС‚РµСЂР° РёР»Рё СѓСЃР»СѓРіСѓ',
  postAdText: 'Р Р°Р·РјРµСЃС‚РёС‚СЊ РѕР±СЉСЏРІР»РµРЅРёРµ',
  loginText: 'Р’РѕР№С‚Рё',
  registerText: 'Р РµРіРёСЃС‚СЂР°С†РёСЏ',
  favoritesCount: 0,
  compareCount: 0,
  quickLinks: () => [
    { href: '/services/massage', text: 'РњР°СЃСЃР°Р¶' },
    { href: '/services/spa', text: 'РЎРџРђ' },
    { href: '/services/cosmetology', text: 'РљРѕСЃРјРµС‚РѕР»РѕРіРёСЏ' },
    { href: '/services/at-home', text: 'РќР° РґРѕРјСѓ' }
  ],
  searchQuery: ''
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'city-click': []
  'catalog-toggle': []
  'search': [query: string]
  'search-input': [query: string]
  'show-login': []
  'show-register': []
  'favorites-click': []
  'compare-click': []
  'menu-toggle': [open: boolean]
}>()

// Local state
const showMenu = ref(false)
const localSearchQuery = ref(props.searchQuery)

// Computed
const displayCity = computed(() => 
  props.currentCity || 'Р’С‹Р±РµСЂРёС‚Рµ РіРѕСЂРѕРґ'
)

const cityButtonClasses = computed(() => [
  'text-sm text-gray-600 flex items-center gap-1 transition-colors hover:text-blue-600',
  'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md p-1',
  {
    'opacity-50 cursor-not-allowed': props.loading || props.cityLoading
  }
])

const burgerButtonClasses = computed(() => [
  'p-2 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  {
    'opacity-50 cursor-not-allowed': props.loading,
    'bg-gray-100': showMenu.value
  }
])

const catalogButtonClasses = computed(() => [
  'bg-blue-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors',
  'hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  {
    'opacity-50 cursor-not-allowed': props.loading,
    'bg-blue-700': props.catalogOpen
  }
])

const searchInputClasses = computed(() => [
  'w-full px-4 py-2 pr-10 border rounded-lg transition-colors',
  'focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
  'placeholder-gray-400 text-sm',
  {
    'opacity-50 cursor-not-allowed bg-gray-50': props.loading,
    'border-gray-300': !props.loading
  }
])

// Watchers
watch(() => props.searchQuery, (newVal) => {
  localSearchQuery.value = newVal || ''
})

// Methods
const toggleMenu = (): void => {
  if (props.loading) return
  showMenu.value = !showMenu.value
  emit('menu-toggle', showMenu.value)
}

const closeMenu = (): void => {
  showMenu.value = false
  emit('menu-toggle', false)
}

const handleCityClick = (): void => {
  if (props.loading || props.cityLoading) return
  emit('city-click')
}

const handleCatalogToggle = (): void => {
  if (props.loading) return
  emit('catalog-toggle')
}

const handleSearch = (): void => {
  if (props.loading || !localSearchQuery.value.trim()) return
  emit('search', localSearchQuery.value.trim())
}

const handleSearchInput = (): void => {
  emit('search-input', localSearchQuery.value)
}

const handleLoginClick = (): void => {
  closeMenu()
  emit('show-login')
}

const handleRegisterClick = (): void => {
  closeMenu()
  emit('show-register')
}
</script>

<style scoped>
/* РђРЅРёРјР°С†РёРё РґР»СЏ smooth transitions */
.transition-transform {
  transition-property: transform;
}

/* РЎРєСЂС‹С‚РёРµ СЃРєСЂРѕР»Р»Р±Р°СЂР° РґР»СЏ РјРѕР±РёР»СЊРЅРѕРіРѕ РјРµРЅСЋ */
.space-y-4 {
  max-height: calc(100vh - 200px);
  overflow-y: auto;
}

/* РЈР»СѓС‡С€РµРЅРЅС‹Рµ focus states */
.focus\:ring-2:focus {
  box-shadow: 0 0 0 2px theme('colors.blue.500');
}

/* Loading spinner Р°РЅРёРјР°С†РёСЏ */
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Hover СЌС„С„РµРєС‚С‹ */
@media (hover: hover) {
  .hover\:bg-gray-50:hover {
    background-color: theme('colors.gray.50');
  }
  
  .hover\:bg-gray-100:hover {
    background-color: theme('colors.gray.100');
  }
  
  .hover\:bg-blue-700:hover {
    background-color: theme('colors.blue.700');
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors.gray.900');
  }
  
  .text-gray-600 {
    color: theme('colors.gray.300');
  }
  
  .text-gray-700 {
    color: theme('colors.gray.200');
  }
  
  .border-gray-200 {
    border-color: theme('colors.gray.700');
  }
  
  .hover\:bg-gray-50:hover {
    background-color: theme('colors.gray.800');
  }
  
  .hover\:bg-gray-100:hover {
    background-color: theme('colors.gray.700');
  }
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .text-blue-600 {
    color: #0066cc;
  }
  
  .bg-blue-600 {
    background-color: #0066cc;
  }
  
  .border-blue-600 {
    border-color: #0066cc;
  }
}

/* Mobile optimizations */
@media (max-width: 374px) {
  .px-4 {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .gap-3 {
    gap: 0.5rem;
  }
  
  .text-sm {
    font-size: 0.75rem;
  }
  
  .max-w-20 {
    max-width: 3rem;
  }
}

/* РЈР»СѓС‡С€РµРЅРёСЏ РґР»СЏ Р±РѕР»СЊС€РёС… РјРѕР±РёР»СЊРЅС‹С… СЌРєСЂР°РЅРѕРІ */
@media (min-width: 375px) and (max-width: 640px) {
  .grid-cols-2 {
    gap: 0.75rem;
  }
}
</style>

