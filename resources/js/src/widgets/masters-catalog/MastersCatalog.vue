<template>
  <div class="masters-catalog">
    <!-- Только контент без фильтров -->
    <div>
      <!-- Content (cards, pagination) -->
      <section>
        <!-- Управление сеткой и сортировка -->
        <GridControls
          v-if="!loading || masters.length > 0"
          :displayed-count="masters.length"
          :total-count="filterStore.filterCounts?.total || masters.length"
          items-label="мастеров"
          :current-sort="currentSort"
          :current-view="props.viewMode"
          :show-view-toggle="true"
          :show-density-toggle="false"
          :show-column-control="false"
          @sort-change="handleSortChange"
          @view-change="handleViewChange"
        />
        
        <!-- Loading с детальными skeleton карточками -->
        <div v-if="loading" class="masters-grid">
          <MasterCardSkeleton v-for="i in 6" :key="`skeleton-${i}`" />
        </div>

        <!-- Error -->
        <div v-else-if="error" class="text-center py-12">
          <p class="text-red-500 mb-4">
            {{ error }}
          </p>
          <SecondaryButton @click="$emit('retry')">
            Попробовать снова
          </SecondaryButton>
        </div>

        <!-- Empty -->
        <div v-else-if="!masters || masters.length === 0" class="text-center py-12">
          <p class="text-gray-500 text-lg mb-4">
            Мастера не найдены
          </p>
          <p class="text-gray-500">
            Попробуйте изменить параметры поиска
          </p>
        </div>

        <!-- Grid с опциональным виртуальным скроллингом -->
        <VirtualScroll
          v-else-if="props.enableVirtualScroll && masters.length > 20"
          :items="masters"
          :item-height="350"
          :container-height="props.virtualScrollHeight"
          :buffer="2"
          :has-more="hasMoreMasters"
          :loading="loadingMore"
          mode="grid"
          :grid-columns="3"
          @load-more="handleLoadMore"
        >
          <template #item="{ item: master, index }">
            <slot
              name="master"
              :master="master"
              :index="index"
            >
              <MasterCard :master="master" :index="index" />
            </slot>
          </template>
        </VirtualScroll>
        
        <!-- Обычный Grid или List (для небольшого количества элементов) -->
        <div v-else-if="props.viewMode === 'grid'" class="masters-grid">
          <slot
            v-for="(master, index) in masters"
            :key="master.id"
            name="master"
            :master="master"
            :index="index"
          >
            <MasterCard :master="master" :index="index" />
          </slot>
        </div>
        
        <!-- Режим списка -->
        <MasterCardList
          v-else-if="props.viewMode === 'list'"
          :masters="masters"
          :view-mode="'list'"
        />

        <!-- Pagination -->
        <div v-if="showPagination" class="mt-8">
          <slot name="pagination" />
        </div>
      </section>
    </div>
  </div>
  
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import MasterCard from '@/src/entities/master/ui/MasterCard/MasterCard.vue'
import { MasterCardSkeleton } from '@/src/entities/master/ui/MasterCardSkeleton'
import { FilterPanel, FilterCategory } from '@/src/features/masters-filter'
import { FilterPanelSkeleton } from '@/src/features/masters-filter/ui/FilterPanelSkeleton'
import { useFilterStore } from '@/src/features/masters-filter/model'
import BaseCheckbox from '@/src/shared/ui/atoms/BaseCheckbox/BaseCheckbox.vue'
import SecondaryButton from '@/src/shared/ui/atoms/SecondaryButton/SecondaryButton.vue'
import GridControls from '@/src/shared/ui/molecules/GridControls/GridControls.vue'
import type { GridView } from '@/src/shared/ui/molecules/GridControls/GridControls.vue'
import { VirtualScroll } from '@/src/shared/ui/organisms'
import { MasterCardList } from '@/src/entities/master/ui/MasterCard'

interface Props {
  masters?: any[]
  loading?: boolean
  error?: string
  showPagination?: boolean
  availableCategories?: any[]
  enableVirtualScroll?: boolean // Включить виртуальный скроллинг
  virtualScrollHeight?: number // Высота контейнера виртуального скролла
  viewMode?: GridView // Текущий режим отображения
}

const props = withDefaults(defineProps<Props>(), {
    masters: () => [],
    loading: false,
    error: '',
    showPagination: false,
    availableCategories: () => [],
    enableVirtualScroll: false,
    virtualScrollHeight: 800,
    viewMode: 'grid'
})

const emit = defineEmits<{
  retry: []
  filtersApply: [filters: any]
  filtersReset: []
  sortingChanged: [sorting: string]
  loadMore: [] // Для подгрузки новых данных
  viewChange: [view: GridView] // Изменение режима отображения
}>()

// Store для фильтров
const filterStore = useFilterStore()

// Состояние для виртуального скролла
const hasMoreMasters = ref(true) // Есть ли еще мастера для загрузки
const loadingMore = ref(false) // Загружаются ли новые мастера

// Обработчики фильтров
const handleFiltersApply = () => {
    emit('filtersApply', filterStore.filters)
}

const handleFiltersReset = () => {
    filterStore.resetFilters()
    emit('filtersReset')
}

// Проверка выбранной категории
const isCategorySelected = (categoryId: number): boolean => {
    return filterStore.filters.services.includes(categoryId)
}

// Обработчик изменения категории
const handleCategoryToggle = (categoryId: number, checked: boolean) => {
    if (checked) {
        filterStore.addServiceToFilter(categoryId)
    } else {
        filterStore.removeServiceFromFilter(categoryId)
    }
}

// Текущая сортировка из store
const currentSort = computed(() => {
    // Маппинг значений из store на значения GridControls
    const sortMap: { [key: string]: string } = {
        'relevance': 'popular',
        'rating': 'rating',
        'price_asc': 'price-asc',
        'price_desc': 'price-desc'
    }
    return sortMap[filterStore.filters.sorting as string] || 'popular'
})

// Обработчик изменения сортировки
const handleSortChange = (newSort: string) => {
    // Маппинг значений из GridControls в store
    const storeMap: { [key: string]: string } = {
        'popular': 'relevance',
        'rating': 'rating',
        'price-asc': 'price_asc',
        'price-desc': 'price_desc',
        'date': 'relevance' // Пока нет сортировки по дате
    }
    
    const storeValue = storeMap[newSort] || 'relevance'
    filterStore.setSorting(storeValue)
    
    // Эмитим событие для родительского компонента
    emit('sortingChanged', storeValue)
}

// Обработчик подгрузки для виртуального скролла
const handleLoadMore = () => {
    if (!loadingMore.value && hasMoreMasters.value) {
        loadingMore.value = true
        emit('loadMore')
        // Сброс флага после загрузки происходит в родительском компоненте
        setTimeout(() => {
            loadingMore.value = false
        }, 1000)
    }
}

// Обработчик изменения режима отображения
const handleViewChange = (view: GridView) => {
    emit('viewChange', view)
}
</script>

<style scoped>
/* Grid с auto-fill для адаптивного размещения карточек */
.masters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 24px;
}

/* Мобильная версия - 1 колонка */
@media (max-width: 640px) {
  .masters-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
}

/* Планшет - минимум 2 колонки */
@media (min-width: 641px) and (max-width: 1024px) {
  .masters-grid {
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  }
}
</style>
