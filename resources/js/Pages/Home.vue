<!-- resources/js/Pages/Home.vue -->
<template>
  <div>
    <!-- SEO‑title -->
    <Head :title="`Массаж в ${currentCity} — найти мастера`" />

    <!-- Хлебные крошки -->
    <Breadcrumbs
      :items="[
        { title: 'Главная', href: '/' },
        { title: 'Массажисты', href: '/masters' },
        { title: currentCity }
      ]"
      class="mb-4"
    />

    <!-- КНОПКА ФИЛЬТРОВ (мобилка) -->
    <button
      class="lg:hidden fixed bottom-6 right-6 z-40 bg-blue-600 text-white p-4 rounded-full shadow-lg"
      @click="showFilters = true"
    >
      Фильтры
    </button>

    <!-- Основная раскладка -->
    <div class="flex gap-6">
      <!-- Боковая панель фильтров -->
      <FiltersSidebar
        v-model:show-mobile="showFilters"
        @reset="resetFilters"
      >
        <PriceFilter      v-model="filters.price" />
        <ServiceFilter    v-model="filters.service" />
        <LocationFilter   v-model="filters.city" />
      </FiltersSidebar>

      <!-- Контент -->
      <main class="flex-1 space-y-6">
        <!-- Карта с мастерами (центр — Пермь) -->
        <SimpleMap
          :cards="masters.data"
          :center="{ lat: 58.0105, lng: 56.2502 }"
        />

        <!-- Сетка карточек мастеров -->
        <CardGrid :items="masters.data" />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { Head } from '@inertiajs/vue3'

/* ───── UI-компоненты ───── */
import Breadcrumbs     from '@/Components/UI/Breadcrumbs.vue'
import FiltersSidebar  from '@/Components/Filters/FiltersSidebar.vue'
import PriceFilter     from '@/Components/Filters/PriceFilter.vue'
import ServiceFilter   from '@/Components/Filters/ServiceFilter.vue'
import LocationFilter  from '@/Components/Filters/LocationFilter.vue'
import SimpleMap       from '@/Components/Maps/SimpleMap.vue'
import CardGrid        from '@/Components/Cards/CardGrid.vue'

/* ───── Пропсы от Inertia ───── */
const props = defineProps({
  masters:      Object,
  currentCity:  String,
})

/* ───── Локальное состояние ───── */
const showFilters = ref(false)
const filters = ref({
  price:   [null, null],
  service: null,
  city:    null,
})

function resetFilters () {
  filters.value = { price: [null, null], service: null, city: null }
}
</script>

<style scoped>
/* кастомная анимация drawer (подключено в SidebarWrapper.vue) */
.drawer-enter-active,
.drawer-leave-active { transition: transform .3s ease; }
.drawer-enter-from   { transform: translateX(100%); }
.drawer-enter-to     { transform: translateX(0); }
.drawer-leave-from   { transform: translateX(0); }
.drawer-leave-to     { transform: translateX(100%); }
</style>
