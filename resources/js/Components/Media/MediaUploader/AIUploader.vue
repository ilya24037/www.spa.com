<template>
  <div class="ai-uploader">
    <!-- Зона загрузки -->
    <div class="upload-zone">
      <input 
        type="file" 
        multiple 
        accept="image/*,video/*"
        @change="handleFiles"
        class="hidden"
        ref="fileInput"
      >
      
      <div 
        @click="$refs.fileInput.click()"
        @dragover.prevent
        @drop.prevent="handleDrop"
        class="upload-area"
      >
        <CloudArrowUpIcon class="w-12 h-12 text-gray-400" />
        <p class="text-lg font-medium">Загрузить фото/видео</p>
        <p class="text-sm text-gray-500">
          До 5 файлов, максимум 5MB каждый
        </p>
      </div>
    </div>

    <!-- Настройки приватности -->
    <div class="privacy-settings">
      <h3 class="text-lg font-medium mb-4">Настройки приватности</h3>
      
      <div class="setting-item">
        <label class="flex items-center gap-3">
          <input 
            type="checkbox" 
            v-model="settings.autoBlurFaces"
            class="w-4 h-4"
          >
          <span>Автоматически размывать лица</span>
        </label>
      </div>

      <div class="setting-item">
        <label class="flex items-center gap-3">
          <input 
            type="checkbox" 
            v-model="settings.addWatermark"
            class="w-4 h-4"
          >
          <span>Добавить водяной знак</span>
        </label>
      </div>

      <div class="setting-item">
        <label class="flex items-center gap-3">
          <input 
            type="checkbox" 
            v-model="settings.optimizeForWeb"
            class="w-4 h-4"
          >
          <span>Оптимизировать для веба (WebP)</span>
        </label>
      </div>
    </div>

    <!-- Прогресс обработки -->
    <div v-if="processing" class="processing-status">
      <div class="progress-bar">
        <div 
          class="progress-fill"
          :style="{ width: progress + '%' }"
        ></div>
      </div>
      <p class="text-sm text-gray-600">
        {{ processingStep }}... {{ progress }}%
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { CloudArrowUpIcon } from '@heroicons/vue/24/outline'

const fileInput = ref(null)
const processing = ref(false)
const progress = ref(0)
const processingStep = ref('')

const settings = reactive({
  autoBlurFaces: true,
  addWatermark: true,
  optimizeForWeb: true
})

const handleFiles = async (event) => {
  const files = Array.from(event.target.files)
  await processFiles(files)
}

const handleDrop = async (event) => {
  const files = Array.from(event.dataTransfer.files)
  await processFiles(files)
}

const processFiles = async (files) => {
  processing.value = true
  progress.value = 0

  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    
    // Загрузка файла
    processingStep.value = 'Загружаем файл'
    progress.value = (i / files.length) * 30
    await uploadFile(file)
    
    // AI-обработка
    if (settings.autoBlurFaces) {
      processingStep.value = 'Обрабатываем изображение'
      progress.value = 30 + (i / files.length) * 40
      await processWithAI(file)
    }
    
    // Оптимизация
    if (settings.optimizeForWeb) {
      processingStep.value = 'Оптимизируем для веба'
      progress.value = 70 + (i / files.length) * 30
      await optimizeFile(file)
    }
  }

  processing.value = false
  progress.value = 100
}

const uploadFile = async (file) => {
  // Логика загрузки
}

const processWithAI = async (file) => {
  // AI-размытие лиц
}

const optimizeFile = async (file) => {
  // Конвертация в WebP
}
</script>

<style scoped>
.upload-area {
  @apply border-2 border-dashed border-gray-300 rounded-lg p-8 text-center cursor-pointer hover:border-gray-400 transition-colors;
}

.privacy-settings {
  @apply mt-6 p-4 bg-gray-50 rounded-lg;
}

.setting-item {
  @apply mb-3;
}

.processing-status {
  @apply mt-4;
}

.progress-bar {
  @apply w-full bg-gray-200 rounded-full h-2 mb-2;
}

.progress-fill {
  @apply bg-blue-600 h-2 rounded-full transition-all duration-300;
}
</style> 