<!-- resources/js/src/entities/ad/ui/AdForm/AdForm.vue - MIGRATED TO OLD STRUCTURE -->
<template>
  <div class="universal-ad-form">
    <!-- Универсальная форма для всех категорий -->
    <form @submit.prevent="handleSubmit" novalidate>
      
      <!-- 1. Подробности (title + specialty) -->
      <div class="form-group-section">
        <TitleSection 
          v-model:title="form.title" 
          :errors="errors"
        />
        <SpecialtySection 
          v-model:specialty="form.specialty" 
          :errors="errors"
        />
      </div>

      <!-- 2. Ваши клиенты -->
      <div class="form-group-section">
        <ClientsSection 
          v-model:clients="form.clients" 
          :errors="errors"
        />
      </div>

      <!-- 3. Где вы оказываете услуги -->
      <div class="form-group-section">
        <LocationSection 
          v-model:serviceLocation="form.service_location" 
          :errors="errors"
        />
      </div>

      <!-- 4. Формат работы -->
      <div class="form-group-section">
        <WorkFormatSection 
          v-model:workFormat="form.work_format" 
          :errors="errors"
        />
      </div>

      <!-- 5. Кто оказывает услуги -->
      <div class="form-group-section">
        <ServiceProviderSection 
          v-model:serviceProvider="form.service_provider" 
          :errors="errors"
        />
      </div>

      <!-- 6. Опыт работы -->
      <div class="form-group-section">
        <ExperienceSection 
          v-model:experience="form.experience" 
          :errors="errors"
        />
      </div>

      <!-- 7. Описание -->
      <div class="form-group-section">
        <DescriptionSection 
          v-model:description="form.description" 
          :errors="errors"
        />
      </div>

      <!-- 7.1. Услуги (новый модульный компонент) -->
      <div class="form-group-section">
        <ServicesModule 
          v-model:services="form.services" 
          v-model:servicesAdditionalInfo="form.services_additional_info" 
          :allowedCategories="[]"
          :errors="errors"
        />
      </div>

      <!-- 7.2. Особенности мастера (новый модульный компонент) -->
      <div class="form-group-section">
        <FeaturesSection 
          v-model:features="form.features" 
          v-model:additionalFeatures="form.additional_features" 
          :errors="errors"
        />
      </div>

      <!-- 7.3. График работы (новый модульный компонент) -->
      <div class="form-group-section">
        <ScheduleSection 
          v-model:schedule="form.schedule" 
          v-model:scheduleNotes="form.schedule_notes" 
          :errors="errors"
        />
      </div>

      <!-- 8. Стоимость основной услуги -->
      <div class="form-group-section">
        <h2 class="form-group-title">Стоимость основной услуги</h2>
        <div class="field-hint" style="margin-bottom: 20px; color: #8c8c8c; font-size: 16px;">
          Заказчик увидит эту цену рядом с названием объявления.
        </div>
        <PriceSection 
          v-model:price="form.price" 
          v-model:priceUnit="form.price_unit" 
          v-model:isStartingPrice="form.is_starting_price" 
          :errors="errors"
        />
      </div>

      <!-- 9. Физические параметры -->
      <div class="form-group-section">
        <ParametersSection 
          v-model:height="form.height" 
          v-model:weight="form.weight" 
          v-model:hairColor="form.hair_color" 
          v-model:eyeColor="form.eye_color" 
          v-model:nationality="form.nationality" 
          :errors="errors"
        />
      </div>

      <!-- 10. Акции -->
      <div class="form-group-section">
        <PromoSection 
          v-model:newClientDiscount="form.new_client_discount" 
          v-model:gift="form.gift" 
          :errors="errors"
        />
      </div>

      <!-- 11. Фотографии -->
      <div class="form-group-section">
        <PhotosSection 
          v-model:photos="form.photos" 
          :errors="errors"
        />
      </div>

      <!-- 11.2 Видео -->
      <div class="form-group-section">
        <VideosSection 
          v-model:videos="form.videos" 
          :errors="errors"
        />
      </div>

      <!-- 13. География -->
      <div class="form-group-section geography-section">
        <GeoSection 
          v-model:geo="form.geo" 
          :errors="errors"
        />
      </div>

      <!-- 12. Контакты -->
      <div class="form-group-section">
        <ContactsSection 
          v-model:phone="form.phone" 
          v-model:contactMethod="form.contact_method" 
          v-model:whatsapp="form.whatsapp" 
          v-model:telegram="form.telegram" 
          :errors="errors"
        />
      </div>

      <!-- Кнопки действий -->
      <div class="form-actions">
        <!-- Левая кнопка - Сохранить черновик -->
        <button 
          type="button" 
          @click="handleSaveDraft"
          :disabled="saving"
          class="btn btn-secondary"
        >
          {{ saving ? 'Сохранение...' : 'Сохранить черновик' }}
        </button>
        
        <!-- Правая кнопка - Разместить объявление -->
        <button 
          type="button" 
          @click="handlePublish"
          :disabled="saving"
          class="btn btn-primary"
        >
          {{ saving ? 'Публикация...' : 'Разместить объявление' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'

// TODO: Нужно создать эти компоненты секций в FSD структуре
// Импорты секций формы - ТРЕБУЮТ СОЗДАНИЯ
import TitleSection from './sections/TitleSection.vue'
import SpecialtySection from './sections/SpecialtySection.vue'
import ServiceProviderSection from './sections/ServiceProviderSection.vue'
import ClientsSection from './sections/ClientsSection.vue'
import LocationSection from './sections/LocationSection.vue'
import WorkFormatSection from './sections/WorkFormatSection.vue'
import ExperienceSection from './sections/ExperienceSection.vue'
import PriceSection from './sections/PriceSection.vue'
import DescriptionSection from './sections/DescriptionSection.vue'
import ParametersSection from './sections/ParametersSection.vue'
import PromoSection from './sections/PromoSection.vue'
import PhotosSection from './sections/PhotosSection.vue'
import VideosSection from './sections/VideosSection.vue'
import GeoSection from './sections/GeoSection.vue'
import ContactsSection from './sections/ContactsSection.vue'

// Модульные компоненты (новая архитектура)
import ServicesModule from '@/src/features/Services/index.vue'
import FeaturesSection from './sections/FeaturesSection.vue'
import ScheduleSection from './sections/ScheduleSection.vue'

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
  }
})

