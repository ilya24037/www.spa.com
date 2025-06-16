<!-- resources/js/Pages/Home.vue -->
<template>
  <div>
    <Head title="Найти массажиста в вашем городе" />
    
    <!-- Хлебные крошки и заголовок -->
    <div class="bg-white border-b">
      <div class="container mx-auto px-4 py-4">
        <!-- Хлебные крошки -->
        <nav class="text-sm text-gray-500 mb-2">
          <Link href="/" class="hover:text-gray-700">Главная</Link>
          <span class="mx-2">›</span>
          <span class="text-gray-700">{{ currentCity }}</span>
          <span class="mx-2">›</span>
          <span class="text-gray-700">Массаж и СПА</span>
        </nav>
        
        <!-- Заголовок с количеством -->
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold">
            Массаж и СПА-услуги в {{ currentCity }}
            <span class="text-gray-500 font-normal text-lg ml-2">{{ totalCount }}</span>
          </h1>
        </div>
      </div>
    </div>

    <!-- Основной контент -->
    <div class="container mx-auto px-4 py-6">
      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Фильтры слева -->
        <aside class="lg:col-span-1">
          <div class="bg-white rounded-lg shadow p-4 sticky top-4">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold">Фильтры</h3>
              <button 
                v-if="hasActiveFilters"
                @click="resetFilters"
                class="text-sm text-blue-600 hover:text-blue-700"
              >
                Сбросить
              </button>
            </div>
            
            <Filters 
              :filters="filters"
              :categories="categories"
              :price-range="priceRange"
              @update="updateFilters"
            />
          </div>
        </aside>

        <!-- Карточки мастеров и карта справа -->
        <main class="lg:col-span-3">
          <!-- Переключатель вида и сортировка -->
          <div class="flex items-center justify-between mb-4">
            <!-- Переключатель карты -->
            <button 
              @click="toggleMap"
              class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition"
            >
              <svg 
                class="w-5 h-5 transition-transform duration-200"
                :class="{ 'rotate-180': !showMap }"
                fill="none" 
                stroke="currentColor" 
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
              </svg>
              <span>{{ showMap ? 'Скрыть карту' : 'Показать на карте' }}</span>
            </button>

            <!-- Сортировка -->
            <select 
              v-model="sortBy"
              class="px-4 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="popular">Популярные</option>
              <option value="price_asc">Сначала дешёвые</option>
              <option value="price_desc">Сначала дорогие</option>
              <option value="rating">По рейтингу</option>
              <option value="distance">По расстоянию</option>
            </select>
          </div>

          <!-- Карта (с анимацией скрытия/показа) -->
          <Transition
            enter-active-class="transition-all duration-500 ease-out"
            enter-from-class="opacity-0 max-h-0"
            enter-to-class="opacity-100 max-h-[500px]"
            leave-active-class="transition-all duration-500 ease-in"
            leave-from-class="opacity-100 max-h-[500px]"
            leave-to-class="opacity-0 max-h-0"
          >
            <div v-if="showMap" class="mb-6 overflow-hidden">
              <div class="bg-white rounded-lg shadow">
                <div class="relative h-[400px]">
                  <MastersMap 
                    :masters="masters?.data || []"
                    :center="mapCenter"
                    @marker-click="handleMarkerClick"
                    @bounds-changed="handleBoundsChanged"
                  />
                  
                  <!-- Кнопка "Показать объявления" при изменении области карты -->
                  <Transition
                    enter-active-class="transition ease-out duration-200"
                    enter-from-class="opacity-0 translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition ease-in duration-150"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 translate-y-2"
                  >
                    <button 
                      v-if="showUpdateButton"
                      @click="updateSearchInBounds"
                      class="absolute top-4 left-1/2 -translate-x-1/2 bg-white px-6 py-2 rounded-full shadow-lg hover:shadow-xl transition flex items-center gap-2"
                    >
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                      </svg>
                      Показать объявления в этой области
                    </button>
                  </Transition>
                </div>
              </div>
            </div>
          </Transition>

          <!-- Дополнительные фильтры-теги -->
          <div class="flex flex-wrap gap-2 mb-4">
            <button 
              v-for="tag in quickFilters"
              :key="tag.key"
              @click="toggleQuickFilter(tag.key)"
              :class="[
                'px-4 py-2 rounded-full text-sm transition',
                activeQuickFilters.includes(tag.key) 
                  ? 'bg-blue-600 text-white' 
                  : 'bg-gray-100 hover:bg-gray-200 text-gray-700'
              ]"
            >
              {{ tag.icon }} {{ tag.label }}
            </button>
          </div>

          <!-- Сетка карточек -->
          <div v-if="masters?.data?.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <MasterCard 
              v-for="master in masters.data"
              :key="master.id"
              :master="master"
              :show-distance="showMap"
            />
          </div>

          <!-- Пустое состояние -->
          <div v-else class="bg-white rounded-lg shadow p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Мастера не найдены</h3>
            <p class="text-gray-500">Попробуйте изменить параметры поиска или расширить область на карте</p>
          </div>

          <!-- Пагинация -->
          <div v-if="masters?.links?.length > 3" class="mt-8">
            <Pagination :links="masters.links" />
          </div>
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import MasterCard from '@/Components/Masters/MasterCard.vue'
import MastersMap from '@/Components/Map/SimpleMap.vue'
import Filters from '@/Components/Filters/Filters.vue'
import Pagination from '@/Components/Common/Pagination.vue'

