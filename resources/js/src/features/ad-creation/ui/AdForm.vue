<template>
  <div class="universal-ad-form">
    <!-- Прогресс заполнения обязательных полей -->
    <FormProgress 
      :progress="requiredFieldsProgress" 
      :title="`Заполнено обязательных полей: ${filledRequiredFields} из 15`"
    />
    
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
      <!-- Основное - объединенная секция -->
      <CollapsibleSection
        title="Основное"
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
            <h3 class="subtitle-form">
              Кто оказывает услуги
              <span class="required-mark">*</span>
            </h3>
            <ServiceProviderSection 
              v-model:serviceProvider="form.service_provider" 
              :errors="errors"
              :forceValidation="forceValidation.service_provider"
              @clearForceValidation="forceValidation.service_provider = false"
            />
          </div>

          <!-- Формат работы -->
          <div class="subsection">
            <h3 class="subtitle-form">
              Формат работы
              <span class="required-mark">*</span>
            </h3>
            <WorkFormatSection 
              v-model:workFormat="form.work_format" 
              :errors="errors"
              :forceValidation="forceValidation.work_format"
              @clearForceValidation="forceValidation.work_format = false"
            />
          </div>

          <!-- Опыт работы -->
          <div class="subsection">
            <h3 
              class="subtitle-form cursor-pointer transition-colors duration-200"
              @click="toggleExperienceField"
            >
              Опыт работы
              <svg 
                xmlns="http://www.w3.org/2000/svg" 
                width="20" 
                height="20" 
                viewBox="0 0 24 24" 
                class="text-gray-500 ml-2 transition-transform duration-200"
                :class="{ 'rotate-180': isExperienceExpanded }"
                fill="currentColor"
              >
                <path d="M6.497 9.385a1.5 1.5 0 0 1 2.118.112L12 13.257l3.385-3.76a1.5 1.5 0 0 1 2.23 2.006l-4.5 5a1.5 1.5 0 0 1-2.23 0l-4.5-5a1.5 1.5 0 0 1 .112-2.118"></path>
              </svg>
            </h3>
            <div v-show="isExperienceExpanded" class="mt-3">
              <ExperienceSection 
                v-model:experience="form.experience" 
                :errors="errors"
              />
            </div>
          </div>

          <!-- Ваши клиенты -->
          <div class="subsection">
            <h3 class="subtitle-form">
              Ваши клиенты
              <span class="required-mark">*</span>
            </h3>
            <ClientsSection 
              v-model:clients="form.clients"
              v-model:client-age-from="form.client_age_from"
              :errors="errors"
              :forceValidation="forceValidation.clients"
              @clearForceValidation="forceValidation.clients = false"
            />
          </div>

          <!-- Описание -->
          <div class="subsection">
            <h3 class="subtitle-form">
              Описание
              <span class="required-mark">*</span>
            </h3>
            <DescriptionSection 
              v-model:description="form.description" 
              :errors="errors"
              :forceValidation="forceValidation.description"
              @clearForceValidation="forceValidation.description = false"
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
        :total-count="9"
        @toggle="toggleSection('parameters')"
        data-section="parameters"
      >
        <ParametersSection 
          v-model:parameters="form.parameters"
          :show-fields="['age', 'breast_size', 'hair_color', 'eye_color', 'nationality', 'appearance', 'bikini_zone']"
          :errors="errors.parameters || {}"
          :forceValidation="forceValidation.parameters"
          @clearForceValidation="(field) => forceValidation.parameters[field] = false"
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
          v-model:startingPrice="form.startingPrice" 
          v-model:newClientDiscount="form.promo.newClientDiscount"
          v-model:gift="form.promo.gift"
          :errors="errors"
          :forceValidation="forceValidation.price"
          @clearForceValidation="(field) => forceValidation.price[field] = false"
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
              :forceValidation="forceValidation"
              @clearForceValidation="forceValidation.services = false"
            />
            
            <!-- Комфорт без отступа -->
            <ComfortSection 
              v-model="form.services"
              :errors="errors"
            />
            
            <!-- Особенности мастера как подкатегория услуг -->
            <div class="service-category mt-4">
              <div 
                class="border border-gray-200 rounded-lg px-4 py-3 bg-gray-50 hover:bg-gray-100 transition-colors duration-200 mb-3 cursor-pointer select-none" 
                @click="isFeaturesExpanded = !isFeaturesExpanded"
              >
                <div class="flex items-center justify-between">
                  <h3 class="text-base font-semibold text-gray-900">
                    Особенности мастера
                    <span 
                      v-if="totalFaqSelected > 0" 
                      class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded-full"
                    >
                      {{ totalFaqSelected }}
                    </span>
                  </h3>
                  <svg 
                    class="w-5 h-5 text-gray-500 transition-transform duration-200" 
                    :class="{ 'rotate-180': isFeaturesExpanded }"
                    fill="none" 
                    stroke="currentColor" 
                    viewBox="0 0 24 24"
                  >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
              </div>
              
              <div v-show="isFeaturesExpanded" class="mt-4 pl-6">
                <FaqSection 
                  v-model:faq="form.faq"
                  :errors="errors"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Общая статистика услуг в конце секции -->
        <div class="services-total-stats mt-6 p-4 rounded-lg">
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

      <!-- Медиа - объединенная секция -->
      <CollapsibleSection
        title="Медиа"
        :is-open="sectionsState.media"
        :is-required="true"
        :is-filled="checkMediaSectionFilled()"
        :filled-count="getMediaFilledCount()"
        :total-count="'мин. 3 фото'"
        @toggle="toggleSection('media')"
        data-section="media"
        class="media-group-section"
      >
        <div class="media-subsections p-5">
          <!-- Фотографии - раскрывающаяся подкатегория -->
          <div class="media-category mb-6">
            <div class="border border-gray-200 rounded-lg px-4 py-3 transition-colors duration-200 mb-3 cursor-pointer select-none" @click="togglePhotosSection">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                  Фотографии
                  <span class="required-mark">*</span>
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
                :force-validation="forceValidation.media"
                @clear-force-validation="forceValidation.media = false"
              />
            </div>
          </div>

          <!-- Видео - раскрывающаяся подкатегория -->
          <div class="media-category mb-6">
            <div class="border border-gray-200 rounded-lg px-4 py-3 transition-colors duration-200 mb-3 cursor-pointer select-none" @click="toggleVideoSection">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
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

          <!-- Проверочное фото - раскрывающаяся подкатегория -->
          <div class="media-category mb-6">
            <div class="border border-gray-200 rounded-lg px-4 py-3 transition-colors duration-200 mb-3 cursor-pointer select-none" @click="toggleVerificationSection">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">
                  Проверочное фото
                </h3>
                <svg 
                  :class="[
                    'text-gray-500 transition-transform duration-200 w-5 h-5',
                    { 'rotate-180': isVerificationExpanded }
                  ]"
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
            <div v-show="isVerificationExpanded">
              <VerificationPhotoSection 
                v-model:photo="form.verification_photo" 
                :status="form.verification_status"
                :ad-id="initialData?.id || 0"
                @uploaded="handleVerificationUploaded"
              />
            </div>
          </div>
        </div>
      </CollapsibleSection>


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
          :force-validation="forceValidation.geo"
          :is-edit-mode="!!props.adId"
          @clear-force-validation="forceValidation.geo = false"
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
          :forceValidation="forceValidation.contacts"
          @clearForceValidation="(field) => forceValidation.contacts[field] = false"
        />
      </CollapsibleSection>
    </form>

    <!-- Кнопки действий -->
    <FormActions
      :can-submit="isActiveAd ? true : true"
      :submitting="saving"
      :saving-draft="saving"
      :publishing="saving"
      :show-progress="true"
      :progress-hint="`Заполнено обязательных полей: ${filledRequiredFields} из 15 (${requiredFieldsProgress}%)`"
      :submit-label="isEditMode ? 'Обновить объявление' : 'Разместить объявление'"
      :is-active-ad="isActiveAd"
      @submit="handleSubmit"
      @save-draft="handleSaveDraft"
      @publish="handlePublishWithValidation"
      @cancel="handleCancel"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, watch, onMounted, ref, reactive } from 'vue'
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

