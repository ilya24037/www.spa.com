<template>
  <div class="verification-photo-section space-y-4">
    <!-- Блок преимуществ верификации -->
    <div class="border border-gray-200 rounded-lg p-4">
      <h3 class="text-base font-semibold text-gray-800 mb-3">Преимущества верификации:</h3>
      <ul class="text-base text-gray-800 space-y-1">
        <li>• Возможность бесплатного размещения</li>
        <li>• Получение специального статуса/значка</li>
        <li>• Увеличение просмотров анкеты (до 40%)</li>
      </ul>
    </div>

    <!-- Если фото уже загружено - показываем в рамке -->
    <div v-if="photo" class="border-2 border-dashed border-gray-300 rounded-lg p-4">
      <div class="flex items-start gap-4">
        <!-- Превью фото -->
        <div class="relative">
          <img
            :src="getPhotoUrl(photo)"
            alt="Проверочное фото"
            class="w-32 h-32 object-cover rounded-lg"
          >
          <!-- Статус badge -->
          <div v-if="status === 'verified'" class="absolute -top-2 -right-2 bg-green-500 text-white rounded-full p-1">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>

        <!-- Информация и действия -->
        <div class="flex-1">
          <p class="text-sm text-gray-700 font-medium mb-1">Проверочное фото</p>
          <p v-if="status === 'verified'" class="text-sm text-green-600">
            Фото проверено
          </p>
          <p v-else-if="status === 'pending'" class="text-sm text-yellow-600">
            На проверке...
          </p>
          <p v-else class="text-sm text-gray-500">
            Загружено
          </p>
          <button
            @click="removePhoto"
            type="button"
            class="mt-2 text-sm text-red-600 hover:text-red-700"
          >
            Удалить фото
          </button>
        </div>
      </div>
    </div>

    <!-- Зона загрузки (минималистичный стиль как в PhotoUploadZone) -->
    <div v-else
         class="photo-upload-zone border-2 border-dashed border-gray-300 rounded-lg transition-all duration-200 cursor-pointer"
         :class="{
           'border-blue-400 bg-blue-50': isDragging,
           'bg-white': !isDragging
         }"
         @drop.prevent="handleDrop"
         @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false">

      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        @change="handleFileSelect"
        class="hidden"
        :disabled="uploading"
      >

      <!-- Контент зоны загрузки (минималистичный) -->
      <div class="text-center py-3 px-4" @click="$refs.fileInput.click()">
        <!-- Иконка и текст -->
        <div class="flex items-center justify-center space-x-2 mb-3">
          <svg class="h-5 w-5 text-gray-400 flex-shrink-0"
               fill="none"
               viewBox="0 0 24 24"
               stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
          </svg>
          <span class="text-sm text-gray-600">
            {{ isDragging ? 'Отпустите файл здесь' : 'Перетащите фото в эту область или нажмите выбрать фото' }}
          </span>
        </div>

        <!-- Кнопка -->
        <button
          type="button"
          class="px-3 py-1.5 bg-blue-500 text-white text-sm rounded hover:bg-blue-600"
        >
          Выбрать проверочное фото
        </button>
      </div>
    </div>

    <!-- Инструкция для проверочного фото -->
    <div class="bg-white border border-gray-200 rounded-lg p-4">
      <h3 class="text-base font-semibold text-gray-800 mb-3">Инструкция для проверочного фото</h3>
      <ol class="text-base text-gray-800 space-y-2">
        <li>
          <strong>1.</strong> Напишите на листе от руки текущую дату
          <div class="text-sm text-gray-500 ml-4">Для новых анкет - не старше 1 недели</div>
        </li>
        <li>
          <strong>2.</strong> Сфотографируйтесь в полный рост с листом в руках, в том же образе что на основных фото
          <div class="text-sm text-gray-500 ml-4">Лицо показывать не обязательно</div>
        </li>
        <li>
          <strong>3.</strong> Загрузите фото
          <div class="text-sm text-gray-500 ml-4">Проверка действительна 6 месяцев</div>
        </li>
      </ol>
      <p class="text-sm text-gray-500 mt-3 pt-3 border-t border-gray-100">
        Проверочное фото не публикуется в анкете
      </p>
    </div>

    <!-- Состояние загрузки -->
    <div v-if="uploading" class="flex items-center justify-center">
      <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span class="ml-2 text-sm text-gray-600">Загрузка...</span>
    </div>

    <!-- Ошибка -->
    <div v-if="error" class="p-2 bg-red-50 border border-red-200 rounded text-sm text-red-600">
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  photo?: string | null
  status?: string
  adId?: number
}

const props = withDefaults(defineProps<Props>(), {
  photo: null,
  status: 'none',
  adId: 0
})

const emit = defineEmits<{
  'update:photo': [value: string | null]
  'uploaded': [path: string]
}>()

const isDragging = ref(false)
const uploading = ref(false)
const error = ref<string | null>(null)

const handleDrop = (e: DragEvent) => {
  isDragging.value = false
  const file = e.dataTransfer?.files[0]
  if (file) processFile(file)
}

const handleFileSelect = (e: Event) => {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  if (file) processFile(file)
}

const processFile = async (file: File) => {
  error.value = null

  // Валидация
  if (!file.type.match(/^image\/(jpeg|jpg|png)$/)) {
    error.value = 'Только JPG или PNG'
    return
  }

  if (file.size > 15 * 1024 * 1024) {
    error.value = 'Максимум 15 МБ'
    return
  }

  uploading.value = true

  try {
    // Создаем временный превью через base64 для отображения
    const reader = new FileReader()
    const previewUrl = await new Promise<string>((resolve, reject) => {
      reader.onload = (e) => resolve(e.target?.result as string)
      reader.onerror = reject
      reader.readAsDataURL(file)
    })

    // Сразу показываем превью пока идет загрузка
    emit('update:photo', previewUrl)

    // НЕ отправляем файл на сервер - просто сохраняем base64 как временное решение
    // Файл будет отправлен вместе с формой при сохранении черновика
    // Это избежит проблем с авторизацией и упростит процесс

    emit('uploaded', previewUrl)

  } catch (err) {
    error.value = 'Ошибка при загрузке файла'
    console.error(err)
    emit('update:photo', null)
  } finally {
    uploading.value = false
  }
}

const removePhoto = () => {
  emit('update:photo', null)
  error.value = null
}

// Функция для получения URL фото (поддерживает base64 и обычные пути)
const getPhotoUrl = (photo: string | null): string => {
  if (!photo) return ''

  // Если это base64
  if (photo.startsWith('data:image')) {
    return photo
  }

  // Если это относительный путь
  if (photo.startsWith('/')) {
    return photo
  }

  // Если это путь из storage
  if (photo.includes('storage/')) {
    return '/' + photo
  }

  // По умолчанию возвращаем как есть
  return photo
}
</script>

<style scoped>
/* Минималистичный стиль как в PhotoUploadZone */
.photo-upload-zone {
  min-height: auto;
}
</style>