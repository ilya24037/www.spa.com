<!-- Выпадающий каталог услуг - FSD Feature -->
<template>
  <div 
    class="bg-white shadow-lg border-t relative z-50"
    role="menu"
    :aria-hidden="!visible"
    @keydown.escape="handleClose"
  >
    <!-- Loading состояние -->
    <div 
      v-if="isLoading && !categories.length" 
      class="container mx-auto px-4 py-8"
      aria-live="polite"
      aria-label="Загрузка категорий"
    >
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <div 
          v-for="i in 4" 
          :key="`skeleton-${i}`"
          class="space-y-4"
          aria-hidden="true"
        >
          <div class="h-6 bg-gray-500 rounded animate-pulse" />
          <div class="space-y-2">
            <div 
              v-for="j in 5" 
              :key="`skeleton-${i}-${j}`"
              class="h-4 bg-gray-500 rounded animate-pulse"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Error состояние -->
    <div 
      v-else-if="error && !categories.length"
      class="container mx-auto px-4 py-8 text-center"
      role="alert"
    >
      <div class="text-red-600 mb-4">
        <svg
          class="w-12 h-12 mx-auto mb-2"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
          />
        </svg>
        <p class="font-medium">
          Ошибка загрузки категорий
        </p>
        <p class="text-sm text-gray-500 mt-1">
          {{ error }}
        </p>
      </div>
      <button
        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        type="button"
        @click="handleRefresh"
      >
        Попробовать снова
      </button>
    </div>

    <!-- Основной контент -->
    <div 
      v-else
      class="container mx-auto px-4 py-8"
    >
      <!-- Сетка категорий -->
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
          <!-- Заголовок категории -->
          <h3 
            :id="`category-${category.id}`"
            class="font-semibold text-gray-500 mb-4 flex items-center gap-2 group"
            role="menuitem"
            tabindex="0"
            @keydown.enter="handleCategoryHeaderClick(category)"
            @keydown.space.prevent="handleCategoryHeaderClick(category)"
          >
            <!-- Иконка -->
            <span 
              v-if="category.icon"
              class="text-2xl transition-transform group-hover:scale-110"
              :aria-label="`Иконка категории ${category.name}`"
            >
              {{ category.icon }}
            </span>
            
            <!-- Название -->
            <span class="transition-colors group-hover:text-blue-600">
              {{ category.name }}
            </span>
            
            <!-- Бейдж для новых категорий -->
            <span
              v-if="category.featured"
              class="px-2 py-0.5 bg-blue-100 text-blue-600 text-xs rounded-full font-medium"
              aria-label="Популярная категория"
            >
              ТОП
            </span>
          </h3>

          <!-- Список услуг категории -->
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
                <!-- Название услуги -->
                <span>{{ item.name }}</span>
                
                <!-- Бейджи -->
                <div v-if="item.popular || item.new" class="flex gap-1 ml-auto">
                  <span
                    v-if="item.popular"
                    class="px-1.5 py-0.5 bg-orange-100 text-orange-600 text-xs rounded font-medium"
                    aria-label="Популярная услуга"
                  >
                    🔥
                  </span>
                  <span
                    v-if="item.new"
                    class="px-1.5 py-0.5 bg-green-100 text-green-600 text-xs rounded font-medium"
                    aria-label="Новая услуга"
                  >
                    NEW
                  </span>
                </div>
                
                <!-- Иконка внешней ссылки -->
                <svg
                  v-if="item.href.startsWith('http')"
                  class="w-3 h-3 ml-1 opacity-60"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                  />
                </svg>
              </component>
            </li>
          </ul>
        </div>
      </div>

      <!-- Кнопка "Показать все" на мобильных -->
      <div 
        v-if="categories.length > mobileVisibleCount"
        class="block sm:hidden mt-6 text-center"
      >
        <button
          class="px-4 py-2 text-blue-600 border border-blue-600 rounded-lg hover:bg-blue-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          type="button"
          :aria-expanded="showAllOnMobile"
          @click="toggleMobileView"
        >
          {{ showAllOnMobile ? 'Скрыть' : `Показать еще ${categories.length - mobileVisibleCount}` }}
        </button>
      </div>

      <!-- Популярные теги -->
      <div 
        v-if="popularTags.length > 0"
        class="mt-8 pt-6 border-t"
      >
        <h4 
          id="popular-tags-heading"
          class="text-sm font-semibold text-gray-500 mb-3"
        >
          Популярные теги:
        </h4>
        
        <div 
          class="flex flex-wrap gap-2"
          role="list"
          aria-labelledby="popular-tags-heading"
        >
          <component
            :is="Link"
            v-for="tag in visibleTags"
            :key="tag.id"
            :href="getTagHref(tag)"
            :class="tagClasses(tag)"
            role="listitem"
            @click="handleTagClick(tag)"
          >
            <span>{{ tag.name }}</span>
            
            <!-- Счетчик популярности -->
            <span 
              v-if="tag.count && showTagCounts"
              class="ml-1 text-xs opacity-75"
              :aria-label="`${tag.count} мастеров`"
            >
              ({{ tag.count }})
            </span>
            
            <!-- Иконка тренда -->
            <span
              v-if="tag.trending"
              class="ml-1"
              aria-label="В тренде"
              title="В тренде"
            >
              🔥
            </span>
          </component>
          
          <!-- Показать еще теги -->
          <button
            v-if="popularTags.length > visibleTagsCount && !showAllTags"
            class="px-3 py-1 text-blue-600 text-sm rounded-full border border-blue-200 hover:bg-blue-50 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
            type="button"
            @click="showAllTags = true"
          >
            +{{ popularTags.length - visibleTagsCount }} еще
          </button>
        </div>
      </div>

      <!-- Статистика (если включена) -->
      <div 
        v-if="showStats && !isLoading"
        class="mt-6 pt-4 border-t text-center text-sm text-gray-500"
      >
        Всего {{ totalItemsCount }} услуг в {{ categories.length }} категориях
        <span v-if="lastUpdate" class="ml-2">
          вЂў Обновлено {{ formatLastUpdate(lastUpdate) }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useCatalogStore, type CatalogCategory, type CatalogItem, type PopularTag } from '../../model/catalog.store'