// Импорт компонента верификации (упрощенная версия)
import VerificationPhotoSection from '@/src/features/verification-upload/ui/VerificationPhotoSection.vue'

// Состояние активного блока навигации
const currentActiveBlock = ref('basic')

// Состояние раскрытия подкатегорий МЕДИА
const isPhotosExpanded = ref(true) // Фотографии развернуты по умолчанию (обязательное поле)
const isVideoExpanded = ref(false) // Видео свернуто по умолчанию
const isVerificationExpanded = ref(false) // Проверочное фото свернуто по умолчанию

// Состояние раскрытия поля опыта работы
const isExperienceExpanded = ref(false) // Опыт работы свернуто по умолчанию

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

const toggleVerificationSection = () => {
  isVerificationExpanded.value = !isVerificationExpanded.value
}

const toggleExperienceField = () => {
  isExperienceExpanded.value = !isExperienceExpanded.value
}

const handleVerificationUploaded = (path: string) => {
  form.verification_status = 'pending'
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

// Объект для принудительной валидации полей при автопрокрутке
const forceValidation = reactive({
  work_format: false,
  service_provider: false,
  clients: false,
  description: false,
  services: false,
  media: false,
  // Для секции параметров - подсветка всех незаполненных полей
  parameters: {
    title: false,
    age: false,
    height: false,
    weight: false,
    breast_size: false,
    hair_color: false
  },
  // Для секции цен - подсветка обязательных полей "1 час" (хотя бы одно)
  price: {
    apartments_1h: false,
    outcall_1h: false
  },
  // Для секции географии
  geo: false,
  // Для секции контактов
  contacts: {
    phone: false,
    contact_method: false
  }
})

// Состояние видимости секции "Опыт работы"
const experienceVisible = ref(false)

// Состояние раскрытия "Особенности мастера" в услугах
const isFeaturesExpanded = ref(false)

// Подсчет выбранных особенностей для счетчика
const totalFaqSelected = computed(() => {
  if (!form.faq) return 0
  
  let count = 0
  Object.entries(form.faq).forEach(([key, value]) => {
    // Считаем только заполненные значения
    if (value !== null && value !== undefined && value !== '' && value !== 0) {
      // Для массивов проверяем длину
      if (Array.isArray(value)) {
        if (value.length > 0) count++
      } else {
        count++
      }
    }
  })
  
  return count
})

// Отладка: отслеживание изменений form.video
watch(() => form.video, (newVideos, oldVideos) => {
  // Form video changed
}, { deep: true })


// Обработчик прямой публикации черновика через Inertia
const handlePublishDirect = async () => {
  if (!props.adId) {
    console.error('🟢 Нет ID объявления для публикации')
    return
  }
  
  
  saving.value = true
  
  // Используем Inertia router вместо fetch
  router.post(`/draft/${props.adId}/publish`, {}, {
    preserveScroll: true,
    onSuccess: (page) => {
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
    title: 'Основное',
    required: true,
    fields: ['service_provider', 'work_format', 'clients', 'description']
  },
  {
    key: 'parameters',
    title: 'Параметры',
    required: true,
    fields: ['title', 'age', 'height', 'weight', 'breast_size', 'hair_color', 'eye_color', 'nationality', 'bikini_zone']
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
    title: 'Медиа',
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
  checkSectionFilled: checkSectionFilledOriginal,
  getFilledCount,
  formProgress
} = useFormSections(sectionsConfig, form)

// Подсчет заполненных обязательных полей для прогресс-бара
const filledRequiredFields = computed(() => {
  let count = 0
  
  // Параметры (6 полей)
  if (form.parameters?.title) count++
  if (form.parameters?.age) count++
  if (form.parameters?.height) count++
  if (form.parameters?.weight) count++
  if (form.parameters?.breast_size) count++
  if (form.parameters?.hair_color) count++
  
  // Описание (1 поле)
  if (form.description) count++
  
  // Контакты (1 поле)
  if (form.contacts?.phone) count++
  
  // Услуги (1 поле)
  if (getTotalSelectedServices() > 0) count++
  
  // Основная информация (3 поля)
  if (form.service_provider?.length) count++
  if (form.work_format) count++
  if (form.clients?.length) count++
  
  // Цены (1 поле) - обязательно "1 час в апартаментах" ИЛИ "1 час выезд к клиенту"
  if ((form.prices?.apartments_1h && Number(form.prices.apartments_1h) > 0) ||
      (form.prices?.outcall_1h && Number(form.prices.outcall_1h) > 0)) count++
  
  // Медиа (1 поле) - обязательно минимум 3 фото
  if (form.photos?.length >= 3) count++
  
  return count
})

// Обновленный прогресс для 15 обязательных полей
const requiredFieldsProgress = computed(() => {
  return Math.round((filledRequiredFields.value / 15) * 100)
})

// Автоскролл к первому незаполненному обязательному полю
const scrollToFirstMissingField = () => {
  // Порядок проверки 15 обязательных полей (сверху вниз по форме)
  const requiredFields = [
    // 1. ОСНОВНОЕ (самая верхняя секция)
    { section: 'basic', field: 'service_provider', name: 'service_provider' },
    { section: 'basic', field: 'work_format', name: 'work_format' },
    { section: 'basic', field: 'clients', name: 'clients' },
    
    // 2. Параметры (вторая секция)
    { section: 'parameters', field: 'title', name: 'title' },
    { section: 'parameters', field: 'age', name: 'age' },
    { section: 'parameters', field: 'height', name: 'height' },
    { section: 'parameters', field: 'weight', name: 'weight' },
    { section: 'parameters', field: 'breast_size', name: 'breast_size' },
    { section: 'parameters', field: 'hair_color', name: 'hair_color' },
    { section: 'basic', field: 'description', name: 'description' },
    
    // 3. Стоимость услуг
    { section: 'price', field: 'price', name: 'price' },
    
    // 4. Услуги
    { section: 'services', field: 'services', name: 'services' },
    
    // 5. МЕДИА (фотографии)
    { section: 'media', field: 'photos', name: 'photos' },
    
    // 6. География (адрес)
    { section: 'geo', field: 'address', name: 'address' },
    
    // 7. Контакты (внизу формы)
    { section: 'contacts', field: 'phone', name: 'phone' }
  ]
  
  for (const field of requiredFields) {
    let isEmpty = false
    
    // Проверяем заполненность поля
    switch (field.section) {
      case 'parameters':
        isEmpty = !form.parameters?.[field.field]
        break
      case 'contacts':
        isEmpty = !form.contacts?.phone
        // Устанавливаем подсветку валидации для полей контактов
        if (isEmpty) {
          forceValidation.contacts.phone = true
        }
        // Также проверяем и подсвечиваем способ связи
        if (!form.contacts?.contact_method || form.contacts.contact_method === 'any') {
          forceValidation.contacts.contact_method = true
        }
        break
      case 'services':
        isEmpty = getTotalSelectedServices() === 0
        break
      case 'basic':
        if (field.field === 'service_provider') {
          isEmpty = !form.service_provider?.length
        } else if (field.field === 'work_format') {
          isEmpty = !form.work_format
        } else if (field.field === 'clients') {
          isEmpty = !form.clients?.length
        } else if (field.field === 'description') {
          isEmpty = !form.description
        }
        break
      case 'price':
        // Обязательно хотя бы одно поле: "1 час в апартаментах" ИЛИ "1 час выезд к клиенту"
        isEmpty = !((form.prices?.apartments_1h && Number(form.prices.apartments_1h) > 0) ||
                   (form.prices?.outcall_1h && Number(form.prices.outcall_1h) > 0))
        break
      case 'media':
        isEmpty = !form.photos?.length || form.photos.length < 3
        break
      case 'geo':
        // Проверяем наличие адреса в geo объекте с безопасным парсингом
        let geoData = {}
        if (typeof form.geo === 'string') {
          try {
            // Безопасный парсинг JSON с fallback на пустой объект
            geoData = form.geo ? JSON.parse(form.geo) : {}
          } catch (error) {
            console.warn('Ошибка парсинга geo данных:', error)
            // В случае ошибки используем пустой объект
            geoData = {}
          }
        } else if (form.geo && typeof form.geo === 'object') {
          // Если уже объект, используем его
          geoData = form.geo
        }
        isEmpty = !geoData?.address
        break
    }
    
    if (isEmpty) {
      // Для секции ОСНОВНОЕ подсвечиваем ВСЕ незаполненные обязательные поля
      if (field.section === 'basic') {
        if (!form.service_provider?.length) forceValidation.service_provider = true
        if (!form.work_format) forceValidation.work_format = true
        if (!form.clients?.length) forceValidation.clients = true
        if (!form.description) forceValidation.description = true
      }
      
      // Для секции параметров подсвечиваем ВСЕ незаполненные обязательные поля
      if (field.section === 'parameters') {
        if (!form.parameters?.title) forceValidation.parameters.title = true
        if (!form.parameters?.age) forceValidation.parameters.age = true
        if (!form.parameters?.height) forceValidation.parameters.height = true
        if (!form.parameters?.weight) forceValidation.parameters.weight = true
        if (!form.parameters?.breast_size) forceValidation.parameters.breast_size = true
        if (!form.parameters?.hair_color) forceValidation.parameters.hair_color = true
      }
      
      // Для секции цены подсвечиваем оба поля "1 час" если ни одно не заполнено
      if (field.section === 'price') {
        const apartmentsEmpty = !(form.prices?.apartments_1h && Number(form.prices.apartments_1h) > 0)
        const outcallEmpty = !(form.prices?.outcall_1h && Number(form.prices.outcall_1h) > 0)
        
        // Подсвечиваем оба поля если ни одно не заполнено
        if (apartmentsEmpty && outcallEmpty) {
          forceValidation.price.apartments_1h = true
          forceValidation.price.outcall_1h = true
        }
      }
      
      // Для секции услуг подсвечиваем валидацию
      if (field.section === 'services') {
        forceValidation.services = true
      }
      
      // Для секции медиа подсвечиваем валидацию
      if (field.section === 'media') {
        forceValidation.media = true
      }
      
      // Для секции географии подсвечиваем валидацию
      if (field.section === 'geo') {
        forceValidation.geo = true
      }
      
      // Открываем секцию
      sectionsState[field.section] = true
      
      // Прокручиваем сразу без задержки для мгновенного появления рамки
      const sectionElement = document.querySelector(`[data-section="${field.section}"]`)
      if (sectionElement) {
        // Автоскролл с отступом от шапки
        const headerHeight = 120 // примерная высота шапки
        const elementTop = sectionElement.offsetTop - headerHeight
        window.scrollTo({
          top: Math.max(0, elementTop), // не уходим выше верха страницы
          behavior: 'smooth'
        })
        
        // Фокусируем поле с небольшой задержкой после прокрутки
        setTimeout(() => {
          const fieldElement = document.querySelector(`[name="${field.name}"], input[placeholder*="${field.field}"], select[name="${field.name}"]`)
          if (fieldElement) {
            fieldElement.focus()
          }
        }, 500)
      }
      
      // Показываем подсказку
      console.info(`📍 Необходимо заполнить: ${field.field} в секции "${field.section}"`)
      
      return field // Возвращаем первое найденное незаполненное поле
    }
  }
  
  return null // Все поля заполнены
}

// Обработчик публикации с валидацией и автоскроллом
const handlePublishWithValidation = async () => {
  // Сначала проверяем валидность формы
  if (!isFormValid.value) {
    const missingField = scrollToFirstMissingField()
    if (missingField) {
      const remainingFields = 15 - filledRequiredFields.value
      console.warn(`⚠️ Необходимо заполнить еще ${remainingFields} обязательных полей`)
      
      // Можно добавить визуальное уведомление (toast)
      // toast.warning(`Заполните еще ${remainingFields} обязательных полей`)
    }
    return
  }
  
  // Если форма валидна - выполняем публикацию
  await handlePublishDirect()
}

// Переопределяем checkSectionFilled для новых требований
const checkSectionFilled = (sectionKey: string): boolean => {
  switch(sectionKey) {
    case 'parameters':
      // Проверяем все 6 обязательных полей параметров
      return !!(
        form.parameters?.title &&
        form.parameters?.age &&
        form.parameters?.height &&
        form.parameters?.weight &&
        form.parameters?.breast_size &&
        form.parameters?.hair_color
      )
    
    case 'services':
      // Секция считается заполненной, если выбрана хотя бы одна услуга 
      // ИЛИ заполнена хотя бы одна особенность мастера
      return getTotalSelectedServices() > 0 || totalFaqSelected.value > 0
    
    case 'price':
      // Хотя бы одна цена за час (апартаменты или выезд)
      return !!(
        (form.prices?.apartments_1h && Number(form.prices.apartments_1h) > 0) ||
        (form.prices?.outcall_1h && Number(form.prices.outcall_1h) > 0)
      )
    
    case 'contacts':
      // Телефон обязателен
      return !!form.contacts?.phone
    
    case 'geo':
      // form.geo хранится как JSON-строка, нужно парсить для проверки
      if (!form.geo || form.geo === '{}') return false

      try {
        const geoData = JSON.parse(form.geo)
        // Проверяем наличие адреса или города в распарсенных данных
        return !!(geoData.address || geoData.city)
      } catch (error) {
        // Если не удалось распарсить, значит данных нет
        return false
      }
    
    default:
      // Для остальных секций используем оригинальную проверку
      return checkSectionFilledOriginal(sectionKey)
  }
}

// Проверка заполненности объединенной секции ОСНОВНОЕ
const checkBasicSectionFilled = () => {
  // experience больше не обязательное поле (KISS принцип)
  const basicFields = ['service_provider', 'work_format', 'clients']
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

// Хук монтирования компонента (если нужна дополнительная инициализация)
onMounted(() => {
  // ✅ ДОБАВЛЯЕМ ЛОГИРОВАНИЕ ДЛЯ ОТЛАДКИ ФОТО
  
})

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
  gap: 8px;
}

/* Стили для медиа подкатегорий (как у услуг) */
.media-category {
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
}

.media-category .photo-upload-zone:hover,
.media-category .video-upload-zone:hover {
  border-color: #3b82f6 !important;
  background: #eff6ff !important;
}

.subsection {
  padding: 20px;
  border-radius: 8px;
}

/* .subsection-title заменен на .subtitle-form в typography.css */

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
    padding: 0px;
  }
  
  .subtitle-form {
    font-size: 16px;
  }
}
</style>