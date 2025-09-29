<template>
  <div class="address-search-test">
    <Head title="Тест гибридного компонента AddressSearchWithMap" />
    
    <!-- Заголовок страницы -->
    <div class="page-header">
      <h1 class="page-title">
        🧪 Тест гибридного компонента AddressSearchWithMap
      </h1>
      <p class="page-description">
        Демонстрация работы Vue + HTML iframe карты с postMessage коммуникацией
      </p>
    </div>
    
    <!-- Форма тестирования -->
    <div class="test-container">
      <form @submit.prevent="handleSubmit" class="test-form">
        <div class="form-section">
          <h2 class="section-title">🏠 Выбор адреса</h2>
          
          <!-- Гибридный компонент -->
          <AddressSearchWithMap
            v-model="formData.location"
            field-name="location"
            :height="500"
            :required="true"
            @address-selected="onAddressSelected"
            @address-cleared="onAddressCleared"
          />
        </div>
        
        <!-- Дополнительные поля формы -->
        <div class="form-section">
          <h2 class="section-title">📝 Дополнительная информация</h2>
          
          <div class="form-group">
            <label class="form-label">
              Название объявления
              <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.title"
              type="text"
              class="form-input"
              placeholder="Введите название объявления"
              required
            />
          </div>
          
          <div class="form-group">
            <label class="form-label">Описание</label>
            <textarea
              v-model="formData.description"
              class="form-textarea"
              rows="3"
              placeholder="Опишите ваше объявление"
            ></textarea>
          </div>
        </div>
        
        <!-- Кнопки действий -->
        <div class="form-actions">
          <button
            type="submit"
            class="submit-button"
            :disabled="!isFormValid"
          >
            ✅ Отправить форму
          </button>
          
          <button
            type="button"
            class="reset-button"
            @click="resetForm"
          >
            🔄 Сбросить
          </button>
          
          <button
            type="button"
            class="demo-button"
            @click="loadDemoData"
          >
            🎯 Загрузить демо данные
          </button>
        </div>
      </form>
      
      <!-- Отладочная информация -->
      <div class="debug-section">
        <h2 class="section-title">🔍 Отладочная информация</h2>
        
        <div class="debug-cards">
          <!-- Состояние формы -->
          <div class="debug-card">
            <h3 class="debug-card-title">📋 Состояние формы</h3>
            <pre class="debug-content">{{ JSON.stringify(formData, null, 2) }}</pre>
          </div>
          
          <!-- Последнее событие -->
          <div class="debug-card">
            <h3 class="debug-card-title">📡 Последнее событие</h3>
            <div class="debug-content">
              <div v-if="lastEvent.type" class="event-info">
                <div class="event-type">Тип: {{ lastEvent.type }}</div>
                <div class="event-time">Время: {{ lastEvent.timestamp }}</div>
                <pre class="event-data">{{ JSON.stringify(lastEvent.data, null, 2) }}</pre>
              </div>
              <div v-else class="text-gray-500">Событий пока не было</div>
            </div>
          </div>
          
          <!-- Валидация -->
          <div class="debug-card">
            <h3 class="debug-card-title">✅ Валидация</h3>
            <div class="debug-content">
              <div class="validation-item">
                <span class="validation-label">Форма валидна:</span>
                <span :class="isFormValid ? 'text-green-600' : 'text-red-600'">
                  {{ isFormValid ? '✅ Да' : '❌ Нет' }}
                </span>
              </div>
              <div class="validation-item">
                <span class="validation-label">Адрес выбран:</span>
                <span :class="hasLocation ? 'text-green-600' : 'text-red-600'">
                  {{ hasLocation ? '✅ Да' : '❌ Нет' }}
                </span>
              </div>
              <div class="validation-item">
                <span class="validation-label">Координаты:</span>
                <span :class="hasCoordinates ? 'text-green-600' : 'text-red-600'">
                  {{ hasCoordinates ? `✅ [${formData.location.lat}, ${formData.location.lng}]` : '❌ Отсутствуют' }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref, computed, reactive } from 'vue'
import { AddressSearchWithMap } from '@/src/shared/ui/molecules/AddressSearchWithMap'

// Состояние формы
const formData = reactive({
  title: '',
  description: '',
  location: {
    address: '',
    lat: null as number | null,
    lng: null as number | null
  }
})

// Состояние отладки
const lastEvent = reactive({
  type: '',
  timestamp: '',
  data: null as any
})

// Computed
const isFormValid = computed(() => {
  return (
    formData.title.trim().length > 0 &&
    formData.location.address.trim().length > 0 &&
    formData.location.lat !== null &&
    formData.location.lng !== null
  )
})

const hasLocation = computed(() => {
  return formData.location.address.trim().length > 0
})

const hasCoordinates = computed(() => {
  return formData.location.lat !== null && formData.location.lng !== null
})

// Methods
const onAddressSelected = (data: any) => {
  console.log('🏠 [AddressSearchTest] Адрес выбран:', data)
  
  lastEvent.type = 'address-selected'
  lastEvent.timestamp = new Date().toLocaleTimeString()
  lastEvent.data = data
}

