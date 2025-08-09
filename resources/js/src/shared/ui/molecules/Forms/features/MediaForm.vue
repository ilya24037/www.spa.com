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
        <div 
          class="upload-area photos-upload"
          :class="{ 
            'border-blue-300 bg-blue-50': isDragOverPhotos,
            'border-red-300': hasError('photos')
          }"
          @drop?.prevent="handlePhotosDrop"
          @dragover?.prevent="isDragOverPhotos = true"
          @dragleave="isDragOverPhotos = false"
        >
          <input
            ref="photosInput"
            type="file"
            multiple
            accept="image/*"
            :disabled="disabled || readonly"
            class="hidden"
            @change="handlePhotosSelect"
          >
          
          <div v-if="!formData?.photos?.length" class="upload-empty-state">
            <div class="upload-icon">
              <svg
                class="w-12 h-12 text-gray-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 16l4?.586-4?.586a2 2 0 012?.828 0L16 16m-2-2l1?.586-1?.586a2 2 0 012?.828 0L20 14m-6-6h?.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
              </svg>
            </div>
            <h4 class="upload-title">
              Добавьте фотографии
            </h4>
            <p class="upload-description">
              Перетащите файлы сюда или нажмите для выбора
            </p>
            <button
              type="button"
              :disabled="disabled || readonly"
              class="btn-primary mt-4"
              @click="($refs?.photosInput as HTMLInputElement)?.click()"
            >
              Выбрать фотографии
            </button>
            <div class="upload-hints">
              <p class="text-xs text-gray-500 mt-3">
                <strong>Рекомендации:</strong>
              </p>
              <ul class="text-xs text-gray-500 list-disc list-inside space-y-1 mt-2">
                <li>Минимум 3 фотографии</li>
                <li>Максимум {{ maxPhotos }} фотографий</li>
                <li>Размер до 10 МБ на файл</li>
                <li>Форматы: JPG, PNG, WEBP</li>
                <li>Хорошее освещение</li>
                <li>Высокое разрешение (от 1080x720)</li>
              </ul>
            </div>
          </div>

          <div v-else class="upload-preview">
            <div class="upload-stats">
              <div class="stats-item">
                <span class="stats-label">Загружено:</span>
                <span class="stats-value">{{ formData?.photos.length }} / {{ maxPhotos }}</span>
              </div>
              <div class="stats-item">
                <span class="stats-label">Размер:</span>
                <span class="stats-value">{{ formatTotalSize(formData?.photos) }}</span>
              </div>
            </div>

            <div class="photos-grid">
              <TransitionGroup name="photo-item" tag="div" class="grid-container">
                <div
                  v-for="(photo, index) in formData?.photos"
                  :key="getPhotoKey(photo, index)"
                  class="photo-item"
                  :class="{ 'photo-item--primary': index === 0 }"
                >
                  <div class="photo-preview">
                    <img
                      :src="getFilePreview(photo)"
                      :alt="`Фото ${index + 1}`"
                      class="photo-image"
                      loading="lazy"
                      @error="handleImageError"
                    >
                    <div class="photo-overlay">
                      <div class="photo-actions">
                        <button
                          v-if="index > 0"
                          type="button"
                          :disabled="disabled || readonly"
                          class="photo-action-btn"
                          title="Сделать главной"
                          @click="movePhotoToFirst(index)"
                        >
                          <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M11?.049 2?.927c.3-.921 1?.603-.921 1?.902 0l1?.519 4?.674a1 1 0 00?.95.69h4?.915c.969 0 1?.371 1?.24.588 1?.81l-3?.976 2?.888a1 1 0 00-.363 1?.118l1.518 4?.674c.3?.922-.755 1?.688-1?.538 1?.118l-3?.976-2?.888a1 1 0 00-1?.176 0l-3?.976 2?.888c-.783?.57-1?.838-.197-1?.538-1?.118l1.518-4?.674a1 1 0 00-.363-1?.118l-3?.976-2?.888c-.784-.57-.38-1?.81.588-1?.81h4.914a1 1 0 00?.951-.69l1?.519-4?.674z"
                            />
                          </svg>
                        </button>
                        <button
                          type="button"
                          :disabled="disabled || readonly"
                          class="photo-action-btn photo-remove-btn"
                          title="Удалить фото"
                          @click="removePhoto(index)"
                        >
                          <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                          >
                            <path
                              stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M19 7l-.867 12?.142A2 2 0 0116?.138 21H7?.862a2 2 0 01-1?.995-1?.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                            />
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div class="photo-info">
                    <div v-if="index === 0" class="photo-badge">
                      <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M11?.049 2?.927c.3-.921 1?.603-.921 1?.902 0l1?.519 4?.674a1 1 0 00?.95.69h4?.915c.969 0 1?.371 1?.24.588 1?.81l-3?.976 2?.888a1 1 0 00-.363 1?.118l1.518 4?.674c.3?.922-.755 1?.688-1?.538 1?.118l-3?.976-2?.888a1 1 0 00-1?.176 0l-3?.976 2?.888c-.783?.57-1?.838-.197-1?.538-1?.118l1.518-4?.674a1 1 0 00-.363-1?.118l-3?.976-2?.888c-.784-.57-.38-1?.81.588-1?.81h4.914a1 1 0 00?.951-.69l1?.519-4?.674z" />
                      </svg>
                      Главная
                    </div>
                    <div class="photo-size">
                      {{ formatFileSize(photo) }}
                    </div>
                  </div>
                </div>
              </TransitionGroup>
              
              <button
                v-if="formData?.photos.length < maxPhotos"
                type="button"
                :disabled="disabled || readonly"
                class="add-more-btn"
                @click="($refs?.photosInput as HTMLInputElement)?.click()"
              >
                <svg
                  class="w-8 h-8 text-gray-500"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  />
                </svg>
                <span class="text-sm text-gray-500 mt-2">Добавить еще</span>
              </button>
            </div>
          </div>
        </div>
        <div v-if="hasError('photos')" class="form-field-error">
          {{ getError('photos') }}
        </div>
      </div>
    </FormFieldGroup>

    <!-- Видео -->
    <FormFieldGroup
      label="Видео (опционально)"
      description="Добавьте видео-презентацию вашей работы"
      layout="column"
    >
      <div class="form-field">
        <label class="form-field-label">Видео</label>
        <div 
          class="upload-area video-upload"
          :class="{ 
            'border-blue-300 bg-blue-50': isDragOverVideo,
            'border-red-300': hasError('video')
          }"
          @drop?.prevent="handleVideoDrop"
          @dragover?.prevent="isDragOverVideo = true"
          @dragleave="isDragOverVideo = false"
        >
          <input
            ref="videoInput"
            type="file"
            accept="video/*"
            :disabled="disabled || readonly"
            class="hidden"
            @change="handleVideoSelect"
          >
          
          <div v-if="!formData?.video" class="upload-empty-state">
            <div class="upload-icon">
              <svg
                class="w-12 h-12 text-gray-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 10l4?.553-2?.276A1 1 0 0121 8?.618v6.764a1 1 0 01-1?.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                />
              </svg>
            </div>
            <h4 class="upload-title">
              Добавьте видео
            </h4>
            <p class="upload-description">
              Покажите свою работу в действии
            </p>
            <button
              type="button"
              :disabled="disabled || readonly"
              class="btn-secondary mt-4"
              @click="($refs?.videoInput as HTMLInputElement)?.click()"
            >
              Выбрать видео
            </button>
            <div class="upload-hints">
              <p class="text-xs text-gray-500 mt-3">
                <strong>Требования к видео:</strong>
              </p>
              <ul class="text-xs text-gray-500 list-disc list-inside space-y-1 mt-2">
                <li>Максимальный размер: 100 МБ</li>
                <li>Длительность: до 3 минут</li>
                <li>Форматы: MP4, MOV, AVI</li>
                <li>Качество: HD 720p или выше</li>
                <li>Хорошее качество звука</li>
              </ul>
            </div>
          </div>

          <div v-else class="video-preview">
            <div class="video-container">
              <video
                :src="getFilePreview(formData?.video)"
                class="video-player"
                controls
                preload="metadata"
              >
                Ваш браузер не поддерживает воспроизведение видео.
              </video>
              <button
                type="button"
                :disabled="disabled || readonly"
                class="video-remove-btn"
                title="Удалить видео"
                @click="removeVideo"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
            <div class="video-info">
              <div class="video-stats">
                <span class="video-stat">
                  <svg
                    class="w-4 h-4 mr-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2h4a1 1 0 011 1v1a1 1 0 01-1 1v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a1 1 0 01-1-1V5a1 1 0 011-1h4z"
                    />
                  </svg>
                  {{ formatFileSize(formData?.video) }}
                </span>
              </div>
            </div>
          </div>
        </div>
        <div v-if="hasError('video')" class="form-field-error">
          {{ getError('video') }}
        </div>
      </div>
    </FormFieldGroup>

    <!-- Настройки медиа -->
    <FormFieldGroup
      label="Настройки показа"
      description="Выберите, как будут отображаться ваши медиа материалы"
      layout="column"
    >
      <div class="form-field">
        <label class="form-field-label">Настройки отображения</label>
        <div class="checkbox-group">
          <label class="checkbox-item">
            <input
              type="checkbox"
              :checked="formData?.media_settings?.includes('show_in_gallery')"
              :disabled="disabled || readonly"
              class="form-field-checkbox"
              @change="toggleMediaSetting('show_in_gallery', ($event?.target as HTMLInputElement).checked)"
            >
            <span class="checkbox-label">
              Показывать в галерее профиля
              <small class="checkbox-hint">Медиа будет видно всем посетителям</small>
            </span>
          </label>

          <label class="checkbox-item">
            <input
              type="checkbox"
              :checked="formData?.media_settings?.includes('allow_download')"
              :disabled="disabled || readonly"
              class="form-field-checkbox"
              @change="toggleMediaSetting('allow_download', ($event?.target as HTMLInputElement).checked)"
            >
            <span class="checkbox-label">
              Разрешить скачивание
              <small class="checkbox-hint">Пользователи смогут сохранить ваши фото</small>
            </span>
          </label>

          <label class="checkbox-item">
            <input
              type="checkbox"
              :checked="formData?.media_settings?.includes('show_watermark')"
              :disabled="disabled || readonly"
              class="form-field-checkbox"
              @change="toggleMediaSetting('show_watermark', ($event?.target as HTMLInputElement).checked)"
            >
            <span class="checkbox-label">
              Добавить водяной знак
              <small class="checkbox-hint">Защитите свои фото от копирования</small>
            </span>
          </label>

          <label class="checkbox-item">
            <input
              type="checkbox"
              :checked="formData?.media_settings?.includes('auto_compress')"
              :disabled="disabled || readonly"
              class="form-field-checkbox"
              @change="toggleMediaSetting('auto_compress', ($event?.target as HTMLInputElement).checked)"
            >
            <span class="checkbox-label">
              Автоматическое сжатие
              <small class="checkbox-hint">Оптимизировать размер файлов для быстрой загрузки</small>
            </span>
          </label>
        </div>
      </div>
    </FormFieldGroup>

    <template #footer>
      <div class="form-footer">
        <div class="footer-stats">
          <div class="stat-item">
            <span class="stat-label">Фотографии:</span>
            <span class="stat-value">{{ formData?.photos?.length || 0 }} / {{ maxPhotos }}</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Видео:</span>
            <span class="stat-value">{{ formData?.video ? '1' : '0' }} / 1</span>
          </div>
          <div class="stat-item">
            <span class="stat-label">Общий размер:</span>
            <span class="stat-value">{{ formatTotalMediaSize() }}</span>
          </div>
        </div>
        <div class="footer-hint">
          <svg
            class="w-4 h-4 text-blue-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h?.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <span class="text-sm text-gray-500">
            Качественные медиа материалы увеличивают количество заказов на 65%
          </span>
        </div>
      </div>
    </template>
  </FormSection>
