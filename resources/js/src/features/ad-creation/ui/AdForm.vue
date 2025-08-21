<template>
  <div class="universal-ad-form">
    <!-- Прогресс заполнения -->
    <FormProgress :progress="formProgress" />
    
    <!-- Управление секциями -->
    <FormControls 
      :disabled="saving"
      @expand-all="expandAll"
      @collapse-all="collapseAll"
      @expand-required="expandRequired"
    />

    <!-- Форма -->
    <form @submit.prevent="handleSubmit" novalidate class="ad-form-sections">
      <!-- Описание (необязательная) -->
      <CollapsibleSection
        title="Описание"
        :is-open="sectionsState.description"
        :is-required="false"
        :is-filled="checkSectionFilled('description')"
        @toggle="toggleSection('description')"
      >
        <DescriptionSection 
          v-model:description="form.description" 
          :errors="errors"
        />
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
      >
        <ParametersSection 
          v-model:title="form.title"
          v-model:age="form.age"
          v-model:height="form.height" 
          v-model:weight="form.weight" 
          v-model:breast_size="form.breast_size"
          v-model:hair_color="form.hair_color" 
          v-model:eye_color="form.eye_color" 
          v-model:nationality="form.nationality" 
          :showAge="true"
          :showBreastSize="true"
          :showHairColor="true"
          :showEyeColor="true"
          :showNationality="true"
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Стоимость услуг -->
      <CollapsibleSection
        title="Стоимость услуг"
        :is-open="sectionsState.price"
        :is-required="true"
        :is-filled="checkSectionFilled('price')"
        @toggle="toggleSection('price')"
      >
        <PricingSection 
          v-model:prices="form.prices" 
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Услуги -->
      <CollapsibleSection
        title="Услуги"
        :is-open="sectionsState.services"
        :is-required="true"
        :is-filled="checkSectionFilled('services')"
        :filled-count="getFilledCount('services')"
        :total-count="3"
        @toggle="toggleSection('services')"
      >
        <ServicesModule 
          v-model:services="form.services" 
          v-model:servicesAdditionalInfo="form.services_additional_info" 
          :allowedCategories="[]"
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Комфорт -->
      <CollapsibleSection
        title="Комфорт"
        :is-open="sectionsState.comfort"
        :is-required="false"
        :is-filled="checkSectionFilled('comfort')"
        @toggle="toggleSection('comfort')"
      >
        <ComfortSection 
          v-model="form.services"
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Фотографии -->
      <CollapsibleSection
        title="Фотографии"
        :is-open="sectionsState.photos"
        :is-required="true"
        :is-filled="checkSectionFilled('photos')"
        :filled-count="form.photos?.length || 0"
        :total-count="'мин. 3'"
        @toggle="toggleSection('photos')"
      >
        <PhotoUpload 
          v-model:photos="form.photos" 
          v-model:show-additional-info="form.media_settings.showAdditionalInfo"
          v-model:show-services="form.media_settings.showServices"
          v-model:show-prices="form.media_settings.showPrices"
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Видео -->
      <CollapsibleSection
        title="Видео"
        :is-open="sectionsState.video"
        :is-required="false"
        :is-filled="!!form.video?.length"
        @toggle="toggleSection('video')"
      >
        <VideoUpload 
          v-model:videos="form.video" 
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- География -->
      <CollapsibleSection
        title="География"
        :is-open="sectionsState.geo"
        :is-required="true"
        :is-filled="checkSectionFilled('geo')"
        @toggle="toggleSection('geo')"
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
      >
        <ScheduleSection 
          v-model:schedule="form.schedule" 
          v-model:schedule-notes="form.schedule_notes" 
          v-model:online-booking="form.online_booking"
          :errors="errors"
        />
      </CollapsibleSection>

      <!-- Особенности (необязательная) -->
      <CollapsibleSection
        title="Особенности"
        :is-open="sectionsState.features"
        :is-required="false"
        :is-filled="checkSectionFilled('features')"
        @toggle="toggleSection('features')"
      >
        <FeaturesSection 
          v-model:features="form.features"
          v-model:additionalFeatures="form.additional_features"
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
      >
        <ContactsSection 
          v-model:phone="form.phone"
          v-model:contactMethod="form.contact_method"
          v-model:whatsapp="form.whatsapp"
          v-model:telegram="form.telegram"
          :errors="errors"
        />
      </CollapsibleSection>
    </form>

    <!-- Кнопки действий -->
    <FormActions
      :can-submit="isActiveAd ? true : isFormValid"
      :submitting="saving"
      :saving-draft="saving"
      :show-progress="true"
      :progress-hint="`Заполнено ${formProgress}% формы`"
      :submit-label="isEditMode ? 'Обновить объявление' : 'Разместить объявление'"
      :is-active-ad="isActiveAd"
      @submit="handlePublish"
      @save-draft="handleSaveDraft"
      @cancel="handleCancel"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, watch, onMounted } from 'vue'
import { useFormSections } from '@/src/shared/composables'
import FormProgress from '@/src/shared/ui/molecules/Forms/components/FormProgress.vue'
import FormControls from '@/src/shared/ui/molecules/Forms/components/FormControls.vue'
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
import FeaturesSection from '@/src/features/AdSections/FeaturesSection/ui/FeaturesSection.vue'
import DescriptionSection from '@/src/features/AdSections/DescriptionSection/ui/DescriptionSection.vue'
import PromoSection from '@/src/features/AdSections/PromoSection/ui/PromoSection.vue'

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

// Определяем активное объявление
const isActiveAd = computed(() => {
  return isEditMode.value && props.initialData?.status === 'active'
})

// Конфигурация секций
const sectionsConfig = [
  {
    key: 'description',
    title: 'Описание',
    required: false,
    fields: ['description']
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
    key: 'comfort',
    title: 'Комфорт',
    required: false,
    fields: []
  },
  {
    key: 'photos',
    title: 'Фотографии',
    required: true,
    fields: ['photos', 'media_settings']
  },
  {
    key: 'video',
    title: 'Видео',
    required: false,
    fields: ['video']
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
    title: 'Особенности',
    required: false,
    fields: ['features']
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

// Валидация формы
const isFormValid = computed(() => {
  const requiredSections = sectionsConfig.filter(s => s.required)
  return requiredSections.every(section => checkSectionFilled(section.key))
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

watch(() => form.media_settings, (newSettings, oldSettings) => {
  console.log('🔄 AdForm: watch form.media_settings ТРИГГЕР', {
    newSettings: newSettings,
    oldSettings: oldSettings,
    isEqual: JSON.stringify(newSettings) === JSON.stringify(oldSettings)
  })
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

@media (max-width: 768px) {
  .universal-ad-form {
    padding: 16px;
  }
}
</style>
