<!-- Р’С‹РїР°РґР°СЋС‰РёР№ РєР°С‚Р°Р»РѕРі СѓСЃР»СѓРі - FSD Feature -->
<template>
  <div 
    class="bg-white shadow-lg border-t relative z-50"
    role="menu"
    :aria-hidden="!visible"
    @keydown.escape="handleClose"
  >
    <!-- Loading СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <div 
      v-if="isLoading && !categories.length" 
      class="container mx-auto px-4 py-8"
      aria-live="polite"
      aria-label="Р—Р°РіСЂСѓР·РєР° РєР°С‚Р°Р»РѕРіР°"
    >
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <div 
          v-for="i in 4" 
          :key="`skeleton-${i}`"
          class="space-y-4"
          aria-hidden="true"
        >
          <div class="h-6 bg-gray-200 rounded animate-pulse"></div>
          <div class="space-y-2">
            <div 
              v-for="j in 5" 
              :key="`skeleton-${i}-${j}`"
              class="h-4 bg-gray-100 rounded animate-pulse"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Error СЃРѕСЃС‚РѕСЏРЅРёРµ -->
    <div 
      v-else-if="error && !categories.length"
      class="container mx-auto px-4 py-8 text-center"
      role="alert"
    >
      <div class="text-red-600 mb-4">
        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <p class="font-medium">РћС€РёР±РєР° Р·Р°РіСЂСѓР·РєРё РєР°С‚Р°Р»РѕРіР°</p>
        <p class="text-sm text-gray-500 mt-1">{{ error }}</p>
      </div>
      <button
        @click="handleRefresh"
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        type="button"
      >
        РџРѕРїСЂРѕР±РѕРІР°С‚СЊ СЃРЅРѕРІР°
      </button>
    </div>

    <!-- РћСЃРЅРѕРІРЅРѕР№ РєРѕРЅС‚РµРЅС‚ -->
    <div 
      v-else
      class="container mx-auto px-4 py-8"
    >
      <!-- РЎРµС‚РєР° РєР°С‚РµРіРѕСЂРёР№ -->
      <div 
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8"
        role="menubar"
      >
        <div
          v-for="(category, index) in visibleCategories"
          :key="category.id"
          class="category-column"
          :class="{ 'hidden sm:block': index >= mobileVisibleCount && !showAllOnMobile }"
        >
          <!-- Р—Р°РіРѕР»РѕРІРѕРє РєР°С‚РµРіРѕСЂРёРё -->
          <h3 
            class="font-semibold text-gray-900 mb-4 flex items-center gap-2 group"
            :id="`category-${category.id}`"
            role="menuitem"
            tabindex="0"
            @keydown.enter="handleCategoryHeaderClick(category)"
            @keydown.space.prevent="handleCategoryHeaderClick(category)"
          >
            <!-- РРєРѕРЅРєР° -->
            <span 
              v-if="category.icon"
              class="text-2xl transition-transform group-hover:scale-110"
              :aria-label="`РРєРѕРЅРєР° РєР°С‚РµРіРѕСЂРёРё ${category.name}`"
            >
              {{ category.icon }}
            </span>
            
            <!-- РќР°Р·РІР°РЅРёРµ -->
            <span class="transition-colors group-hover:text-blue-600">
              {{ category.name }}
            </span>
            
            <!-- Р‘РµР№РґР¶ РґР»СЏ РЅРѕРІС‹С… РєР°С‚РµРіРѕСЂРёР№ -->
            <span
              v-if="category.featured"
              class="px-2 py-0.5 bg-blue-100 text-blue-600 text-xs rounded-full font-medium"
              aria-label="РџРѕРїСѓР»СЏСЂРЅР°СЏ РєР°С‚РµРіРѕСЂРёСЏ"
            >
              РўРћРџ
            </span>
          </h3>

          <!-- РЎРїРёСЃРѕРє СѓСЃР»СѓРі РєР°С‚РµРіРѕСЂРёРё -->
          <ul 
            class="space-y-2"
            role="menu"
            :aria-labelledby="`category-${category.id}`"
          >
            <li
              v-for="item in category.items"
              :key="item.id"
              role="none"
            >
              <component
                :is="item.href.startsWith('http') ? 'a' : Link"
                :href="item.href"
                :target="item.href.startsWith('http') ? '_blank' : undefined"
                :rel="item.href.startsWith('http') ? 'noopener noreferrer' : undefined"
                :class="linkClasses(item)"
                role="menuitem"
                @click="handleItemClick(category, item)"
                @keydown.enter="handleItemClick(category, item)"
              >
                <!-- РќР°Р·РІР°РЅРёРµ СѓСЃР»СѓРіРё -->
                <span>{{ item.name }}</span>
                
                <!-- Р‘РµР№РґР¶Рё -->
                <div v-if="item.popular || item.new" class="flex gap-1 ml-auto">
                  <span
                    v-if="item.popular"
                    class="px-1.5 py-0.5 bg-orange-100 text-orange-600 text-xs rounded font-medium"
                    aria-label="РџРѕРїСѓР»СЏСЂРЅР°СЏ СѓСЃР»СѓРіР°"
                  >
                    рџ”Ґ
                  </span>
                  <span
                    v-if="item.new"
                    class="px-1.5 py-0.5 bg-green-100 text-green-600 text-xs rounded font-medium"
                    aria-label="РќРѕРІР°СЏ СѓСЃР»СѓРіР°"
                  >
                    NEW
                  </span>
                </div>
                
                <!-- РРєРѕРЅРєР° РІРЅРµС€РЅРµР№ СЃСЃС‹Р»РєРё -->
                <svg
                  v-if="item.href.startsWith('http')"
                  class="w-3 h-3 ml-1 opacity-60"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
              </component>
            </li>
          </ul>
        </div>
      </div>

      <!-- РљРЅРѕРїРєР° "РџРѕРєР°Р·Р°С‚СЊ РІСЃРµ" РЅР° РјРѕР±РёР»СЊРЅС‹С… -->
      <div 
        v-if="categories.length > mobileVisibleCount"
        class="block sm:hidden mt-6 text-center"
      >
        <button
          @click="toggleMobileView"
          class="px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          type="button"
          :aria-expanded="showAllOnMobile"
        >
          {{ showAllOnMobile ? 'РЎРєСЂС‹С‚СЊ' : `РџРѕРєР°Р·Р°С‚СЊ РµС‰Рµ ${categories.length - mobileVisibleCount}` }}
        </button>
      </div>

      <!-- РџРѕРїСѓР»СЏСЂРЅС‹Рµ Р·Р°РїСЂРѕСЃС‹ -->
      <div 
        v-if="popularTags.length > 0"
        class="mt-8 pt-6 border-t"
      >
        <h4 
          id="popular-tags-heading"
          class="text-sm font-semibold text-gray-700 mb-3"
        >
          РџРѕРїСѓР»СЏСЂРЅС‹Рµ Р·Р°РїСЂРѕСЃС‹:
        </h4>
        
        <div 
          class="flex flex-wrap gap-2"
          role="list"
          aria-labelledby="popular-tags-heading"
        >
          <component
            v-for="tag in visibleTags"
            :key="tag.id"
            :is="Link"
            :href="getTagHref(tag)"
            :class="tagClasses(tag)"
            role="listitem"
            @click="handleTagClick(tag)"
          >
            <span>{{ tag.name }}</span>
            
            <!-- РЎС‡РµС‚С‡РёРє РїРѕРїСѓР»СЏСЂРЅРѕСЃС‚Рё -->
            <span 
              v-if="tag.count && showTagCounts"
              class="ml-1 text-xs opacity-75"
              :aria-label="`${tag.count} РјР°СЃС‚РµСЂРѕРІ`"
            >
              ({{ tag.count }})
            </span>
            
            <!-- РРЅРґРёРєР°С‚РѕСЂ С‚СЂРµРЅРґР° -->
            <span
              v-if="tag.trending"
              class="ml-1"
              aria-label="Р’ С‚СЂРµРЅРґРµ"
              title="Р’ С‚СЂРµРЅРґРµ"
            >
              рџ“€
            </span>
          </component>
          
          <!-- РџРѕРєР°Р·Р°С‚СЊ РµС‰Рµ С‚РµРіРё -->
          <button
            v-if="popularTags.length > visibleTagsCount && !showAllTags"
            @click="showAllTags = true"
            class="px-3 py-1 text-blue-600 text-sm rounded-full border border-blue-200 hover:bg-blue-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
            type="button"
          >
            +{{ popularTags.length - visibleTagsCount }} РµС‰Рµ
          </button>
        </div>
      </div>

      <!-- РЎС‚Р°С‚РёСЃС‚РёРєР° (РµСЃР»Рё РІРєР»СЋС‡РµРЅР°) -->
      <div 
        v-if="showStats && !isLoading"
        class="mt-6 pt-4 border-t text-center text-sm text-gray-500"
      >
        Р’СЃРµРіРѕ {{ totalItemsCount }} СѓСЃР»СѓРі РІ {{ categories.length }} РєР°С‚РµРіРѕСЂРёСЏС…
        <span v-if="lastUpdate" class="ml-2">
          вЂў РћР±РЅРѕРІР»РµРЅРѕ {{ formatLastUpdate(lastUpdate) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useCatalogStore, type CatalogCategory, type CatalogItem, type PopularTag } from '../../model/catalog.store'

// TypeScript РёРЅС‚РµСЂС„РµР№СЃС‹
interface Props {
  visible?: boolean
  maxCategories?: number
  maxTags?: number
  showTagCounts?: boolean
  showStats?: boolean
  mobileVisibleCount?: number
  autoClose?: boolean
  closeDelay?: number
  customClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  visible: true,
  maxCategories: 0, // 0 = РїРѕРєР°Р·Р°С‚СЊ РІСЃРµ
  maxTags: 8,
  showTagCounts: false,
  showStats: true,
  mobileVisibleCount: 2,
  autoClose: true,
  closeDelay: 300,
  customClass: ''
})

