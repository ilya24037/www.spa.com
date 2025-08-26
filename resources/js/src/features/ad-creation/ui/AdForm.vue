<template>
  <div class="universal-ad-form">
    <!-- Прогресс заполнения -->
    <FormProgress :progress="formProgress" />
    
    <!-- Навигация по блокам -->
    <SectionBlocksNavigation
      :active-block="currentActiveBlock"
      @block-changed="handleBlockChange"
    />

    <!-- Управление секциями -->
    <FormControls 
      :disabled="saving"
      @expand-all="expandAll"
      @collapse-all="collapseAll"
      @expand-required="expandRequired"
    />

    <!-- Форма -->
    <form @submit.prevent="handleSubmit" novalidate class="ad-form-sections">
      <!-- ОСНОВНОЕ - объединенная секция -->
      <CollapsibleSection
        title="ОСНОВНОЕ"
        :is-open="sectionsState.basic"
        :is-required="true"
        :is-filled="checkBasicSectionFilled()"
        @toggle="toggleSection('basic')"
        data-section="basic"
        class="basic-group-section"
      >
        <div class="basic-subsections">
          <!-- Кто оказывает услуги -->
          <div class="subsection">
            <h3 class="subsection-title">
              Кто оказывает услуги
              <span class="required-mark">*</span>
            </h3>
            <ServiceProviderSection 
              v-model:serviceProvider="form.service_provider" 
              :errors="errors"
            />
          </div>

          <!-- Формат работы -->
          <div class="subsection">
            <h3 class="subsection-title">
              Формат работы
              <span class="required-mark">*</span>
            </h3>
            <WorkFormatSection 
              v-model:workFormat="form.work_format" 
              :errors="errors"
            />
          </div>

          <!-- Опыт работы -->
          <div class="subsection">
            <h3 class="subsection-title">
              Опыт работы
              <span class="required-mark">*</span>
            </h3>
            <ExperienceSection 
              v-model:experience="form.experience" 
              :errors="errors"
            />
          </div>

          <!-- Ваши клиенты -->
          <div class="subsection">
            <h3 class="subsection-title">
              Ваши клиенты
              <span class="required-mark">*</span>
            </h3>
            <ClientsSection 
              v-model:clients="form.clients" 
              :errors="errors"
            />
          </div>

          <!-- Описание -->
          <div class="subsection">
            <h3 class="subsection-title">Описание</h3>
            <DescriptionSection 
              v-model:description="form.description" 
              :errors="errors"
            />
          </div>
        </div>
      </CollapsibleSection>

      <!-- Параметры -->
       <CollapsibleSection
         title="Параметры"
        :is-open="sectionsState.parameters"
        :is-required="true"
        :is-filled="checkSectionFilled('parameters')"
        :filled-count="getFilledCount('parameters')"
        :total-count="8"
        @toggle="toggleSection('parameters')"
        data-section="parameters"
      >
        <ParametersSection 
          v-model:parameters="form.parameters"
          :show-fields="['age', 'breast_size', 'hair_color', 'eye_color', 'nationality']"
          :errors="errors.parameters || {}"
        />
      </CollapsibleSection>

      <!-- Стоимость услуг -->
      <CollapsibleSection
        title="Стоимость услуг"
        :is-open="sectionsState.price"
        :is-required="true"
        :is-filled="checkSectionFilled('price')"
        @toggle="toggleSection('price')"
        data-section="price"
      >
        <PricingSection 
          v-model:prices="form.prices" 
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Услуги - объединенная секция с Комфортом -->
      <CollapsibleSection
        title="Услуги"
        :is-open="sectionsState.services"
        :is-required="true"
        :is-filled="checkSectionFilled('services')"
        :filled-count="getFilledCount('services')"
        :total-count="3"
        @toggle="toggleSection('services')"
        data-section="services"
        class="services-group-section"
      >
                <div class="services-subsections">
          <!-- Основные услуги -->
          <div class="subsection">
            <ServicesModule 
              v-model:services="form.services"
              v-model:servicesAdditionalInfo="form.services_additional_info"
              :allowedCategories="[]"
              :errors="errors"
            />
            
            <!-- Комфорт без отступа -->
            <ComfortSection 
              v-model="form.services"
              :errors="errors"
            />
          </div>
        </div>

        <!-- Общая статистика услуг в конце секции -->
        <div class="services-total-stats mt-6 p-4 bg-gray-50 rounded-lg">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-700">
              Выбрано услуг: <strong>{{ getTotalSelectedServices() }}</strong>
            </span>
            <button
              v-if="getTotalSelectedServices() > 0"
              @click="clearAllServices"
              type="button"
              class="px-3 py-1 text-sm text-red-600 hover:text-red-800 transition-colors"
            >
              Очистить все
            </button>
          </div>
        </div>
      </CollapsibleSection>

      <!-- МЕДИА - объединенная секция -->
      <CollapsibleSection
        title="МЕДИА"
        :is-open="sectionsState.media"
        :is-required="true"
        :is-filled="checkMediaSectionFilled()"
        :filled-count="getMediaFilledCount()"
        :total-count="'мин. 3 фото'"
        @toggle="toggleSection('media')"
        data-section="media"
        class="media-group-section"
      >
        <div class="media-subsections">
          <!-- Фотографии - раскрывающаяся подкатегория -->
          <div class="media-category mb-6">
            <div class="border border-gray-200 rounded-lg px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors duration-200 mb-3 cursor-pointer select-none" @click="togglePhotosSection">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900 flex items-center">
                  Фотографии
                  <span class="required-mark ml-1">*</span>
                  <span v-if="getPhotosCount() > 0" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full">
                    {{ getPhotosCount() }}
                  </span>
                </h3>
                <svg 
                  :class="[
                    'text-gray-500 transition-transform duration-200 w-5 h-5',
                    { 'rotate-180': isPhotosExpanded }
                  ]"
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
            <div v-show="isPhotosExpanded">
              <PhotoUpload 
                v-model:photos="form.photos" 
                :errors="errors"
              />
            </div>
          </div>

          <!-- Видео - раскрывающаяся подкатегория -->
          <div class="media-category mb-6">
            <div class="border border-gray-200 rounded-lg px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors duration-200 mb-3 cursor-pointer select-none" @click="toggleVideoSection">
              <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900 flex items-center">
                  Видео
                  <span v-if="getVideosCount() > 0" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full">
                    {{ getVideosCount() }}
                  </span>
                </h3>
                <svg 
                  :class="[
                    'text-gray-500 transition-transform duration-200 w-5 h-5',
                    { 'rotate-180': isVideoExpanded }
                  ]"
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
            <div v-show="isVideoExpanded">
              <VideoUpload 
                v-model:videos="form.video" 
                :errors="errors"
              />
            </div>
          </div>
        </div>
      </CollapsibleSection>

      <!-- Подтверждение фотографий -->
      <VerificationSection
        :ad-id="initialData?.id || 0"
        :verification-photo="form.verification_photo"
        :verification-video="form.verification_video"
        :verification-status="form.verification_status"
        :verification-comment="form.verification_comment"
        :verification-expires-at="form.verification_expires_at"
        @update:verification-photo="form.verification_photo = $event"
        @update:verification-video="form.verification_video = $event"
        @update:verification-status="form.verification_status = $event"
      />

      <!-- География -->
      <CollapsibleSection
        title="География"
        :is-open="sectionsState.geo"
        :is-required="true"
        :is-filled="checkSectionFilled('geo')"
        @toggle="toggleSection('geo')"
        data-section="geo"
      >
        <GeoSection 
          v-model:geo="form.geo" 
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- График работы (необязательная) -->
      <CollapsibleSection
        title="График работы"
        :is-open="sectionsState.schedule"
        :is-required="false"
        :is-filled="checkSectionFilled('schedule')"
        @toggle="toggleSection('schedule')"
        data-section="schedule"
      >
        <ScheduleSection 
          v-model:schedule="form.schedule" 
          v-model:schedule-notes="form.schedule_notes" 
          v-model:online-booking="form.online_booking"
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Особенности мастера (объединенная секция) -->
      <CollapsibleSection
        title="Особенности мастера"
        :is-open="sectionsState.features"
        :is-required="false"
        :is-filled="checkSectionFilled('features')"
        @toggle="toggleSection('features')"
        data-section="features"
      >
        <FaqSection 
          v-model:faq="form.faq"
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Акции (необязательная) -->
      <CollapsibleSection
        title="Акции и скидки"
        :is-open="sectionsState.promo"
        :is-required="false"
        :is-filled="checkSectionFilled('promo')"
        @toggle="toggleSection('promo')"
        data-section="promo"
      >
        <PromoSection 
          v-model:promo="form.promo" 
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Контакты (в самом низу) -->
      <CollapsibleSection
        title="Контакты"
        :is-open="sectionsState.contacts"
        :is-required="true"
        :is-filled="checkSectionFilled('contacts')"
        @toggle="toggleSection('contacts')"
        data-section="contacts"
      >
        <ContactsSection 
          v-model:contacts="form.contacts"
          :errors="errors.contacts || {}"
        />
      </CollapsibleSection>
    </form>

    <!-- Кнопки действий -->
    <FormActions
      :can-submit="isActiveAd ? true : isFormValid"
      :submitting="saving"
      :saving-draft="saving"
      :publishing="saving"
      :show-progress="true"
      :progress-hint="`Заполнено ${formProgress}% формы`"
      :submit-label="isEditMode ? 'Обновить объявление' : 'Разместить объявление'"
      :is-active-ad="isActiveAd"
      @submit="handleSubmit"
      @save-draft="handleSaveDraft"
      @publish="handlePublishDirect"
      @cancel="handleCancel"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, watch, onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { useFormSections } from '@/src/shared/composables'
