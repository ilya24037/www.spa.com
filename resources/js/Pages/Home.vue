<!-- resources/js/Pages/Home.vue - FSD Refactored с Loading состояниями -->
<template>
  <div class="py-6 lg:py-8">
    <Head :title="`Массаж в ${currentCity} — найти мастера`" />
    
    <!-- Loading состояние -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="catalog"
      :message="pageLoader.message.value"
      :show-progress="false"
      :skeleton-count="6"
    />
    
    <!-- Основной контент -->
    <template v-else>
      <!-- Хлебные крошки -->
      <Breadcrumbs :items="breadcrumbs" class="mb-6" />
      
      <MastersCatalog 
        :masters="masters.data || []"
        :available-categories="categories"
        :loading="pageLoader.isLoading.value"
        @loading-start="handleCatalogLoading"
        @loading-complete="handleCatalogComplete"
        @filters-apply="handleFiltersApply"
        @filters-reset="handleFiltersReset"
      />
    </template>
  </div>
</template>

<script setup lang="ts">
import { logger } from '@/src/shared/lib/logger'
import { Head } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'

// FSD импорты согласно плану
import Breadcrumbs from '@/src/shared/ui/molecules/Breadcrumbs/Breadcrumbs.vue'
import { MastersCatalog } from '@/src/widgets/masters-catalog'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// Props из Inertia с типизацией
interface HomePageProps {
  masters: {
    data: any[]
    links?: any
    meta?: any
  }
  currentCity: string
  categories: any[]
}

const props = withDefaults(defineProps<HomePageProps>(), {
  currentCity: 'Пермь',
  categories: () => []
})

// Управление загрузкой страницы
const pageLoader = usePageLoading({
  type: 'catalog',
  autoStart: true,
  timeout: 10000,
  onStart: () => {
    // Home page loading started
  },
  onComplete: () => {
    // Home page loading completed
  },
  onError: (error) => {
    logger.error('Home page loading error:', error)
  }
})

// Вычисляемые свойства
const breadcrumbs = computed(() => [
  { title: 'Главная', href: '/' },
  { title: 'Массажисты', href: '/masters' },
  { title: props.currentCity }
])

// Обработчики загрузки каталога
const handleCatalogLoading = (): void => {
  pageLoader.setProgress(50, 'Загружаем данные мастеров...')
}

const handleCatalogComplete = (): void => {
  pageLoader.completeLoading()
}

// Обработчики фильтров
const handleFiltersApply = (filters: any): void => {
  logger.info('Применение фильтров:', filters)
  // TODO: Здесь будет запрос к API с фильтрами
  pageLoader.setProgress(30, 'Применяем фильтры...')
}

const handleFiltersReset = (): void => {
  logger.info('Сброс фильтров')
  // TODO: Здесь будет сброс фильтров и загрузка всех мастеров
}

// Завершаем загрузку при монтировании, если данные уже есть
onMounted(() => {
  // Проверяем, есть ли данные
  if (props.masters?.data && props.masters.data.length > 0) {
    setTimeout(() => {
      pageLoader.completeLoading()
    }, 800) // Небольшая задержка для плавности
  } else {
    // Если данных нет, продолжаем показывать загрузку
    pageLoader.setProgress(30, 'Поиск мастеров в вашем городе...')
    
    // Симулируем загрузку данных
    setTimeout(() => {
      pageLoader.setProgress(70, 'Обработка результатов...')
      setTimeout(() => {
        pageLoader.completeLoading()
      }, 1000)
    }, 1500)
  }
})
</script>

<style scoped>
/* Стили для плавных переходов */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>