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
  
  <!-- Контейнер с отступами -->
  <div class="container mx-auto px-4 py-8 max-w-7xl">
    <div class="flex gap-8">
      <!-- Левая колонка — фильтры с отступом -->
      <aside class="w-[300px] shrink-0">
        <div class="sticky top-4">
          <SidebarColumn>
            <Filters :filters="filters" :cities="cities" />
          </SidebarColumn>
        </div>
      </aside>
      
      <!-- Правая колонка — карта и карточки с отступами -->
      <section class="flex-1 space-y-6 min-w-0">
        <!-- Заголовок -->
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-900 mb-2">
            СПА-услуги в Москве
          </h1>
          <p class="text-gray-600">
            {{ cards.length }} {{ pluralize(cards.length, ['объявление', 'объявления', 'объявлений']) }}
          </p>
        </div>
        
        <!-- Карта с отступами -->
        <div class="bg-white rounded-xl shadow-sm mb-6 overflow-hidden">
          <div class="h-[300px]">
            <Map :cards="cards" />
          </div>
        </div>
        
        <!-- Карточки -->
        <div class="bg-white rounded-xl shadow-sm p-6">
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