// TypeScript С‚РёРїРёР·Р°С†РёСЏ emits
const emit = defineEmits<{
  'close': []
  'category-click': [category: CatalogCategory, item?: CatalogItem]
  'tag-click': [tag: PopularTag]
  'refresh': []
}>()

// Store
const catalogStore = useCatalogStore()

// Local state
const showAllOnMobile = ref(false)
const showAllTags = ref(false)
const closeTimeout = ref<number>()

// Computed
const categories = computed(() => catalogStore.categories)
const popularTags = computed(() => catalogStore.popularTags)
const isLoading = computed(() => catalogStore.isLoading)
const error = computed(() => catalogStore.error)
const totalItemsCount = computed(() => catalogStore.totalItemsCount)
const lastUpdate = computed(() => catalogStore.lastFetchTime)

const visibleCategories = computed(() => {
  if (props.maxCategories > 0) {
    return categories.value.slice(0, props.maxCategories)
  }
  return categories.value
})

const visibleTagsCount = computed(() => 
  showAllTags.value ? popularTags.value.length : props.maxTags
)

const visibleTags = computed(() => 
  popularTags.value.slice(0, visibleTagsCount.value)
)

const linkClasses = (item: CatalogItem) => [
  'text-gray-600 hover:text-blue-600 text-sm flex items-center justify-between py-1 px-2 -mx-2 rounded transition-all duration-200',
  'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  {
    'hover:bg-blue-50': !item.popular,
    'hover:bg-orange-50 font-medium': item.popular,
  }
]