// TypeScript интерфейсы
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
    maxCategories: 0, // 0 = показать все
    maxTags: 8,
    showTagCounts: false,
    showStats: true,
    mobileVisibleCount: 2,
    autoClose: true,
    closeDelay: 300,
    customClass: ''
})

// TypeScript типы emits
const emit = defineEmits<{
  'close': []
  'category-click': [category: CatalogCategory, item?: CatalogItem]
  'tag-click': [tag: PopularTag]
  'refresh': []
}>()

// Store
const catalogStore = useCatalogStore()

// Локальное состояние
const showAllOnMobile = ref(false)
const showAllTags = ref(false)
const closeTimeout = ref<number>()

// Вычисляемые свойства
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
    'text-gray-500 hover:text-blue-600 text-sm flex items-center justify-between py-1 px-2 -mx-2 rounded transition-all duration-200',
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
        'bg-gray-500 hover:bg-gray-500 text-gray-500': !tag.trending,
        'bg-blue-100 hover:bg-blue-200 text-blue-700 font-medium': tag.trending,
    }
]

// Методы
const handleClose = (): void => {
    emit('close')
}

const handleItemClick = (category: CatalogCategory, item: CatalogItem): void => {
    // Трекинг клика по категории
    catalogStore.trackCategoryClick(category, item)
  
    // Уведомление о клике
    emit('category-click', category, item)
  
    // Автозакрытие с задержкой
    if (props.autoClose) {
        closeTimeout.value = setTimeout(() => {
            handleClose()
        }, props.closeDelay)
    }
}

const handleCategoryHeaderClick = (category: CatalogCategory): void => {
    // Переход к странице категории (если есть)
    if (category.items.length > 0) {
        const mainItem = category.items[0]
        if (mainItem) {
            handleItemClick(category, mainItem)
        }
    }
}

const handleTagClick = (tag: PopularTag): void => {
    // Трекинг клика по тегу
    catalogStore.trackTagClick(tag)
  
    // Уведомление о клике
    emit('tag-click', tag)
  
    // Автозакрытие
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
  
    if (minutes < 1) return 'только что'
    if (minutes < 60) return `${minutes} минут назад`
  
    const hours = Math.floor(minutes / 60)
    if (hours < 24) return `${hours} час назад`
  
    return date.toLocaleDateString('ru-RU')
}

// Навигация по клавиатуре
const handleKeyboardNavigation = (event: KeyboardEvent): void => {
    if (!props.visible) return
  
    switch (event.key) {
    case 'Escape':
        handleClose()
        break
    case 'Tab':
        // Разрешаем переключение табов
        break
    }
}

// Жизненный цикл
onMounted(() => {
    document.addEventListener('keydown', handleKeyboardNavigation)
  
    // Загрузка данных
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
/* Анимации для состояний загрузки */
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

/* Hover эффекты для категорий */
.category-column {
  transition: transform 0.2s ease-in-out;
}

@media (hover: hover) {
  .category-column:hover {
    transform: translateY(-2px);
  }
}

/* Улучшенные состояния фокуса */
.focus\:ring-2:focus {
  box-shadow: 0 0 0 2px theme('colors.blue.500');
}

/* Улучшения доступности */
@media (prefers-reduced-motion: reduce) {
  * {
    animation: none !important;
    transition: none !important;
  }
  
  .category-column:hover {
    transform: none;
  }
}

/* Режим высокого контраста */
@media (prefers-contrast: high) {
  .text-gray-500 {
    color: #1f2937;
  }
  
  .hover\:text-blue-600:hover {
    color: #1d4ed8;
  }
  
  .bg-gray-500 {
    background-color: #f3f4f6;
    border: 1px solid #d1d5db;
  }
}

/* Темный режим подложки */
@media (prefers-color-scheme: dark) {
  .bg-white {
    background-color: theme('colors.gray.900');
  }
  
  .text-gray-500 {
    color: theme('colors.gray.100');
  }
  
  .text-gray-500 {
    color: theme('colors.gray.300');
  }
  
  .text-gray-500 {
    color: theme('colors.gray.200');
  }
  
  .border-t {
    border-color: theme('colors.gray.700');
  }
  
  .bg-gray-500 {
    background-color: theme('colors.gray.800');
  }
  
  .hover\:bg-gray-500:hover {
    background-color: theme('colors.gray.700');
  }
}

/* Оптимизация для мобильных */
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

/* Стили для печати */
@media print {
  .category-column {
    break-inside: avoid;
  }
}
</style>