import FormProgress from '@/src/shared/ui/molecules/Forms/components/FormProgress.vue'
import FormControls from '@/src/shared/ui/molecules/Forms/components/FormControls.vue'
import SectionBlocksNavigation from '@/src/shared/ui/molecules/Forms/components/SectionBlocksNavigation.vue'

import FormActions from '@/src/shared/ui/molecules/Forms/components/FormActions.vue'
import CollapsibleSection from '@/src/shared/ui/organisms/CollapsibleSection.vue'

// Используем существующую модель AdForm
import { useAdFormModel } from '../model/adFormModel'

// Импорт секций из существующей структуры
import ParametersSection from '@/src/features/AdSections/ParametersSection/ui/ParametersSection.vue'
import PricingSection from '@/src/features/AdSections/PricingSection/ui/PricingSection.vue'
import ServicesModule from '@/src/features/Services/index.vue'
import ComfortSection from '@/src/features/Services/ComfortSection.vue'
import { PhotoUpload } from '@/src/features/media/photo-upload'
import { VideoUpload } from '@/src/features/media/video-upload'
import GeoSection from '@/src/features/AdSections/GeoSection/ui/GeoSection.vue'
import ContactsSection from '@/src/features/AdSections/ContactsSection/ui/ContactsSection.vue'
import ScheduleSection from '@/src/features/AdSections/ScheduleSection/ui/ScheduleSection.vue'
import FaqSection from '@/src/features/AdSections/FaqSection/ui/FaqSection.vue'
import DescriptionSection from '@/src/features/AdSections/DescriptionSection/ui/DescriptionSection.vue'
import ServiceProviderSection from '@/src/features/AdSections/ServiceProviderSection/ui/ServiceProviderSection.vue'
import WorkFormatSection from '@/src/features/AdSections/WorkFormatSection/ui/WorkFormatSection.vue'
import ExperienceSection from '@/src/features/AdSections/ExperienceSection/ui/ExperienceSection.vue'
import ClientsSection from '@/src/features/AdSections/ClientsSection/ui/ClientsSection.vue'
import PromoSection from '@/src/features/AdSections/PromoSection/ui/PromoSection.vue'