const props = defineProps({
  masters: Object,
  filters: Object,
  categories: Array,
  priceRange: Object,
  currentCity: {
    type: String,
    default: 'Москва'
  },
  mapCenter: {
    type: Object,
    default: () => ({ lat: 55.7558, lng: 37.6173 })
  }
})

// Состояние
const showMap = ref(true) // Карта показана по умолчанию
const sortBy = ref(props.filters?.sort || 'popular')
const showUpdateButton = ref(false)
const mapBounds = ref(null)
const activeQuickFilters = ref([])

// Быстрые фильтры
const quickFilters = [
  { key: 'at_home', label: 'Выезд на дом', icon: '🏠' },
  { key: 'online_booking', label: 'Онлайн-запись', icon: '📅' },
  { key: 'available_now', label: 'Свободен сейчас', icon: '✅' },
  { key: 'certificates', label: 'Сертификаты', icon: '🎁' },
  { key: 'premium', label: 'Премиум', icon: '⭐' }
]

// Вычисляемые свойства
const totalCount = computed(() => props.masters?.total || 0)
const hasActiveFilters = computed(() => {
  return Object.values(props.filters || {}).some(value => 
    value !== null && value !== '' && value !== undefined
  )
})

// Методы
const toggleMap = () => {
  showMap.value = !showMap.value
  // Сохраняем предпочтение в localStorage
  localStorage.setItem('showMap', showMap.value)
}

const updateFilters = (newFilters) => {
  router.get(route('home'), {
    ...props.filters,
    ...newFilters,
    sort: sortBy.value
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

const resetFilters = () => {
  router.get(route('home'), {
    sort: sortBy.value
  })
}

const toggleQuickFilter = (key) => {
  const index = activeQuickFilters.value.indexOf(key)
  if (index > -1) {
    activeQuickFilters.value.splice(index, 1)
  } else {
    activeQuickFilters.value.push(key)
  }
  
  updateFilters({
    [key]: index > -1 ? null : true
  })
}

const handleMarkerClick = (master) => {
  // Прокрутить к карточке мастера
  const element = document.getElementById(`master-${master.id}`)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'center' })
    // Подсветка карточки
    element.classList.add('ring-2', 'ring-blue-500')
    setTimeout(() => {
      element.classList.remove('ring-2', 'ring-blue-500')
    }, 2000)
  }
}

const handleBoundsChanged = (bounds) => {
  mapBounds.value = bounds
  showUpdateButton.value = true
}

const updateSearchInBounds = () => {
  showUpdateButton.value = false
  updateFilters({
    bounds: mapBounds.value
  })
}

// Загружаем сохраненное состояние карты
if (typeof window !== 'undefined') {
  const savedShowMap = localStorage.getItem('showMap')
  if (savedShowMap !== null) {
    showMap.value = savedShowMap === 'true'
  }
}

// Следим за изменением сортировки
watch(sortBy, (newValue) => {
  updateFilters({ sort: newValue })
})
</script>

<style scoped>
/* Анимация для transition высоты */
.max-h-0 {
  max-height: 0;
}
.max-h-\[500px\] {
  max-height: 500px;
}
</style>