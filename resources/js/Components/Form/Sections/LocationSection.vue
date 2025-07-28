<template>
  <div class="location-section">
    <h2 class="form-group-title">Где вы оказываете услуги</h2>
    
    <!-- Основные опции расположения -->
    <div class="main-locations">
      <CheckboxGroup 
        v-model="localLocation"
        :options="mainLocationOptions"
        @update:modelValue="emitLocation"
      />
    </div>
    
    <!-- Дополнительные опции выезда -->
    <div class="outcall-locations" v-if="hasOutcallSelected">
      <h3 class="subcategory-title">Выезд:</h3>
      <CheckboxGroup 
        v-model="localOutcallOptions"
        :options="outcallLocationOptions"
        @update:modelValue="emitOutcallOptions"
      />
    </div>
    
    <!-- Секция "Такси" (показывается только если выбран "Выезд") -->
    <div class="taxi-section" v-if="hasOutcallSelected">
      <div class="taxi-row">
        <span class="taxi-title">Такси</span>
        <div class="taxi-options">
          <label class="radio-option">
            <input 
              type="radio" 
              name="taxi" 
              value="separately" 
              v-model="localTaxiOption"
              @change="emitTaxiOption"
            />
            <span class="radio-label">оплачивается отдельно</span>
          </label>
          <label class="radio-option">
            <input 
              type="radio" 
              name="taxi" 
              value="included" 
              v-model="localTaxiOption"
              @change="emitTaxiOption"
            />
            <span class="radio-label">включено в стоимость</span>
          </label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import CheckboxGroup from '@/Components/UI/CheckboxGroup.vue'

const props = defineProps({
  serviceLocation: { type: Array, default: () => [] },
  outcallLocations: { type: Array, default: () => [] },
  taxiOption: { type: String, default: '' },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:serviceLocation', 'update:outcallLocations', 'update:taxiOption'])

const localLocation = ref([...props.serviceLocation])
const localOutcallOptions = ref([...props.outcallLocations])
const localTaxiOption = ref(props.taxiOption)

watch(() => props.serviceLocation, (val) => {
  localLocation.value = [...val]
})

watch(() => props.outcallLocations, (val) => {
  localOutcallOptions.value = [...val]
})

watch(() => props.taxiOption, (val) => {
  localTaxiOption.value = val || ''
})

// Основные опции расположения
const mainLocationOptions = computed(() => [
  { value: 'Апартаменты', label: 'Апартаменты' },
  { value: 'В салоне', label: 'В салоне' },
  { value: 'Выезд', label: 'Выезд' }
])

// Дополнительные опции выезда (показываются только если выбран "Выезд")
const outcallLocationOptions = computed(() => [
  { value: 'На квартиру', label: 'На квартиру' },
  { value: 'В гостиницу', label: 'В гостиницу' },
  { value: 'В загородный дом', label: 'В загородный дом' },
  { value: 'В сауну', label: 'В сауну' },
  { value: 'В офис', label: 'В офис' }
])

// Проверяем, выбран ли "Выезд"
const hasOutcallSelected = computed(() => {
  return localLocation.value.includes('Выезд')
})

const emitLocation = () => {
  emit('update:serviceLocation', [...localLocation.value])
  
  // Если "Выезд" не выбран, очищаем дополнительные опции и такси
  if (!hasOutcallSelected.value) {
    localOutcallOptions.value = []
    emit('update:outcallLocations', [])
    localTaxiOption.value = ''
    emit('update:taxiOption', '')
  }
}

const emitOutcallOptions = () => {
  emit('update:outcallLocations', [...localOutcallOptions.value])
}

const emitTaxiOption = () => {
  emit('update:taxiOption', localTaxiOption.value)
}
</script>

<style scoped>
.location-section {
  margin-bottom: 24px;
}

.form-group-title {
  font-size: 20px;
  font-weight: 500;
  color: #000000;
  margin: 0 0 20px 0;
  line-height: 1.3;
}

.main-locations {
  margin-bottom: 20px;
}

.outcall-locations {
  padding-left: 20px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e5e5;
}

.subcategory-title {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin: 0 0 12px 0;
  line-height: 1.3;
}

.taxi-section {
  padding-left: 20px;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e5e5;
}

.taxi-row {
  display: flex;
  align-items: center;
  gap: 30px;
  flex-wrap: wrap;
}

.taxi-title {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  min-width: 50px;
}

.taxi-options {
  display: flex;
  gap: 30px;
  flex-wrap: wrap;
}

.radio-option {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
  color: #333;
}

.radio-option input[type="radio"] {
  width: 16px;
  height: 16px;
  margin: 0;
  cursor: pointer;
}

.radio-label {
  user-select: none;
  cursor: pointer;
}
</style> 