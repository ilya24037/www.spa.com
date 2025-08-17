<template>
  <div class="file-upload-component">
    <!-- Зона загрузки -->
    <div
      class="upload-zone"
      :class="{ 'drag-over': isDragOver }"
      @drop="handleDrop"
      @dragover.prevent="isDragOver = true"
      @dragleave="isDragOver = false"
    >
      <input
        ref="fileInput"
        type="file"
        :multiple="multiple"
        :accept="accept"
        @change="handleFileSelect"
        hidden
      />
      
      <div v-if="!files.length" class="upload-placeholder" @click="$refs.fileInput.click()">
        <svg class="upload-icon" width="48" height="48" viewBox="0 0 24 24">
          <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
        </svg>
        <p class="upload-text">
          Перетащите файлы сюда или <span class="link">выберите</span>
        </p>
        <p class="upload-hint">
          Максимум {{ maxFiles }} файлов, до {{ maxSizeMB }}MB каждый
        </p>
      </div>
    </div>
    
    <!-- Загруженные файлы -->
    <div v-if="files.length" class="upload-files">
      <TransitionGroup name="list">
        <div
          v-for="(file, index) in files"
          :key="file.id"
          class="file-item"
          :class="{ video: file.type.startsWith('video/') }"
          draggable="true"
          @dragstart="startDrag(index)"
          @dragend="endDrag"
          @dragover.prevent
          @drop="handleReorder($event, index)"
        >
          <!-- Превью -->
          <span class="item">
            <img
              v-if="file.preview"
              :src="file.preview"
              :alt="file.name"
              class="file-preview"
            />
            <div v-else class="file-icon">
              {{ getFileExtension(file.name) }}
            </div>
          </span>
          
          <!-- Информация о файле -->
          <div class="file-info">
            <p class="file-name">{{ file.name }}</p>
            <p class="file-size">{{ formatFileSize(file.size) }}</p>
            
            <!-- Прогресс загрузки -->
            <div v-if="file.progress < 100" class="progress-bar">
              <div class="progress-fill" :style="{ width: file.progress + '%' }"></div>
            </div>
          </div>
          
          <!-- Действия -->
          <nav class="icons">
            <button
              v-if="file.type.startsWith('image/')"
              class="icon-btn crop"
              @click="cropImage(file)"
              title="Обрезать"
            >
              ✂️
            </button>
            <button
              class="icon-btn delete"
              @click="removeFile(index)"
              title="Удалить"
            >
              ❌
            </button>
          </nav>
        </div>
      </TransitionGroup>
      
      <!-- Кнопка добавления -->
      <button
        v-if="files.length < maxFiles"
        class="add-more-btn"
        @click="$refs.fileInput.click()"
      >
        + Добавить еще
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'

interface UploadFile {
  id: string
  name: string
  size: number
  type: string
  file: File
  preview?: string
  progress: number
}

const props = defineProps<{
  multiple?: boolean
  maxFiles?: number
  maxSizeMB?: number
  accept?: string
}>()

const emit = defineEmits<{
  upload: [files: File[]]
  remove: [index: number]
  reorder: [from: number, to: number]
}>()

// Значения по умолчанию
const multiple = computed(() => props.multiple ?? true)
const maxFiles = computed(() => props.maxFiles ?? 10)
const maxSizeMB = computed(() => props.maxSizeMB ?? 10)
const accept = computed(() => props.accept ?? 'image/*,video/*')

// Состояние
const files = ref<UploadFile[]>([])
const isDragOver = ref(false)
const draggedIndex = ref<number | null>(null)

// Обработка выбора файлов
const handleFileSelect = (event: Event) => {
  const input = event.target as HTMLInputElement
  if (input.files) {
    addFiles(Array.from(input.files))
  }
}

// Обработка drag & drop
const handleDrop = (event: DragEvent) => {
  event.preventDefault()
  isDragOver.value = false
  
  if (event.dataTransfer?.files) {
    addFiles(Array.from(event.dataTransfer.files))
  }
}