const tagClasses = (tag: PopularTag) => [
  'inline-flex items-center px-3 py-1 text-sm rounded-full transition-all duration-200',
  'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
  {
    'bg-gray-100 hover:bg-gray-200 text-gray-700': !tag.trending,
    'bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium': tag.trending,
  }
]

// Methods
const handleClose = (): void => {
  emit('close')
}

const handleItemClick = (category: CatalogCategory, item: CatalogItem): void => {
  // РўСЂРµРєРёРЅРі РєР»РёРєР°
  catalogStore.trackCategoryClick(category, item)
  
  // РЈРІРµРґРѕРјР»РµРЅРёРµ СЂРѕРґРёС‚РµР»СЏ
  emit('category-click', category, item)
  
  // РђРІС‚РѕР·Р°РєСЂС‹С‚РёРµ СЃ Р·Р°РґРµСЂР¶РєРѕР№
  if (props.autoClose) {
    closeTimeout.value = setTimeout(() => {
      handleClose()
    }, props.closeDelay)
  }
}

const handleCategoryHeaderClick = (category: CatalogCategory): void => {
  // РџРµСЂРµС…РѕРґ РЅР° СЃС‚СЂР°РЅРёС†Сѓ РєР°С‚РµРіРѕСЂРёРё (РµСЃР»Рё РµСЃС‚СЊ)
  if (category.items.length > 0) {
    const mainItem = category.items[0]
    if (mainItem) {
      handleItemClick(category, mainItem)
    }
  }
}

