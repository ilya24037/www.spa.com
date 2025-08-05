<template>
  <div class="media-uploader">
    <div class="space-y-6">
      <!-- Загрузка аватара -->
      <div class="bg-white rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Аватар</h3>
        
        <div class="flex items-center space-x-4">
          <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-100">
            <img 
              :src="avatarUrl || '/images/default-avatar.jpg'"
              :alt="masterName"
              class="w-full h-full object-cover"
            >
          </div>
          
          <div>
            <input 
              ref="avatarInput"
              type="file"
              accept="image/*"
              @change="uploadAvatar"
              class="hidden"
            >
            <button 
              @click="$refs.avatarInput.click()"
              :disabled="uploading.avatar"
              class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {{ uploading.avatar ? 'Загрузка...' : 'Загрузить аватар' }}
            </button>
            <p class="text-sm text-gray-500 mt-1">
              JPG, PNG, WebP до 10MB
            </p>
          </div>
        </div>
      </div>

      <!-- Загрузка фотографий -->
      <div class="bg-white rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Фотографии (до 10 штук)</h3>
        
        <!-- Сетка фотографий -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mb-4">
          <div 
            v-for="photo in photos" 
            :key="photo.id"
            class="relative group"
          >
            <img 
              :src="photo.thumb_url"
              :alt="`Фото ${photo.sort_order}`"
              class="w-full h-32 object-cover rounded-lg"
            >
            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all rounded-lg">
              <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button 
                  @click="deletePhoto(photo.id)"
                  class="p-1 bg-red-600 text-white rounded-full hover:bg-red-700"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
              <div class="absolute bottom-2 left-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button 
                  v-if="!photo.is_main"
                  @click="setMainPhoto(photo.id)"
                  class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700"
                >
                  Главное
                </button>
                <span 
                  v-else
                  class="px-2 py-1 bg-green-600 text-white text-xs rounded"
                >
                  Главное
                </span>
              </div>
            </div>
          </div>
          
          <!-- Кнопка добавления -->
          <div 
            v-if="photos.length < 10"
            class="w-full h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center cursor-pointer hover:border-gray-400"
            @click="$refs.photosInput.click()"
          >
            <div class="text-center">
              <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
              <p class="text-sm text-gray-500">Добавить фото</p>
            </div>
          </div>
        </div>
        
        <input 
          ref="photosInput"
          type="file"
          accept="image/*"
          multiple
          @change="uploadPhotos"
          class="hidden"
        >
        
        <div v-if="uploading.photos" class="text-blue-600">
          Загрузка фотографий...
        </div>
      </div>

      <!-- Загрузка видео -->
      <div class="bg-white rounded-lg p-6 shadow-sm">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Видео-презентация</h3>
        
        <div v-if="video" class="mb-4">
          <div class="relative">
            <video 
              :src="video.video_url"
              :poster="video.poster_url"
              controls
              class="w-full max-w-md rounded-lg"
            ></video>
            <button 
              @click="deleteVideo"
              class="absolute top-2 right-2 p-1 bg-red-600 text-white rounded-full hover:bg-red-700"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          <p class="text-sm text-gray-500 mt-2">
            Длительность: {{ video.duration }} | Размер: {{ formatFileSize(video.file_size) }}
          </p>
        </div>
        
        <div v-else>
          <input 
            ref="videoInput"
            type="file"
            accept="video/*"
            @change="uploadVideo"
            class="hidden"
          >
          <button 
            @click="$refs.videoInput.click()"
            :disabled="uploading.video"
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
          >
            {{ uploading.video ? 'Загрузка...' : 'Загрузить видео' }}
          </button>
          <p class="text-sm text-gray-500 mt-1">
            MP4, WebM, AVI до 100MB
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, type Ref } from 'vue'
import { useToast } from '@/src/shared/composables/useToast'
import type { 
  MediaUploaderProps, 
  MediaUploaderEmits, 
  Photo, 
  Video, 
  UploadingState,
  UploadResponse 
} from './MediaUploader.types'

// Toast для замены alert()
const toast = useToast()

const props = withDefaults(defineProps<MediaUploaderProps>(), {
  initialPhotos: () => [],
  initialVideo: null
})

const emit = defineEmits<MediaUploaderEmits>()

// Состояние
const photos: import("vue").Ref<Photo[]> = ref([...props.initialPhotos])
const video: import("vue").Ref<Video | null> = ref(props.initialVideo)
const avatarUrl: import("vue").Ref<string> = ref(`/masters/${props.masterId}/avatar`)
const uploading: import("vue").Ref<UploadingState> = ref({
  avatar: false,
  photos: false,
  video: false
})

