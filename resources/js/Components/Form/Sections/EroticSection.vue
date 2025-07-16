<template>
  <div class="form-section">
    <div class="section-header">
      <h2 class="section-title">🔥 Эротический массаж</h2>
      <p class="section-subtitle">Укажите детали вашего предложения</p>
    </div>

    <div class="section-content">
      <!-- Тип массажа -->
      <div class="form-group">
        <label class="form-label">Тип массажа</label>
        <div class="checkbox-group">
          <label v-for="type in massageTypes" :key="type.id" class="checkbox-item">
            <input 
              type="checkbox" 
              :value="type.id"
              v-model="form.massage_types"
              class="checkbox-input"
            >
            <span class="checkbox-label">{{ type.name }}</span>
          </label>
        </div>
        <div v-if="errors.massage_types" class="error-message">{{ errors.massage_types }}</div>
      </div>

      <!-- Возраст -->
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

      <!-- Параметры -->
      <div class="form-row">
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
          <label class="form-label">Вес (кг)</label>
          <input 
            type="number" 
            v-model="form.weight"
            :class="['form-input', { 'error': errors.weight }]"
            placeholder="55"
            min="40"
            max="120"
          >
          <div v-if="errors.weight" class="error-message">{{ errors.weight }}</div>
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

      <!-- Особенности -->
      <div class="form-group">
        <label class="form-label">Особенности и предпочтения</label>
        <textarea 
          v-model="form.features"
          :class="['form-textarea', { 'error': errors.features }]"
          placeholder="Расскажите о ваших особенностях, предпочтениях клиентов..."
          rows="4"
        ></textarea>
        <div v-if="errors.features" class="error-message">{{ errors.features }}</div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'EroticSection',
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
      massageTypes: [
        { id: 'tantra', name: 'Тантрический массаж' },
        { id: 'body_to_body', name: 'Body-to-body' },
        { id: 'intimate', name: 'Интимный массаж' },
        { id: 'relaxing', name: 'Расслабляющий массаж' },
        { id: 'erotic_classic', name: 'Эротический классический' }
      ],
      breastSizes: ['1', '2', '3', '4', '5', '6'],
      additionalServices: [
        { id: 'shower', name: 'Душ вместе' },
        { id: 'role_play', name: 'Ролевые игры' },
        { id: 'toys', name: 'Использование игрушек' },
        { id: 'outfit', name: 'Специальная одежда' },
        { id: 'domination', name: 'Доминирование' },
        { id: 'submission', name: 'Подчинение' }
      ]
    }
  },
  created() {
    // Инициализируем поля формы если они не существуют
    if (!this.form.massage_types) this.form.massage_types = []
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