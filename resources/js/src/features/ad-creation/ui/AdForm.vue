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
      <!-- О себе (необязательная) -->
      <CollapsibleSection
        title="О себе"
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
        :total-count="7"
        @toggle="toggleSection('parameters')"
      >
        <ParametersSection 
          v-model:age="form.age"
          v-model:height="form.height" 
          v-model:weight="form.weight" 
          v-model:breastSize="form.breast_size"
          v-model:hairColor="form.hair_color" 
          v-model:eyeColor="form.eye_color" 
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

      <!-- Фото и видео -->
      <CollapsibleSection
        title="Фото и видео"
        :is-open="sectionsState.media"
        :is-required="true"
        :is-filled="checkSectionFilled('media')"
        :filled-count="form.photos?.length || 0"
        :total-count="'мин. 3'"
        @toggle="toggleSection('media')"
      >
        <MediaSection 
          v-model:photos="form.photos" 
          v-model:video="form.video" 
          v-model:media-settings="form.media_settings" 
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
          v-model:contacts="form.contacts" 
          :errors="errors"
        />
      </CollapsibleSection>
    </form>

    <!-- Кнопки действий -->
    <FormActions
      :can-submit="isFormValid"
      :submitting="saving"
      :saving-draft="saving"
      :show-progress="true"
      :progress-hint="`Заполнено ${formProgress}% формы`"
      :submit-label="isEditMode ? 'Обновить объявление' : 'Разместить объявление'"
      @submit="handlePublish"
      @save-draft="handleSaveDraft"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
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
import MediaSection from '@/src/features/AdSections/MediaSection/ui/MediaSection.vue'
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
}>()

// Используем существующую модель для всей логики
const {
  form,
  errors,
  saving,
  isEditMode,
  handleSubmit,
  handleSaveDraft,
  handlePublish
} = useAdFormModel(props, emit)

// Конфигурация секций
const sectionsConfig = [
  {
    key: 'description',
    title: 'О себе',
    required: false,
    fields: ['description']
  },
  {
     key: 'parameters',
     title: 'Параметры',
     required: true,
     fields: ['age', 'height', 'weight', 'breast_size', 'hair_color', 'eye_color', 'nationality']
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
    key: 'media',
    title: 'Фото и видео',
    required: true,
    fields: ['photos', 'video', 'media_settings']
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
