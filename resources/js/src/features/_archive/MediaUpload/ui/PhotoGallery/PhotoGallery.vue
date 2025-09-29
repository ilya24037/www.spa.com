<template>
  <div class="bg-white rounded-lg p-6">
    <h4 class="text-base font-medium text-gray-900 mb-2">Фотографии работ</h4>
    <p class="text-sm text-gray-600 mb-4">
      Добавьте до 10 качественных фотографий ваших работ. Рекомендуемый формат 4:3
    </p>
    
    <!-- Область загрузки с drag-and-drop -->
    <div 
      class="border-2 border-dashed border-gray-300 rounded-lg min-h-[200px] relative transition-all duration-300"
      :class="[
        isDragOver ? 'border-blue-500 bg-blue-50' : '',
        localPhotos.length > 0 ? 'border-solid p-4' : ''
      ]"
      @drop.prevent="handleDrop"
      @dragover.prevent="handleDragOver"
      @dragleave.prevent="handleDragLeave"
    >
      <!-- Скрытый input -->
      <input
        ref="photoInput"
        type="file"
        multiple
        accept="image/*"
        @change="handlePhotoUpload"
        class="hidden"
      />

      <!-- Список фотографий с возможностью перетаскивания -->
      <draggable 
        v-if="localPhotos.length > 0" 
        v-model="localPhotos"
        class="flex flex-wrap gap-4 sm:flex-col sm:gap-3"
        item-key="id"
        ghost-class="opacity-50"
        drag-class="opacity-50 scale-105"
        animation="200"
        @end="onDragEnd"
      >
        <template #item="{ element: photo, index }">
          <div 
            class="relative w-[266px] h-[200px] sm:w-full sm:h-[150px] cursor-move transition-all duration-300 group"
          >
            <div :class="[
              'relative w-full h-full rounded-lg overflow-hidden bg-gray-100 border transition-colors duration-200',
              index === 0 ? 'border-2 border-blue-500' : 'border border-gray-200'
            ]">
              <img 
                :src="getPhotoUrl(photo)" 
                :alt="`Фото ${index + 1}`"
                class="w-full h-full object-contain transition-transform duration-300"
                :style="{ transform: `rotate(${photo.rotation || 0}deg)` }"
              />
              
              <!-- Контролы фото -->
              <div class="absolute top-2 right-2 flex gap-1 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                <button 
                  type="button"
                  @click.stop="rotatePhoto(index)"
                  class="w-8 h-8 sm:w-7 sm:h-7 rounded-md bg-white/95 border border-gray-300 flex items-center justify-center cursor-pointer transition-all duration-200 hover:bg-white hover:shadow-lg"
                  title="Повернуть"
                >
                  <svg viewBox="0 0 24 24"
                       fill="none"
                       stroke="currentColor"
                       stroke-width="2"
                       class="w-4 h-4 sm:w-3.5 sm:h-3.5 text-gray-600 hover:text-blue-500 transition-colors">
                    <polyline points="23,4 23,10 17,10"/>
                    <path d="M20.49,15a9,9,0,1,1-2.12-9.36L23,10"/>
                  </svg>
                </button>
                
                <button 
                  type="button"
                  @click.stop="removePhoto(index)"
                  class="w-8 h-8 sm:w-7 sm:h-7 rounded-md bg-white/95 border border-gray-300 flex items-center justify-center cursor-pointer transition-all duration-200 hover:bg-white hover:shadow-lg"
                  title="Удалить"
                >
                  <svg viewBox="0 0 24 24"
                       fill="none"
                       stroke="currentColor"
                       stroke-width="2"
                       class="w-4 h-4 text-gray-600 hover:text-red-500 transition-colors">
                    <polyline points="3,6 5,6 21,6"/>
                    <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2v2"/>
                  </svg>
                </button>
              </div>
              
              <!-- Метка основного фото -->
              <div v-if="index === 0" class="absolute bottom-2 left-2 bg-blue-500 text-white px-3 py-1.5 rounded text-xs font-medium z-10 shadow-md">
                Основное
              </div>
            </div>
          </div>
        </template>
        
      </draggable>
      
      <!-- Кнопка добавить еще -->
      <div 
        v-if="localPhotos.length > 0 && localPhotos.length < 10" 
        class="w-[266px] h-[200px] sm:w-full sm:h-[150px] border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center gap-2 cursor-pointer transition-all duration-300 bg-gray-50 hover:border-blue-500 hover:bg-blue-50 mt-4"
        @click="triggerPhotoInput"
      >
        <svg width="32"
             height="32"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             class="text-gray-600">
          <line x1="12"
                y1="5"
                x2="12"
                y2="19"
                stroke-width="2"/>
          <line x1="5"
                y1="12"
                x2="19"
                y2="12"
                stroke-width="2"/>
        </svg>
        <span class="text-sm text-gray-600">Добавить фото</span>
      </div>

      <!-- Пустое состояние -->
      <div v-else class="flex flex-col items-center justify-center min-h-[200px] cursor-pointer p-8" @click="triggerPhotoInput">
        <svg width="48"
             height="48"
             viewBox="0 0 24 24"
             fill="none"
             stroke="currentColor"
             class="text-gray-400 mb-4">
          <rect x="3"
                y="3"
                width="18"
                height="18"
                rx="2"
                ry="2"/>
          <circle cx="8.5" cy="8.5" r="1.5"/>
          <polyline points="21,15 16,10 5,21"/>
        </svg>
        <p class="text-base text-gray-900 mb-2">Перетащите фото сюда или нажмите для выбора</p>
        <p class="text-sm text-gray-600">До 10 фото, формат JPG, PNG</p>
      </div>

      <!-- Оверлей для drag-and-drop -->
      <div v-if="isDragOver" class="absolute inset-0 bg-blue-500/10 rounded-lg flex items-center justify-center pointer-events-none">
        <div class="text-center">
          <svg width="48"
               height="48"
               viewBox="0 0 24 24"
               fill="none"
               stroke="currentColor"
               class="text-blue-500 mb-4">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7,10 12,15 17,10"/>
            <line x1="12"
                  y1="15"
                  x2="12"
                  y2="3"/>
          </svg>
          <p class="text-lg font-medium text-blue-500">Перетащите сюда изображения</p>
        </div>
      </div>
    </div>

    <!-- Ошибки -->
    <div v-if="error" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md text-red-700 text-sm">{{ error }}</div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import draggable from 'vuedraggable'

