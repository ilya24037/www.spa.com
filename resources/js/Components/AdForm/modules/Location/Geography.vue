<template>
  <FormSection
    title="География"
    hint="Укажите ваше местоположение для поиска"
    required
    :error="errors.geo"
  >
    <div class="geography-container">
      <!-- Поле адреса с автодополнением -->
      <FormField
        label="Адрес или район"
        hint="Начните вводить адрес, и мы подскажем варианты"
        :error="errors.geo"
      >
        <div class="address-input-container">
          <div class="address-input-wrapper">
            <input
              v-model="localAddress"
              @input="handleAddressInput"
              @focus="showSuggestions = true"
              type="text"
              placeholder="Например: Москва, Тверская улица, 1"
              class="address-input"
            />
            
            <button
              v-if="localAddress"
              @click="clearAddress"
              type="button"
              class="clear-button"
            >
              ✕
            </button>
          </div>

          <!-- Подсказки адресов -->
          <div v-if="showSuggestions && addressSuggestions.length > 0" class="suggestions-dropdown">
            <div
              v-for="(suggestion, index) in addressSuggestions"
              :key="index"
              @click="selectSuggestion(suggestion)"
              class="suggestion-item"
            >
              <div class="suggestion-icon">📍</div>
              <div class="suggestion-content">
                <div class="suggestion-address">{{ suggestion.address }}</div>
                <div class="suggestion-details">{{ suggestion.details }}</div>
              </div>
            </div>
          </div>
        </div>
      </FormField>

      <!-- Карта (заглушка) -->
      <FormField
        label="Местоположение на карте"
        hint="Проверьте правильность адреса на карте"
      >
        <div class="map-container">
          <div class="map-placeholder">
            <div class="map-icon">🗺️</div>
            <div class="map-text">
              <p class="map-title">Карта будет загружена</p>
              <p class="map-subtitle">После ввода адреса здесь появится карта с вашим местоположением</p>
            </div>
          </div>
          
          <!-- Координаты (если есть) -->
          <div v-if="hasCoordinates" class="coordinates-info">
            <div class="coordinates-label">Координаты:</div>
            <div class="coordinates-values">
              <span>{{ currentGeo.lat }}, {{ currentGeo.lng }}</span>
              <button @click="copyCoordinates" type="button" class="copy-btn">📋</button>
            </div>
          </div>
        </div>
      </FormField>

      <!-- Быстрый выбор районов -->
      <FormField
        label="Популярные районы"
        hint="Выберите из часто запрашиваемых районов"
      >
        <div class="districts-grid">
          <button
            v-for="district in popularDistricts"
            :key="district.name"
            @click="selectDistrict(district)"
            type="button"
            class="district-button"
          >
            <div class="district-name">{{ district.name }}</div>
            <div class="district-metro">{{ district.metro }}</div>
          </button>
        </div>
      </FormField>

      <!-- Детали локации -->
      <div v-if="localAddress" class="location-details">
        <div class="details-header">
          <span class="details-icon">ℹ️</span>
          <span class="details-title">Детали локации</span>
        </div>
        
        <div class="details-grid">
          <div class="detail-item">
            <span class="detail-label">Адрес:</span>
            <span class="detail-value">{{ localAddress }}</span>
          </div>
          
          <div v-if="currentGeo.district" class="detail-item">
            <span class="detail-label">Район:</span>
            <span class="detail-value">{{ currentGeo.district }}</span>
          </div>
          
          <div v-if="currentGeo.metro" class="detail-item">
            <span class="detail-label">Ближайшее метро:</span>
            <span class="detail-value">{{ currentGeo.metro }}</span>
          </div>
        </div>
      </div>

      <!-- Настройки приватности -->
      <FormField
        label="Настройки приватности"
        hint="Как показывать ваше местоположение в объявлении"
      >
        <div class="privacy-options">
          <label class="privacy-option">
            <input
              type="radio"
              name="privacy"
              value="exact"
              :checked="privacyLevel === 'exact'"
              @change="updatePrivacyLevel('exact')"
              class="privacy-radio"
            />
            <div class="privacy-content">
              <div class="privacy-title">Точный адрес</div>
              <div class="privacy-description">Показывать полный адрес</div>
            </div>
          </label>
          
          <label class="privacy-option">
            <input
              type="radio"
              name="privacy"
              value="district"
              :checked="privacyLevel === 'district'"
              @change="updatePrivacyLevel('district')"
              class="privacy-radio"
            />
            <div class="privacy-content">
              <div class="privacy-title">Только район</div>
              <div class="privacy-description">Показывать только район и метро</div>
            </div>
          </label>
          
          <label class="privacy-option">
            <input
              type="radio"
              name="privacy"
              value="metro"
              :checked="privacyLevel === 'metro'"
              @change="updatePrivacyLevel('metro')"
              class="privacy-radio"
            />
            <div class="privacy-content">
              <div class="privacy-title">Только метро</div>
              <div class="privacy-description">Показывать только станцию метро</div>
            </div>
          </label>
        </div>
      </FormField>
    </div>
  </FormSection>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FormSection from '@/Components/UI/Forms/FormSection.vue'
