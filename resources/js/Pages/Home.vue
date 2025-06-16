<script setup>
import { Head } from '@inertiajs/vue3'
import MasterCard from '@/Components/Cards/Cards.vue'
import SimpleMap from '@/Components/Map/SimpleMap.vue'
import Filters from '@/Components/Filters/Filters.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SidebarColumn from '@/Components/Layout/SidebarColumn.vue'

const props = defineProps({
  masters: {
    type: Object,
    default: () => ({ data: [] })
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  categories: {
    type: Array,
    default: () => []
  },
  currentCity: {
    type: String,
    default: 'Москва'
  }
})

defineOptions({ layout: AppLayout })
</script>

<template>
  <div>
    <Head :title="`Массаж в ${currentCity} - найти мастера`" />
    
    <div class="flex gap-8">
      <!-- Левая колонка с фильтрами -->
      <aside class="w-[300px] shrink-0">
        <SidebarColumn>
          <Filters 
            :filters="filters" 
            :categories="categories" 
          />
        </SidebarColumn>
      </aside>
      
      <!-- Правая колонка — карта и карточки -->
      <section class="flex-1 space-y-6">
        <!-- Карта -->
        <div class="rounded-xl shadow mb-2 flex items-center justify-center min-h-[200px] bg-white">
          <SimpleMap 
            :masters="masters.data"
            :center="{ lat: 55.7558, lng: 37.6173 }"
          />
        </div>
        
        <!-- Карточки мастеров -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <MasterCard 
            v-for="master in masters.data"
            :key="master.id"
            :master="master"
          />
        </div>
        
        <!-- Пустое состояние -->
        <div v-if="!masters.data?.length" class="bg-white rounded-xl shadow p-12 text-center">
          <p class="text-gray-500">Мастера не найдены</p>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
/* Адаптивность для мобильных */
@media (max-width: 768px) {
  .flex {
    flex-direction: column;
  }
  
  aside {
    width: 100%;
    margin-bottom: 1rem;
  }
}
</style>