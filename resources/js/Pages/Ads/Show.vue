<!-- resources/js/Pages/Ads/Show.vue - FSD Refactored с Loading состояниями -->
<template>
  <MainLayout>
    <!-- Loading состояние -->
    <PageLoader
      v-if="pageLoader.isLoading.value"
      type="detail"
      :message="pageLoader.message.value"
      :show-progress="false"
      :skeleton-count="1"
    />

    <!-- Основной контент -->
    <AdDetail
      v-else
      :ad="ad"
      @detail-loading="handleDetailLoading"
      @gallery-loading="handleGalleryLoading"
      @reviews-loading="handleReviewsLoading"
      @content-loaded="handleContentLoaded"
    />
  </MainLayout>
</template>

<script setup lang="ts">
import { logger } from '@/src/shared/lib/logger'
import { onMounted } from 'vue'
import MainLayout from '@/src/shared/layouts/MainLayout/MainLayout.vue'
import AdDetail from '@/src/widgets/ad-detail/AdDetail.vue'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// Типизация props
interface Ad {
  id: number
  title: string
  name?: string
  avatar?: string
  specialty?: string
  description?: string
  rating?: number
  reviews_count?: number
  services?: any[]
  photos?: any[]
  location?: string
  [key: string]: any
}

interface AdDetailProps {
  ad: Ad
  isOwner?: boolean
}

const props = defineProps<AdDetailProps>()

// Управление загрузкой страницы
const pageLoader = usePageLoading({
    type: 'detail',
    autoStart: true,
    timeout: 12000,
    onStart: () => {
    // Ad detail loading started
    },
    onComplete: () => {
    // Ad detail loading completed
    },
    onError: (error) => {
        logger.error('Ad detail loading error:', error)
    }
})

// Обработчики загрузки разных секций профиля
const handleDetailLoading = (): void => {
    pageLoader.setProgress(25, 'Загружаем информацию об объявлении...')
}

const handleGalleryLoading = (): void => {
    pageLoader.setProgress(50, 'Загружаем галерею фото...')
}

const handleReviewsLoading = (): void => {
    pageLoader.setProgress(75, 'Загружаем отзывы...')
}

const handleContentLoaded = (): void => {
    pageLoader.setProgress(95, 'Финализация...')
    setTimeout(() => {
        pageLoader.completeLoading()
    }, 300)
}

// Логика загрузки при монтировании
onMounted(() => {
    // Проверяем наличие базовых данных объявления
    if (!props.ad || !props.ad.id) {
        const noDataError = {
            type: 'client' as const,
            message: 'Данные объявления не найдены',
            code: 404
        }
        pageLoader.errorLoading(noDataError)
        return
    }

    // Поэтапная загрузка разных секций
    setTimeout(() => {
        pageLoader.setProgress(20, 'Обрабатываем данные объявления...')
    }, 400)

    setTimeout(() => {
    // Проверяем наличие фотографий
        if (props.ad.photos && props.ad.photos.length > 0) {
            pageLoader.setProgress(45, 'Загружаем фотографии...')
        } else {
            pageLoader.setProgress(45, 'Подготавливаем объявление...')
        }
    }, 800)

    setTimeout(() => {
    // Проверяем наличие отзывов
        if (props.ad.reviews_count && props.ad.reviews_count > 0) {
            pageLoader.setProgress(70, 'Загружаем отзывы клиентов...')
        } else {
            pageLoader.setProgress(70, 'Обрабатываем услуги...')
        }
    }, 1200)

    setTimeout(() => {
        pageLoader.setProgress(90, 'Подготавливаем к отображению...')
    }, 1600)

    setTimeout(() => {
        pageLoader.completeLoading()
    }, 2000)
})
</script>

<style scoped>
/* Стили страницы объявления с анимациями */
.ad-detail-enter-active,
.ad-detail-leave-active {
  transition: all 0.4s ease;
}

.ad-detail-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.ad-detail-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}

/* Специальная анимация для детальной страницы */
.detail-fade-enter-active {
  transition: opacity 0.6s ease, transform 0.6s ease;
}

.detail-fade-enter-from {
  opacity: 0;
  transform: scale(0.98);
}
</style>