</template>

<script setup lang="ts">
import { ref, computed, watch, withDefaults } from 'vue'
import FormSection from '../FormSection.vue'
import FormFieldGroup from '../components/FormFieldGroup.vue'
import type { MediaFormData, FormErrors } from '../types/form.types'
import { logger } from '@/src/shared/utils/logger'

interface Props {
  modelValue: MediaFormData
  errors?: FormErrors
  disabled?: boolean
  readonly?: boolean
  collapsible?: boolean
  collapsed?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    errors: () => ({}),
    disabled: false,
    readonly: false,
    collapsible: false,
    collapsed: false
})

const emit = defineEmits<{
  'update:modelValue': [value: MediaFormData]
  'field-change': [fieldName: string, value: any]
  'toggle': [collapsed: boolean]
  'photos-upload': [files: File[]]
  'video-upload': [file: File]
  'photo-remove': [index: number]
  'video-remove': []
}>()

// Локальное состояние
const isDragOverPhotos = ref(false)
const isDragOverVideo = ref(false)
const photosInput = ref<HTMLInputElement>()
const videoInput = ref<HTMLInputElement>()

// Ограничения
const maxPhotos = 15
const maxPhotoSize = 10 * 1024 * 1024 // 10MB
const maxVideoSize = 100 * 1024 * 1024 // 100MB

