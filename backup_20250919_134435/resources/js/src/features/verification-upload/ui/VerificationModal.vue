<template>
  <Teleport to="body">
    <div v-if="isOpen" class="verification-modal">
      <!-- Оверлей -->
      <div class="fixed inset-0 bg-black bg-opacity-50 z-40" @click="close"></div>
      
      <!-- Модальное окно -->
      <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
          <!-- Заголовок -->
          <div class="sticky top-0 bg-white border-b px-6 py-4">
            <div class="flex items-center justify-between">
              <h2 class="text-xl font-semibold">Подтверждение фото</h2>
              <button @click="close" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Контент -->
          <div class="p-6">
            <!-- Простая инструкция как на Avito -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
              <h3 class="font-medium text-blue-900 mb-2">Как подтвердить фото?</h3>
              <ol class="text-sm text-blue-800 space-y-1">
                <li>1. Напишите на листке текущую дату и "для SPA"</li>
                <li>2. Сфотографируйтесь с листком (лицо должно быть видно)</li>
                <li>3. Загрузите фото ниже</li>
              </ol>
            </div>
            
            <!-- Если уже загружено -->
            <div v-if="uploadedPhoto" class="mb-4">
              <img :src="uploadedPhoto" class="w-full rounded-lg" alt="Проверочное фото">
              <button @click="removePhoto" class="mt-2 text-red-600 text-sm hover:underline">
                Удалить и загрузить другое
              </button>
            </div>
            
            <!-- Зона загрузки -->
            <div v-else 
                 @dragover.prevent="isDragging = true"
                 @dragleave.prevent="isDragging = false"
                 @drop.prevent="handleDrop"
                 :class="[
                   'border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors',
                   isDragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300 hover:border-gray-400'
                 ]"
                 @click="$refs.fileInput.click()">
              
              <input ref="fileInput" 
                     type="file" 
                     accept="image/*" 
                     @change="handleFileSelect" 
                     class="hidden">
              
              <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
              </svg>
              
              <p class="text-gray-600 mb-1">Нажмите или перетащите фото</p>
              <p class="text-sm text-gray-500">JPG, PNG до 10MB</p>
            </div>
            
            <!-- Ошибка -->
            <div v-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
              <p class="text-sm text-red-600">{{ error }}</p>
            </div>
          </div>
          
          <!-- Кнопки действий -->
          <div class="sticky bottom-0 bg-white border-t px-6 py-4">
            <div class="flex gap-3">
              <button @click="close" 
                      class="flex-1 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                Отмена
              </button>
              <button @click="submitVerification" 
                      :disabled="!uploadedPhoto || submitting"
                      :class="[
                        'flex-1 px-4 py-2 rounded-lg transition-colors',
                        uploadedPhoto && !submitting
                          ? 'bg-blue-500 text-white hover:bg-blue-600' 
                          : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                      ]">
                <span v-if="submitting">Отправка...</span>
                <span v-else>Отправить на проверку</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref } from 'vue'

interface Props {
  adId: number
  modelValue: boolean
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: boolean]
  'submitted': []
}>()

const isDragging = ref(false)
const uploadedPhoto = ref<string | null>(null)
const uploadedFile = ref<File | null>(null)
const error = ref<string | null>(null)
const submitting = ref(false)

const isOpen = computed(() => props.modelValue)

const close = () => {
  emit('update:modelValue', false)
}

const handleDrop = (event: DragEvent) => {
  isDragging.value = false
  const file = event.dataTransfer?.files[0]
  if (file) processFile(file)
}

const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  if (file) processFile(file)
}

const processFile = (file: File) => {
  error.value = null
  
  // Проверка типа
  if (!file.type.startsWith('image/')) {
    error.value = 'Выберите изображение'
    return
  }
  
  // Проверка размера
  if (file.size > 10 * 1024 * 1024) {
    error.value = 'Файл слишком большой (максимум 10MB)'
    return
  }
  
  // Создаем превью
  const reader = new FileReader()
  reader.onload = (e) => {
    uploadedPhoto.value = e.target?.result as string
    uploadedFile.value = file
  }
  reader.readAsDataURL(file)
}

const removePhoto = () => {
  uploadedPhoto.value = null
  uploadedFile.value = null
  error.value = null
}

const submitVerification = async () => {
  if (!uploadedFile.value) return
  
  submitting.value = true
  error.value = null
  
  try {
    const formData = new FormData()
    formData.append('photo', uploadedFile.value)
    
    const response = await fetch(`/api/ads/${props.adId}/verification/photo`, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      credentials: 'include',
      body: formData
    })
    
    if (response.ok) {
      emit('submitted')
      close()
    } else {
      const data = await response.json()
      error.value = data.message || 'Ошибка при загрузке'
    }
  } catch (err) {
    error.value = 'Произошла ошибка. Попробуйте еще раз'
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.verification-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
}
</style>