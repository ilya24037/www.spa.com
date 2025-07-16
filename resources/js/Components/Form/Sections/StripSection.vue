<template>
  <div class="form-section">
    <div class="section-header">
      <h2 class="section-title">💃 Стриптиз</h2>
      <p class="section-subtitle">Расскажите о ваших танцевальных программах</p>
    </div>

    <div class="section-content">
      <!-- Тип шоу -->
      <div class="form-group">
        <label class="form-label">Тип шоу-программы</label>
        <div class="checkbox-group">
          <label v-for="type in showTypes" :key="type.id" class="checkbox-item">
            <input 
              type="checkbox" 
              :value="type.id"
              v-model="form.show_types"
              class="checkbox-input"
            >
            <span class="checkbox-label">{{ type.name }}</span>
          </label>
        </div>
        <div v-if="errors.show_types" class="error-message">{{ errors.show_types }}</div>
      </div>

      <!-- Возраст и параметры -->
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

      <!-- Костюмы и образы -->
      <div class="form-group">
        <label class="form-label">Доступные костюмы и образы</label>
        <div class="checkbox-group">
          <label v-for="costume in costumes" :key="costume.id" class="checkbox-item">
            <input 
              type="checkbox" 
              :value="costume.id"
              v-model="form.costumes"
              class="checkbox-input"
            >
            <span class="checkbox-label">{{ costume.name }}</span>
          </label>
        </div>
        <div v-if="errors.costumes" class="error-message">{{ errors.costumes }}</div>
      </div>

      <!-- Продолжительность -->
      <div class="form-group">
        <label class="form-label">Продолжительность программы</label>
        <div class="checkbox-group">
          <label v-for="duration in durations" :key="duration.id" class="checkbox-item">
            <input 
              type="checkbox" 
              :value="duration.id"
              v-model="form.durations"
              class="checkbox-input"
            >
            <span class="checkbox-label">{{ duration.name }}</span>
          </label>
        </div>
        <div v-if="errors.durations" class="error-message">{{ errors.durations }}</div>
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

      <!-- Особенности программы -->
      <div class="form-group">
        <label class="form-label">Описание программы</label>
        <textarea 
          v-model="form.program_description"
          :class="['form-textarea', { 'error': errors.program_description }]"
          placeholder="Расскажите о ваших танцевальных программах, особенностях выступлений..."
          rows="4"
        ></textarea>
        <div v-if="errors.program_description" class="error-message">{{ errors.program_description }}</div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'StripSection',
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
      showTypes: [
        { id: 'private', name: 'Приватные танцы' },
        { id: 'group', name: 'Групповые шоу' },
        { id: 'party', name: 'Вечеринки' },
        { id: 'corporate', name: 'Корпоративы' },
        { id: 'bachelor', name: 'Мальчишники' },
        { id: 'birthday', name: 'Дни рождения' }
      ],
      breastSizes: ['1', '2', '3', '4', '5', '6'],
      costumes: [
        { id: 'schoolgirl', name: 'Школьница' },
        { id: 'nurse', name: 'Медсестра' },
        { id: 'police', name: 'Полицейская' },
        { id: 'secretary', name: 'Секретарша' },
        { id: 'maid', name: 'Горничная' },
        { id: 'cat', name: 'Кошечка' },
        { id: 'angel', name: 'Ангел' },
        { id: 'devil', name: 'Дьяволица' },
        { id: 'custom', name: 'Свой костюм' }
      ],
      durations: [
        { id: '15min', name: '15 минут' },
        { id: '30min', name: '30 минут' },
        { id: '45min', name: '45 минут' },
        { id: '60min', name: '1 час' },
        { id: '90min', name: '1.5 часа' },
        { id: '120min', name: '2 часа' }
      ],
      additionalServices: [
        { id: 'lap_dance', name: 'Лап-данс' },
        { id: 'couple_dance', name: 'Парный танец' },
        { id: 'photo_session', name: 'Фотосессия' },
        { id: 'role_play', name: 'Ролевые игры' },
        { id: 'games', name: 'Игры и конкурсы' },
        { id: 'champagne', name: 'Шампанское' }
      ]
    }
  },
  mounted() {
    // Инициализируем поля формы если они не существуют
    this.$nextTick(() => {
      if (!this.form.show_types) this.form.show_types = []
      if (!this.form.costumes) this.form.costumes = []
      if (!this.form.durations) this.form.durations = []
      if (!this.form.additional_services) this.form.additional_services = []
    })
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