// Импорт компонента верификации
import VerificationSection from '@/src/features/verification-upload/ui/VerificationSection.vue'

// Состояние активного блока навигации
const currentActiveBlock = ref('basic')

// Состояние раскрытия подкатегорий МЕДИА
const isPhotosExpanded = ref(true) // Фотографии развернуты по умолчанию (обязательное поле)
const isVideoExpanded = ref(false) // Видео свернуто по умолчанию

// Обработчик смены блока
const handleBlockChange = (blockKey) => {
  currentActiveBlock.value = blockKey
}

// Методы для управления раскрытием подкатегорий МЕДИА
const togglePhotosSection = () => {
  isPhotosExpanded.value = !isPhotosExpanded.value
}

const toggleVideoSection = () => {
  isVideoExpanded.value = !isVideoExpanded.value
}

// Методы для подсчета количества медиа
const getPhotosCount = () => {
  return form.photos && Array.isArray(form.photos) ? form.photos.length : 0
}

const getVideosCount = () => {
  return form.video && Array.isArray(form.video) ? form.video.length : 0
}

// Props
interface Props {
  category: string
  categories: any[]
  adId?: string | number | null
  initialData?: any
}

const props = withDefaults(defineProps<Props>(), {
  adId: null,
  initialData: () => ({})
})