// Добавление файлов
const addFiles = (newFiles: File[]) => {
  const remainingSlots = maxFiles.value - files.value.length
  const filesToAdd = newFiles.slice(0, remainingSlots)
  
  filesToAdd.forEach(file => {
    // Проверка размера
    if (file.size > maxSizeMB.value * 1024 * 1024) {
      alert(`Файл ${file.name} превышает максимальный размер`)
      return
    }
    
    const uploadFile: UploadFile = {
      id: Math.random().toString(36).substr(2, 9),
      name: file.name,
      size: file.size,
      type: file.type,
      file: file,
      progress: 0
    }
    
    // Создание превью для изображений
    if (file.type.startsWith('image/')) {
      const reader = new FileReader()
      reader.onload = (e) => {
        uploadFile.preview = e.target?.result as string
      }
      reader.readAsDataURL(file)
    }
    
    files.value.push(uploadFile)
    
    // Имитация загрузки
    simulateUpload(uploadFile)
  })
  
  emit('upload', filesToAdd)
}

// Имитация процесса загрузки
const simulateUpload = (file: UploadFile) => {
  const interval = setInterval(() => {
    file.progress += 10
    if (file.progress >= 100) {
      clearInterval(interval)
    }
  }, 200)
}

// Удаление файла
const removeFile = (index: number) => {
  files.value.splice(index, 1)
  emit('remove', index)
}

// Drag & drop для сортировки
const startDrag = (index: number) => {
  draggedIndex.value = index
}

const endDrag = () => {
  draggedIndex.value = null
}

const handleReorder = (event: DragEvent, targetIndex: number) => {
  event.preventDefault()
  if (draggedIndex.value !== null && draggedIndex.value !== targetIndex) {
    const draggedFile = files.value[draggedIndex.value]
    files.value.splice(draggedIndex.value, 1)
    files.value.splice(targetIndex, 0, draggedFile)
    emit('reorder', draggedIndex.value, targetIndex)
  }
}

// Утилиты
const formatFileSize = (bytes: number): string => {
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

const getFileExtension = (filename: string): string => {
  return filename.split('.').pop()?.toUpperCase() || 'FILE'
}

const cropImage = (file: UploadFile) => {
  console.log('Crop image:', file.name)
  // Здесь можно добавить логику обрезки изображения
}
</script>

<style scoped>
/* Стили адаптированы из file-upload.css */
.file-upload-component {
  width: 100%;
}

.upload-zone {
  border: 2px dashed #ddd;
  border-radius: 8px;
  padding: 40px;
  text-align: center;
  transition: all 0.3s;
  cursor: pointer;
}

.upload-zone.drag-over {
  border-color: #2196f3;
  background: #f0f8ff;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.upload-icon {
  fill: #999;
}

.upload-text {
  color: #666;
  font-size: 16px;
}

.upload-text .link {
  color: #2196f3;
  text-decoration: underline;
  cursor: pointer;
}

.upload-hint {
  color: #999;
  font-size: 14px;
  margin: 0;
}

.upload-files {
  display: flex;
  flex-wrap: wrap;
  gap: 15px;
  margin-top: 20px;
  padding: 0;
}

.file-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  width: 100%;
  cursor: move;
  transition: all 0.3s;
}

.file-item:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.file-item.video .item::after {
  content: '▶';
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  color: white;
  font-size: 24px;
  background: rgba(0, 0, 0, 0.5);
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.item {
  position: relative;
  width: 80px;
  height: 80px;
  flex-shrink: 0;
}

.file-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 4px;
}

.file-icon {
  width: 100%;
  height: 100%;
  background: #f5f5f5;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  font-size: 12px;
  color: #666;
  font-weight: bold;
}

.file-info {
  flex: 1;
  min-width: 0;
}

.file-name {
  margin: 0 0 5px;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.file-size {
  margin: 0;
  color: #999;
  font-size: 14px;
}

.progress-bar {
  margin-top: 10px;
  height: 4px;
  background: #e0e0e0;
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #2196f3;
  transition: width 0.3s;
}

.icons {
  display: flex;
  gap: 5px;
}

.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 20px;
  padding: 5px;
  opacity: 0.7;
  transition: opacity 0.3s;
}

.icon-btn:hover {
  opacity: 1;
}

.add-more-btn {
  width: 100%;
  padding: 20px;
  border: 2px dashed #ddd;
  background: none;
  border-radius: 8px;
  color: #666;
  cursor: pointer;
  font-size: 16px;
  transition: all 0.3s;
}

.add-more-btn:hover {
  border-color: #2196f3;
  color: #2196f3;
}

/* Анимации */
.list-enter-active,
.list-leave-active {
  transition: all 0.3s;
}

.list-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}

.list-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>