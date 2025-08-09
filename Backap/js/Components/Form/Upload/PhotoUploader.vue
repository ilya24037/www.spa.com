<template>
  <div class="photo-uploader">
    <label v-if="label" class="uploader-label">{{ label }}</label>
    
    <!-- Область загрузки -->
    <div 
      class="upload-area"
      :class="{ 
        'drag-over': isDragOver, 
        'has-files': photos.length > 0,
        'error': error 
      }"
      @drop="handleDrop"
      @dragover="handleDragOver"
      @dragleave="handleDragLeave"
      @click="triggerFileInput"
    >
      <input
        ref="fileInput"
        type="file"
        multiple
        accept="image/*"
        @change="handleFileSelect"
        class="hidden-input"
      />
      
      <div v-if="photos.length === 0" class="upload-placeholder">
        <div class="upload-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7,10 12,15 17,10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
        </div>
        <p class="upload-text">Перетащите фото сюда или нажмите для выбора</p>
        <p class="upload-hint">До {{ maxFiles }} фото, формат JPG, PNG</p>
      </div>
      
      <!-- Превью фотографий -->
      <div v-else class="photos-grid">
        <div 
          v-for="(photo, index) in photos" 
          :key="photo.id"
          class="photo-item"
        >
          <img 
            :src="photo.preview" 
            :alt="`Фото ${index + 1}`"
            class="photo-preview"
          />
          <div class="photo-overlay">
            <button 
              type="button"
              class="remove-btn"
              @click.stop="removePhoto(index)"
              title="Удалить фото"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
            <div class="photo-number">{{ index + 1 }}</div>
          </div>
        </div>
        
        <!-- Кнопка добавления еще фото -->
        <div 
          v-if="photos.length < maxFiles"
          class="add-more-btn"
          @click="triggerFileInput"
        >
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <line x1="12" y1="5" x2="12" y2="19"/>
            <line x1="5" y1="12" x2="19" y2="12"/>
          </svg>
          <span>Добавить фото</span>
        </div>
      </div>
    </div>
    
    <!-- Ошибки -->
    <div v-if="error" class="error-message">{{ error }}</div>
    <div v-if="hint && !error" class="hint-text">{{ hint }}</div>
    
    <!-- Прогресс загрузки -->
    <div v-if="uploading" class="upload-progress">
      <div class="progress-bar">
        <div 
          class="progress-fill" 
          :style="{ width: uploadProgress + '%' }"
        ></div>
      </div>
      <span class="progress-text">Загрузка... {{ uploadProgress }}%</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'PhotoUploader',
  props: {
    modelValue: {
      type: Array,
      default: () => []
    },
    label: {
      type: String,
      default: 'Фотографии'
    },
    maxFiles: {
      type: Number,
      default: 10
    },
    maxFileSize: {
      type: Number,
      default: 5 * 1024 * 1024 // 5MB
    },
    error: {
      type: String,
      default: ''
    },
    hint: {
      type: String,
      default: 'Добавьте качественные фото ваших работ'
    },
    uploading: {
      type: Boolean,
      default: false
    },
    uploadProgress: {
      type: Number,
      default: 0
    }
  },
  emits: ['update:modelValue', 'upload'],
  data() {
    return {
      isDragOver: false
    }
  },
  computed: {
    photos: {
      get() {
        return this.modelValue
      },
      set(value) {
        this.$emit('update:modelValue', value)
      }
    }
  },
  methods: {
    triggerFileInput() {
      this.$refs.fileInput.click()
    },
    
    handleFileSelect(event) {
      const files = Array.from(event.target.files)
      this.processFiles(files)
      event.target.value = '' // Сброс input
    },
    
    handleDrop(event) {
      event.preventDefault()
      this.isDragOver = false
      
      const files = Array.from(event.dataTransfer.files)
      this.processFiles(files)
    },
    
    handleDragOver(event) {
      event.preventDefault()
      this.isDragOver = true
    },
    
    handleDragLeave(event) {
      event.preventDefault()
      this.isDragOver = false
    },
    
    processFiles(files) {
      const imageFiles = files.filter(file => file.type.startsWith('image/'))
      
      if (imageFiles.length === 0) {
        this.$emit('error', 'Выберите изображения')
        return
      }
      
      // Проверяем лимит файлов
      if (this.photos.length + imageFiles.length > this.maxFiles) {
        this.$emit('error', `Максимум ${this.maxFiles} фотографий`)
        return
      }
      
      // Проверяем размер файлов
      const oversizedFiles = imageFiles.filter(file => file.size > this.maxFileSize)
      if (oversizedFiles.length > 0) {
        this.$emit('error', 'Некоторые файлы слишком большие (максимум 5MB)')
        return
      }
      
      // Создаем превью и добавляем файлы
      imageFiles.forEach(file => {
        const reader = new FileReader()
        reader.onload = (e) => {
          const photo = {
            id: Date.now() + Math.random(),
            file: file,
            preview: e.target.result,
            name: file.name,
            size: file.size
          }
          
          this.photos = [...this.photos, photo]
        }
        reader.readAsDataURL(file)
      })
    },
    
    removePhoto(index) {
      const newPhotos = [...this.photos]
      newPhotos.splice(index, 1)
      this.photos = newPhotos
    },
    
    // Метод для получения файлов для отправки
    getFiles() {
      return this.photos.map(photo => photo.file)
    },
    
    // Метод для очистки
    clear() {
      this.photos = []
    }
  }
}
</script>

