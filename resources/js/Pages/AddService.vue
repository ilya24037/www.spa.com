<template>
  <Head :title="pageTitle" />
  
  <div class="add-service-page">
    <!-- Навигация -->
    <div class="page-navigation">
      <BackButton @click="goBack" />
      <Breadcrumbs :items="breadcrumbs" />
    </div>

    <!-- Заголовок -->
    <PageHeader 
      :title="pageTitle"
      subtitle="Создайте привлекательное объявление для привлечения клиентов"
    />

    <!-- Основная форма -->
    <div class="form-container">
      <form @submit.prevent="handleSubmit" novalidate>
        <!-- Скрытое поле с категорией -->
        <input v-if="selectedCategory" type="hidden" name="category" :value="selectedCategory">
        
        <!-- Селектор категории -->
        <CategorySelector 
          :selected-category="selectedCategory"
          @category-changed="handleCategoryChange"
        />
        
        <!-- Секции формы показываются только если выбрана категория -->
        <template v-if="selectedCategory">
          <!-- Специфичные секции для категорий -->
          <EroticSection 
            v-if="selectedCategory === 'erotic'"
            :form="form" 
            :errors="errors" 
          />
          
          <StripSection 
            v-if="selectedCategory === 'strip'"
            :form="form" 
            :errors="errors" 
          />
          
          <EscortSection 
            v-if="selectedCategory === 'escort'"
            :form="form" 
            :errors="errors" 
          />
          
          <!-- Общие секции для всех категорий -->
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
        </template>
        
        <!-- Заглушка если категория не выбрана -->
        <div v-else class="no-category-selected">
          <div class="text-center py-12">
            <div class="text-6xl mb-4">📝</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              Выберите категорию услуг
            </h3>
            <p class="text-gray-600">
              Для продолжения выберите категорию услуг в меню выше
            </p>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { Head } from '@inertiajs/vue3'
import { useAdForm } from '@/Composables/useAdForm'
import { computed, watch, ref } from 'vue'
import BackButton from '@/Components/Layout/BackButton.vue'
import Breadcrumbs from '@/Components/Layout/Breadcrumbs.vue'
import PageHeader from '@/Components/Layout/PageHeader.vue'
import FormActions from '@/Components/Form/Sections/FormActions.vue'

// Селектор категории
import CategorySelector from '@/Components/Form/Sections/CategorySelector.vue'

// Специфичные секции
import EroticSection from '@/Components/Form/Sections/EroticSection.vue'
import StripSection from '@/Components/Form/Sections/StripSection.vue'
import EscortSection from '@/Components/Form/Sections/EscortSection.vue'

// Общие секции формы
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
  props: {
    category: {
      type: String,
      default: null
    },
    categoryName: {
      type: String,
      default: 'Новое объявление'
    },
    breadcrumbs: {
      type: Array,
      default: () => [
        { label: 'Главная', url: '/' },
        { label: 'Разместить объявление', url: '/additem' },
        { label: 'Новое объявление', url: null }
      ]
    }
  },
  components: {
    Head,
    BackButton,
    Breadcrumbs,
    PageHeader,
    FormActions,
    CategorySelector,
    EroticSection,
    StripSection,
    EscortSection,
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
  setup(props) {
    // Используем composable для управления формой
    const {
      form,
      errors,
      isValid,
      isSubmitting,
      isSaving,
      lastSaved,
      hasUnsavedChanges,
      submitForm,
      saveAndExit
    } = useAdForm()

    // Состояние выбранной категории
    const selectedCategory = ref(props.category)
    
    // Названия категорий
    const categoryNames = {
      'erotic': 'Эротический массаж',
      'strip': 'Стриптиз',
      'escort': 'Сопровождение'
    }
    
    // Заголовок страницы
    const pageTitle = computed(() => {
      return selectedCategory.value ? categoryNames[selectedCategory.value] : 'Новое объявление'
    })

    // Обработчик смены категории
    const handleCategoryChange = (categoryId) => {
      selectedCategory.value = categoryId
      
      // Обновляем URL без перезагрузки
      const url = new URL(window.location)
      url.searchParams.set('category', categoryId)
      window.history.replaceState({}, '', url)
      
      // Обновляем поле формы
      form.value.category = categoryId
    }

    // Следим за изменениями в URL
    watch(() => props.category, (newCategory) => {
      selectedCategory.value = newCategory
    })

    return {
      form,
      errors,
      isValid,
      isSubmitting,
      isSaving,
      lastSaved,
      hasUnsavedChanges,
      selectedCategory,
      pageTitle,
      handleSubmit: submitForm,
      handleSaveAndExit: saveAndExit,
      handleMediaError: (error) => console.warn('Ошибка медиа:', error),
      handleAddressSelected: (address) => console.log('Выбран адрес:', address),
      handleCategoryChange,
      goBack: () => window.history.back()
    }
  }
}
</script>

<style scoped>
.add-service-page {
  @apply max-w-4xl mx-auto px-4 py-6;
}

.page-navigation {
  @apply mb-6;
}

.form-container {
  @apply space-y-6;
}

.no-category-selected {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 p-8;
}

/* Адаптивность */
@media (max-width: 768px) {
  .add-service-page {
    @apply px-2 py-4;
  }
}
</style> 