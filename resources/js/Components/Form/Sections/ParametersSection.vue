<template>
  <div class="parameters-section">
    <div class="field-hint" style="margin-bottom: 20px; color: #8c8c8c; font-size: 16px;">
      Заполните физические параметры. Это поможет клиентам лучше понять ваши услуги.
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
      <!-- Возраст -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Возраст
        </label>
        <input 
          v-model="form.age"
          type="number"
          min="18"
          max="65"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="34"
        >
        <div class="text-xs text-gray-500 mt-1">Ваш возраст в годах</div>
        <div v-if="errors.age" class="text-red-500 text-sm mt-1">{{ errors.age }}</div>
      </div>
      
      <!-- Рост -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Рост (см)
        </label>
        <input 
          v-model="form.height"
          type="number"
          min="140"
          max="200"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="161"
        >
        <div class="text-xs text-gray-500 mt-1">Ваш рост в сантиметрах</div>
        <div v-if="errors.height" class="text-red-500 text-sm mt-1">{{ errors.height }}</div>
      </div>
      
      <!-- Вес -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Вес (кг)
        </label>
        <input 
          v-model="form.weight"
          type="number"
          min="40"
          max="120"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
          placeholder="56"
        >
        <div class="text-xs text-gray-500 mt-1">Ваш вес в килограммах</div>
        <div v-if="errors.weight" class="text-red-500 text-sm mt-1">{{ errors.weight }}</div>
      </div>
      
      <!-- Размер груди -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Размер груди
        </label>
        <select 
          v-model="form.breast_size"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Не указано</option>
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option value="6">6</option>
        </select>
        <div v-if="errors.breast_size" class="text-red-500 text-sm mt-1">{{ errors.breast_size }}</div>
      </div>
    </div>

    <!-- Дополнительные параметры -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
      <!-- Цвет волос -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Цвет волос
        </label>
        <select 
          v-model="form.hair_color"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Не указано</option>
          <option value="блондинка">Блондинка</option>
          <option value="брюнетка">Брюнетка</option>
          <option value="шатенка">Шатенка</option>
          <option value="рыжая">Рыжая</option>
          <option value="русая">Русая</option>
        </select>
        <div v-if="errors.hair_color" class="text-red-500 text-sm mt-1">{{ errors.hair_color }}</div>
      </div>
      
      <!-- Цвет глаз -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Цвет глаз
        </label>
        <select 
          v-model="form.eye_color"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Не указано</option>
          <option value="карие">Карие</option>
          <option value="голубые">Голубые</option>
          <option value="зеленые">Зеленые</option>
          <option value="серые">Серые</option>
          <option value="черные">Черные</option>
        </select>
        <div v-if="errors.eye_color" class="text-red-500 text-sm mt-1">{{ errors.eye_color }}</div>
      </div>
      
      <!-- Национальность -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          Национальность
        </label>
        <select 
          v-model="form.nationality"
          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="">Не указано</option>
          <option value="русская">Русская</option>
          <option value="украинка">Украинка</option>
          <option value="белоруска">Белоруска</option>
          <option value="казашка">Казашка</option>
          <option value="татарка">Татарка</option>
          <option value="армянка">Армянка</option>
          <option value="грузинка">Грузинка</option>
          <option value="азербайджанка">Азербайджанка</option>
          <option value="узбечка">Узбечка</option>
          <option value="другая">Другая</option>
        </select>
        <div v-if="errors.nationality" class="text-red-500 text-sm mt-1">{{ errors.nationality }}</div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  form: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})
</script>

<style scoped>
.parameters-section {
  /* Дополнительные стили если нужны */
}
</style> 