// Events
const emit = defineEmits<{
  'success': []
  'cancel': []
}>()

// Используем существующую модель для всей логики
const {
  form,
  errors,
  saving,
  isEditMode,
  handleSubmit,
  handleSaveDraft,
  handlePublish,
  handleCancel
} = useAdFormModel(props, emit)

// Отладка: отслеживание изменений form.video
watch(() => form.video, (newVideos, oldVideos) => {
  console.log('🎥 AdForm: form.video изменено:', {
    newVideos,
    oldVideos,
    newCount: newVideos?.length || 0,
    oldCount: oldVideos?.length || 0
  })
}, { deep: true })

// Обработчик прямой публикации черновика через Inertia
const handlePublishDirect = async () => {
  if (!props.adId) {
    console.error('🟢 Нет ID объявления для публикации')
    return
  }
  
  console.log('🟢 Начинаем публикацию черновика ID:', props.adId)
  
  saving.value = true
  
  // Используем Inertia router вместо fetch
  router.post(`/draft/${props.adId}/publish`, {}, {
    preserveScroll: true,
    onSuccess: (page) => {
      console.log('🟢 Публикация успешна!', page)
      // Inertia сам перенаправит по redirect из контроллера
      saving.value = false
    },
    onError: (errors) => {
      console.error('🟢 Ошибка публикации:', errors)
      saving.value = false
    },
    onFinish: () => {
      saving.value = false
    }
  })
}

// Определяем активное объявление
const isActiveAd = computed(() => {
  return isEditMode.value && props.initialData?.status === 'active'
})

