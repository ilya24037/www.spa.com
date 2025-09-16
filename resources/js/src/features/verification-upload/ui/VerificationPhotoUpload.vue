<template>
  <div class="verification-photo-upload">
    <!-- Блок преимуществ -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 mb-6">
      <div class="flex items-start gap-3">
        <div class="text-2xl">✨</div>
        <div>
          <h3 class="font-semibold text-blue-900 mb-2">Получите значок "Фото проверено"</h3>
          <ul class="text-sm text-blue-800 space-y-1">
            <li>📈 +40% больше просмотров анкеты</li>
            <li>🔝 Приоритет в выдаче результатов</li>
            <li>🔒 Фото остается конфиденциальным (не публикуется)</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Простая инструкция -->
    <div class="bg-white border-2 border-gray-200 rounded-lg p-6 mb-6">
      <h3 class="font-semibold text-lg mb-4 text-center">Как сделать проверочное фото?</h3>

      <div class="grid md:grid-cols-2 gap-6">
        <!-- Шаг 1 -->
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0 w-10 h-10 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-lg">
            1
          </div>
          <div class="flex-1">
            <h4 class="font-semibold mb-2">Подготовьте листок</h4>
            <div class="bg-gray-50 border border-gray-300 rounded p-3 mb-2">
              <p class="text-sm mb-1">Напишите от руки:</p>
              <div class="font-mono bg-white p-2 rounded border">
                <div class="font-bold">{{ currentDate }}</div>
                <div class="font-bold">для FEIPITER</div>
              </div>
            </div>
            <p class="text-xs text-gray-600">✍️ Обязательно пишите от руки</p>
          </div>
        </div>

        <!-- Шаг 2 -->
        <div class="flex items-start gap-3">
          <div class="flex-shrink-0 w-10 h-10 bg-blue-500 text-white rounded-full flex items-center justify-center font-bold text-lg">
            2
          </div>
          <div class="flex-1">
            <h4 class="font-semibold mb-2">Сделайте фото</h4>
            <ul class="text-sm text-gray-700 space-y-2">
              <li class="flex items-start gap-2">
                <span class="text-green-500">✓</span>
                <span>Держите листок в руке</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-green-500">✓</span>
                <span>Должно быть видно ваше тело</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-green-500">✓</span>
                <span>Текст должен читаться</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-green-500">✓</span>
                <span>Как на фото в анкете</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Зона загрузки -->
    <div v-if="!currentFile && !uploadedPath" class="upload-zone">
      <label
        class="upload-label bg-gray-50 hover:bg-blue-50"
        :class="{ 'dragging': isDragging, 'bg-blue-50 border-blue-400': isDragging }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
      >
        <input
          ref="fileInput"
          type="file"
          accept="image/jpeg,image/jpg,image/png"
          class="hidden"
          @change="handleFileSelect"
          :disabled="uploading"
        >

        <div class="upload-content">
          <svg class="w-12 h-12 text-gray-400 mb-3 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
          </svg>

          <p class="text-lg font-medium text-gray-700 mb-1">
            Перетащите фото в эту область
          </p>
          <p class="text-sm text-gray-500 mb-3">
            или нажмите кнопку
          </p>
          <button type="button" class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors font-medium">
            Выбрать проверочное фото
          </button>
          <p class="text-xs text-gray-400 mt-3">
            JPG, PNG до 15 МБ
          </p>
        </div>
      </label>
    </div>
    
    <!-- Предпросмотр -->
    <div v-if="currentFile || uploadedPath" class="preview mt-4">
      <div class="relative inline-block">
        <img 
          :src="previewUrl || uploadedPath"
          alt="Проверочное фото"
          class="max-h-64 rounded-lg border border-gray-300"
        >
        
        <!-- Статус -->
        <div v-if="uploading" class="absolute inset-0 bg-black bg-opacity-50 rounded-lg flex items-center justify-center">
          <div class="text-white">
            <svg class="animate-spin h-8 w-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Загрузка...
          </div>
        </div>
        
        <!-- Кнопки действий -->
        <div v-if="!uploading" class="mt-2 flex gap-2">
          <button
            v-if="!uploadedPath"
            @click="uploadPhoto"
            class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
          >
            Отправить на проверку
          </button>
          <button
            @click="removePhoto"
            class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
          >
            Удалить
          </button>
        </div>
      </div>
    </div>
    
    <!-- Ошибка -->
    <div v-if="error" class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3">
      <p class="text-sm text-red-600">{{ error }}</p>
    </div>
    
    <!-- Важная информация -->
    <div class="mt-6 flex flex-col sm:flex-row gap-4 text-xs text-gray-600">
      <div class="flex items-start gap-2">
        <span>⏰</span>
        <div>
          <strong>Актуальность:</strong><br>
          Для новых анкет - не старше недели
        </div>
      </div>
      <div class="flex items-start gap-2">
        <span>📅</span>
        <div>
          <strong>Срок действия:</strong><br>
          4 месяца (рекомендуем обновлять)
        </div>
      </div>
      <div class="flex items-start gap-2">
        <span>⚡</span>
        <div>
          <strong>Проверка:</strong><br>
          До 24 часов
        </div>
      </div>
    </div>

    <!-- Примеры фото (спойлер) -->
    <details class="mt-6">
      <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 select-none">
        Показать примеры правильных и неправильных фото
      </summary>
      <div class="mt-4 grid md:grid-cols-2 gap-4">
        <!-- Правильный пример -->
        <div class="border-2 border-green-200 rounded-lg p-3 bg-green-50">
          <div class="flex items-center gap-2 mb-2">
            <span class="text-green-500 text-xl">✓</span>
            <span class="font-semibold text-green-700">Правильно</span>
          </div>
          <ul class="text-xs text-gray-600 space-y-1">
            <li>• Видно лицо и тело</li>
            <li>• Листок с датой в руке</li>
            <li>• Текст читается четко</li>
            <li>• Соответствует фото в анкете</li>
          </ul>
        </div>
        <!-- Неправильный пример -->
        <div class="border-2 border-red-200 rounded-lg p-3 bg-red-50">
          <div class="flex items-center gap-2 mb-2">
            <span class="text-red-500 text-xl">✗</span>
            <span class="font-semibold text-red-700">Неправильно</span>
          </div>
          <ul class="text-xs text-gray-600 space-y-1">
            <li>• Только листок без человека</li>
            <li>• Нечитаемый текст</li>
            <li>• Старая дата</li>
            <li>• Не видно тело</li>
          </ul>
        </div>
      </div>
    </details>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { verificationApi } from '../api/verificationApi'

