<template>
  <AppLayout>
    <Head title="СПА-услуги, массаж в Москве | Услуги на SPA.COM" />
    
    <!-- Поисковая секция -->
    <div class="border-b">
      <div class="px-4 sm:px-6 lg:px-8 py-4">
        <SearchBar 
          :initial-query="filters.q"
          @search="handleSearch"
        />
      </div>
    </div>
    
    <!-- Основной контент без дополнительных контейнеров -->
    <div class="flex">
      <!-- Фильтры слева -->
      <aside class="w-64 flex-shrink-0 border-r bg-gray-50 min-h-screen hidden lg:block">
        <div class="p-6">
          <h2 class="text-lg font-semibold mb-4">Фильтры</h2>
          <Filters 
            :filters="filters" 
            :cities="cities"
            :categories="categories"
            :priceRange="priceRange"
            @update="updateFilters"
          />
        </div>
      </aside>
      
      <!-- Контент справа -->
      <main class="flex-1 min-w-0">
        <!-- Заголовок и управление -->
        <div class="px-6 py-4 border-b">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold">
                СПА-услуги в Москве
              </h1>
              <p class="text-gray-600 text-sm mt-1">
                {{ masters?.total || cards.length }} {{ pluralize(masters?.total || cards.length, ['объявление', 'объявления', 'объявлений']) }}
              </p>
            </div>
            
            <div class="flex items-center gap-3">
              <!-- Переключатель вида -->
              <div class="hidden sm:flex items-center border rounded-lg bg-white">
                <button 
                  @click="showMap = false"
                  :class="[
                    'p-2 transition-colors',
                    !showMap ? 'bg-gray-100' : 'hover:bg-gray-50'
                  ]"
                  title="Сетка"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                  </svg>
                </button>
                <div class="w-px h-6 bg-gray-200"></div>
                <button 
                  @click="showMap = true"
                  :class="[
                    'p-2 transition-colors',
                    showMap ? 'bg-gray-100' : 'hover:bg-gray-50'
                  ]"
                  title="На карте"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                  </svg>
                </button>
              </div>
              
              <!-- Сортировка -->
              <select 
                v-model="sort"
                @change="updateFilters({ sort })"
                class="border rounded-lg text-sm px-3 py-2 bg-white hover:border-gray-400 transition-colors"
              >
                <option value="popular">Популярные</option>
                <option value="price_asc">Сначала дешевле</option>
                <option value="price_desc">Сначала дороже</option>
                <option value="rating">По рейтингу</option>
              </select>
              
              <!-- Мобильные фильтры -->
              <button 
                @click="showMobileFilters = true"
                class="lg:hidden p-2 border rounded-lg hover:bg-gray-50 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Карта (если включена) -->
        <div v-if="showMap" class="h-[400px] border-b">
          <Map :cards="masters?.data || cards" />
        </div>
        
        <!-- Сетка карточек -->
        <div class="p-6">
          <!-- Хлебные крошки (опционально) -->
          <nav class="text-sm text-gray-600 mb-4">
            <a href="/" class="hover:text-gray-900">Главная</a>
            <span class="mx-2">/</span>
            <span>СПА-услуги</span>
          </nav>
          
          <!-- Карточки -->
          <Cards :cards="masters?.data || cards" />
          
          <!-- Пагинация -->
          <div v-if="masters?.links && masters.last_page > 1" class="mt-8">
            <Pagination :links="masters.links" />
          </div>
        </div>
      </main>
    </div>
    
    <!-- Мобильные фильтры -->
    <Teleport to="body">
      <MobileFilters 
        v-if="showMobileFilters"
        :show="showMobileFilters"
        :filters="filters"
        :cities="cities"
        :categories="categories"
        :priceRange="priceRange"
        @close="showMobileFilters = false"
        @update="updateFilters"
      />
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Cards from '@/Components/Cards.vue'
import Map from '@/Components/Map.vue' 
import Filters from '@/Components/Filters.vue'
import SearchBar from '@/Components/Common/SearchBar.vue'
import Pagination from '@/Components/Common/Pagination.vue'
import MobileFilters from '@/Components/Common/MobileFilters.vue'

// Props
const props = defineProps({
  cards: {
    type: Array,
    default: () => []
  },
  masters: {
    type: Object,
    default: null
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  cities: {
    type: Array,
    default: () => []
  },
  categories: {
    type: Array,
    default: () => []
  },
  priceRange: {
    type: Object,
    default: () => ({ min: 0, max: 10000 })
  }
})

// Состояние
const showMap = ref(false)
const showMobileFilters = ref(false)
const sort = ref(props.filters.sort || 'popular')

// Обработка поиска
const handleSearch = (query) => {
  updateFilters({ q: query })
}

// Обновление фильтров
const updateFilters = (newFilters) => {
  router.get(route('home'), {
    ...props.filters,
    ...newFilters
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

// Склонение числительных
const pluralize = (count, forms) => {
  const cases = [2, 0, 1, 1, 1, 2]
  return forms[(count % 100 > 4 && count % 100 < 20) ? 2 : cases[(count % 10 < 5) ? count % 10 : 5]]
}
</script>