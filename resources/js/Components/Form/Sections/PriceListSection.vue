<template>
  <PageSection title="Прайс-лист">
    <p class="section-description">
      Создайте подробный прайс-лист ваших услуг. Клиенты смогут увидеть все цены.
    </p>
    
    <div class="price-list-fields">
      <!-- Основная услуга -->
      <div class="main-service">
        <h4 class="field-title">Основная услуга</h4>
        <div class="service-row">
          <BaseInput
            v-model="form.main_service_name"
            placeholder="Название услуги"
            :error="errors.main_service_name"
          />
          <PriceInput
            id="main_service_price"
            name="main_service_price"
            placeholder="0"
            v-model="form.main_service_price"
            :unit-value="form.main_service_price_unit"
            @update:unit-value="form.main_service_price_unit = $event"
            :error="errors.main_service_price"
          />
        </div>
      </div>

      <!-- Дополнительные услуги -->
      <div class="additional-services">
        <div class="services-header">
          <h4 class="field-title">Дополнительные услуги</h4>
          <button 
            type="button" 
            class="btn btn-primary"
            @click="addService"
          >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <line x1="12" y1="5" x2="12" y2="19"/>
              <line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Добавить услугу
          </button>
        </div>

        <div v-if="form.additional_services.length === 0" class="empty-services">
          <p>Добавьте дополнительные услуги, которые вы предоставляете</p>
        </div>

        <div v-else class="services-list">
          <div 
            v-for="(service, index) in form.additional_services"
            :key="service.id"
            class="service-item"
          >
            <div class="service-content">
              <div class="service-row">
                <BaseInput
                  v-model="service.name"
                  label="Название услуги"
                  placeholder="Название дополнительной услуги"
                  :error="getServiceError(index, 'name')"
                />
                <PriceInput
                  :id="`service_price_${index}`"
                  :name="`service_price_${index}`"
                  label="Цена"
                  placeholder="0"
                  v-model="service.price"
                  :unit-value="service.price_unit"
                  @update:unit-value="service.price_unit = $event"
                  :error="getServiceError(index, 'price')"
                />
              </div>
              
              <BaseTextarea
                v-model="service.description"
                label="Описание услуги"
                placeholder="Краткое описание дополнительной услуги"
                :error="getServiceError(index, 'description')"
                :rows="2"
              />
            </div>
            
            <button 
              type="button"
              class="remove-btn"
              @click="removeService(index)"
              title="Удалить услугу"
            >
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <line x1="18" y1="6" x2="6" y2="18"/>
                <line x1="6" y1="6" x2="18" y2="18"/>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Настройки прайс-листа -->
      <div class="price-list-settings">
        <h4 class="field-title">Настройки прайс-листа</h4>
        
        <CheckboxGroup
          v-model="form.price_list_settings"
          :options="[
            { value: 'show_price_list', label: 'Показывать подробный прайс-лист в объявлении' },
            { value: 'price_negotiable', label: 'Цена договорная' },
            { value: 'payment_on_arrival', label: 'Оплата по прибытию' }
          ]"
        />
      </div>

      <!-- Валюта -->
      <div class="currency-settings">
        <h4 class="field-title">Валюта</h4>
        <BaseSelect
          v-model="form.currency"
          placeholder="Выберите валюту"
          :options="currencyOptions"
          :error="errors.currency"
        />
      </div>
    </div>
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import BaseInput from '@/Components/UI/BaseInput.vue'
import BaseTextarea from '@/Components/UI/BaseTextarea.vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import PriceInput from '@/Components/Form/Controls/PriceInput.vue'

export default {
  name: 'PriceListSection',
  components: {
    PageSection,
    BaseInput,
    BaseTextarea,
    CheckboxGroup,
    BaseSelect,
    PriceInput
  },
  props: {
    form: {
      type: Object,
      required: true
    },
    errors: {
      type: Object,
      default: () => ({})
    }
  },
  data() {
    return {
      currencyOptions: [
        { value: 'RUB', label: 'Российский рубль (₽)' },
        { value: 'USD', label: 'Доллар США ($)' },
        { value: 'EUR', label: 'Евро (€)' }
      ]
    }
  },
  methods: {
    addService() {
      const newService = {
        id: Date.now() + Math.random(),
        name: '',
        price: '',
        price_unit: 'service',
        description: ''
      }
      
      if (!this.form.additional_services) {
        this.form.additional_services = []
      }
      
      this.form.additional_services.push(newService)
    },
    
    removeService(index) {
      this.form.additional_services.splice(index, 1)
    },
    
    getServiceError(index, field) {
      const errorKey = `additional_services.${index}.${field}`
      return this.errors[errorKey] || ''
    }
  }
}
</script>

<style scoped>
.section-description {
  color: #666;
  font-size: 14px;
  margin-bottom: 20px;
  line-height: 1.5;
}

.price-list-fields {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.main-service,
.additional-services,
.price-list-settings,
.currency-settings {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.field-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin: 0 0 16px 0;
}

.service-row {
  display: flex;
  gap: 12px;
  align-items: flex-end;
}

.service-row .form-input {
  flex: 2;
}

.service-row .price-input {
  flex: 1;
}

.services-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  border: 1px solid #007bff;
  border-radius: 6px;
  background: #007bff;
  color: white;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:hover {
  background: #0056b3;
  border-color: #0056b3;
}

.btn svg {
  width: 16px;
  height: 16px;
}

.empty-services {
  padding: 32px 16px;
  text-align: center;
  color: #666;
  font-size: 14px;
  background: white;
  border-radius: 8px;
  border: 2px dashed #ddd;
}

.services-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.service-item {
  position: relative;
  padding: 16px;
  background: white;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.service-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.remove-btn {
  position: absolute;
  top: 8px;
  right: 8px;
  width: 24px;
  height: 24px;
  background: none;
  border: none;
  color: #dc3545;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.remove-btn:hover {
  background: #fff5f5;
}

.remove-btn svg {
  width: 14px;
  height: 14px;
}

/* Адаптивность */
@media (max-width: 768px) {
  .service-row {
    flex-direction: column;
    gap: 8px;
  }
  
  .services-header {
    flex-direction: column;
    gap: 12px;
    align-items: stretch;
  }
  
  .btn {
    justify-content: center;
  }
}
</style> 