import FormField from '@/Components/UI/Forms/FormField.vue'

const props = defineProps({
  geo: { type: [Object, String], default: () => ({}) },
  errors: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['update:geo'])

// Локальное состояние
const localAddress = ref('')
const currentGeo = ref({})
const showSuggestions = ref(false)
const privacyLevel = ref('district')

// Инициализация данных
const initializeGeo = () => {
  let geo = props.geo
  
  if (typeof geo === 'string') {
    try {
      geo = JSON.parse(geo) || {}
    } catch (e) {
      geo = {}
    }
  }
  
  currentGeo.value = { ...geo }
  localAddress.value = geo.address || ''
  privacyLevel.value = geo.privacy || 'district'
}

// Отслеживание изменений пропсов
watch(() => props.geo, () => {
  initializeGeo()
}, { immediate: true })

// Популярные районы Москвы
const popularDistricts = [
  { name: 'Центральный', metro: 'м. Охотный Ряд', lat: 55.7558, lng: 37.6176 },
  { name: 'Арбат', metro: 'м. Арбатская', lat: 55.7520, lng: 37.5925 },
  { name: 'Тверской', metro: 'м. Тверская', lat: 55.7664, lng: 37.6156 },
  { name: 'Хамовники', metro: 'м. Сокольники', lat: 55.7342, lng: 37.5970 },
  { name: 'Измайлово', metro: 'м. Измайловская', lat: 55.7882, lng: 37.7536 },
  { name: 'Сокольники', metro: 'м. Сокольники', lat: 55.7887, lng: 37.6707 },
  { name: 'Китай-город', metro: 'м. Китай-Город', lat: 55.7558, lng: 37.6295 },
  { name: 'Замоскворечье', metro: 'м. Новокузнецкая', lat: 55.7423, lng: 37.6298 }
]

// Подсказки адресов (заглушка)
const addressSuggestions = ref([
  {
    address: 'Москва, Тверская улица, 1',
    details: 'Центральный район, м. Охотный Ряд'
  },
  {
    address: 'Москва, Арбат, 10',
    details: 'Центральный район, м. Арбатская'
  },
  {
    address: 'Москва, Новый Арбат, 15',
    details: 'Центральный район, м. Арбатская'
  }
])

// Методы
const handleAddressInput = () => {
  // Здесь будет логика автодополнения через API
  showSuggestions.value = localAddress.value.length > 2
  updateGeo()
}

const selectSuggestion = (suggestion) => {
  localAddress.value = suggestion.address
  showSuggestions.value = false
  
  // Парсим детали
  const parts = suggestion.details.split(', ')
  currentGeo.value = {
    ...currentGeo.value,
    address: suggestion.address,
    district: parts[0] || '',
    metro: parts[1] || '',
    lat: 55.7558, // Заглушка
    lng: 37.6176  // Заглушка
  }
  
  updateGeo()
}

const selectDistrict = (district) => {
  localAddress.value = `Москва, ${district.name}`
  
  currentGeo.value = {
    ...currentGeo.value,
    address: localAddress.value,
    district: district.name,
    metro: district.metro,
    lat: district.lat,
    lng: district.lng
  }
  
  updateGeo()
}

const clearAddress = () => {
  localAddress.value = ''
  currentGeo.value = {}
  showSuggestions.value = false
  updateGeo()
}

const updatePrivacyLevel = (level) => {
  privacyLevel.value = level
  currentGeo.value = {
    ...currentGeo.value,
    privacy: level
  }
  updateGeo()
}

const updateGeo = () => {
  const geoData = {
    ...currentGeo.value,
    address: localAddress.value,
    privacy: privacyLevel.value
  }
  
  emit('update:geo', geoData)
}

const copyCoordinates = () => {
  const coords = `${currentGeo.value.lat}, ${currentGeo.value.lng}`
  navigator.clipboard.writeText(coords)
}

// Computed
const hasCoordinates = computed(() => {
  return currentGeo.value.lat && currentGeo.value.lng
})

// Скрытие подсказок при клике вне
document.addEventListener('click', (e) => {
  if (!e.target.closest('.address-input-container')) {
    showSuggestions.value = false
  }
})
</script>

<style scoped>
.geography-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.address-input-container {
  position: relative;
}

.address-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.address-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.address-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.clear-button {
  position: absolute;
  right: 0.75rem;
  background: none;
  border: none;
  color: #9ca3af;
  cursor: pointer;
  font-size: 1rem;
  padding: 0.25rem;
}

.clear-button:hover {
  color: #6b7280;
}

.suggestions-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  z-index: 10;
  background: white;
  border: 1px solid #d1d5db;
  border-top: none;
  border-radius: 0 0 0.5rem 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  max-height: 200px;
  overflow-y: auto;
}