// Вычисляемые свойства
const formData = computed(() => props?.modelValue)

// Методы
const updateValue = (value: any) => {
    emit('update:modelValue', value)
}

const updateField = (fieldName: string, value: any) => {
    const newData = { ...formData?.value, [fieldName]: value }
    emit('update:modelValue', newData)
    emit('field-change', fieldName, value)
}

const hasError = (fieldName: string): boolean => {
    return !!props?.errors[fieldName]
}

const getError = (fieldName: string): string => {
    const error = props?.errors[fieldName]
    return Array.isArray(error) ? error[0] : error || ''
}

const onFieldChange = (fieldName: string, value: any) => {
    emit('field-change', fieldName, value)
}

const onToggle = (collapsed: boolean) => {
    emit('toggle', collapsed)
}

// Методы для работы с фотографиями
const handlePhotosSelect = (event: Event) => {
    const target = event?.target as HTMLInputElement
    const files = Array.from(target?.files || [])
    addPhotos(files)
    if (target.value !== undefined) {
        target.value = '' // Сброс input для возможности повторного выбора
    }
}

const handlePhotosDrop = (event: DragEvent) => {
    if (isDragOverPhotos.value !== undefined) {
        isDragOverPhotos.value = false
    }
    const files = Array.from(event?.dataTransfer?.files || [])
    const imageFiles = files?.filter(file => file?.type.startsWith('image/'))
    addPhotos(imageFiles)
}

