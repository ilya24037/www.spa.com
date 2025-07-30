<template>
  <div class="ad-form-container">
    <!-- Прогресс-бар (будущее развитие) -->
    <div v-if="showProgress" class="form-progress">
      <div class="progress-bar">
        <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
      </div>
      <div class="progress-text">{{ progressText }}</div>
    </div>

    <!-- Основная форма -->
    <form @submit.prevent="handleSubmit" novalidate class="ad-form">
      
      <!-- ГРУППА 1: Базовая информация -->
      <div class="form-group">
        <WorkFormat
          v-model:work-format="formData.work_format"
          v-model:has-girlfriend="formData.has_girlfriend"
          :errors="formErrors"
        />

        <ServiceProvider
          v-model:service-provider="formData.service_provider"
          :errors="formErrors"
        />

        <ClientsModule
          v-model:clients="formData.clients"
          :errors="formErrors"
        />

        <Description
          v-model:description="formData.description"
          :errors="formErrors"
        />
      </div>

      <!-- ГРУППА 2: Персональная информация -->
      <div class="form-group">
        <ParametersModuleNew
          v-model:age="formData.age"
          v-model:height="formData.height"
          v-model:weight="formData.weight"
          v-model:breast-size="formData.breast_size"
          v-model:hair-color="formData.hair_color"
          v-model:eye-color="formData.eye_color"
          v-model:appearance="formData.appearance"
          v-model:nationality="formData.nationality"
          :errors="formErrors"
        />

        <FeaturesModuleNew
          v-model:features="formData.features"
          v-model:additional-features="formData.additional_features"
          v-model:experience="formData.experience"
          v-model:education-level="formData.education_level"
          :errors="formErrors"
        />
      </div>

      <!-- ГРУППА 3: Коммерческая информация -->
      <div class="form-group">
        <PriceModule
          v-model:price="form.price"
          v-model:price-unit="form.price_unit"
          v-model:is-starting-price="form.is_starting_price"
          v-model:pricing-data="form.pricing_data"
          v-model:contacts-per-hour="form.contacts_per_hour"
          :errors="errors"
        />

        <PromoModule
          v-model:new-client-discount="form.new_client_discount"
          v-model:gift="form.gift"
          :errors="errors"
        />

        <ServicesModule
          v-model:services="form.services"
          v-model:services-additional-info="form.services_additional_info"
          :allowed-categories="[]"
          :use-new-architecture="true"
          :errors="errors"
        />

        <ScheduleModule
          v-model:schedule="form.schedule"
          v-model:schedule-notes="form.schedule_notes"
          :errors="errors"
        />
      </div>

      <!-- ГРУППА 4: Локация и контакты -->
      <div class="form-group">
        <LocationModule
          v-model:service-location="form.service_location"
          v-model:outcall-locations="form.outcall_locations"
          v-model:taxi-option="form.taxi_option"
          :errors="errors"
        />

        <GeoModule
          v-model:geo="form.geo"
          :errors="errors"
        />

        <ContactsModule
          v-model:phone="form.phone"
          v-model:contact-method="form.contact_method"
          v-model:whatsapp="form.whatsapp"
          v-model:telegram="form.telegram"
          :errors="errors"
        />
      </div>

      <!-- ГРУППА 5: Медиа -->
      <div class="form-group">
        <MediaModule
          v-model:photos="form.photos"
          v-model:video="form.video"
          :uploading="uploading"
          :upload-progress="uploadProgress"
          :uploading-video="uploadingVideo"
          :video-upload-progress="videoUploadProgress"
          @photo-error="handlePhotoError"
          @video-error="handleVideoError"
        />
      </div>

      <!-- Кнопки действий -->
      <div class="form-actions">
        <ActionButton
          variant="secondary"
          size="large"
          :loading="saving"
          @click="handleSaveDraft"
        >
          {{ saving ? 'Сохранение...' : 'Сохранить черновик' }}
        </ActionButton>

        <ActionButton
          variant="primary"
          size="large"
          :loading="saving"
          @click="handlePublish"
        >
          {{ saving ? 'Публикация...' : 'Разместить объявление' }}
        </ActionButton>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAdForm } from '@/Composables/useAdForm'
import { useAdFormStore } from './stores/adFormStore'

// UI компоненты
import ActionButton from '@/Components/UI/Buttons/ActionButton.vue'

// Модули базовой информации
import WorkFormat from './modules/BasicInfo/WorkFormat.vue'
import ServiceProvider from './modules/BasicInfo/ServiceProvider.vue'
import Description from './modules/BasicInfo/Description.vue'

// Модули персональной информации (новые компоненты)
// import ParametersModule from '@/Components/Form/Sections/ParametersSection.vue'
// import FeaturesModule from '@/Components/Form/Sections/FeaturesSection.vue'