interface Props {
  adId: number
  currentPhoto?: string | null
  status?: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  uploaded: [path: string]
  deleted: []
}>()

// Состояние
const fileInput = ref<HTMLInputElement>()
const currentFile = ref<File | null>(null)
const uploadedPath = ref<string | null>(props.currentPhoto || null)
const previewUrl = ref<string | null>(null)
const isDragging = ref(false)
const uploading = ref(false)
const error = ref<string | null>(null)

// Вычисляемые
const currentDate = computed(() => {
  const date = new Date()
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  })
})

// Методы
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectFile(target.files[0])
  }
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
    selectFile(event.dataTransfer.files[0])
  }
}

const selectFile = (file: File) => {
  error.value = null
  
  // Валидация
  if (!['image/jpeg', 'image/jpg', 'image/png'].includes(file.type)) {
    error.value = 'Недопустимый формат файла. Разрешены JPG, PNG'
    return
  }
  
  if (file.size > 10 * 1024 * 1024) {
    error.value = 'Файл слишком большой. Максимум 10MB'
    return
  }
  
  currentFile.value = file
  
  // Создаем preview
  const reader = new FileReader()
  reader.onload = (e) => {
    previewUrl.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

const uploadPhoto = async () => {
  if (!currentFile.value) return
  
  uploading.value = true
  error.value = null
  
  try {
    const result = await verificationApi.uploadPhoto(props.adId, currentFile.value)
    
    if (result.success) {
      uploadedPath.value = result.path || null
      currentFile.value = null
      previewUrl.value = null
      emit('uploaded', result.path!)
    } else {
      error.value = result.message
    }
  } catch (err: any) {
    error.value = err.response?.data?.message || 'Ошибка при загрузке фото'
  } finally {
    uploading.value = false
  }
}

const removePhoto = async () => {
  if (uploadedPath.value) {
    try {
      await verificationApi.deleteFiles(props.adId)
      emit('deleted')
    } catch (err) {
      console.error('Failed to delete photo:', err)
    }
  }
  
  currentFile.value = null
  previewUrl.value = null
  uploadedPath.value = null
  error.value = null
  
  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const handleImageError = (event: Event) => {
  const target = event.target as HTMLImageElement
  target.style.display = 'none'
}

// Watchers
watch(() => props.currentPhoto, (newVal) => {
  if (newVal && !currentFile.value) {
    uploadedPath.value = newVal
  }
})
</script>

<style scoped>
.upload-label {
  @apply block w-full cursor-pointer;
  @apply border-2 border-dashed border-gray-300 rounded-lg;
  @apply hover:border-blue-400 transition-all duration-200;
  @apply p-8 text-center;
}

.upload-label.dragging {
  @apply border-blue-500;
}

.upload-content {
  @apply flex flex-col items-center;
}

.upload-content button {
  @apply pointer-events-none;
}
</style>