// Конфигурация секций
const sectionsConfig = [
  {
    key: 'basic',
    title: 'ОСНОВНОЕ',
    required: true,
    fields: ['service_provider', 'work_format', 'experience', 'clients', 'description']
  },
  {
    key: 'parameters',
    title: 'Параметры',
    required: true,
    fields: ['title', 'age', 'height', 'weight', 'breast_size', 'hair_color', 'eye_color', 'nationality']
  },
  {
    key: 'price',
    title: 'Стоимость услуг',
    required: true,
    fields: ['prices']
  },
  {
    key: 'services',
    title: 'Услуги',
    required: true,
    fields: ['services', 'services_additional_info']
  },
  {
    key: 'media',
    title: 'МЕДИА',
    required: true,
    fields: ['photos', 'media_settings', 'video']
  },
  {
    key: 'geo',
    title: 'География',
    required: true,
    fields: ['geo']
  },
  {
    key: 'schedule',
    title: 'График работы',
    required: false,
    fields: ['schedule', 'schedule_notes', 'online_booking']
  },
  {
    key: 'features',
    title: 'Особенности мастера',
    required: false,
    fields: ['faq']
  },
  {
    key: 'promo',
    title: 'Акции и скидки',
    required: false,
    fields: ['promo']
  },
  {
    key: 'contacts',
    title: 'Контакты',
    required: true,
    fields: ['contacts']
  }
]

// Управление секциями
const {
  sectionsState,
  toggleSection,
  expandAll,
  collapseAll,
  expandRequired,
  checkSectionFilled,
  getFilledCount,
  formProgress
} = useFormSections(sectionsConfig, form)

// Проверка заполненности объединенной секции ОСНОВНОЕ
const checkBasicSectionFilled = () => {
  const basicFields = ['service_provider', 'work_format', 'experience', 'clients']
  return basicFields.every(field => {
    const value = form[field]
    if (Array.isArray(value)) {
      return value.length > 0
    }
    return value && value !== ''
  })
}

// Подсчет общего количества выбранных услуг
const getTotalSelectedServices = () => {
  if (!form.services || typeof form.services !== 'object') return 0
  
  let count = 0
  Object.values(form.services).forEach(categoryServices => {
    if (categoryServices && typeof categoryServices === 'object') {
      Object.values(categoryServices).forEach(service => {
        if (service?.enabled) count++
      })
    }
  })
  return count
}

// Очистка всех услуг
const clearAllServices = () => {
  if (form.services && typeof form.services === 'object') {
    const clearedServices = {}
    Object.keys(form.services).forEach(categoryId => {
      clearedServices[categoryId] = {}
      if (form.services[categoryId] && typeof form.services[categoryId] === 'object') {
        Object.keys(form.services[categoryId]).forEach(serviceId => {
          clearedServices[categoryId][serviceId] = {
            enabled: false,
            price_comment: ''
          }
        })
      }
    })
    form.services = clearedServices
  }
}

// Проверка заполненности объединенной секции МЕДИА
const checkMediaSectionFilled = () => {
  const hasPhotos = form.photos && Array.isArray(form.photos) && form.photos.length >= 3
  return hasPhotos // Видео не обязательно, поэтому проверяем только фото
}

// Подсчет заполненности медиа
const getMediaFilledCount = () => {
  const photosCount = form.photos && Array.isArray(form.photos) ? form.photos.length : 0
  const videosCount = form.video && Array.isArray(form.video) ? form.video.length : 0
  return photosCount + videosCount
}

// Валидация формы
const isFormValid = computed(() => {
  const requiredSections = sectionsConfig.filter(s => s.required)
  return requiredSections.every(section => {
    if (section.key === 'basic') {
      return checkBasicSectionFilled()
    }
    return checkSectionFilled(section.key)
  })
})

// ===== ЛОГИРОВАНИЕ ДЛЯ ОТЛАДКИ SCHEDULE =====
watch(() => form.schedule, (newSchedule, oldSchedule) => {
  console.log('🔄 AdForm: watch form.schedule ТРИГГЕР', {
    newSchedule: newSchedule,
    newScheduleType: typeof newSchedule,
    oldSchedule: oldSchedule,
    oldScheduleType: typeof oldSchedule,
    isEqual: JSON.stringify(newSchedule) === JSON.stringify(oldSchedule)
  })
}, { deep: true })

// Логируем инициализацию form.schedule
console.log('🔍 AdForm: ИНИЦИАЛИЗАЦИЯ form.schedule:', {
  schedule: form.schedule,
  scheduleType: typeof form.schedule,
  scheduleKeys: form.schedule ? Object.keys(form.schedule) : 'undefined',
  scheduleValue: form.schedule
})

