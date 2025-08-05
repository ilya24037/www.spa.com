<template>
  <Head title="Редактирование объявления" />
  
  <div class="min-h-screen bg-gray-50">
    <!-- Loading состояние -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="form"
      :message="pageLoader.message.value"
      :show-progress="false"
      :skeleton-count="1"
    />
    
    <!-- Основной контент -->
    <div v-else class="max-w-4xl mx-auto py-6 lg:py-8">
      <!-- Хлебные крошки в стиле Avito -->
      <nav class="flex items-center mb-6" aria-label="Breadcrumb">
        <button 
          @click="goBack"
          class="flex items-center text-gray-500 hover:text-gray-700 transition-colors mr-4"
        >
          <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
          </svg>
          Назад
        </button>
        
        <ol class="flex items-center space-x-2 text-sm">
          <li>
            <Link 
              href="/profile/items/active/all"
              class="text-gray-500 hover:text-gray-700 transition-colors"
            >
              Мои объявления
            </Link>
          </li>
          <li class="text-gray-400">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
            </svg>
          </li>
          <li>
            <span class="text-gray-900 font-medium">Редактирование объявления</span>
          </li>
        </ol>
      </nav>
      
      <!-- Основной контент -->
      <div class="bg-white rounded-lg shadow-sm">
        <!-- Заголовок страницы -->
        <div class="px-6 py-4 border-b border-gray-200">
          <h1 class="text-2xl font-bold text-gray-900">Редактирование объявления</h1>
          <p class="text-sm text-gray-600 mt-1">
            Внесите изменения в ваше объявление. Все поля с * обязательны для заполнения.
          </p>
        </div>
        
        <!-- Форма редактирования -->
        <div class="p-6">
          <AdForm 
            :category="ad.category || 'massage'"
            :categories="[]"
            :ad-id="ad.id"
            :initial-data="ad"
            @success="handleSuccess"
            @form-loading="handleFormLoading"
            @data-loaded="handleDataLoaded"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { logger } from '@/src/shared/lib/logger'
import { Head, Link, router } from '@inertiajs/vue3'
import { onMounted } from 'vue'

// 🎯 FSD Импорты
import AdForm from '@/src/entities/ad/ui/AdForm/AdForm.vue'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// Типизация объявления
interface Ad {
  id: number | string
  title?: string
  name?: string
  description?: string
  category?: string
  price?: number
  location?: string
  photos?: any[]
  services?: any[]
  [key: string]: any
}

interface EditAdProps {
  ad: Ad
}

const props = defineProps<EditAdProps>()

// Управление загрузкой страницы
const pageLoader = usePageLoading({
  type: 'form',
  autoStart: true,
  timeout: 10000,
  onStart: () => {
    // EditAd loading started
  },
  onComplete: () => {
    // EditAd loading completed
  },
  onError: (error) => {
    logger.error('EditAd loading error:', error)
  }
})

// Обработчики загрузки формы
const handleFormLoading = (): void => {
  pageLoader.setProgress(50, 'Подготавливаем форму редактирования...')
}

const handleDataLoaded = (): void => {
  pageLoader.setProgress(90, 'Загружаем данные объявления...')
  setTimeout(() => {
    pageLoader.completeLoading()
  }, 300)
}

// Навигация
const goBack = (): void => {
  router.visit('/profile/items/active/all')
}

const handleSuccess = (): void => {
  router.visit('/profile/items/active/all')
}

// Инициализация при монтировании
onMounted(() => {
  // Проверяем наличие данных объявления
  if (!props.ad || !props.ad.id) {
    const noDataError = {
      type: 'client' as const,
      message: 'Данные объявления не найдены',
      code: 404
    }
    pageLoader.errorLoading(noDataError)
    return
  }

  // Поэтапная загрузка для лучшего UX
  setTimeout(() => {
    pageLoader.setProgress(30, 'Инициализируем редактор...')
  }, 200)

  setTimeout(() => {
    pageLoader.setProgress(60, 'Загружаем данные формы...')
  }, 600)

  setTimeout(() => {
    pageLoader.setProgress(85, 'Подготавливаем интерфейс...')
  }, 1000)

  setTimeout(() => {
    pageLoader.completeLoading()
  }, 1400)
})
</script> 