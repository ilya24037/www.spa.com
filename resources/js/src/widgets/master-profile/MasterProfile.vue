<template>
  <div class="master-profile">
    <!-- Loading с детальным skeleton -->
    <MasterProfileSkeleton v-if="loading" />
    
    <!-- Profile -->
    <div v-else-if="master">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-6">
          <div class="flex-shrink-0">
            <img
              :src="master.avatar || '/placeholder-avatar.jpg'"
              :alt="master.name"
              class="w-32 h-32 rounded-full object-cover"
            >
          </div>
          
          <div class="flex-1">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
              {{ master.name || 'Мастер' }}
            </h1>
            
            <p v-if="master.description" class="text-gray-600 mb-4">
              {{ master.description }}
            </p>
            
            <div class="flex flex-wrap gap-4">
              <span v-if="master.rating" class="flex items-center gap-1">
                <StarRating :rating="master.rating" :show-text="true" />
              </span>
              
              <span v-if="master.reviewsCount" class="text-gray-600">
                {{ master.reviewsCount }} отзывов
              </span>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Content slots -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <slot name="services" />
          <!-- Gallery with PhotoViewer -->
          <div v-if="galleryImages.length" class="mb-8">
            <h3 class="text-xl font-semibold mb-4">
              Галерея работ
            </h3>
            <PhotoThumbnails :images="galleryImages" />
          </div>
          
          <slot name="gallery" />
          <slot name="reviews" />
        </div>
        
        <div class="space-y-6">
          <slot name="booking" />
          <slot name="contacts" />
        </div>
      </div>
    </div>
    
    <!-- Empty -->
    <div v-else class="text-center py-12">
      <p class="text-gray-500">
        Информация о мастере недоступна
      </p>
    </div>
  </div>
  
  <!-- PhotoViewer - глобальный компонент -->
  <PhotoViewer />
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { StarRating } from '@/src/shared/ui/organisms'
import MasterProfileSkeleton from './MasterProfileSkeleton.vue'

// Gallery imports
import { PhotoThumbnails, PhotoViewer } from '@/src/features/gallery'

interface Master {
  id?: string | number
  name?: string
  avatar?: string
  description?: string
  rating?: number
  reviewsCount?: number
  photos?: Array<{
    id: string | number
    url: string
    thumbnail_url?: string
    alt?: string
    caption?: string
  }>
}

interface Props {
  master?: Master | null
  loading?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    master: null,
    loading: false
})

// Gallery images computed
const galleryImages = computed(() => {
    if (!props.master?.photos?.length) return []
  
    return props.master.photos.map((photo, index) => ({
        id: String(photo.id),
        url: photo.url,
        thumbnail: photo.thumbnail_url || photo.url,
        alt: photo.alt || `Фото мастера ${props.master?.name} ${index + 1}`,
        caption: photo.caption,
        type: 'photo' as const
    }))
})
</script>