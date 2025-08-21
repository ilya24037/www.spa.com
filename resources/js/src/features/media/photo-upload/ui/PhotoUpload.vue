<!-- Главный компонент загрузки фотографий -->
<template>
  <ErrorBoundary>
    <!-- Skeleton loader при загрузке -->
    <PhotoUploadSkeleton v-if="isLoading" />
    
    <!-- Основной контент (всегда показывается когда не loading) -->
    <section 
      v-else
      class="photo-upload space-y-4"
      role="region"
      aria-label="Загрузка и управление фотографиями"
    >
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-medium">Фотографии</h3>
      <span class="text-sm text-gray-500">
        {{ safePhotosCount }} из {{ maxFiles }}
      </span>
    </div>

    <UploadZone 
      ref="uploadZone"
      :has-content="safePhotosCount > 0"
      @files-selected="handleFilesSelected"
    >
      <!-- Контент когда есть фото -->
      <div v-if="safePhotosCount > 0" class="space-y-3">
        <!-- Сетка фотографий -->
        <PhotoGrid
          :photos="safePhotos"
          :dragged-index="draggedIndex"
          :drag-over-index="dragOverIndex"
          @update:photos="handlePhotosUpdate"
          @rotate="handleRotatePhoto"
          @remove="handleRemovePhoto"
          @dragstart="handleDragStart"
          @dragover="handleDragOver"
          @drop="onDragDrop"
          @dragend="onDragEnd"
        />
        
        <!-- Кнопка добавить еще -->
        <button 
          v-if="safePhotosCount < maxFiles"
          type="button"
          @click="uploadZone?.openFileDialog()"
          class="w-full py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
        >
          Добавить еще фото
        </button>
      </div>
    </UploadZone>
    
    <!-- ЧЕКБОКСЫ НАСТРОЕК (КРИТИЧЕСКИ ВАЖНО!) -->
    <MediaSettings
      v-if="safePhotosCount > 0"
      :show-additional-info="showAdditionalInfo"
      :show-services="showServices"
      :show-prices="showPrices"
      @update:show-additional-info="$emit('update:showAdditionalInfo', $event)"
      @update:show-services="$emit('update:showServices', $event)"
      @update:show-prices="$emit('update:showPrices', $event)"
    />
    
    <!-- Ошибки -->
    <div v-if="error" class="rounded-md bg-red-50 p-3">
      <p class="text-sm text-red-800">{{ error }}</p>
    </div>
    </section>
  </ErrorBoundary>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { usePhotoUpload } from '../composables/usePhotoUpload'
import UploadZone from './components/UploadZone.vue'
import PhotoGrid from './components/PhotoGrid.vue'
import MediaSettings from './components/MediaSettings.vue'
import PhotoUploadSkeleton from './components/PhotoUploadSkeleton.vue'
import EmptyState from './components/EmptyState.vue'
import ErrorBoundary from './components/ErrorBoundary.vue'
import type { PhotoUploadProps, PhotoUploadEmits, Photo } from '../model/types'

const props = withDefaults(defineProps<PhotoUploadProps>(), {
  photos: () => [],
  maxFiles: 10,
  showAdditionalInfo: false,
  showServices: false,
  showPrices: false,
  isLoading: false
})

const emit = defineEmits<PhotoUploadEmits>()

const uploadZone = ref<InstanceType<typeof UploadZone>>()

// Использование composable
const {
  localPhotos,
  error,
  isUploading,
  addPhotos,
  removePhoto,
  rotatePhoto,
  reorderPhotos,
  initializeFromProps,
  draggedIndex,
  dragOverIndex,
  handleDragStart,
  handleDragOver,
  handleDragDrop,
  handleDragEnd
} = usePhotoUpload()

// Computed для защиты от null/undefined (ТОЧНО как VideoUpload)
const safePhotos = computed(() => {
  // Явная проверка на null и undefined
  if (localPhotos.value === null || localPhotos.value === undefined) {
    return []
  }
  return localPhotos.value
})

const safePhotosCount = computed(() => {
  return safePhotos.value.length
})

const isLoading = computed(() => props.isLoading || isUploading.value)

// УПРОЩЕНИЕ по принципу KISS: только инициализация при первой загрузке
watch(() => props.photos, (newPhotos) => {
  console.log('🔄 PhotoUpload: watch props.photos', {
    newPhotosLength: newPhotos?.length,
    localPhotosLength: localPhotos.value?.length
  })
  
  // Инициализируем только если localPhotos пустой и есть новые фото
  if (localPhotos.value.length === 0 && 
      newPhotos && 
      newPhotos.length > 0) {
    console.log('✅ PhotoUpload: Инициализация из props')
    initializeFromProps(newPhotos)
  }
}, { immediate: true })

// УПРОЩЕНИЕ: простая обработка файлов
const handleFilesSelected = async (files: File[]) => {
  console.log('📁 PhotoUpload: handleFilesSelected', { count: files?.length })
  
  if (!files || files.length === 0) return
  
  // Проверка лимита
  if (safePhotosCount.value + files.length > props.maxFiles) {
    error.value = `Максимум ${props.maxFiles} фотографий`
    return
  }
  
  try {
    await addPhotos(files)
    emit('update:photos', safePhotos.value)
  } catch (err) {
    console.error('❌ PhotoUpload: Ошибка при загрузке:', err)
    error.value = 'Ошибка при загрузке фото'
  }
}

// Обработка изменений от PhotoGrid (мобильные кнопки)
const handlePhotosUpdate = (photos: Photo[]) => {
  if (!photos) return
  // Обновляем localPhotos
  localPhotos.value = photos
  // Эмитим изменения в AdForm ОДИН РАЗ
  emit('update:photos', photos)
}

const handleRotatePhoto = (index: number) => {
  if (index == null) return
  rotatePhoto(index)
  emit('update:photos', safePhotos.value)
}

const handleRemovePhoto = (index: number) => {
  if (index == null) return
  removePhoto(index)
  emit('update:photos', safePhotos.value)
}

// Wrapper для drag&drop с эмитом
const onDragDrop = (index: number) => {
  handleDragDrop(index)
  // Эмитим изменения после drag&drop
  emit('update:photos', safePhotos.value)
}

const onDragEnd = () => {
  handleDragEnd()
}
</script>