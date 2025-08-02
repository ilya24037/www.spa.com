<!-- resources/js/Pages/Home.vue - FSD Refactored согласно плану -->
<template>
  <MainLayout>
    <Head :title="`Массаж в ${currentCity} — найти мастера`" />
    
    <!-- Хлебные крошки -->
    <Breadcrumbs :items="breadcrumbs" class="mb-6" />
    
    <MastersCatalog 
      :initial-masters="masters.data || []"
      :current-city="currentCity"
      :available-categories="categories"
    />
  </MainLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

// 🎯 FSD Импорты согласно плану
import { MainLayout, Breadcrumbs } from '@/src/shared'
import { MastersCatalog } from '@/src/widgets/masters-catalog'

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

// Вычисляемые свойства
const breadcrumbs = computed(() => [
  { title: 'Главная', href: '/' },
  { title: 'Массажисты', href: '/masters' },
  { title: props.currentCity }
])
</script>