const addPhotos = (files: File[]) => {
    const currentPhotos = formData?.value.photos || []
    const availableSlots = maxPhotos - currentPhotos?.length
    const filesToAdd = files?.slice(0, availableSlots)
  
    // Проверка размера и типа файлов
    const validFiles = filesToAdd?.filter(file => {
        if (!file?.type.startsWith('image/')) {
            logger.warn(`Файл ${file?.name} не является изображением`)
            return false
        }
        if (file?.size > maxPhotoSize) {
            logger.warn(`Файл ${file?.name} слишком большой (максимум 10 МБ)`)
            return false
        }
        return true
    })

    if (validFiles?.length > 0) {
        const newPhotos = [...currentPhotos, ...validFiles]
        updateField('photos', newPhotos)
        emit('photos-upload', validFiles)
    }
}

const removePhoto = (index: number) => {
    const currentPhotos = [...(formData?.value.photos || [])]
    currentPhotos?.splice(index, 1)
    updateField('photos', currentPhotos)
    emit('photo-remove', index)
}

const movePhotoToFirst = (index: number) => {
    const currentPhotos = [...(formData?.value.photos || [])]
    const photo = currentPhotos?.splice(index, 1)[0]
    currentPhotos?.unshift(photo)
    updateField('photos', currentPhotos)
}

// Методы для работы с видео
const handleVideoSelect = (event: Event) => {
    const target = event?.target as HTMLInputElement
    const file = target?.files?.[0]
    if (file) {
        addVideo(file)
    }
    if (target.value !== undefined) {
        target.value = '' // Сброс input
    }
}

