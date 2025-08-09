<template>
  <FormSection
    title="Медиа материалы"
    description="Загрузите фотографии и видео для вашего профиля"
    :model-value="modelValue"
    :errors="errors"
    :disabled="disabled"
    :readonly="readonly"
    :collapsible="collapsible"
    :collapsed="collapsed"
    @update:model-value="updateValue"
    @field-change="onFieldChange"
    @toggle="onToggle"
  >
    <!-- Фотографии -->
    <FormFieldGroup
      label="Фотографии"
      description="Добавьте качественные фотографии вашей работы и рабочего места"
      layout="column"
    >
      <div class="form-field">
        <label class="form-field-label">
          Фотографии <span class="text-red-500">*</span>
        </label>
        
        <!-- Область загрузки фотографий -->
        <PhotoUploadArea
          :has-error="hasError('photos')"
          :disabled="disabled"
          @files-selected="handlePhotoFiles"
        />
        
        <!-- Превью загруженных фотографий -->
        <MediaPreview
          v-if="photos.length > 0"
          :photos="photos"
          :videos="[]"
          :readonly="readonly"
          @remove-photo="removePhoto"
          @view-photo="viewPhoto"
        />
        
        <div v-if="hasError('photos')" class="text-red-500 text-sm mt-1">{{ getError('photos') }}</div>
      </div>
    </FormFieldGroup>

    <!-- Видео -->
    <FormFieldGroup
      label="Видео"
      description="Добавьте короткие видео демонстрирующие вашу работу"
      layout="column"
    >
      <div class="form-field">
        <label class="form-field-label">Видео (опционально)</label>
        
        <!-- Область загрузки видео -->
        <VideoUploadArea
          :has-error="hasError('videos')"
          :disabled="disabled"
          @files-selected="handleVideoFiles"
        />
        
        <!-- Превью загруженных видео -->
        <MediaPreview
          v-if="videos.length > 0"
          :photos="[]"
          :videos="videos"
          :readonly="readonly"
          @remove-video="removeVideo"
          @play-video="playVideo"
        />
        
        <div v-if="hasError('videos')" class="text-red-500 text-sm mt-1">{{ getError('videos') }}</div>
      </div>
    </FormFieldGroup>

    <!-- Настройки медиа -->
    <FormFieldGroup
      label="Настройки"
      description="Дополнительные настройки для медиа файлов"
      layout="column"
    >
      <FormField
        label="Качество изображений"
        field="image_quality"
        type="select"
        :model-value="modelValue?.image_quality || 'high'"
        :options="imageQualityOptions"
        :disabled="disabled"
        :readonly="readonly"
        @update:model-value="updateField('image_quality', $event)"
      />
      
      <FormField
        label="Автосжатие"
        field="auto_compress"
        type="switch"
        :model-value="modelValue?.auto_compress || true"
        :disabled="disabled"
        :readonly="readonly"
        @update:model-value="updateField('auto_compress', $event)"
      />
    </FormFieldGroup>
  </FormSection>
</template>

<script setup lang="ts">
import { ref, computed, defineAsyncComponent } from 'vue'
import { logger } from '@/src/shared/utils/logger'

// Lazy load компонентов для оптимизации
const PhotoUploadArea = defineAsyncComponent(() => import('./components/PhotoUploadArea.vue'))
const VideoUploadArea = defineAsyncComponent(() => import('./components/VideoUploadArea.vue'))
const MediaPreview = defineAsyncComponent(() => import('./components/MediaPreview.vue'))

// Базовые компоненты формы (уже загружены)
import FormSection from '@/src/shared/ui/molecules/Forms/FormSection.vue'
import FormFieldGroup from '@/src/shared/ui/molecules/Forms/components/FormFieldGroup.vue'
import FormField from '@/src/shared/ui/organisms/Forms/FormField.vue'


// Types
interface MediaItem {
  id?: string | number
  url?: string
  file?: File
  uploading?: boolean
  progress?: number
}

interface MediaFormData {
  photos?: MediaItem[]
  videos?: MediaItem[]
  image_quality?: 'low' | 'medium' | 'high'
  auto_compress?: boolean
}