const onAddressCleared = () => {
  console.log('🧹 [AddressSearchTest] Адрес очищен')
  
  lastEvent.type = 'address-cleared'
  lastEvent.timestamp = new Date().toLocaleTimeString()
  lastEvent.data = null
}

const handleSubmit = () => {
  if (!isFormValid.value) {
    alert('❌ Заполните все обязательные поля!')
    return
  }
  
  console.log('📤 [AddressSearchTest] Отправка формы:', formData)
  
  lastEvent.type = 'form-submitted'
  lastEvent.timestamp = new Date().toLocaleTimeString()
  lastEvent.data = { ...formData }
  
  alert(`✅ Форма отправлена!\n\nАдрес: ${formData.location.address}\nКоординаты: [${formData.location.lat}, ${formData.location.lng}]`)
}

const resetForm = () => {
  console.log('🔄 [AddressSearchTest] Сброс формы')
  
  formData.title = ''
  formData.description = ''
  formData.location = {
    address: '',
    lat: null,
    lng: null
  }
  
  lastEvent.type = 'form-reset'
  lastEvent.timestamp = new Date().toLocaleTimeString()
  lastEvent.data = null
}

const loadDemoData = () => {
  console.log('🎯 [AddressSearchTest] Загрузка демо данных')
  
  formData.title = 'Массаж в центре Москвы'
  formData.description = 'Предлагаю качественный массаж в центре города. Опыт работы более 5 лет.'
  formData.location = {
    address: 'Москва, Красная площадь, 1',
    lat: 55.7539,
    lng: 37.6208
  }
  
  lastEvent.type = 'demo-loaded'
  lastEvent.timestamp = new Date().toLocaleTimeString()
  lastEvent.data = { ...formData }
}
</script>

<style scoped>
.address-search-test {
  @apply min-h-screen bg-gray-50 py-8;
}

.page-header {
  @apply text-center mb-8 px-4;
}

.page-title {
  @apply text-3xl font-bold text-gray-900 mb-4;
}

.page-description {
  @apply text-lg text-gray-600 max-w-2xl mx-auto;
}

.test-container {
  @apply max-w-6xl mx-auto px-4 space-y-8;
}

.test-form {
  @apply bg-white rounded-xl shadow-lg overflow-hidden;
}

.form-section {
  @apply p-6 border-b border-gray-200 last:border-b-0;
}

.section-title {
  @apply text-xl font-semibold text-gray-800 mb-4;
}

.form-group {
  @apply space-y-2;
}

.form-label {
  @apply block text-sm font-medium text-gray-700;
}

.form-input {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm;
  @apply focus:ring-blue-500 focus:border-blue-500;
  @apply text-sm placeholder-gray-400;
}

.form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm;
  @apply focus:ring-blue-500 focus:border-blue-500;
  @apply text-sm placeholder-gray-400 resize-y;
}

.form-actions {
  @apply flex flex-wrap gap-3 p-6 bg-gray-50;
}

.submit-button {
  @apply px-6 py-2 bg-green-600 text-white rounded-lg font-semibold;
  @apply hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500;
  @apply disabled:bg-gray-300 disabled:cursor-not-allowed;
  @apply transition-colors duration-200;
}

.reset-button {
  @apply px-6 py-2 bg-gray-600 text-white rounded-lg font-semibold;
  @apply hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500;
  @apply transition-colors duration-200;
}

.demo-button {
  @apply px-6 py-2 bg-blue-600 text-white rounded-lg font-semibold;
  @apply hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500;
  @apply transition-colors duration-200;
}

.debug-section {
  @apply bg-white rounded-xl shadow-lg overflow-hidden p-6;
}

.debug-cards {
  @apply grid lg:grid-cols-3 gap-6;
}

.debug-card {
  @apply bg-gray-50 rounded-lg p-4;
}

.debug-card-title {
  @apply text-sm font-semibold text-gray-700 mb-3;
}

.debug-content {
  @apply text-xs text-gray-600;
}

.debug-content pre {
  @apply bg-gray-100 p-2 rounded text-xs overflow-auto;
  @apply font-mono whitespace-pre-wrap;
}

.event-info {
  @apply space-y-2;
}

.event-type,
.event-time {
  @apply text-xs font-medium;
}

.event-data {
  @apply bg-gray-100 p-2 rounded text-xs overflow-auto;
  @apply font-mono whitespace-pre-wrap;
}

.validation-item {
  @apply flex items-center justify-between py-1;
}

.validation-label {
  @apply text-xs font-medium text-gray-600;
}

/* Мобильная адаптация */
@media (max-width: 1024px) {
  .debug-cards {
    @apply grid-cols-1;
  }
}

@media (max-width: 640px) {
  .test-container {
    @apply px-2;
  }
  
  .form-section {
    @apply p-4;
  }
  
  .form-actions {
    @apply flex-col;
  }
  
  .submit-button,
  .reset-button,
  .demo-button {
    @apply w-full;
  }
}
</style>