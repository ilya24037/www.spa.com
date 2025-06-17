<!-- resources/js/Pages/Home.vue -->
<template>
  <AppLayout>
    <!-- SEO title -->
    <Head :title="`Массаж в ${currentCity} — найти мастера`" />

    <!-- Обертка с отступами как договорились -->
    <div class="px-6 lg:px-8 py-6 lg:py-8">
      
   <!-- Хлебные крошки - отдельная белая плитка -->
      <div class="bg-white rounded-lg shadow-sm p-4 mb-4">
        <Breadcrumbs
          :items="[
            { title: 'Главная', href: '/' },
            { title: 'Массажисты', href: '/masters' },
            { title: currentCity }
          ]"
        />
      </div>

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

      <!-- Основной контент -->
      <div class="flex gap-6">
        
        <!-- Фильтры слева -->
        <SidebarWrapper 
          :show-mobile="showFilters"
          @update:show-mobile="showFilters = $event"
        >
          <Filters 
            :filters="filters"
            :categories="categories"
            @update="updateFilters"
          />
        </SidebarWrapper>

        <!-- Контент справа -->
        <section class="flex-1 space-y-6">
          
 <!-- Карта - отдельная белая плитка -->
          <div class="bg-white rounded-lg shadow-sm p-4">
            <div class="h-[400px] rounded-lg overflow-hidden">
              <SimpleMap :cards="filteredMasters" />
            </div>
          </div>

          <!-- Карточки -->
          <Cards :cards="sortedMasters" />
        </section>
      </div>

      <!-- Мобильная кнопка фильтров -->
      <button 
        @click="showFilters = true"
        class="lg:hidden fixed bottom-6 right-6 z-40 bg-blue-600 text-white p-4 rounded-full shadow-lg"
      >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
        </svg>
      </button>
      
    </div>
  </AppLayout>
</template>
<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'

// Импорты ТОЛЬКО существующих компонентов
import Breadcrumbs from '@/Components/Common/Breadcrumbs.vue'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import Filters from '@/Components/Filters/Filters.vue'
import QuickTagsRow from '@/Components/Filters/QuickTagsRow.vue'
import Cards from '@/Components/Cards/Cards.vue'
import MasterCardList from '@/Components/Cards/MasterCardList.vue' 
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