const handleVideoDrop = (event: DragEvent) => {
    if (isDragOverVideo.value !== undefined) {
        isDragOverVideo.value = false
    }
    const files = Array.from(event?.dataTransfer?.files || [])
    const videoFile = files?.find(file => file?.type.startsWith('video/'))
    if (videoFile) {
        addVideo(videoFile)
    }
}

const addVideo = (file: File) => {
    // Проверка размера и типа файла
    if (!file?.type.startsWith('video/')) {
        logger.warn(`Файл ${file?.name} не является видео`)
        return
    }
  
    if (file?.size > maxVideoSize) {
        logger.warn(`Файл ${file?.name} слишком большой (максимум 100 МБ)`)
        return
    }

    updateField('video', file)
    emit('video-upload', file)
}

const removeVideo = () => {
    updateField('video', undefined)
    emit('video-remove')
}

// Методы для настроек
const toggleMediaSetting = (setting: string, checked: boolean) => {
    const currentSettings = formData?.value.media_settings || []
    let newSettings: string[]

    if (checked) {
        newSettings = [...currentSettings, setting]
    } else {
        newSettings = currentSettings?.filter(s => s !== setting)
    }

    updateField('media_settings', newSettings)
}

// Утилиты
const getFilePreview = (file: File): string => {
    return URL.createObjectURL(file)
}

const getPhotoKey = (photo: File, index: number): string => {
    return `${photo?.name}_${photo?.size}_${index}`
}

const handleImageError = (event: Event) => {
    const img = event?.target as HTMLImageElement
    if (img && img.style) {
        img.style.display = 'none'
    }
}