// Логируем при монтировании компонента
onMounted(() => {
  console.log('🔍 AdForm: onMounted - form.schedule:', {
    schedule: form.schedule,
    scheduleType: typeof form.schedule,
    scheduleKeys: form.schedule ? Object.keys(form.schedule) : 'undefined',
    scheduleValue: form.schedule
  })
  
  console.log('🔍 AdForm: onMounted - props.initialData:', {
    hasInitialData: !!props.initialData,
    initialDataKeys: props.initialData ? Object.keys(props.initialData) : 'undefined',
    scheduleInInitialData: props.initialData?.schedule,
    scheduleType: typeof props.initialData?.schedule
  })
})

watch(() => form.schedule_notes, (newNotes, oldNotes) => {
  console.log('🔄 AdForm: watch form.schedule_notes ТРИГГЕР', {
    newNotes: newNotes,
    oldNotes: oldNotes,
    isEqual: newNotes === oldNotes
  })
}, { deep: true })

// ===== ЛОГИРОВАНИЕ ДЛЯ ОТЛАДКИ PHOTOS =====
watch(() => form.photos, (newPhotos, oldPhotos) => {
  console.log('🔄 AdForm: watch form.photos ТРИГГЕР', {
    newPhotos: newPhotos,
    newPhotosLength: newPhotos?.length,
    newPhotosType: typeof newPhotos,
    oldPhotos: oldPhotos,
    oldPhotosLength: oldPhotos?.length,
    oldPhotosType: typeof oldPhotos,
    isEqual: JSON.stringify(newPhotos) === JSON.stringify(oldPhotos),
    stackTrace: new Error().stack?.split('\n').slice(1, 4)
  })
  
  if (newPhotos !== oldPhotos) {
    console.log('✅ AdForm: form.photos изменен')
    
    // Детальное сравнение
    if (Array.isArray(newPhotos) && Array.isArray(oldPhotos)) {
      console.log('📊 AdForm: Детальное сравнение массивов photos:', {
        oldLength: oldPhotos.length,
        newLength: newPhotos.length,
        added: newPhotos.length - oldPhotos.length,
        oldIds: oldPhotos.map(p => p?.id || 'no-id'),
        newIds: newPhotos.map(p => p?.id || 'no-id')
      })
    }
  }
}, { deep: true })

</script>

<style scoped>
.universal-ad-form {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.ad-form-sections {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

/* Стили для объединенных секций */
.basic-subsections,
.services-subsections,
.media-subsections {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* Стили для медиа подкатегорий (как у услуг) */
.media-category {
  background: #ffffff;
  border-radius: 8px;
  overflow: hidden;
}

/* Убираем границы у содержимого медиа подкатегорий */
.media-category .photo-upload,
.media-category .video-upload {
  border: none;
  background: transparent;
  padding: 16px;
  margin: 0;
}

/* Но СОХРАНЯЕМ пунктирные границы у зон загрузки */
.media-category .photo-upload-zone,
.media-category .video-upload-zone {
  border: 2px dashed #d1d5db !important;
  background: white !important;
}

.media-category .photo-upload-zone:hover,
.media-category .video-upload-zone:hover {
  border-color: #3b82f6 !important;
  background: #eff6ff !important;
}

.subsection {
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.subsection:hover {
  background: #f1f5f9;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.subsection-title {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 12px 0;
  display: flex;
  align-items: center;
  gap: 4px;
}

.required-mark {
  color: #ef4444;
  font-weight: 700;
  font-size: 18px;
}

@media (max-width: 768px) {
  .universal-ad-form {
    padding: 16px;
  }
  
  .subsection {
    padding: 12px;
  }
  
  .subsection-title {
    font-size: 14px;
  }
}
</style>