// Загрузка аватара
const uploadAvatar = async (event: Event): Promise<void> => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  uploading.value.avatar = true
  
  try {
    const formData = new FormData()
    formData.append('avatar', file)

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (!csrfToken) {
      throw new Error('CSRF token не найден')
    }

    const response = await fetch(`/masters/${props.masterId}/upload/avatar`, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })

    const result: UploadResponse = await response.json()
    
    if (result.success && result.avatar_url) {
      avatarUrl.value = result.avatar_url + '?t=' + Date.now()
      emit('avatarUpdated', avatarUrl.value)
      toast.success('Аватар загружен успешно!')
    } else {
      toast.error('Ошибка: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка загрузки: ' + errorMessage)
  } finally {
    uploading.value.avatar = false
    if (target) {
      target.value = ''
    }
  }
}

// Загрузка фотографий
const uploadPhotos = async (event: Event): Promise<void> => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  if (!files.length) return

  uploading.value.photos = true
  
  try {
    const formData = new FormData()
    files.forEach(file => formData.append('photos[]', file))

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (!csrfToken) {
      throw new Error('CSRF token не найден')
    }

    const response = await fetch(`/masters/${props.masterId}/upload/photos`, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })

    const result: UploadResponse = await response.json()
    
    if (result.success && result.photos) {
      photos.value.push(...result.photos)
      emit('photosUpdated', photos.value)
      toast.success(result.message || 'Фотографии загружены успешно!')
    } else {
      toast.error('Ошибка: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка загрузки: ' + errorMessage)
  } finally {
    uploading.value.photos = false
    if (target) {
      target.value = ''
    }
  }
}

// Загрузка видео
const uploadVideo = async (event: Event): Promise<void> => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (!file) return

  uploading.value.video = true
  
  try {
    const formData = new FormData()
    formData.append('video', file)

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (!csrfToken) {
      throw new Error('CSRF token не найден')
    }

    const response = await fetch(`/masters/${props.masterId}/upload/video`, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })

    const result: UploadResponse = await response.json()
    
    if (result.success && result.video) {
      video.value = result.video
      emit('videoUpdated', video.value)
      toast.success('Видео загружено успешно!')
    } else {
      toast.error('Ошибка: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка загрузки: ' + errorMessage)
  } finally {
    uploading.value.video = false
    if (target) {
      target.value = ''
    }
  }
}

// Удаление фото
const deletePhoto = async (photoId: number): Promise<void> => {
  // TODO: Заменить confirm на Modal компонент
  if (!confirm('Удалить фотографию?')) return

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (!csrfToken) {
      throw new Error('CSRF token не найден')
    }

    const response = await fetch(`/photos/${photoId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })

    const result: UploadResponse = await response.json()
    
    if (result.success) {
      photos.value = photos.value.filter(p => p.id !== photoId)
      emit('photosUpdated', photos.value)
      toast.success('Фотография удалена')
    } else {
      toast.error('Ошибка: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка удаления: ' + errorMessage)
  }
}

// Удаление видео
const deleteVideo = async (): Promise<void> => {
  // TODO: Заменить confirm на Modal компонент
  if (!confirm('Удалить видео?')) return

  if (!video.value?.id) {
    toast.error('Видео не найдено')
    return
  }

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (!csrfToken) {
      throw new Error('CSRF token не найден')
    }

    const response = await fetch(`/videos/${video.value.id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })

    const result: UploadResponse = await response.json()
    
    if (result.success) {
      video.value = null
      emit('videoUpdated', null)
      toast.success('Видео удалено')
    } else {
      toast.error('Ошибка: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка удаления: ' + errorMessage)
  }
}

// Установка главного фото
const setMainPhoto = async (photoId: number): Promise<void> => {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    if (!csrfToken) {
      throw new Error('CSRF token не найден')
    }

    const response = await fetch(`/photos/${photoId}/set-main`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    })

    const result: UploadResponse = await response.json()
    
    if (result.success) {
      photos.value.forEach(p => {
        p.is_main = p.id === photoId
      })
      emit('photosUpdated', photos.value)
      toast.success('Главное фото установлено')
    } else {
      toast.error('Ошибка: ' + (result.error || 'Неизвестная ошибка'))
    }
  } catch (error: unknown) {
    const errorMessage = error instanceof Error ? error.message : 'Неизвестная ошибка'
    toast.error('Ошибка: ' + errorMessage)
  }
}

// Форматирование размера файла
const formatFileSize = (bytes: number): string => {
  const units = ['B', 'KB', 'MB', 'GB']
  let size = bytes
  let unitIndex = 0
  
  while (size >= 1024 && unitIndex < units.length - 1) {
    size /= 1024
    unitIndex++
  }
  
  return `${size.toFixed(1)} ${units[unitIndex]}`
}
</script> 