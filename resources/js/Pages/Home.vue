<!-- resources/js/Pages/Home.vue -->
<template>
  <div>
    <!-- SEO title -->
    <Head :title="`Массаж в ${currentCity} — найти мастера`" />

    <!-- Хлебные крошки -->
    <Breadcrumbs
      :items="[
        { title: 'Главная', href: '/' },
        { title: 'Массажисты', href: '/masters' },
        { title: currentCity }
      ]"
      class="mb-4"
    />

    <!-- Заголовок страницы -->
    <div class="mb-6">
      <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">
        Массажисты в {{ currentCity }}
      </h1>
      <p class="text-gray-600 mt-1">
        Найдено: <span class="font-semibold">{{ filteredCount }}</span> мастеров
      </p>
    </div>

      <!-- Быстрые теги -->
      <QuickTagsRow class="mb-6" />

      <!-- Основной контент с фильтрами -->
      <div class="flex gap-6">
        <!-- Фильтры слева (используем ваш FiltersSidebar + Filters) -->
        <aside class="w-60 flex-shrink-0 hidden lg:block">
          <FiltersSidebar
            :results-count="filteredCount"
            :has-active-filters="hasActiveFilters"
            @reset="resetFilters"
          >
            <Filters 
              :filters="filters"
              :categories="categories"
              @update="updateFilters"
            />
          </FiltersSidebar>
        </aside>

        <!-- Правая часть с картой и карточками -->
        <div class="flex-1 min-w-0">
          <!-- Карта -->
          <div class="mb-6">
            <div class="h-[400px] rounded-lg overflow-hidden shadow-sm">
              <SimpleMap
                :cards="filteredMasters"
                :center="{ lat: 58.0105, lng: 56.2502 }"
              />
            </div>
          </div>

          <!-- Панель инструментов -->
          <div class="flex items-center justify-between mb-4">
            <!-- Переключатель вида -->
            <div class="flex items-center gap-2">
              <button
                @click="viewMode = 'grid'"
                :class="[
                  'p-2 rounded-lg transition-colors',
                  viewMode === 'grid' 
                    ? 'bg-blue-100 text-blue-600' 
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
              </button>
              <button
                @click="viewMode = 'list'"
                :class="[
                  'p-2 rounded-lg transition-colors',
                  viewMode === 'list' 
                    ? 'bg-blue-100 text-blue-600' 
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                ]"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
              </button>
            </div>

            <!-- Сортировка -->
            <select
              v-model="sortBy"
              @change="applySort"
              class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
              <option value="popular">Популярные</option>
              <option value="price_asc">Сначала дешевле</option>
              <option value="price_desc">Сначала дороже</option>
              <option value="rating">По рейтингу</option>
              <option value="distance">По расстоянию</option>
            </select>
          </div>

          <!-- Карточки (grid) -->
          <Cards 
            v-if="viewMode === 'grid'"
            :cards="sortedMasters"
          />

          <!-- Карточки (list) - используем MasterCardList -->
          <div 
            v-else
            class="space-y-4"
          >
            <MasterCardList 
              v-for="master in sortedMasters"
              :key="master.id"
              :card="master"
            />
          </div>
        </div>
      </div>

    <!-- Мобильная кнопка фильтров -->
    <button
      class="lg:hidden fixed bottom-6 right-6 z-40 bg-blue-600 text-white p-4 rounded-full shadow-lg"
      @click="showFilters = true"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
      </svg>
    </button>

    <!-- Мобильные фильтры -->
    <MobileFilters
      :show="showFilters"
      :filters="filters"
      :categories="categories"
      @close="showFilters = false"
      @update="updateFilters"
/>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'

// Импорты ТОЛЬКО существующих компонентов
import Breadcrumbs from '@/Components/Common/Breadcrumbs.vue'
import FiltersSidebar from '@/Components/Filters/FiltersSidebar.vue'
import Filters from '@/Components/Filters/Filters.vue'
import QuickTagsRow from '@/Components/Filters/QuickTagsRow.vue'
import Cards from '@/Components/Cards/Cards.vue'
import MasterCardList from '@/Components/Cards/MasterCardList.vue' 
import SimpleMap from '@/Components/Map/SimpleMap.vue'
import MobileFilters from '@/Components/Common/MobileFilters.vue'

// Props из Inertia
const props = defineProps({
  masters: {
    type: Object,
    required: true
  },
  currentCity: {
    type: String,
    default: 'Пермь'
  },
  categories: {
    type: Array,
    default: () => []
  }
})

// Состояние
const showFilters = ref(false)
const viewMode = ref('grid')
const sortBy = ref('popular')

// Фильтры (адаптированы под структуру вашего Filters.vue)
const filters = ref({
  categories: [],
  price_from: null,
  price_to: null,
  home_service: false,
  online_booking: false,
  verified: false
})

// Фильтрация мастеров
const filteredMasters = computed(() => {
  let result = props.masters.data || []
  
  // Фильтр по категориям
  if (filters.value.categories.length > 0) {
    result = result.filter(master => {
      // Проверяем есть ли у мастера услуги в выбранных категориях
      return master.services?.some(service => 
        filters.value.categories.includes(service.category_id)
      )
    })
  }
  
  // Фильтр по цене
  if (filters.value.price_from) {
    result = result.filter(master => {
      const price = master.min_price || master.price || 0
      return price >= filters.value.price_from
    })
  }
  
  if (filters.value.price_to) {
    result = result.filter(master => {
      const price = master.max_price || master.price || 999999
      return price <= filters.value.price_to
    })
  }
  
  // Фильтр по выезду на дом
  if (filters.value.home_service) {
    result = result.filter(master => master.home_visit || master.home_service)
  }
  
  // Фильтр по верификации
  if (filters.value.verified) {
    result = result.filter(master => master.is_verified)
  }
  
  return result
})

// Сортировка
const sortedMasters = computed(() => {
  const masters = [...filteredMasters.value]
  
  switch (sortBy.value) {
    case 'price_asc':
      return masters.sort((a, b) => 
        (a.min_price || a.price || 0) - (b.min_price || b.price || 0)
      )
    case 'price_desc':
      return masters.sort((a, b) => 
        (b.max_price || b.price || 0) - (a.max_price || a.price || 0)
      )
    case 'rating':
      return masters.sort((a, b) => (b.rating || 0) - (a.rating || 0))
    case 'distance':
      return masters.sort((a, b) => (a.distance || 0) - (b.distance || 0))
    default:
      return masters
  }
})

// Количество отфильтрованных
const filteredCount = computed(() => filteredMasters.value.length)

// Есть ли активные фильтры
const hasActiveFilters = computed(() => {
  return filters.value.categories.length > 0 ||
         filters.value.price_from !== null ||
         filters.value.price_to !== null ||
         filters.value.home_service ||
         filters.value.verified
})

// Методы
function resetFilters() {
  filters.value = {
    categories: [],
    price_from: null,
    price_to: null,
    home_service: false,
    online_booking: false,
    verified: false
  }
}

function updateFilters(newFilters) {
  filters.value = { ...filters.value, ...newFilters }
}

function applySort() {
  // Сортировка применяется автоматически через computed
}
</script>