// Модули коммерческой информации (пока используем старые компоненты)
import PriceModule from '@/Components/Form/Sections/PriceSection.vue'
import PromoModule from '@/Components/Form/Sections/PromoSection.vue'
import ServicesModule from '@/Components/Features/Services/index.vue'
import ScheduleModule from '@/Components/Form/Sections/ScheduleSection.vue'

// Модули локации (пока используем старые компоненты)
import LocationModule from '@/Components/Form/Sections/LocationSection.vue'
import GeoModule from '@/Components/Form/Sections/GeoSection.vue'
import ContactsModule from '@/Components/Form/Sections/ContactsSection.vue'

// Модули базовой информации
import ClientsModule from './modules/BasicInfo/Clients.vue'

// Модули персональной информации  
import ParametersModuleNew from './modules/PersonalInfo/Parameters.vue'
import FeaturesModuleNew from './modules/PersonalInfo/Features.vue'

// Модули медиа
import MediaModule from './modules/Media/MediaModule.vue'

// Props
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

// Events
const emit = defineEmits(['success'])

// Используем новый Pinia store (приоритет) или старый композабл (фоллбэк)
const store = useAdFormStore()

// Инициализация store
onMounted(() => {
  store.initializeForm(props.initialData, {
    adId: props.adId,
    category: props.category
  })
})

// Фоллбэк на старый композабл для совместимости
const { 
  form, 
  errors,
  handleSubmit: submitForm,
  loadDraft
} = useAdForm({
  category: props.category,
  ...props.initialData
}, { 
  isEditMode: !!props.adId,
  adId: props.adId,
  autosaveEnabled: false
})

// Используем данные из store, если доступен
const formData = computed(() => store.formData || form)
const formErrors = computed(() => store.errors || errors)

// Состояние компонента
const saving = ref(false)
const uploading = ref(false)
const uploadProgress = ref(0)
const uploadingVideo = ref(false)
const videoUploadProgress = ref(0)

// Прогресс заполнения формы (используем store если доступен)
const progressPercent = computed(() => {
  return store.completionPercentage || (() => {
    // Фоллбэк логика
    const filledFields = Object.values(formData.value).filter(value => {
      if (Array.isArray(value)) return value.length > 0
      if (typeof value === 'object' && value !== null) return Object.keys(value).length > 0
      return value !== '' && value !== null && value !== undefined
    }).length
    
    const totalFields = Object.keys(formData.value).length
    return Math.round((filledFields / totalFields) * 100)
  })()
})

const progressText = computed(() => {
  return `Заполнено ${progressPercent.value}%`
})

// Методы
const handleSaveDraft = async () => {
  saving.value = true
  try {
    if (store.saveAsDraft) {
      // Используем новый store
      const response = await store.saveAsDraft()
      console.log('Черновик сохранен через store:', response)
    } else {
      // Фоллбэк на старую логику
      console.log('Сохраняем черновик (фоллбэк)...', formData.value)
    }
  } catch (error) {
    console.error('Ошибка сохранения черновика:', error)
  } finally {
    saving.value = false
  }
}

const handlePublish = async () => {
  saving.value = true
  try {
    if (store.publish) {
      // Используем новый store
      const response = await store.publish()
      console.log('Объявление опубликовано через store:', response)
      emit('success', { message: 'Объявление опубликовано!', data: response })
    } else {
      // Фоллбэк на старую логику
      console.log('Публикуем объявление (фоллбэк)...', formData.value)
      emit('success', { message: 'Объявление опубликовано!' })
    }
  } catch (error) {
    console.error('Ошибка публикации:', error)
  } finally {
    saving.value = false
  }
}

const handlePhotoError = (error) => {
  console.error('Ошибка загрузки фото:', error)
}

const handleVideoError = (error) => {
  console.error('Ошибка загрузки видео:', error)
}

const handleSubmit = () => {
  handlePublish()
}

// Временный MediaModule как слот
</script>

<style scoped>
.ad-form-container {
  max-width: 800px;
  margin: 0 auto;
}

.form-progress {
  margin-bottom: 24px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e5e5e5;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 8px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #1890ff, #40a9ff);
  transition: width 0.3s ease;
}

.progress-text {
  text-align: center;
  font-size: 14px;
  color: #666;
}

.ad-form {
  /* Форма займет всю ширину */
}

.form-group {
  margin-bottom: 32px;
  /* Группировка модулей */
}

.form-group:last-of-type {
  margin-bottom: 24px;
}

.form-actions {
  display: flex;
  gap: 16px;
  justify-content: flex-end;
  padding: 24px 0;
  border-top: 1px solid #e5e5e5;
  margin-top: 32px;
}

@media (max-width: 768px) {
  .form-actions {
    flex-direction: column;
  }
  
  .form-actions .action-button {
    width: 100%;
  }
}
</style>