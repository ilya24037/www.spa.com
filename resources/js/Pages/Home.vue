<!-- resources/js/Pages/Home.vue -->
<template>
  <div>
    <Head :title="`Массаж в ${currentCity} - найти мастера`" />
    
    <!-- Хлебные крошки -->
    <Breadcrumbs :items="[
      { title: 'Главная', href: '/' },
      { title: 'Мастера массажа', href: '/masters' },
      { title: currentCity }
    ]" />
    
    <!-- Заголовок и количество -->
    <div class="mb-4">
      <h1 class="text-2xl font-bold">
        Мастера массажа в {{ currentCity }}
        <span class="text-gray-500 text-lg ml-2">({{ masters.total || 0 }})</span>
      </h1>
    </div>
    
    <!-- Быстрые фильтры -->
    <QuickTagsRow class="mb-6" />
    
    <!-- Основной контент -->
    <div class="flex gap-6">
      <!-- FiltersSidebar -->
      <aside class="w-64 shrink-0 hidden lg:block">
        <div class="bg-white rounded-lg p-4 shadow-sm sticky" style="top: 110px;">
          <Filters 
            :filters="filters" 
            :categories="categories" 
          />
        </div>
      </aside>
      
      <!-- Контент -->
      <section class="flex-1">
        <!-- Сортировка -->
        <div class="flex justify-between items-center mb-4">
          <span class="text-sm text-gray-600">
            Найдено {{ masters.data?.length || 0 }} мастеров
          </span>
          
          <select 
            v-model="sortBy"
            @change="applySort"
            class="px-3 py-2 border rounded-lg text-sm"
          >
            <option value="popular">Популярные</option>
            <option value="price_asc">Сначала дешевле</option>
            <option value="price_desc">Сначала дороже</option>
            <option value="rating">По рейтингу</option>
            <option value="distance">По расстоянию</option>
          </select>
        </div>
        
        <!-- Карта -->
        <div class="rounded-lg shadow-sm mb-6 h-[300px] bg-white overflow-hidden">
          <SimpleMap 
            :cards="masters.data"
            :center="{ lat: 55.7558, lng: 37.6173 }"
          />
        </div>
        
        <!-- ProductGrid для карточек -->
        <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
          <MasterCard 
            v-for="master in masters.data"
            :key="master.id"
            :master="master"
          />
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import Breadcrumbs from '@/Components/Common/Breadcrumbs.vue'
import QuickTagsRow from '@/Components/Filters/QuickTagsRow.vue'
import MasterCard from '@/Components/Cards/MasterCard.vue'
import SimpleMap from '@/Components/Map/SimpleMap.vue'
import Filters from '@/Components/Filters/Filters.vue'

const props = defineProps({
  masters: Object,
  filters: Object,
  categories: Array,
  currentCity: String
})

const sortBy = ref('popular')

const applySort = () => {
  router.reload({
    data: { sort: sortBy.value },
    preserveState: true,
    preserveScroll: true
  })
}
</script>