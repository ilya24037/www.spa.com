<template>
  <div class="video-uploader">
    <label v-if="label" class="uploader-label">{{ label }}</label>
    
    <!-- Область загрузки -->
    <div 
      class="upload-area"
      :class="{ 
        'drag-over': isDragOver, 
        'has-video': video,
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
        accept="video/*"
        @change="handleFileSelect"
        class="hidden-input"
      />
      
      <div v-if="!video" class="upload-placeholder">
        <div class="upload-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <polygon points="23,7 16,12 23,17 23,7"/>
            <rect x="1" y="5" width="15" height="14" rx="2" ry="2"/>
          </svg>
        </div>
        <p class="upload-text">Перетащите видео сюда или нажмите для выбора</p>
        <p class="upload-hint">Максимум {{ maxFileSize / (1024 * 1024) }}MB, формат MP4, AVI</p>
      </div>
      
      <!-- Превью видео -->
      <div v-else class="video-preview">
        <video 
          :src="video.preview" 
          class="video-element"
          controls
          preload="metadata"
        ></video>
        <div class="video-overlay">
          <button 
            type="button"
            class="remove-btn"
            @click.stop="removeVideo"
            title="Удалить видео"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
          <div class="video-info">
            <span class="video-name">{{ video.name }}</span>
            <span class="video-size">{{ formatFileSize(video.size) }}</span>
          </div>
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
      <span class="progress-text">Загрузка видео... {{ uploadProgress }}%</span>
    </div>
  </div>
</template>

<script>
export default {
  name: 'VideoUploader',
  props: {
    modelValue: {
      type: Object,
      default: null
    },
    label: {
      type: String,
      default: 'Видео'
    },
    maxFileSize: {
      type: Number,
      default: 50 * 1024 * 1024 // 50MB
    },
    error: {
      type: String,
      default: ''
    },
    hint: {
      type: String,
      default: 'Добавьте видео презентацию ваших услуг'
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
    video: {
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
      if (files.length > 0) {
        this.processFile(files[0])
      }
      event.target.value = '' // Сброс input
    },
    
    handleDrop(event) {
      event.preventDefault()
      this.isDragOver = false
      
      const files = Array.from(event.dataTransfer.files)
      if (files.length > 0) {
        this.processFile(files[0])
      }
    },
    
    handleDragOver(event) {
      event.preventDefault()
      this.isDragOver = true
    },
    
    handleDragLeave(event) {
      event.preventDefault()
      this.isDragOver = false
    },
    
    processFile(file) {
      if (!file.type.startsWith('video/')) {
        this.$emit('error', 'Выберите видео файл')
        return
      }
      
      if (file.size > this.maxFileSize) {
        this.$emit('error', `Файл слишком большой (максимум ${this.maxFileSize / (1024 * 1024)}MB)`)
        return
      }
      
      // Создаем превью
      const reader = new FileReader()
      reader.onload = (e) => {
        this.video = {
          id: Date.now() + Math.random(),
          file: file,
          preview: e.target.result,
          name: file.name,
          size: file.size
        }
      }
      reader.readAsDataURL(file)
    },
    
    removeVideo() {
      this.video = null
    },
    
    formatFileSize(bytes) {
      if (bytes === 0) return '0 Bytes'
      const k = 1024
      const sizes = ['Bytes', 'KB', 'MB', 'GB']
      const i = Math.floor(Math.log(bytes) / Math.log(k))
      return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
    },
    
    // Метод для получения файла для отправки
    getFile() {
      return this.video ? this.video.file : null
    },
    
    // Метод для очистки
    clear() {
      this.video = null
    }
  }
}
</script>

<style scoped>
.video-uploader {
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

.upload-area.has-video {
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

.video-preview {
  position: relative;
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
}

.video-element {
  width: 100%;
  border-radius: 8px;
  background: #000;
}

.video-overlay {
  position: absolute;
  top: 8px;
  right: 8px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.remove-btn {
  width: 32px;
  height: 32px;
  background: rgba(255, 255, 255, 0.9);
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #dc3545;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.remove-btn svg {
  width: 16px;
  height: 16px;
}

.video-info {
  background: rgba(0, 0, 0, 0.7);
  color: white;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  text-align: left;
}

.video-name {
  display: block;
  font-weight: 500;
  margin-bottom: 2px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 200px;
}

.video-size {
  display: block;
  opacity: 0.8;
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
  .upload-area {
    padding: 16px;
  }
  
  .video-preview {
    max-width: 100%;
  }
  
  .video-info {
    max-width: 150px;
  }
}
</style> 