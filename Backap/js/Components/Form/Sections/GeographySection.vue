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
        
                 <div class="space-y-2">
           <BaseRadio
             v-for="option in travelAreaOptions"
             :key="option.value"
             v-model="form.travel_area"
             :value="option.value"
             :label="option.label"
           />
         </div>
         <div v-if="errors.travel_area" class="mt-1 text-sm text-red-600">{{ errors.travel_area }}</div>

         <!-- Дополнительные районы -->
         <div v-if="form.travel_area === 'custom'" class="custom-areas">
           <label class="block text-sm font-medium text-gray-700 mb-3">Выберите районы</label>
           <CheckboxGroup
             v-model="form.custom_travel_areas"
             :options="customTravelAreasOptions"
           />
           <div v-if="errors.custom_travel_areas" class="mt-1 text-sm text-red-600">{{ errors.custom_travel_areas }}</div>
         </div>
      </div>

      <!-- Радиус выезда -->
             <div v-if="form.travel_area !== 'no_travel'" class="travel-radius">
         <BaseSelect
           v-model="form.travel_radius"
           label="Радиус выезда"
           placeholder="Выберите радиус"
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
         <BaseSelect
           v-model="form.travel_time"
           label="Время в пути"
           placeholder="Выберите время"
           :options="travelTimeOptions"
           :error="errors.travel_time"
         />
       </div>

      <!-- Карта -->
      <div class="map-container">
        <h4 class="field-title">Карта</h4>
        <UniversalMap 
          mode="picker"
          :height="200"
          placeholder-text="Кликните для выбора места на карте"
          @map-click="handleMapClick"
        />
      </div>
    </div>
  </PageSection>
</template>

<script>
import PageSection from '@/Components/Layout/PageSection.vue'
import GeoSuggest from '@/Components/Form/Geo/GeoSuggest.vue'
import BaseRadio from '@/Components/UI/BaseRadio.vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'
import BaseSelect from '@/Components/UI/BaseSelect.vue'
import PriceInput from '@/Components/Form/Controls/PriceInput.vue'
import UniversalMap from '@/Components/Map/UniversalMap.vue'

export default {
  name: 'GeographySection',
  components: {
    PageSection,
    GeoSuggest,
    BaseRadio,
    CheckboxGroup,
    BaseSelect,
    PriceInput,
    UniversalMap
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
    },
    handleMapClick(data) {
      // Здесь можно установить координаты в форму
      this.$emit('map-location-selected', data)
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
