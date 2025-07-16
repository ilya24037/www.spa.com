<template>
  <PageSection title="География работы">
    <p class="section-description">
      Укажите, где вы работаете и куда готовы выезжать к клиентам.
    </p>
    
    <div class="geography-fields">
      <!-- Основной адрес -->
      <GeoSuggest
        v-model="form.address"
        label="Основной адрес"
        placeholder="Введите адрес салона или офиса"
        :error="errors.address"
        hint="Укажите точный адрес, где вы принимаете клиентов"
        @select="handleAddressSelect"
      />

      <!-- Районы выезда -->
      <div class="travel-areas">
        <h4 class="field-title">Куда выезжаете</h4>
        <p class="field-hint">Укажите все зоны выезда, чтобы клиенты понимали, доберётесь ли вы до них.</p>
        
        <FormRadio
          label="Районы выезда"
          name="travel_area"
          v-model="form.travel_area"
          :options="travelAreaOptions"
          :error="errors.travel_area"
        />

        <!-- Дополнительные районы -->
        <div v-if="form.travel_area === 'custom'" class="custom-areas">
          <FormCheckbox
            label="Выберите районы"
            name="custom_travel_areas"
            v-model="form.custom_travel_areas"
            :options="customTravelAreasOptions"
            :error="errors.custom_travel_areas"
          />
        </div>
      </div>

      <!-- Радиус выезда -->
      <div v-if="form.travel_area !== 'no_travel'" class="travel-radius">
        <FormSelect
          id="travel_radius"
          name="travel_radius"
          label="Радиус выезда"
          placeholder="Выберите радиус"
          v-model="form.travel_radius"
          :options="travelRadiusOptions"
          :error="errors.travel_radius"
        />
      </div>

      <!-- Доплата за выезд -->
      <div v-if="form.travel_area !== 'no_travel'" class="travel-fee">
        <PriceInput
          id="travel_fee"
          name="travel_fee"
          label="Доплата за выезд"
          placeholder="0"
          v-model="form.travel_fee"
          :error="errors.travel_fee"
          :show-unit="false"
          hint="Укажите дополнительную плату за выезд к клиенту"
        />
      </div>

      <!-- Время в пути -->
      <div v-if="form.travel_area !== 'no_travel'" class="travel-time">
        <FormSelect
          id="travel_time"
          name="travel_time"
          label="Время в пути"
          placeholder="Выберите время"
          v-model="form.travel_time"
          :options="travelTimeOptions"
          :error="errors.travel_time"
        />
      </div>

      <!-- Карта -->
      <div class="map-container">
        <h4 class="field-title">Карта</h4>
        <div class="map-placeholder">
          <div class="map-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
              <circle cx="12" cy="10" r="3"/>
            </svg>
          </div>
          <p>Здесь будет карта с вашим адресом и зоной выезда</p>
          <button type="button" class="btn btn-secondary" @click="showMap = true">
            Открыть карту
          </button>
        </div>
      </div>
    </div>
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import GeoSuggest from '@/Components/Form/Geo/GeoSuggest.vue'
import FormRadio from '@/Components/Form/FormRadio.vue'
import FormCheckbox from '@/Components/Form/FormCheckbox.vue'
import FormSelect from '@/Components/Form/FormSelect.vue'
import PriceInput from '@/Components/Form/Controls/PriceInput.vue'

export default {
  name: 'GeographySection',
  components: {
    PageSection,
    GeoSuggest,
    FormRadio,
    FormCheckbox,
    FormSelect,
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
      showMap: false,
      travelAreaOptions: [
        { value: 'no_travel', label: 'Не выезжаю' },
        { value: 'nearby', label: 'Ближайшие районы' },
        { value: 'city', label: 'По всему городу' },
        { value: 'region', label: 'По области' },
        { value: 'custom', label: 'Выбрать районы' }
      ],
      customTravelAreasOptions: [
        { value: 'center', label: 'Центр' },
        { value: 'north', label: 'Север' },
        { value: 'south', label: 'Юг' },
        { value: 'east', label: 'Восток' },
        { value: 'west', label: 'Запад' },
        { value: 'north_west', label: 'Северо-Запад' },
        { value: 'north_east', label: 'Северо-Восток' },
        { value: 'south_west', label: 'Юго-Запад' },
        { value: 'south_east', label: 'Юго-Восток' }
      ],
      travelRadiusOptions: [
        { value: '5', label: '5 км' },
        { value: '10', label: '10 км' },
        { value: '15', label: '15 км' },
        { value: '20', label: '20 км' },
        { value: '30', label: '30 км' },
        { value: '50', label: '50 км' }
      ],
      travelTimeOptions: [
        { value: '15', label: '15 минут' },
        { value: '30', label: '30 минут' },
        { value: '45', label: '45 минут' },
        { value: '60', label: '1 час' },
        { value: '90', label: '1.5 часа' },
        { value: '120', label: '2 часа' }
      ]
    }
  },
  methods: {
    handleAddressSelect(address) {
      this.$emit('address-selected', address)
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

.geography-fields {
  display: flex;
  flex-direction: column;
  gap: 24px;
}

.travel-areas {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.field-title {
  font-size: 14px;
  font-weight: 600;
  color: #333;
  margin: 0 0 8px 0;
}

.field-hint {
  color: #666;
  font-size: 13px;
  margin: 0 0 16px 0;
  line-height: 1.4;
}

.custom-areas {
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e9ecef;
}

.travel-radius,
.travel-fee,
.travel-time {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.map-container {
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e9ecef;
}

.map-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 32px 16px;
  background: white;
  border-radius: 8px;
  border: 2px dashed #ddd;
  text-align: center;
}

.map-icon {
  width: 48px;
  height: 48px;
  color: #666;
  margin-bottom: 12px;
}

.map-icon svg {
  width: 100%;
  height: 100%;
}

.map-placeholder p {
  color: #666;
  font-size: 14px;
  margin: 0 0 16px 0;
}

.btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: white;
  color: #333;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn:hover {
  background: #f8f9fa;
  border-color: #007bff;
  color: #007bff;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  border-color: #6c757d;
}

.btn-secondary:hover {
  background: #5a6268;
  border-color: #545b62;
}
</style> 