<!-- Главная страница - полная FSD миграция -->
<template>
  <div>
    <Head :title="`Массаж в ${currentCity} — найти мастера`" />
    
    <!-- Заголовок -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">
        Мастера массажа в {{ currentCity }}
      </h1>
      <p class="text-gray-600 mt-2">
        Найдите лучших мастеров массажа в вашем городе
      </p>
    </div>
    
    <!-- MastersCatalog Widget - единая точка входа -->
    <MastersCatalog
      :masters="masters?.data || []"
      :categories="categories"
      :districts="districts"
      :current-city="currentCity"
      :loading="isLoading"
      :error="error"
      @filters-applied="handleFiltersApplied"
      @master-favorited="handleMasterFavorited"
      @booking-requested="handleBookingRequested"
      @retry="handleRetry"
    >
      <!-- Кастомный master card через slot -->
      <template #master="{ master }">
        <MasterCard 
          :master="master"
          :is-favorite="isFavorite(master.id)"
          @toggle-favorite="toggleFavorite"
          @booking="() => handleBooking(master.id)"
        />
      </template>
      
      <!-- Кастомная пагинация -->
      <template #pagination>
        <Pagination 
          v-if="masters?.links" 
          :links="masters.links" 
        />
      </template>
    </MastersCatalog>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'

// FSD imports
import { MastersCatalog } from '@/src/widgets/masters-catalog'
import { MasterCard } from '@/src/entities/master/ui/MasterCard'
import { Pagination } from '@/src/shared/ui/molecules/Pagination'
import { logger } from '@/src/shared/utils/logger'

// Stores - используем основные TypeScript stores
import { useFavoritesStore, type Master } from '@/stores/favorites'

// Props из Inertia 
interface HomePageProps {
  masters?: {
    data: Master[]
    links?: any
    meta?: any
  }
  currentCity?: string
  categories?: Category[]
  districts?: string[]
}

interface Category {
  id: number
  name: string
  slug: string
}

const props = withDefaults(defineProps<HomePageProps>(), {
  currentCity: 'Москва',
  categories: () => [],
  masters: () => ({ data: [] }),
  districts: () => []
})

// Stores
const favoritesStore = useFavoritesStore()

// Local state
const isLoading = ref(false)
const error = ref<string | null>(null)

// Computed
const favoriteIds = computed(() => favoritesStore.favoriteIds)

// Methods
const isFavorite = (masterId: number): boolean => {
  return favoriteIds.value.includes(masterId)
}

const toggleFavorite = async (masterId: number) => {
  try {
    const master = props.masters?.data.find(m => m.id === masterId)
    if (master) {
      await favoritesStore.toggle(master)
    }
  } catch (err) {
    logger.error('Error toggling favorite:', err)
    error.value = 'Ошибка при обновлении избранного'
  }
}

const handleBooking = (masterId: number) => {
  // Переход на страницу мастера с модальным окном бронирования
  window.location.href = `/masters/${masterId}?booking=true`
}

const handleFiltersApplied = (filters: any) => {
  // Применение фильтров через Inertia
  isLoading.value = true
  
  const url = new URL(window.location.href)
  Object.keys(filters).forEach(key => {
    if (filters[key] !== null && filters[key] !== '') {
      url.searchParams.set(key, filters[key])
    } else {
      url.searchParams.delete(key)
    }
  })
  
  window.history.pushState({}, '', url.toString())
  // Здесь должен быть Inertia.get() или router.get()
}

const handleMasterFavorited = (data: { masterId: number, isFavorite: boolean }) => {
  // Обновление состояния избранного из widget
  const master = props.masters?.data.find(m => m.id === data.masterId)
  if (master) {
    if (data.isFavorite) {
      favoritesStore.addToFavorites(master)
    } else {
      favoritesStore.removeFromFavorites(data.masterId)
    }
  }
}

const handleBookingRequested = (masterId: number) => {
  handleBooking(masterId)
}

const handleRetry = () => {
  error.value = null
  window.location.reload()
}

// Initialize favorites on mount
onMounted(() => {
  favoritesStore.loadFavorites()
})
</script>

<style scoped>
/* Стили специфичные для главной страницы */
</style>