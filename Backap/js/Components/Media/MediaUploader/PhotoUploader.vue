<template>
  <div class="photo-uploader">
    <label class="upload-area">
      <input 
        type="file" 
        multiple 
        accept="image/*"
        @change="handleFileSelect"
        class="hidden"
      >
      
      <div class="upload-content">
        <CameraIcon class="w-8 h-8 text-gray-400" />
        <p class="text-sm text-gray-600">
          Выберите фотографии (до 5 файлов)
        </p>
        <p class="text-xs text-gray-500">
          JPG, PNG, WebP до 5MB каждый
        </p>
      </div>
    </label>

    <!-- Прогресс загрузки -->
    <div v-if="uploading" class="upload-progress">
      <div class="progress-bar">
        <div 
          class="progress-fill"
          :style="{ width: uploadProgress + '%' }"
        ></div>
      </div>
      <p class="text-sm text-gray-600">
        Загружается... {{ uploadProgress }}%
      </p>
    </div>

    <!-- Ошибка -->
    <div v-if="error" class="upload-error">
      <p class="text-red-600 text-sm">{{ error }}</p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { CameraIcon } from '@heroicons/vue/24/outline'
import { useMedia } from '@/Composables/useMedia'

const props = defineProps({
  masterId: {
    type: Number,
    required: true
  }
})

const emit = defineEmits(['uploaded', 'error'])

const { uploading, uploadProgress, uploadError, uploadPhotos } = useMedia()

const error = ref(null)

// Обработка выбора файлов
const handleFileSelect = async (event) => {
  const files = Array.from(event.target.files)
  
  if (files.length === 0) return
  
  // Проверяем количество файлов
  if (files.length > 5) {
    error.value = 'Максимум 5 фотографий за раз'
    return
  }

  // Проверяем размеры файлов
  const maxSize = 5 * 1024 * 1024 // 5MB
  const oversizedFiles = files.filter(file => file.size > maxSize)
  
  if (oversizedFiles.length > 0) {
    error.value = `Файлы превышают 5MB: ${oversizedFiles.map(f => f.name).join(', ')}`
    return
  }

  try {
    error.value = null
    const uploadedPhotos = await uploadPhotos(props.masterId, files)
    emit('uploaded', uploadedPhotos)
  } catch (err) {
    error.value = err.message
    emit('error', err.message)
  }
}
</script>

<style scoped>
.upload-area {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-gray-400 transition-colors;
}

.upload-content {
  @apply flex flex-col items-center gap-2;
}

.upload-progress {
  @apply mt-4;
}

.progress-bar {
  @apply w-full bg-gray-200 rounded-full h-2 mb-2;
}

.progress-fill {
  @apply bg-blue-600 h-2 rounded-full transition-all duration-300;
}

.upload-error {
  @apply mt-4 p-3 bg-red-50 border border-red-200 rounded-lg;
}
</style> 