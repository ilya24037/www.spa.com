<!-- resources/js/Pages/Home.vue - FSD Refactored с Loading состояниями -->
<template>
  <MainLayout>
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
        :initial-masters="masters.data || []"
        :current-city="currentCity"
        :available-categories="categories"
        @loading-start="handleCatalogLoading"
        @loading-complete="handleCatalogComplete"
      />
    </template>
  </MainLayout>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'

// 🎯 FSD Импорты согласно плану
import MainLayout from '@/src/shared/layouts/MainLayout/MainLayout.vue'
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
    console.log('Home page loading started')
  },
  onComplete: () => {
    console.log('Home page loading completed')
  },
  onError: (error) => {
    console.error('Home page loading error:', error)
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

