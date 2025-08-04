<!-- resources/js/src/entities/ad/ui/AdForm/AdForm.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <!-- Прогресс-бар -->
    <div v-if="showProgress" :class="PROGRESS_CONTAINER_CLASSES">
      <div :class="PROGRESS_BAR_CLASSES">
        <div :class="PROGRESS_FILL_CLASSES" :style="{ width: progressPercent + '%' }"></div>
      </div>
      <div :class="PROGRESS_TEXT_CLASSES">{{ progressText }}</div>
    </div>

    <!-- Основная форма -->
    <form @submit.prevent="handleSubmit" novalidate :class="FORM_CLASSES">
      
      <!-- ГРУППА 1: Базовая информация -->
      <div :class="FORM_GROUP_CLASSES">
        <AdFormBasicInfo :errors="formErrors" />
      </div>

      <!-- ГРУППА 2: Персональная информация -->
      <div :class="FORM_GROUP_CLASSES">
        <AdFormPersonalInfo :errors="formErrors" />
      </div>

      <!-- ГРУППА 3: Коммерческая информация -->
      <div :class="FORM_GROUP_CLASSES">
        <AdFormCommercialInfo :errors="formErrors" />
      </div>

      <!-- ГРУППА 4: Локация и контакты -->
      <div :class="FORM_GROUP_CLASSES">
        <AdFormLocationInfo :errors="formErrors" />
      </div>

      <!-- ГРУППА 5: Медиа -->
      <div :class="FORM_GROUP_CLASSES">
        <AdFormMediaInfo 
          :uploading="uploading"
          :upload-progress="uploadProgress"
          :uploading-video="uploadingVideo"
          :video-upload-progress="videoUploadProgress"
          :errors="formErrors"
          @photo-error="handlePhotoError"
          @video-error="handleVideoError"
        />
      </div>

      <!-- Кнопки действий -->
      <div :class="ACTIONS_CLASSES">
        <AdFormActionButton
          variant="secondary"
          size="large"
          :loading="saving"
          @click="handleSaveDraft"
        >
          {{ saving ? 'Сохранение...' : 'Сохранить черновик' }}
        </AdFormActionButton>

        <AdFormActionButton
          variant="primary"
          size="large"
          :loading="saving"
          @click="handlePublish"
        >
          {{ saving ? 'Публикация...' : 'Разместить объявление' }}
        </AdFormActionButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAdFormStore } from './stores/adFormStore'

// Компоненты групп формы
import AdFormBasicInfo from './components/AdFormBasicInfo.vue'
import AdFormPersonalInfo from './components/AdFormPersonalInfo.vue'
import AdFormCommercialInfo from './components/AdFormCommercialInfo.vue'
import AdFormLocationInfo from './components/AdFormLocationInfo.vue'
import AdFormMediaInfo from './components/AdFormMediaInfo.vue'
import AdFormActionButton from './components/AdFormActionButton.vue'

// 🎯 Стили согласно дизайн-системе
const CONTAINER_CLASSES = 'ad-form-container'
const PROGRESS_CONTAINER_CLASSES = 'form-progress mb-6'
const PROGRESS_BAR_CLASSES = 'w-full bg-gray-200 rounded-full h-2 mb-2'
const PROGRESS_FILL_CLASSES = 'bg-blue-600 h-2 rounded-full transition-all duration-300'
const PROGRESS_TEXT_CLASSES = 'text-sm text-gray-600 text-center'
const FORM_CLASSES = 'ad-form space-y-8'
const FORM_GROUP_CLASSES = 'form-group space-y-6'
const ACTIONS_CLASSES = 'form-actions flex gap-4 pt-6 border-t border-gray-200'

const props = defineProps({
  category: {
    type: String,
    required: true
  },
  categories: {
    type: Array,
    required: true
  },
  adId: {
    type: [String, Number],
    default: null
  },
  initialData: {
    type: Object,
    default: () => ({})
  },
  showProgress: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['success', 'error'])

// Используем Pinia store для состояния формы
const store = useAdFormStore()

// Состояние компонента
const saving = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const uploadingVideo = ref(false)
const videoUploadProgress = ref(0)

// Инициализация store
onMounted(() => {
  store.initializeForm(props.initialData, {
    adId: props.adId,
    category: props.category,
    categories: props.categories
  })
})

// Вычисляемые свойства
const formData = computed(() => store.formData)
const formErrors = computed(() => store.errors)

const progressPercent = computed(() => {
  return store.completionPercentage || 0
})

const progressText = computed(() => {
  return `Заполнено ${progressPercent.value}%`
})

// Методы
const handleSubmit = async () => {
  // Проверяем какая кнопка была нажата
  // Логика обработки будет в соответствующих методах
}

const handleSaveDraft = async () => {
  saving.value = true
  try {
    await store.saveAsDraft()
    emit('success', { action: 'draft', data: store.formData })
  } catch (error) {
    emit('error', { action: 'draft', error })
  } finally {
    saving.value = false
  }
}

const handlePublish = async () => {
  saving.value = true
  try {
    await store.publishAd()
    emit('success', { action: 'publish', data: store.formData })
  } catch (error) {
    emit('error', { action: 'publish', error })
  } finally {
    saving.value = false
  }
}

const handlePhotoError = (error) => {
  emit('error', { action: 'photo_upload', error })
}

const handleVideoError = (error) => {
  emit('error', { action: 'video_upload', error })
}
</script>

<style scoped>
.ad-form-container {
  @apply max-w-4xl mx-auto;
}

.form-progress {
  @apply sticky top-0 z-10 bg-white p-4 border-b border-gray-200;
}

.ad-form {
  @apply bg-white;
}

.form-group {
  @apply bg-gray-50 p-6 rounded-lg;
}

.form-actions {
  @apply sticky bottom-0 bg-white p-4 border-t border-gray-200;
}
</style>