interface Props {
  modelValue?: MediaFormData
  errors?: Record<string, string>
  disabled?: boolean
  readonly?: boolean
  collapsible?: boolean
  collapsed?: boolean
  maxPhotos?: number
  maxVideos?: number
  maxFileSize?: number
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => ({}),
  errors: () => ({}),
  disabled: false,
  readonly: false,
  collapsible: false,
  collapsed: false,
  maxPhotos: 10,
  maxVideos: 3,
  maxFileSize: 10 * 1024 * 1024 // 10MB
})

// Emits
const emit = defineEmits<{
  'update:model-value': [value: MediaFormData]
  'field-change': [field: string, value: any]
  'toggle': [collapsed: boolean]
}>()

// State
const photos = ref<MediaItem[]>(props.modelValue?.photos || [])
const videos = ref<MediaItem[]>(props.modelValue?.videos || [])

// Options
const imageQualityOptions = [
  { value: 'low', label: 'Низкое (быстрая загрузка)' },
  { value: 'medium', label: 'Среднее (оптимально)' },
  { value: 'high', label: 'Высокое (лучшее качество)' }
]

// Computed
const currentValue = computed(() => ({
  photos: photos.value,
  videos: videos.value,
  image_quality: props.modelValue?.image_quality || 'high',
  auto_compress: props.modelValue?.auto_compress ?? true
}))

// Methods
const updateValue = (newValue: MediaFormData) => {
  emit('update:model-value', newValue)
}

const updateField = (field: string, value: any) => {
  const updated = { ...currentValue.value, [field]: value }
  emit('update:model-value', updated)
  emit('field-change', field, value)
}

const onFieldChange = (field: string, value: any) => {
  emit('field-change', field, value)
}

const onToggle = (collapsed: boolean) => {
  emit('toggle', collapsed)
}

const hasError = (field: string): boolean => {
  return !!props.errors?.[field]
}

const getError = (field: string): string => {
  return props.errors?.[field] || ''
}

// Photo handlers
const handlePhotoFiles = async (files: File[]) => {
  if (photos.value.length + files.length > props.maxPhotos) {
    logger.warn(`Превышен лимит фотографий: ${props.maxPhotos}`, null, 'MediaForm')
    return
  }

  const newPhotos = files.map(file => ({
    file,
    uploading: true,
    progress: 0
  }))

  photos.value.push(...newPhotos)
  updateField('photos', photos.value)

  // Симуляция загрузки (в реальном проекте здесь будет API)
  for (const photo of newPhotos) {
    await simulateUpload(photo)
  }
}

const removePhoto = (index: number) => {
  photos.value.splice(index, 1)
  updateField('photos', photos.value)
  logger.userAction('photo_removed', { index })
}

const viewPhoto = (photo: MediaItem, index: number) => {
  logger.userAction('photo_viewed', { index })
  // Здесь можно открыть модальное окно просмотра
}

// Video handlers
const handleVideoFiles = async (files: File[]) => {
  if (videos.value.length + files.length > props.maxVideos) {
    logger.warn(`Превышен лимит видео: ${props.maxVideos}`, null, 'MediaForm')
    return
  }

  const newVideos = files.map(file => ({
    file,
    uploading: true,
    progress: 0
  }))

  videos.value.push(...newVideos)
  updateField('videos', videos.value)

  // Симуляция загрузки
  for (const video of newVideos) {
    await simulateUpload(video)
  }
}

const removeVideo = (index: number) => {
  videos.value.splice(index, 1)
  updateField('videos', videos.value)
  logger.userAction('video_removed', { index })
}

const playVideo = (video: MediaItem, index: number) => {
  logger.userAction('video_played', { index })
  // Здесь можно открыть модальное окно с видеоплеером
}

// Utility functions
const simulateUpload = async (item: MediaItem): Promise<void> => {
  return new Promise((resolve) => {
    const interval = setInterval(() => {
      if (item.progress === undefined) item.progress = 0
      item.progress += 10
      
      if (item.progress >= 100) {
        item.uploading = false
        item.progress = 100
        clearInterval(interval)
        resolve()
      }
    }, 200)
  })
}
</script>

<style scoped>
/* Минимальные стили для оптимизации */
.form-field {
  @apply space-y-3;
}

.form-field-label {
  @apply block text-sm font-medium text-gray-700;
}
</style>