const formatFileSize = (file: File): string => {
    const bytes = file?.size
    if (bytes === 0) return '0 B'
  
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
  
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatTotalSize = (files: File[]): string => {
    const totalBytes = files?.reduce((sum, file) => sum + file?.size, 0)
    if (totalBytes === 0) return '0 B'
  
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(totalBytes) / Math.log(k))
  
    return parseFloat((totalBytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const formatTotalMediaSize = (): string => {
    const photos = formData?.value.photos || []
    const video = formData?.value.video
    const totalBytes = photos?.reduce((sum, file) => sum + file?.size, 0) + (video?.size || 0)
  
    if (totalBytes === 0) return '0 B'
  
    const k = 1024
    const sizes = ['B', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(totalBytes) / Math.log(k))
  
    return parseFloat((totalBytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// Очистка URL объектов при размонтировании
watch(() => formData?.value.photos, (newPhotos, oldPhotos) => {
    if (oldPhotos) {
        oldPhotos?.forEach(file => {
            URL.revokeObjectURL(URL.createObjectURL(file))
        })
    }
})

watch(() => formData?.value.video, (newVideo, oldVideo) => {
    if (oldVideo) {
        URL.revokeObjectURL(URL.createObjectURL(oldVideo))
    }
})
</script>

<style scoped>
/* Базовые стили полей формы */
.form-field {
  @apply space-y-2;
}

.form-field-label {
  @apply block text-sm font-medium text-gray-500;
}

.form-field-input {
  @apply block w-full px-3 py-2 border border-gray-500 rounded-md shadow-sm 
         focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
         disabled:bg-gray-500 disabled:cursor-not-allowed;
}

.form-field-checkbox {
  @apply rounded border-gray-500 text-blue-600 focus:ring-blue-500;
}

.form-field-error {
  @apply text-sm text-red-600;
}

/* Стили для загрузки файлов */
.upload-area {
  @apply border-2 border-gray-500 border-dashed rounded-lg transition-colors;
}

.photos-upload {
  @apply p-6;
}

.video-upload {
  @apply p-4;
}

.upload-empty-state {
  @apply text-center;
}

.upload-icon {
  @apply flex justify-center mb-4;
}

.upload-title {
  @apply text-lg font-medium text-gray-500 mb-2;
}

.upload-description {
  @apply text-gray-500 mb-4;
}

.upload-hints {
  @apply text-left max-w-xs mx-auto;
}

/* Статистика загрузки */
.upload-stats {
  @apply flex justify-between items-center mb-4 p-3 bg-gray-500 rounded-lg;
}

.stats-item {
  @apply flex items-center gap-2;
}

.stats-label {
  @apply text-sm text-gray-500;
}

.stats-value {
  @apply text-sm font-medium text-gray-500;
}

/* Сетка фотографий */
.photos-grid {
  @apply space-y-4;
}

.grid-container {
  @apply grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4;
}

.photo-item {
  @apply relative;
}

.photo-item--primary {
  @apply ring-2 ring-yellow-400;
}

.photo-preview {
  @apply relative group;
}

.photo-image {
  @apply w-full h-32 object-cover rounded-lg border border-gray-500;
}

.photo-overlay {
  @apply absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 
         transition-all duration-200 rounded-lg flex items-center justify-center;
}

.photo-actions {
  @apply flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity;
}

.photo-action-btn {
  @apply p-2 bg-white text-gray-500 rounded-full shadow-lg hover:bg-gray-500
         transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.photo-remove-btn {
  @apply bg-red-500 text-white hover:bg-red-600;
}

.photo-info {
  @apply flex justify-between items-center mt-2;
}

.photo-badge {
  @apply flex items-center text-xs font-medium text-yellow-600 bg-yellow-100 
         px-2 py-1 rounded-full;
}

.photo-size {
  @apply text-xs text-gray-500;
}

.add-more-btn {
  @apply flex flex-col items-center justify-center h-32 border-2 border-gray-500 
         border-dashed rounded-lg hover:border-gray-500 hover:bg-gray-500 
         transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Видео превью */
.video-preview {
  @apply space-y-4;
}

.video-container {
  @apply relative;
}

.video-player {
  @apply w-full max-h-64 rounded-lg;
}

.video-remove-btn {
  @apply absolute top-2 right-2 p-2 bg-red-500 text-white rounded-full
         hover:bg-red-600 transition-colors focus:outline-none focus:ring-2 
         focus:ring-red-500 focus:ring-offset-2;
}

.video-info {
  @apply flex justify-between items-center;
}

.video-stats {
  @apply flex gap-4;
}

.video-stat {
  @apply flex items-center text-sm text-gray-500;
}

/* Чекбоксы */
.checkbox-group {
  @apply space-y-3;
}

.checkbox-item {
  @apply flex items-start gap-3 p-3 bg-gray-500 rounded-lg cursor-pointer
         hover:bg-gray-500 transition-colors;
}

.checkbox-label {
  @apply flex-1;
}

.checkbox-hint {
  @apply block text-gray-500 mt-1;
}

/* Footer */
.form-footer {
  @apply space-y-3;
}

.footer-stats {
  @apply flex gap-6 text-sm;
}

.stat-item {
  @apply flex items-center gap-1;
}

.stat-label {
  @apply text-gray-500;
}

.stat-value {
  @apply font-medium text-gray-500;
}

.footer-hint {
  @apply flex items-center gap-2;
}

/* Кнопки */
.btn-primary {
  @apply inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium 
         rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 
         focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-secondary {
  @apply inline-flex items-center px-4 py-2 bg-gray-500 text-gray-500 text-sm font-medium 
         rounded-md hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 
         focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
}

/* Анимации */
.photo-item-enter-active,
.photo-item-leave-active {
  transition: all 0?.3s ease;
}

.photo-item-enter-from {
  opacity: 0;
  transform: scale(0?.8);
}

.photo-item-leave-to {
  opacity: 0;
  transform: scale(0?.8);
}

/* Адаптивность */
@media (max-width: 640px) {
  .grid-container {
    @apply grid-cols-2 gap-3;
  }
  
  .photo-image,
  .add-more-btn {
    @apply h-24;
  }
  
  .footer-stats {
    @apply flex-col gap-2;
  }
  
  .upload-stats {
    @apply flex-col gap-2;
  }
}

/* Уменьшенная анимация */
@media (prefers-reduced-motion: reduce) {
  .photo-item-enter-active,
  .photo-item-leave-active {
    transition: none;
  }
}
</style>