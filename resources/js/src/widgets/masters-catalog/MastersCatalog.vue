<template>
  <div class="masters-catalog">
    <!-- Filters -->
    <div class="mb-6">
      <slot name="filters">
        <FilterPanel @apply="handleFiltersApply" @reset="handleFiltersReset">
          <FilterCategory 
            title="РљР°С‚РµРіРѕСЂРёРё СѓСЃР»СѓРі"
            icon="рџЏ·пёЏ"
            :count="filterStore.filters.services.length"
          >
            <!-- Р—РґРµСЃСЊ Р±СѓРґРµС‚ СЃРѕРґРµСЂР¶РёРјРѕРµ С„РёР»СЊС‚СЂР° РєР°С‚РµРіРѕСЂРёР№ -->
            <div class="space-y-2">
              <label v-for="category in availableCategories" :key="category.id" class="flex items-center">
                <input 
                  type="checkbox" 
                  :checked="isCategorySelected(category.id)"
                  @change="handleCategoryChange(category.id, $event)"
                  class="mr-2"
                />
                {{ category.name }}
              </label>
            </div>
          </FilterCategory>
        </FilterPanel>
      </slot>
    </div>
    
    <!-- Loading -->
    <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="i in 6" :key="i" class="animate-pulse">
        <div class="h-64 bg-gray-200 rounded-lg"></div>
      </div>
    </div>
    
    <!-- Error -->
    <div v-else-if="error" class="text-center py-12">
      <p class="text-red-500 mb-4">{{ error }}</p>
      <button @click="$emit('retry')" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
        РџРѕРїСЂРѕР±РѕРІР°С‚СЊ СЃРЅРѕРІР°
      </button>
    </div>
    
    <!-- Empty -->
    <div v-else-if="!masters || masters.length === 0" class="text-center py-12">
      <p class="text-gray-500 text-lg mb-4">РњР°СЃС‚РµСЂР° РЅРµ РЅР°Р№РґРµРЅС‹</p>
      <p class="text-gray-400">РџРѕРїСЂРѕР±СѓР№С‚Рµ РёР·РјРµРЅРёС‚СЊ РїР°СЂР°РјРµС‚СЂС‹ РїРѕРёСЃРєР°</p>
    </div>
    
    <!-- Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <slot v-for="master in masters" :key="master.id" name="master" :master="master">
        <MasterCard :master="master" />
      </slot>
    </div>
    
    <!-- Pagination -->
    <div v-if="showPagination" class="mt-8">
      <slot name="pagination" />
    </div>
  </div>
</template>

<script setup lang="ts">
import MasterCard from '@/src/entities/master/ui/MasterCard/MasterCard.vue'
import { FilterPanel, FilterCategory } from '@/src/features/masters-filter'
import { useFilterStore } from '@/src/features/masters-filter/model'

interface Props {
  masters?: any[]
  loading?: boolean
  error?: string
  showPagination?: boolean
  availableCategories?: any[]
}

const _props = withDefaults(defineProps<Props>(), {
  masters: () => [],
  loading: false,
  error: '',
  showPagination: false,
  availableCategories: () => []
})

const emit = defineEmits<{
  retry: []
  filtersApply: [filters: any]
  filtersReset: []
}>()

// Store РґР»СЏ С„РёР»СЊС‚СЂРѕРІ
const filterStore = useFilterStore()

// РћР±СЂР°Р±РѕС‚С‡РёРєРё С„РёР»СЊС‚СЂРѕРІ
const handleFiltersApply = () => {
  emit('filtersApply', filterStore.filters)
}

const handleFiltersReset = () => {
  filterStore.resetFilters()
  emit('filtersReset')
}

// РњРµС‚РѕРґС‹ РґР»СЏ СЂР°Р±РѕС‚С‹ СЃ РєР°С‚РµРіРѕСЂРёСЏРјРё
const isCategorySelected = (categoryId: number): boolean => {
  // РџСЂРѕСЃС‚Р°СЏ РїСЂРѕРІРµСЂРєР° - СЃС‡РёС‚Р°РµРј С‡С‚Рѕ РєР°С‚РµРіРѕСЂРёСЏ РІС‹Р±СЂР°РЅР° РµСЃР»Рё РµСЃС‚СЊ С…РѕС‚СЊ РѕРґРёРЅ СЃРµСЂРІРёСЃ
  return filterStore.filters.services.includes(categoryId)
}

const handleCategoryChange = (categoryId: number, event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.checked) {
    filterStore.addServiceToFilter(categoryId)
  } else {
    filterStore.removeServiceFromFilter(categoryId)
  }
}
</script>

