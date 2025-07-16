<template>
  <div class="add-service-page">
    <!-- Навигация -->
    <div class="page-navigation">
      <BackButton @click="goBack" />
      <Breadcrumbs :items="breadcrumbs" />
    </div>

    <!-- Заголовок -->
    <PageHeader 
      title="Новое объявление"
      subtitle="Создайте привлекательное объявление для привлечения клиентов"
    />

    <!-- Основная форма -->
    <div class="form-container">
      <form @submit.prevent="handleSubmit" novalidate>
        <!-- Секции формы -->
        <DetailsSection 
          :form="form" 
          :errors="errors" 
        />
        
        <DescriptionSection 
          :form="form" 
          :errors="errors" 
        />
        
        <PriceSection 
          :form="form" 
          :errors="errors" 
        />
        
        <PromoSection 
          :form="form" 
          :errors="errors" 
        />
        
        <MediaSection 
          :form="form" 
          :errors="errors" 
          @error="handleMediaError"
        />
        
        <GeographySection 
          :form="form" 
          :errors="errors" 
          @address-selected="handleAddressSelected"
        />
        
        <PriceListSection 
          :form="form" 
          :errors="errors" 
        />
        
        <EducationSection 
          :form="form" 
          :errors="errors" 
        />
        
        <ContactsSection 
          :form="form" 
          :errors="errors" 
        />
        
        <!-- Действия формы -->
        <FormActions 
          :is-submitting="isSubmitting"
          :is-saving="isSaving"
          :has-unsaved-changes="hasUnsavedChanges"
          :last-saved="lastSaved"
          submit-text="Опубликовать объявление"
          @submit="handleSubmit"
          @save-and-exit="handleSaveAndExit"
        />
      </form>
    </div>
  </div>
</template>

<script>
import { useAdForm } from '@/Composables/useAdForm'
import BackButton from '@/Components/Layout/BackButton.vue'
import Breadcrumbs from '@/Components/Layout/Breadcrumbs.vue'
import PageHeader from '@/Components/Layout/PageHeader.vue'
import FormActions from '@/Components/Form/Sections/FormActions.vue'

// Секции формы
import DetailsSection from '@/Components/Form/Sections/DetailsSection.vue'
import DescriptionSection from '@/Components/Form/Sections/DescriptionSection.vue'
import PriceSection from '@/Components/Form/Sections/PriceSection.vue'
import PromoSection from '@/Components/Form/Sections/PromoSection.vue'
import MediaSection from '@/Components/Form/Sections/MediaSection.vue'
import GeographySection from '@/Components/Form/Sections/GeographySection.vue'
import PriceListSection from '@/Components/Form/Sections/PriceListSection.vue'
import EducationSection from '@/Components/Form/Sections/EducationSection.vue'
import ContactsSection from '@/Components/Form/Sections/ContactsSection.vue'

export default {
  name: 'AddService',
  components: {
    BackButton,
    Breadcrumbs,
    PageHeader,
    FormActions,
    DetailsSection,
    DescriptionSection,
    PriceSection,
    PromoSection,
    MediaSection,
    GeographySection,
    PriceListSection,
    EducationSection,
    ContactsSection
  },
  data() {
    return {
      breadcrumbs: [
        { label: 'Главная', url: '/' },
        { label: 'Предложение услуг', url: '/services' },
        { label: 'Красота', url: '/services/beauty' },
        { label: 'СПА-услуги, массаж', url: '/services/beauty/spa-massage' }
      ]
    }
  },
  setup() {
    // Используем composable для управления формой
    const {
      form,
      errors,
      isValid,
      isSubmitting,
      isSaving,
      lastSaved,
      hasUnsavedChanges,
      validateForm,
      submitForm,
      saveAndExit,
      startAutosave,
      stopAutosave
    } = useAdForm({}, {
      autosaveEnabled: true,
      onSuccess: (result) => {
        // Перенаправление на страницу успеха
        window.location.href = `/ads/${result.id}/success`
      },
      onError: (error) => {
        console.error('Ошибка отправки формы:', error)
      }
    })

    return {
      form,
      errors,
      isValid,
      isSubmitting,
      isSaving,
      lastSaved,
      hasUnsavedChanges,
      validateForm,
      submitForm,
      saveAndExit,
      startAutosave,
      stopAutosave
    }
  },
  mounted() {
    // Запускаем автосохранение при загрузке страницы
    this.startAutosave()
  },
  beforeUnmount() {
    // Останавливаем автосохранение при уходе со страницы
    this.stopAutosave()
  },
  methods: {
    goBack() {
      if (this.hasUnsavedChanges) {
        if (confirm('У вас есть несохраненные изменения. Уйти без сохранения?')) {
          window.history.back()
        }
      } else {
        window.history.back()
      }
    },
    
    async handleSubmit() {
      if (await this.submitForm()) {
        // Форма успешно отправлена
        console.log('Объявление создано!')
      }
    },
    
    async handleSaveAndExit() {
      await this.saveAndExit()
    },
    
    handleMediaError(error) {
      // Обработка ошибок медиа
      console.warn('Ошибка медиа:', error)
    },
    
    handleAddressSelected(address) {
      // Обработка выбора адреса
      console.log('Выбран адрес:', address)
    }
  }
}
</script>

<style scoped>
.add-service-page {
  max-width: 636px;
  margin: 0 auto;
  padding: 32px 20px;
}

.page-navigation {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 32px;
}

.form-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  padding: 32px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .add-service-page {
    padding: 24px 16px;
  }
  
  .page-navigation {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 24px;
  }
  
  .form-container {
    padding: 24px;
  }
}
</style> 