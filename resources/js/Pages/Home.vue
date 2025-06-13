<script setup>
import { Head } from '@inertiajs/vue3'
import Cards from '@/Components/Cards.vue'
import Map from '@/Components/Map.vue' 
import Filters from '@/Components/Filters.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import SidebarColumn from '@/Components/SidebarColumn.vue'

// Добавляем значения по умолчанию для props
const props = defineProps({
  cards: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  cities: {
    type: Array,
    default: () => []
  }
})

defineOptions({ layout: AppLayout })
</script>

<template>
  <Head title="СПА-услуги, массаж в Москве | Услуги на SPA.COM" />
  
  <div class="p-8">
    <div class="flex gap-8">
      <!-- Фильтры -->
      <aside class="w-[300px] shrink-0">
        <div class="sticky top-4">
          <div class="bg-gray-50/80 backdrop-blur-sm rounded-xl p-6 border border-gray-200/50">
            <Filters :filters="filters" :cities="cities" />
          </div>
        </div>
      </aside>
      
      <!-- Контент -->
      <section class="flex-1 space-y-6 min-w-0">
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-2">СПА-услуги в Москве</h1>
          <p class="text-gray-600">{{ cards.length }} объявлений</p>
        </div>
        
        <!-- Карта -->
        <div class="bg-gray-50/80 backdrop-blur-sm rounded-xl overflow-hidden border border-gray-200/50">
          <div class="h-[300px]">
            <Map :cards="cards" />
          </div>
        </div>
        
        <!-- Карточки -->
        <div class="bg-gray-50/80 backdrop-blur-sm rounded-xl p-6 border border-gray-200/50">
          <Cards :cards="cards" />
        </div>
      </section>
    </div>
  </div>
</template>

<script>
export default {
  methods: {
    pluralize(count, forms) {
      const cases = [2, 0, 1, 1, 1, 2]
      return forms[(count % 100 > 4 && count % 100 < 20) ? 2 : cases[(count % 10 < 5) ? count % 10 : 5]]
    }
  }
}
</script>

<style scoped>
/* Дополнительные стили для отладки */
.container {
  background: #f8fafc; /* Серый фон для видимости отступов */
}
</style>