interface Photo {
  id: string | number
  file?: File
  url?: string
  preview?: string
  name?: string
  rotation?: number
}

interface Props {
  photos?: Array<string | Photo>
  errors?: Record<string, string>
}

interface Emits {
  (e: 'update:photos', photos: Array<File | string>): void
}

const props = withDefaults(defineProps<Props>(), {
  photos: () => [],
  errors: () => ({})
})

const emit = defineEmits<Emits>()

// Refs
const photoInput = ref<HTMLInputElement | null>(null)

// State
const localPhotos = ref<Photo[]>([])
const error = ref('')

// Drag and drop state
const isDragOver = ref(false)

// Initialize from props - ТОЛЬКО при первой загрузке
// После этого управление полностью локальное
let isInitialized = false
watch(() => props.photos, (newPhotos, oldPhotos) => {
  // Отладка
  if (window.debugPhotos) {
    console.log('📷 PhotoGallery: props.photos изменились', {
      old: oldPhotos?.length,
      new: newPhotos?.length,
      isInitialized,
      localLength: localPhotos.value.length
    })
  }
  
  // Инициализируем ТОЛЬКО при первой загрузке компонента
  if (!isInitialized && newPhotos && newPhotos.length > 0) {
    localPhotos.value = newPhotos.map((photo, index) => {
      if (typeof photo === 'string') {
        return {
          id: `existing-${index}-${Date.now()}`,
          url: photo,
          preview: photo,
          rotation: 0
        }
      }
      return {
        ...photo,
        id: photo.id || `photo-${index}-${Date.now()}`,
        rotation: photo.rotation || 0
      }
    })
    isInitialized = true
    if (window.debugPhotos) {
      console.log('📷 PhotoGallery: инициализировано с', localPhotos.value.length, 'фото')
    }
  }
}, { immediate: true })

