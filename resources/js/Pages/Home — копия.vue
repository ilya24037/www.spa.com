<!-- resources/js/Pages/Home.vue -->
<template>
  <AppLayout>
    
    <Head :title="`Массаж в ${currentCity} — найти мастера`" />

    <!-- Обертка с правильными отступами -->

    <div class=" py-6 lg:py-8">
      
      <!-- Хлебные крошки - отдельная белая плитка -->
      
        <Breadcrumbs
        :items="[
          { title: 'Главная', href: '/' },
          { title: 'Массажисты', href: '/masters' },
          { title: currentCity }
        ]"
        class="mb-4"
      />
   

      <!-- Заголовок -->
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

      <!-- Основной контент с гэпом между блоками -->
      <div class="flex gap-6">
        
        <!-- Универсальная боковая панель через SidebarWrapper -->
        <SidebarWrapper 
          v-model="showFilters"
          :sticky-top="120"
          desktop-width="w-64"
          content-class="p-0"
          :show-desktop-header="true"
          aria-label="Панель фильтров"
        >
          <!-- Заголовок для десктопа -->
          <template #header>
            <h2 class="font-semibold text-lg">Фильтры</h2>
          </template>

          <!-- Контент фильтров -->
          <div class="p-6">
            <Filters 
              :filters="filters"
              :categories="categories"
              @update="updateFilters"
            />
          </div>
          
          <!-- Мобильный футер с кнопками -->
          <template #footer>
            <div class="lg:hidden space-y-2">
              <button 
                @click="showFilters = false"
                class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium"
              >
                Показать {{ filteredCount }} {{ pluralize(filteredCount, 'результат', 'результата', 'результатов') }}
              </button>
              <button 
                v-if="hasActiveFilters"
                @click="resetFilters"
                class="w-full border border-gray-300 py-3 rounded-lg hover:bg-gray-50 transition-colors"
              >
                Сбросить фильтры
              </button>
            </div>
          </template>
        </SidebarWrapper>

        <!-- Контент справа -->
        <section class="flex-1 space-y-6">
          
          <!-- Карта - отдельная белая плитка -->
          <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="h-[400px] rounded-lg overflow-hidden">
              <SimpleMap :cards="filteredMasters" />
            </div>
          </div>

          <!-- Блок с карточками - обернуть в белую плитку -->
          <div class="bg-white rounded-lg shadow-sm p-6">
            <!-- Заголовок и контролы -->
            <div class="flex items-center justify-between mb-6">
              <h2 class="text-lg font-semibold">Показать списком</h2>
              
              <!-- Сортировка и вид -->
              <div class="flex items-center gap-4">
                <!-- Переключатель вида -->
                <div class="hidden sm:flex items-center bg-gray-100 rounded-lg p-1">
                  <button 
                    @click="viewMode = 'grid'"
                    :class="[
                      'p-2 rounded transition-all',
                      viewMode === 'grid' ? 'bg-white shadow-sm' : 'text-gray-600 hover:text-gray-900'
                    ]"
                    title="Сетка"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                  </button>
                  <button 
                    @click="viewMode = 'list'"
                    :class="[
                      'p-2 rounded transition-all',
                      viewMode === 'list' ? 'bg-white shadow-sm' : 'text-gray-600 hover:text-gray-900'
                    ]"
                    title="Список"
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
                  class="border border-gray-300 rounded-lg px-4 py-2 text-sm bg-white hover:border-gray-400 transition-colors"
                >
                  <option value="popular">Популярные</option>
                  <option value="price_asc">Сначала дешевые</option>
                  <option value="price_desc">Сначала дорогие</option>
                  <option value="rating">По рейтингу</option>
                  <option value="distance">По расстоянию</option>
                </select>
              </div>
            </div>
            
            <!-- Карточки -->
            <Cards 
              :cards="sortedMasters" 
              :view-mode="viewMode"
            />
          </div>
        </section>
      </div>

      <!-- Мобильная кнопка фильтров -->
      <button 
        @click="showFilters = true"
        class="lg:hidden fixed bottom-6 right-6 z-40 bg-blue-600 text-white p-4 rounded-full shadow-lg hover:bg-blue-700 transition-all active:scale-95"
      >
        <span class="sr-only">Открыть фильтры</span>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
        </svg>
        <!-- Бейдж с количеством активных фильтров -->
        <span 
          v-if="activeFiltersCount > 0"
          class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"
        >
          {{ activeFiltersCount }}
        </span>
      </button>
      
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'

// Импорты компонентов
import Breadcrumbs from '@/Components/Common/Breadcrumbs.vue'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import Filters from '@/Components/Filters/Filters.vue'
import Cards from '@/Components/Cards/Cards.vue'
import SimpleMap from '@/Components/Map/SimpleMap.vue'

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

// Фильтры
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

// Количество активных фильтров
const activeFiltersCount = computed(() => {
  let count = 0
  if (filters.value.categories.length > 0) count += filters.value.categories.length
  if (filters.value.price_from !== null) count++
  if (filters.value.price_to !== null) count++
  if (filters.value.home_service) count++
  if (filters.value.verified) count++
  return count
})

// Есть ли активные фильтры
const hasActiveFilters = computed(() => activeFiltersCount.value > 0)

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

// Хелпер для склонения
function pluralize(count, one, two, five) {
  const n = Math.abs(count) % 100
  const n1 = n % 10
  if (n > 10 && n < 20) return five
  if (n1 > 1 && n1 < 5) return two
  if (n1 === 1) return one
  return five
}
</script>