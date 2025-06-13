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
  
  <div class="flex gap-8">
    <!-- Левая колонка — теперь через SidebarColumn -->
    <aside class="w-[300px] shrink-0">
      <SidebarColumn>
        <Filters :filters="filters" :cities="cities" />
      </SidebarColumn>
    </aside>
    
    <!-- Правая колонка — карта и карточки -->
    <section class="flex-1 space-y-6">
      <!-- Заголовок -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-2">
          СПА-услуги в Москве
        </h1>
        <p class="text-gray-600">
          {{ cards.length }} {{ pluralize(cards.length, ['объявление', 'объявления', 'объявлений']) }}
        </p>
      </div>
      
      <!-- Карта -->
      <div class="rounded-xl shadow mb-2 flex items-center justify-center min-h-[200px] bg-white">
        <Map :cards="cards" />
      </div>
      
      <!-- Карточки -->
      <Cards :cards="cards" />
    </section>
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
/* Дополнительные стили если нужны */
</style>