const handleTagClick = (tag: PopularTag): void => {
  // РўСЂРµРєРёРЅРі РєР»РёРєР°
  catalogStore.trackTagClick(tag)
  
  // РЈРІРµРґРѕРјР»РµРЅРёРµ СЂРѕРґРёС‚РµР»СЏ
  emit('tag-click', tag)
  
  // РђРІС‚РѕР·Р°РєСЂС‹С‚РёРµ
  if (props.autoClose) {
    closeTimeout.value = setTimeout(() => {
      handleClose()
    }, props.closeDelay)
  }
}

const handleRefresh = async (): Promise<void> => {
  await catalogStore.refreshCatalog()
  emit('refresh')
}

const toggleMobileView = (): void => {
  showAllOnMobile.value = !showAllOnMobile.value
}

const getTagHref = (tag: PopularTag): string => {
  return `/search?q=${encodeURIComponent(tag.name)}`
}

const formatLastUpdate = (date: Date): string => {
  const now = new Date()
  const diff = now.getTime() - date.getTime()
  const minutes = Math.floor(diff / 60000)
  
  if (minutes < 1) return 'С‚РѕР»СЊРєРѕ С‡С‚Рѕ'
  if (minutes < 60) return `${minutes} РјРёРЅ РЅР°Р·Р°Рґ`
  
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours} С‡ РЅР°Р·Р°Рґ`
  
  return date.toLocaleDateString('ru-RU')
}

// Keyboard navigation
const handleKeyboardNavigation = (event: KeyboardEvent): void => {
  if (!props.visible) return
  
  switch (event.key) {
    case 'Escape':
      handleClose()
      break
    case 'Tab':
      // РџРѕР·РІРѕР»СЏРµРј РЅР°С‚РёРІРЅСѓСЋ С‚Р°Р±СѓР»СЏС†РёСЋ
      break
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('keydown', handleKeyboardNavigation)
  
  // РџСЂРµРґР·Р°РіСЂСѓР·РєР° РґР°РЅРЅС‹С…
  if (categories.value.length === 0) {
    catalogStore.loadCatalog()
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeyboardNavigation)
  clearTimeout(closeTimeout.value)
})
</script>

<style scoped>
/* РђРЅРёРјР°С†РёРё РґР»СЏ loading СЃРѕСЃС‚РѕСЏРЅРёР№ */
.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

/* Hover СЌС„С„РµРєС‚С‹ РґР»СЏ РєР°С‚РµРіРѕСЂРёР№ */
.category-column {
  transition: transform 0.2s ease-in-out;
}

@media (hover: hover) {
  .category-column:hover {
    transform: translateY(-2px);
  }
}

/* РЈР»СѓС‡С€РµРЅРЅС‹Рµ focus states */
.focus\:ring-2:focus {
  box-shadow: 0 0 0 2px theme('colors.blue.500');
}

/* Accessibility improvements */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
  
  .category-column:hover {
    transform: none;
  }
}

/* High contrast mode */
@media (prefers-contrast: high) {
  .text-gray-600 {
    color: #1f2937;
  }
  
  .hover\:text-blue-600:hover {
    color: #1d4ed8;
  }
  
  .bg-gray-100 {
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
  }
}

/* Dark mode РїРѕРґРґРµСЂР¶РєР° */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors.gray.900');
  }
  
  .text-gray-900 {
    color: theme('colors.gray.100');
  }
  
  .text-gray-600 {
    color: theme('colors.gray.300');
  }
  
  .text-gray-700 {
    color: theme('colors.gray.200');
  }
  
  .border-t {
    border-color: theme('colors.gray.700');
  }
  
  .bg-gray-100 {
    background-color: theme('colors.gray.800');
  }
  
  .hover\:bg-gray-200:hover {
    background-color: theme('colors.gray.700');
  }
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .grid-cols-4 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
  
  .gap-8 {
    gap: 1.5rem;
  }
  
  .text-2xl {
    font-size: 1.5rem;
  }
}

/* Print styles */
@media print {
  .category-column {
    break-inside: avoid;
  }
}
</style>

