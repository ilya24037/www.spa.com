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


    <!-- Если нет фото - показываем основную зону -->
    <PhotoUploadZone
      v-if="safePhotosCount === 0"
      ref="uploadZone"
      :max-size="maxSize"
      :accepted-formats="acceptedFormats"
      @files-selected="handleFilesSelected"
    />
    
    <!-- Если есть фото - показываем сетку + доп зону -->
    <div v-else class="space-y-3">
      <!-- Обертка для сетки фото -->
      <div class="border-2 border-dashed border-gray-300 rounded-lg pt-4 px-4 pb-2">
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
      </div>
      
      <!-- Дополнительная зона загрузки (как у видео) -->
      <PhotoUploadZone
        v-if="safePhotosCount < props.maxFiles"
        ref="additionalUploadZone"
        :max-size="maxSize"
        :accepted-formats="acceptedFormats"
        @files-selected="handleFilesSelected"
      />
    </div>
    
    <!-- Информация об ограничениях -->
    <div class="text-sm text-gray-800 space-y-1">
      <p>• Минимум 3 фото, максимум 20</p>
      <p>• Без водяных знаков других сайтов</p>
      <p>• На фото не должны быть видны гениталии</p>
      <p>• Фото должны соответствовать услугам</p>
    </div>
    

    
    <!-- Ошибки -->
    <div v-if="error || validationError" class="rounded-md bg-red-50 p-3">
      <p v-if="error" class="text-sm text-red-800">{{ error }}</p>
      <p v-if="validationError" class="text-sm text-red-800">{{ validationError }}</p>
    </div>
    </section>
  </ErrorBoundary>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { usePhotoUpload } from '../composables/usePhotoUpload'
import UploadZone from './components/UploadZone.vue'
import PhotoUploadZone from './components/PhotoUploadZone.vue'
import PhotoGrid from './components/PhotoGrid.vue'

import PhotoUploadSkeleton from './components/PhotoUploadSkeleton.vue'
import EmptyState from './components/EmptyState.vue'
import ErrorBoundary from './components/ErrorBoundary.vue'
import type { PhotoUploadProps, PhotoUploadEmits, Photo } from '../model/types'

const props = withDefaults(defineProps<PhotoUploadProps>(), {
  photos: () => [],
  maxFiles: 20,
  isLoading: false,
  forceValidation: false
})

// Константы для PhotoUploadZone
const maxSize = 10 * 1024 * 1024 // 10MB (унифицировано с backend)
const acceptedFormats = [
  'image/jpeg',
  'image/png',
  'image/bmp',
  'image/gif',
  'image/webp',
  'image/heic',
  'image/heif'
]

const emit = defineEmits<PhotoUploadEmits>()

const uploadZone = ref<InstanceType<typeof PhotoUploadZone>>()
const additionalUploadZone = ref<InstanceType<typeof PhotoUploadZone>>()



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

// Computed для показа валидационной ошибки
const validationError = computed(() => {
  if (props.forceValidation && safePhotosCount.value === 0) {
    return 'Добавьте минимум 3 фотографии'
  }
  if (props.forceValidation && safePhotosCount.value < 3) {
    return `Добавьте еще ${3 - safePhotosCount.value} фото (минимум 3)`
  }
  return ''
})

const isLoading = computed(() => props.isLoading || isUploading.value)

// 🔍 ЛОГИРОВАНИЕ ДЛЯ ДИАГНОСТИКИ
console.log('🔍 PhotoUpload: инициализация');
console.log('  props.photos:', props.photos);
console.log('  props.photos_type:', typeof props.photos);
console.log('  props.photos_isArray:', Array.isArray(props.photos));
console.log('  props.photos_length:', props.photos?.length);

// УПРОЩЕНИЕ по принципу KISS: только инициализация при первой загрузке
watch(() => props.photos, (newPhotos) => {
  console.log('🔍 PhotoUpload: watch сработал');
  console.log('  newPhotos:', newPhotos);
  console.log('  localPhotos.value.length:', localPhotos.value.length);
  
  // КРИТИЧНО: Если newPhotos пустой массив, НЕ инициализируем localPhotos
  if (!newPhotos || newPhotos.length === 0) {
    console.log('  ❌ newPhotos пустой, НЕ инициализируем localPhotos');
    localPhotos.value = []
    return
  }
  
  // Инициализируем только если localPhotos пустой и есть новые фото
  if (localPhotos.value.length === 0 && 
      newPhotos && 
      newPhotos.length > 0) {
    console.log('  ✅ Инициализируем из props');
    initializeFromProps(newPhotos)
  } else {
    console.log('  ❌ Пропускаем инициализацию');
  }
}, { immediate: true })

// УПРОЩЕНИЕ: простая обработка файлов
const handleFilesSelected = async (files: File[]) => {
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
    error.value = 'Ошибка при загрузке фото'
  }
}

// Обработка изменений от PhotoGrid (мобильные кнопки)
const handlePhotosUpdate = (photos: Photo[]) => {
  console.log('🔍 handlePhotosUpdate: НАЧАЛО');
  console.log('  photos:', photos);
  console.log('  photos.length:', photos?.length);
  
  if (!photos) {
    console.log('  ❌ photos пустой, выходим');
    return
  }
  
  // Обновляем localPhotos
  localPhotos.value = photos
  console.log('  ✅ localPhotos.value обновлен:', localPhotos.value);
  
  // Эмитим изменения в AdForm ОДИН РАЗ
  emit('update:photos', photos)
  console.log('  ✅ emit update:photos вызван');
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
  // ✅ Эмитим изменения ОДИН РАЗ после drag&drop
  // handleDragDrop уже обновил localPhotos, поэтому эмитим safePhotos.value
  emit('update:photos', safePhotos.value)
}

const onDragEnd = () => {
  handleDragEnd()
}

// Следим за добавлением фотографий для сброса валидации
watch(() => safePhotosCount.value, (newCount) => {
  if (props.forceValidation && newCount >= 3) {
    emit('clear-force-validation')
  }
})

// Метод для открытия диалога выбора файлов
const openFileDialog = () => {
  if (safePhotosCount.value === 0) {
    // Основная зона
    uploadZone.value?.openFileDialog()
  } else {
    // Дополнительная зона
    additionalUploadZone.value?.openFileDialog()
  }
}

defineExpose({
  openFileDialog
})
</script>