// Emit changes - emit full photo objects for proper saving
watch(localPhotos, (newPhotos) => {
  const photosToEmit = newPhotos.map(photo => {
    // For new files, return the file object
    if (photo.file) return photo.file
    // For existing photos, return the URL
    return photo.url || photo.preview || photo
  })
  emit('update:photos', photosToEmit)
}, { deep: true })

// Methods
const triggerPhotoInput = () => {
  photoInput.value?.click()
}

const handlePhotoUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  processPhotos(files)
  target.value = ''
}

const processPhotos = (files: File[]) => {
  error.value = ''
  
  const imageFiles = files.filter(file => file.type.startsWith('image/'))
  
  if (imageFiles.length === 0) {
    error.value = 'Выберите изображения'
    return
  }
  
  if (localPhotos.value.length + imageFiles.length > 10) {
    error.value = 'Максимум 10 фотографий'
    return
  }
  
  imageFiles.forEach(file => {
    const reader = new FileReader()
    reader.onload = (e) => {
      const photo: Photo = {
        id: Date.now() + Math.random(),
        file: file,
        preview: e.target?.result as string,
        name: file.name,
        rotation: 0
      }
      localPhotos.value = [...localPhotos.value, photo]
    }
    reader.readAsDataURL(file)
  })
}

const removePhoto = (index: number) => {
  if (window.debugPhotos) {
    console.log(`🗑️ Удаление фото [${index}] из ${localPhotos.value.length}`)
  }
  
  // Создаем новый массив без удаляемого элемента
  const newPhotos = localPhotos.value.filter((_, i) => i !== index)
  
  // Обновляем локальный массив (это должно триггерить watch)
  localPhotos.value = newPhotos
  
  // Явно эмитим изменения для надежности
  const photosToEmit = newPhotos.map(photo => {
    if (photo.file) return photo.file
    return photo.url || photo.preview || photo
  })
  
  // Всегда эмитим актуальный массив
  emit('update:photos', photosToEmit)
  
  if (window.debugPhotos) {
    console.log(`✅ Эмит update:photos с ${photosToEmit.length} фото:`, photosToEmit)
  }
}

const rotatePhoto = (index: number) => {
  if (index < 0 || index >= localPhotos.value.length) return
  
  const newPhotos = [...localPhotos.value]
  const currentRotation = newPhotos[index].rotation || 0
  newPhotos[index] = {
    ...newPhotos[index],
    rotation: (currentRotation + 90) % 360
  }
  localPhotos.value = newPhotos
}

// Drag and drop handlers for file upload
const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  event.stopPropagation()
  
  isDragOver.value = false
  const files = Array.from(event.dataTransfer?.files || [])
  if (files.length > 0) {
    processPhotos(files)
  }
}

const handleDragOver = (event: DragEvent) => {
  event.preventDefault()
  if (event.dataTransfer?.types.includes('Files')) {
    isDragOver.value = true
  }
}

const handleDragLeave = (event: DragEvent) => {
  event.preventDefault()
  // Only reset if leaving the entire zone
  if (event.currentTarget === event.target) {
    isDragOver.value = false
  }
}

// Called when draggable reorder ends
const onDragEnd = () => {
  // The v-model binding automatically updates localPhotos
  // We just need to emit the change
  const photosToEmit = localPhotos.value.map(photo => {
    if (photo.file) return photo.file
    return photo.url || photo.preview || photo
  })
  emit('update:photos', photosToEmit)
}

const getPhotoUrl = (photo: Photo): string => {
  if (!photo) return ''
  if (typeof photo === 'string') return photo
  if (photo.preview) return photo.preview
  if (photo.url) return photo.url
  if (photo.file && photo.file instanceof File) {
    return URL.createObjectURL(photo.file)
  }
  return ''
}
</script>

<!-- Все стили мигрированы на Tailwind CSS с полной адаптивностью -->