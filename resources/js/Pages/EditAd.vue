<template>
  <div class="edit-ad-page">
    <BackButton @click="goBack" />
    
    <PageHeader 
      title="Редактировать объявление"
      :breadcrumbs="breadcrumbs"
    />
    
    <div class="form-container">
      <form @submit.prevent="handleSubmit" novalidate>
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
        
        <ContactsSection 
          :form="form" 
          :errors="errors" 
        />
        
        <FormActions 
          :is-submitting="isSubmitting"
          :is-saving="isSaving"
          :has-unsaved-changes="hasUnsavedChanges"
          :last-saved="lastSaved"
          submit-text="Сохранить изменения"
          @submit="handleSubmit"
          @save-and-exit="handleSaveAndExit"
        />
        </form>
      </div>
    </div>
</template>

<script>
import FormInput from '@/Components/Form/FormInput.vue'
import FormSelect from '@/Components/Form/FormSelect.vue'
import FormCheckbox from '@/Components/Form/FormCheckbox.vue'
import FormRadio from '@/Components/Form/FormRadio.vue'
import FormTextarea from '@/Components/Form/FormTextarea.vue'
import { router } from '@inertiajs/vue3'

export default {
  name: 'EditAd',
  components: {
    FormInput,
    FormSelect,
    FormCheckbox,
    FormRadio,
    FormTextarea
  },
  props: {
    ad: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      isSubmitting: false,
      form: {
        title: this.ad.title || '',
        specialty: this.ad.specialty || '',
        clients: this.ad.clients || [],
        service_location: this.ad.service_location || [],
        work_format: this.ad.work_format || '',
        service_provider: this.ad.service_provider || [],
        experience: this.ad.experience || '',
        description: this.ad.description || '',
        price: this.ad.price || '',
        price_unit: this.ad.price_unit || 'service',
        is_starting_price: this.ad.is_starting_price ? ['1'] : [],
        discount: this.ad.discount || '',
        gift: this.ad.gift || '',
        address: this.ad.address || '',
        travel_area: this.ad.travel_area || '',
        phone: this.ad.phone || '',
        contact_method: this.ad.contact_method || 'messages'
      },
      errors: {},
      specialtyOptions: [
        { value: 'massage', label: 'Массаж' },
        { value: 'apparatus_massage', label: 'Аппаратный массаж' },
        { value: 'solarium', label: 'Солярий' },
        { value: 'spa', label: 'Спа-процедуры' }
      ],
      clientOptions: [
        { value: 'women', label: 'Женщины' },
        { value: 'men', label: 'Мужчины' }
      ],
      serviceLocationOptions: [
        { value: 'client_home', label: 'У заказчика дома' },
        { value: 'my_home', label: 'У себя дома' },
        { value: 'salon', label: 'В салоне' },
        { value: 'coworking', label: 'В коворкинге' },
        { value: 'clinic', label: 'В клинике' }
      ],
      workFormatOptions: [
        { 
          value: 'individual', 
          label: 'Индивидуально',
          description: 'Работаю с одним клиентом'
        },
        { 
          value: 'group', 
          label: 'Групповые занятия',
          description: 'Работаю с группой клиентов'
        }
      ],
      serviceProviderOptions: [
        { value: 'individual', label: 'Частное лицо' },
        { value: 'company', label: 'Компания' }
      ],
      experienceOptions: [
        { value: 'no_experience', label: 'Нет опыта' },
        { value: 'less_than_year', label: 'Менее года' },
        { value: '1_3_years', label: '1-3 года' },
        { value: '3_5_years', label: '3-5 лет' },
        { value: 'more_than_5', label: 'Более 5 лет' }
      ],
      priceUnitOptions: [
        { value: 'service', label: 'За услугу' },
        { value: 'hour', label: 'За час' },
        { value: 'session', label: 'За сеанс' }
      ],
      contactMethodOptions: [
        { value: 'any', label: 'Любой способ' },
        { value: 'calls', label: 'Только звонки' },
        { value: 'messages', label: 'Только сообщения' }
      ]
    }
  },
  methods: {
    goBack() {
      window.history.back()
    },
    submitForm() {
      this.isSubmitting = true
      
      router.put(`/ads/${this.ad.id}`, this.form, {
        onSuccess: () => {
          this.isSubmitting = false
          router.visit('/profile')
        },
        onError: (errors) => {
          this.errors = errors
          this.isSubmitting = false
        }
      })
    },
    saveAndExit() {
      router.visit('/profile')
    }
  }
}
</script>

<style scoped>
.create-ad-page {
  max-width: 636px;
  margin: 0 auto;
  padding: 20px;
  background: #f8f9fa;
  min-height: 100vh;
}

.back-button-container {
  margin-bottom: 20px;
}

.back-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: white;
  border: 1px solid #ddd;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s;
}

.back-button:hover {
  background: #f5f5f5;
}

.back-icon {
  width: 20px;
  height: 20px;
  fill: #333;
  transform: rotate(-90deg);
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 24px;
  font-weight: 600;
  color: #333;
  margin-bottom: 8px;
}

.breadcrumbs {
  display: flex;
  align-items: center;
  font-size: 14px;
  color: #666;
}

.breadcrumb-item {
  color: #666;
}

.breadcrumb-separator {
  margin: 0 8px;
  color: #999;
}

.form-container {
  background: white;
  border-radius: 8px;
  padding: 24px;
}

.form-section {
  margin-bottom: 32px;
}

.form-section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin-bottom: 16px;
}

.form-group {
  margin-bottom: 16px;
}

.form-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #333;
  margin-bottom: 8px;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.radio-group {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.price-group {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 16px;
  align-items: end;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 32px;
  padding-top: 24px;
  border-top: 1px solid #eee;
}

.submit-button {
  flex: 1;
  padding: 12px 24px;
  background: #0066cc;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.submit-button:hover:not(:disabled) {
  background: #0052a3;
}

.submit-button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.draft-button {
  padding: 12px 24px;
  background: white;
  color: #0066cc;
  border: 1px solid #0066cc;
  border-radius: 4px;
  font-size: 16px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.draft-button:hover {
  background: #f0f8ff;
}

@media (max-width: 768px) {
  .create-ad-page {
    padding: 16px;
  }
  
  .form-container {
    padding: 16px;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .price-group {
    grid-template-columns: 1fr;
  }
}
</style> 