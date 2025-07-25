<template>
  <PageSection title="Фотографии и видео">
    <p class="section-description">
      Добавьте качественные фото и видео ваших работ. Это поможет привлечь больше клиентов.
    </p>
    
    <div class="media-fields">
      <!-- Загрузка фотографий -->
      <PhotoUploader
        v-model="form.photos"
        label="Фотографии работ"
        :max-files="10"
        :error="errors.photos"
        hint="Добавьте до 10 качественных фотографий ваших работ. Рекомендуемый формат 4:3"
        @error="handlePhotoError"
      />

      <!-- Загрузка видео -->
      <VideoUploader
        v-model="form.video"
        label="Видео презентация"
        :error="errors.video"
        hint="Добавьте короткое видео (до 50MB) для демонстрации ваших услуг"
        @error="handleVideoError"
      />

      <!-- Настройки медиа -->
      <div class="media-settings">
        <h4 class="settings-title">Настройки отображения</h4>
        
        <CheckboxGroup
          v-model="form.media_settings"
          :options="[
            { value: 'show_photos_in_gallery', label: 'Показывать фото в галерее на странице объявления' },
            { value: 'allow_download_photos', label: 'Разрешить клиентам скачивать фотографии' },
            { value: 'watermark_photos', label: 'Добавить водяной знак на фотографии' }
          ]"
        />
      </div>

      <!-- Советы по медиа -->
      <div class="media-tips">
        <h4 class="tips-title">Советы по качественным фото и видео</h4>
        <ul class="tips-list">
          <li>Используйте хорошее освещение</li>
          <li>Делайте фото в высоком разрешении</li>
          <li>Показывайте процесс работы</li>
          <li>Добавляйте фото до и после</li>
          <li>Видео должно быть не более 2-3 минут</li>
        </ul>
      </div>
    </div>
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import PhotoUploader from '@/Components/Form/Upload/PhotoUploader.vue'
import VideoUploader from '@/Components/Form/Upload/VideoUploader.vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

export default {
  name: 'MediaSection',
  components: {
    PageSection,
    PhotoUploader,
    VideoUploader,
    CheckboxGroup
  },
  props: {
    form: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  methods: {
    handlePhotoError(error) {
      this.$emit('error', { photos: error })
    },
    
    handleVideoError(error) {
      this.$emit('error', { video: error })
    }
  }
}
</script>

<style scoped>
.section-description {
  color: #666;
  font-size: 14px;
  margin-bottom: 20px;
  line-height: 1.5;
}

.media-fields {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.media-settings {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.settings-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin: 0 0 12px 0;
}

.media-tips {
  padding: 16px;
  background: #e3f2fd;
  border-radius: 8px;
  border: 1px solid #bbdefb;
}

.tips-title {
  font-size: 14px;
  font-weight: 600;
  color: #1976d2;
  margin: 0 0 8px 0;
}

.tips-list {
  margin: 0;
  padding-left: 20px;
  color: #1976d2;
  font-size: 13px;
  line-height: 1.5;
}

.tips-list li {
  margin-bottom: 4px;
}

.tips-list li:last-child {
  margin-bottom: 0;
}
</style> 