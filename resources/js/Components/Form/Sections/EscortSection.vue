<template>
  <div class="form-section">
    <div class="section-header">
      <h2 class="section-title">👥 Сопровождение</h2>
      <p class="section-subtitle">Расскажите о ваших услугах сопровождения</p>
    </div>

    <div class="section-content">
      <!-- Тип сопровождения -->
      <div class="form-group">
        <label class="form-label">Тип сопровождения</label>
        <div class="checkbox-group">
          <label v-for="type in escortTypes" :key="type.id" class="checkbox-item">
            <input 
              type="checkbox" 
              :value="type.id"
              v-model="form.escort_types"
              class="checkbox-input"
            >
            <span class="checkbox-label">{{ type.name }}</span>
          </label>
        </div>
        <div v-if="errors.escort_types" class="error-message">{{ errors.escort_types }}</div>
      </div>

      <!-- Личные данные -->
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Возраст</label>
          <input 
            type="number" 
            v-model="form.age"
            :class="['form-input', { 'error': errors.age }]"
            placeholder="Укажите ваш возраст"
            min="18"
            max="65"
          >
          <div v-if="errors.age" class="error-message">{{ errors.age }}</div>
        </div>

        <div class="form-group">
          <label class="form-label">Рост (см)</label>
          <input 
            type="number" 
            v-model="form.height"
            :class="['form-input', { 'error': errors.height }]"
            placeholder="165"
            min="150"
            max="200"
          >
          <div v-if="errors.height" class="error-message">{{ errors.height }}</div>
        </div>

        <div class="form-group">
          <label class="form-label">Размер груди</label>
          <select 
            v-model="form.breast_size"
            :class="['form-select', { 'error': errors.breast_size }]"
          >
            <option value="">Выберите размер</option>
            <option v-for="size in breastSizes" :key="size" :value="size">{{ size }}</option>
          </select>
          <div v-if="errors.breast_size" class="error-message">{{ errors.breast_size }}</div>
        </div>
      </div>

      <!-- Образование -->
      <div class="form-group">
        <label class="form-label">Образование</label>
        <select 
          v-model="form.education"
          :class="['form-select', { 'error': errors.education }]"
        >
          <option value="">Выберите образование</option>
          <option v-for="edu in educationLevels" :key="edu.id" :value="edu.id">{{ edu.name }}</option>
        </select>
        <div v-if="errors.education" class="error-message">{{ errors.education }}</div>
      </div>

      <!-- Языки -->
      <div class="form-group">
        <label class="form-label">Знание языков</label>
        <div class="checkbox-group">
          <label v-for="language in languages" :key="language.id" class="checkbox-item">
            <input 
              type="checkbox" 
              :value="language.id"
              v-model="form.languages"
              class="checkbox-input"
            >
            <span class="checkbox-label">{{ language.name }}</span>
          </label>
        </div>
        <div v-if="errors.languages" class="error-message">{{ errors.languages }}</div>
      </div>

      <!-- Стиль одежды -->
      <div class="form-group">
        <label class="form-label">Стиль одежды</label>
        <div class="checkbox-group">
          <label v-for="style in clothingStyles" :key="style.id" class="checkbox-item">
            <input 
              type="checkbox" 
              :value="style.id"
              v-model="form.clothing_styles"
              class="checkbox-input"
            >
            <span class="checkbox-label">{{ style.name }}</span>
          </label>
        </div>
        <div v-if="errors.clothing_styles" class="error-message">{{ errors.clothing_styles }}</div>
      </div>

      <!-- Дополнительные услуги -->
      <div class="form-group">
        <label class="form-label">Дополнительные услуги</label>
        <div class="checkbox-group">
          <label v-for="service in additionalServices" :key="service.id" class="checkbox-item">
            <input 
              type="checkbox" 
              :value="service.id"
              v-model="form.additional_services"
              class="checkbox-input"
            >
            <span class="checkbox-label">{{ service.name }}</span>
          </label>
        </div>
        <div v-if="errors.additional_services" class="error-message">{{ errors.additional_services }}</div>
      </div>

      <!-- О себе -->
      <div class="form-group">
        <label class="form-label">О себе</label>
        <textarea 
          v-model="form.about_me"
          :class="['form-textarea', { 'error': errors.about_me }]"
          placeholder="Расскажите о себе, своих интересах, хобби, чем можете заинтересовать клиента..."
          rows="4"
        ></textarea>
        <div v-if="errors.about_me" class="error-message">{{ errors.about_me }}</div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EscortSection',
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
      escortTypes: [
        { id: 'business', name: 'Деловые мероприятия' },
        { id: 'social', name: 'Светские мероприятия' },
        { id: 'cultural', name: 'Культурные мероприятия' },
        { id: 'travel', name: 'Путешествия' },
        { id: 'dinner', name: 'Ужины и встречи' },
        { id: 'party', name: 'Вечеринки' },
        { id: 'shopping', name: 'Шоппинг' },
        { id: 'personal', name: 'Личное общение' }
      ],
      breastSizes: ['1', '2', '3', '4', '5', '6'],
      educationLevels: [
        { id: 'school', name: 'Среднее образование' },
        { id: 'college', name: 'Среднее специальное' },
        { id: 'bachelor', name: 'Высшее (бакалавр)' },
        { id: 'master', name: 'Высшее (магистр)' },
        { id: 'phd', name: 'Ученая степень' }
      ],
      languages: [
        { id: 'russian', name: 'Русский' },
        { id: 'english', name: 'Английский' },
        { id: 'german', name: 'Немецкий' },
        { id: 'french', name: 'Французский' },
        { id: 'spanish', name: 'Испанский' },
        { id: 'italian', name: 'Итальянский' },
        { id: 'chinese', name: 'Китайский' },
        { id: 'japanese', name: 'Японский' }
      ],
      clothingStyles: [
        { id: 'business', name: 'Деловой стиль' },
        { id: 'casual', name: 'Повседневный' },
        { id: 'evening', name: 'Вечерний' },
        { id: 'cocktail', name: 'Коктейльный' },
        { id: 'sport', name: 'Спортивный' },
        { id: 'elegant', name: 'Элегантный' }
      ],
      additionalServices: [
        { id: 'photo_session', name: 'Фотосессия' },
        { id: 'conversation', name: 'Интеллектуальная беседа' },
        { id: 'dance', name: 'Танцы' },
        { id: 'massage', name: 'Массаж' },
        { id: 'games', name: 'Игры' },
        { id: 'cooking', name: 'Приготовление еды' }
      ]
    }
  },
  created() {
    // Инициализируем поля формы если они не существуют
    if (!this.form.escort_types) this.form.escort_types = []
    if (!this.form.languages) this.form.languages = []
    if (!this.form.clothing_styles) this.form.clothing_styles = []
    if (!this.form.additional_services) this.form.additional_services = []
  }
}
</script>

<style scoped>
.form-section {
  @apply bg-white rounded-lg shadow-sm border border-gray-200 mb-6;
}

.section-header {
  @apply px-6 py-4 border-b border-gray-200;
}

.section-title {
  @apply text-xl font-semibold text-gray-900 mb-1;
}

.section-subtitle {
  @apply text-sm text-gray-600;
}

.section-content {
  @apply p-6 space-y-4;
}

.form-group {
  @apply space-y-2;
}

.form-row {
  @apply grid grid-cols-1 md:grid-cols-3 gap-4;
}

.form-label {
  @apply block text-sm font-medium text-gray-700;
}

.form-input, .form-select, .form-textarea {
  @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
}

.form-input.error, .form-select.error, .form-textarea.error {
  @apply border-red-500 focus:ring-red-500 focus:border-red-500;
}

.checkbox-group {
  @apply grid grid-cols-1 md:grid-cols-2 gap-2;
}

.checkbox-item {
  @apply flex items-center space-x-2 cursor-pointer;
}

.checkbox-input {
  @apply rounded border-gray-300 text-blue-600 focus:ring-blue-500;
}

.checkbox-label {
  @apply text-sm text-gray-700;
}

.error-message {
  @apply text-sm text-red-600;
}
</style> 