// Events
const emit = defineEmits(['success', 'draft-saved'])

// Реактивное состояние формы (как в старой версии)
const form = reactive({
  // Базовые поля
  title: '',
  specialty: '',
  clients: [],
  service_location: [],
  work_format: '',
  service_provider: '',
  experience: '',
  description: '',
  
  // Услуги и особенности
  services: [],
  services_additional_info: '',
  features: [],
  additional_features: '',
  schedule: {},
  schedule_notes: '',
  
  // Коммерческая информация
  price: '',
  price_unit: 'hour',
  is_starting_price: false,
  
  // Физические параметры
  height: '',
  weight: '',
  hair_color: '',
  eye_color: '',
  nationality: '',
  
  // Акции
  new_client_discount: '',
  gift: '',
  
  // Медиа
  photos: [],
  videos: [],
  
  // География и контакты
  geo: {},
  phone: '',
  contact_method: '',
  whatsapp: '',
  telegram: ''
})

// Состояние компонента
const saving = ref(false)
const errors = ref({})
const validationErrors = ref({})

// Обязательные поля
const requiredFields = {
  title: 'Название объявления',
  specialty: 'Специальность или сфера',
  clients: 'Ваши клиенты',
  service_location: 'Где вы оказываете услуги', 
  work_format: 'Формат работы',
  experience: 'Опыт работы',
  description: 'Описание услуги',
  price: 'Стоимость услуги',
  phone: 'Телефон для связи'
}

// Инициализация формы
onMounted(() => {
  if (props.initialData && Object.keys(props.initialData).length > 0) {
    Object.assign(form, props.initialData)
  }
})

// Функция валидации
const validateForm = () => {
  validationErrors.value = {}
  let isValid = true

  for (const [field, label] of Object.entries(requiredFields)) {
    if (field === 'clients' || field === 'service_location') {
      if (!form[field] || form[field].length === 0) {
        validationErrors.value[field] = `Поле "${label}" обязательно для заполнения`
        isValid = false
      }
    } else if (field === 'description') {
      if (!form[field] || form[field].toString().trim() === '') {
        validationErrors.value[field] = `Поле "${label}" обязательно для заполнения`
        isValid = false
      } else if (form[field].toString().trim().length < 50) {
        validationErrors.value[field] = `${label} должно содержать не менее 50 символов`
        isValid = false
      }
    } else {
      if (!form[field] || form[field].toString().trim() === '') {
        validationErrors.value[field] = `Поле "${label}" обязательно для заполнения`
        isValid = false
      }
    }
  }

  errors.value = validationErrors.value
  return isValid
}

// Обработчики событий
const handleSaveDraft = async () => {
  saving.value = true
  try {
    // TODO: Реализовать сохранение черновика через API
    emit('draft-saved', form)
  } catch (error) {
    console.error('Draft save error:', error)
  } finally {
    saving.value = false
  }
}

const handlePublish = async () => {
  if (!validateForm()) {
    return
  }
  
  saving.value = true
  try {
    // TODO: Реализовать публикацию через API
    emit('success', { ad: { id: 1 } })
  } catch (error) {
    console.error('Publish error:', error)
  } finally {
    saving.value = false
  }
}

const handleSubmit = () => {
  handlePublish()
}
</script>

<style scoped>
.universal-ad-form {
  max-width: 100%;
  margin: 0 auto;
}

.form-group-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.form-group-title {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #212529;
}

.field-hint {
  color: #6c757d;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  min-width: 200px;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: #5a6268;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.geography-section {
  border: 2px solid #28a745;
  background: #d4edda;
}
</style>