.suggestion-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.suggestion-item:hover {
  background: #f3f4f6;
}

.suggestion-icon {
  font-size: 1rem;
  flex-shrink: 0;
}

.suggestion-content {
  flex: 1;
}

.suggestion-address {
  font-weight: 500;
  color: #1f2937;
}

.suggestion-details {
  font-size: 0.875rem;
  color: #6b7280;
}

.map-container {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.map-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  height: 200px;
  background: #f8fafc;
  border: 2px dashed #cbd5e1;
  border-radius: 0.5rem;
}

.map-icon {
  font-size: 3rem;
}

.map-text {
  text-align: center;
}

.map-title {
  font-weight: 600;
  color: #475569;
  margin-bottom: 0.25rem;
}

.map-subtitle {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

.coordinates-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  background: #f1f5f9;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.coordinates-label {
  font-weight: 500;
  color: #475569;
}

.coordinates-values {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1e293b;
}

.copy-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 0.875rem;
  padding: 0.25rem;
}

.districts-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.75rem;
}

.district-button {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.district-button:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.district-name {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.district-metro {
  font-size: 0.875rem;
  color: #6b7280;
}

.location-details {
  padding: 1rem;
  background: #f0f9ff;
  border: 1px solid #0ea5e9;
  border-radius: 0.5rem;
}

.details-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.details-icon {
  font-size: 1rem;
}

.details-title {
  font-size: 0.875rem;
  font-weight: 500;
  color: #0c4a6e;
}

.details-grid {
  display: grid;
  gap: 0.5rem;
}

.detail-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.detail-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #0c4a6e;
  min-width: 100px;
}

.detail-value {
  font-size: 0.875rem;
  color: #0c4a6e;
}

.privacy-options {
  display: grid;
  gap: 0.75rem;
}

.privacy-option {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.privacy-option:hover {
  border-color: #9ca3af;
  background: #f9fafb;
}

.privacy-radio:checked + .privacy-content {
  color: #3b82f6;
}

.privacy-radio {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
}

.privacy-content {
  flex: 1;
}

.privacy-title {
  font-weight: 500;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.privacy-description {
  font-size: 0.875rem;
  color: #6b7280;
}

@media (max-width: 768px) {
  .districts-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .map-placeholder {
    height: 150px;
  }
  
  .map-icon {
    font-size: 2rem;
  }
}
</style>