<style scoped>
.photo-uploader {
  margin-bottom: 16px;
}

.uploader-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.upload-area {
  border: 2px dashed #ddd;
  border-radius: 8px;
  padding: 24px;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
  background: #fafafa;
  min-height: 120px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.upload-area:hover {
  border-color: #007bff;
  background: #f8f9ff;
}

.upload-area.drag-over {
  border-color: #007bff;
  background: #e3f2fd;
}

.upload-area.has-files {
  padding: 16px;
  min-height: auto;
}

.upload-area.error {
  border-color: #dc3545;
  background: #fff5f5;
}

.hidden-input {
  display: none;
}

.upload-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 8px;
}

.upload-icon {
  width: 48px;
  height: 48px;
  color: #666;
}

.upload-icon svg {
  width: 100%;
  height: 100%;
}

.upload-text {
  font-size: 16px;
  color: #333;
  margin: 0;
}

.upload-hint {
  font-size: 12px;
  color: #666;
  margin: 0;
}

.photos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  gap: 12px;
  width: 100%;
}

.photo-item {
  position: relative;
  aspect-ratio: 4/3;
  border-radius: 8px;
  overflow: hidden;
  background: #f0f0f0;
}

.photo-preview {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  opacity: 0;
  transition: opacity 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.photo-item:hover .photo-overlay {
  opacity: 1;
}

.remove-btn {
  position: absolute;
  top: 4px;
  right: 4px;
  width: 24px;
  height: 24px;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #dc3545;
}

.remove-btn svg {
  width: 14px;
  height: 14px;
}

.photo-number {
  position: absolute;
  bottom: 4px;
  left: 4px;
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
}

.add-more-btn {
  aspect-ratio: 4/3;
  border: 2px dashed #ddd;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  color: #666;
}

.add-more-btn:hover {
  border-color: #007bff;
  color: #007bff;
}

.add-more-btn svg {
  width: 24px;
  height: 24px;
  margin-bottom: 4px;
}

.add-more-btn span {
  font-size: 12px;
}

.error-message {
  color: #dc3545;
  font-size: 12px;
  margin-top: 4px;
}

.hint-text {
  color: #666;
  font-size: 12px;
  margin-top: 4px;
}

.upload-progress {
  margin-top: 12px;
}

.progress-bar {
  width: 100%;
  height: 4px;
  background: #e0e0e0;
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #007bff;
  transition: width 0.3s;
}

.progress-text {
  font-size: 12px;
  color: #666;
  margin-top: 4px;
  display: block;
}

/* Адаптивность */
@media (max-width: 768px) {
  .photos-grid {
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 8px;
  }
  
  .upload-area {
    padding: 16px;
  }
}
</style> 