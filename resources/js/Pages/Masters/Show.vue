<!-- resources/js/Pages/Masters/Show.vue - FSD Refactored с Loading состояниями -->
<template>
  <MainLayout>
    <!-- Loading состояние -->
    <PageLoader 
      v-if="pageLoader.isLoading.value"
      type="profile"
      :message="pageLoader.message.value"
      :show-progress="false"
      :skeleton-count="1"
    />
    
    <!-- Основной контент -->
    <MasterProfile 
      v-else
      :master="master" 
      @profile-loading="handleProfileLoading"
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
import MasterProfile from '@/src/widgets/master-profile/MasterProfile.vue'
import PageLoader from '@/src/shared/ui/organisms/PageLoader/PageLoader.vue'
import { usePageLoading } from '@/src/shared/composables/usePageLoading'

// Типизация props
interface Master {
  id: number
  name: string
  display_name?: string
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

interface MasterProfileProps {
  master: Master
}

const props = defineProps<MasterProfileProps>()

// Управление загрузкой страницы
const pageLoader = usePageLoading({
  type: 'profile',
  autoStart: true,
  timeout: 12000,
  onStart: () => {
    // Master profile loading started
  },
  onComplete: () => {
    // Master profile loading completed
  },
  onError: (error) => {
    logger.error('Master profile loading error:', error)
  }
})

// Обработчики загрузки разных секций профиля
const handleProfileLoading = (): void => {
  pageLoader.setProgress(25, 'Загружаем информацию о мастере...')
}

const handleGalleryLoading = (): void => {
  pageLoader.setProgress(50, 'Загружаем галерею работ...')
}

const handleReviewsLoading = (): void => {
  pageLoader.setProgress(75, 'Загружаем отзывы...')
}

const handleContentLoaded = (): void => {
  pageLoader.setProgress(95, 'Финализация профиля...')
  setTimeout(() => {
    pageLoader.completeLoading()
  }, 300)
}

// Логика загрузки при монтировании
onMounted(() => {
  // Проверяем наличие базовых данных мастера
  if (!props.master || !props.master.id) {
    const noDataError = {
      type: 'client' as const,
      message: 'Данные мастера не найдены',
      code: 404
    }
    pageLoader.errorLoading(noDataError)
    return
  }

  // Поэтапная загрузка разных секций
  setTimeout(() => {
    pageLoader.setProgress(20, 'Обрабатываем данные профиля...')
  }, 400)

  setTimeout(() => {
    // Проверяем наличие фотографий
    if (props.master.photos && props.master.photos.length > 0) {
      pageLoader.setProgress(45, 'Загружаем фотографии...')
    } else {
      pageLoader.setProgress(45, 'Подготавливаем профиль...')
    }
  }, 800)

  setTimeout(() => {
    // Проверяем наличие отзывов
    if (props.master.reviews_count && props.master.reviews_count > 0) {
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
/* Стили страницы мастера с анимациями */
.master-profile-enter-active,
.master-profile-leave-active {
  transition: all 0.4s ease;
}

.master-profile-enter-from {
  opacity: 0;
  transform: translateY(30px);
}

.master-profile-leave-to {
  opacity: 0;
  transform: translateY(-30px);
}

/* Специальная анимация для профиля */
.profile-fade-enter-active {
  transition: opacity 0.6s ease, transform 0.6s ease;
}

.profile-fade-enter-from {
  opacity: 0;
  transform: scale(0.98);
}
</style>