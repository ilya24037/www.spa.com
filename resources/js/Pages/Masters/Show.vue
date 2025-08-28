<template>
  <MainLayout>
    <Head :title="meta?.title || 'Профиль мастера'" />
    
    <!-- Основной контент - делегируем виджету MasterProfileDetailed -->
    <MasterProfileDetailed 
      :master="masterData"
      :loading="false"
    />
    
    <!-- Дополнительная секция похожих мастеров - используем готовый компонент -->
    <SimilarMastersSection 
      v-if="similarMasters?.length"
      :masters="similarMasters"
      :current-master="masterData"
      class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-12"
    />
  </MainLayout>
</template>

<script setup lang="ts">
import MainLayout from '@/src/shared/layouts/MainLayout/MainLayout.vue'
import { Head } from '@inertiajs/vue3'
import MasterProfileDetailed from '@/src/widgets/master-profile/MasterProfileDetailed.vue'
import SimilarMastersSection from '@/src/features/similar-masters/ui/SimilarMastersSection.vue'
import { computed } from 'vue'

// TypeScript интерфейсы
interface Master {
  id: number
  name: string
  avatar?: string
  rating?: number
  reviews_count?: number
  reviews?: any[]
  photos?: Array<{ 
    url: string
    thumbnail_url?: string
    alt?: string 
  }>
  services?: Array<{
    id: string | number
    name: string
    price: number
    duration?: number
  }>
  specialty?: string
  description?: string
  location?: string
  city?: string
  experience?: string
  completion_rate?: string
  views_count?: number
  [key: string]: any
}

// Props от Inertia
interface Props {
  master: Master
  gallery?: Array<any>
  meta?: {
    title?: string
    description?: string
    [key: string]: any
  }
  similarMasters?: Array<any>
  reviews?: Array<any>
}

const props = defineProps<Props>()

// Объединяем данные мастера с фотографиями из gallery
const masterData = computed(() => ({
  ...props.master,
  photos: props.gallery || props.master.photos || [],
  reviews: props.reviews || props.master.reviews || []
}))

// Вся логика отображения делегирована в MasterProfileDetailed widget
// Show.vue остается чистой страницей-оберткой по FSD архитектуре
</script>

<style scoped>
/* Минимальные стили для страницы-обертки */
</style>