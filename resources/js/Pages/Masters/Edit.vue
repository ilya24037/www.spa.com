<template>
  <div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
      <!-- Заголовок -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Редактирование профиля</h1>
        <p class="mt-2 text-gray-600">Обновите информацию о себе и загрузите фотографии</p>
      </div>

      <!-- Форма профиля -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Основная информация</h2>
        
        <form @submit.prevent="updateProfile" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Имя
              </label>
              <input 
                v-model="form.name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Специализация
              </label>
              <input 
                v-model="form.specialization"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Например: Массаж спины, расслабляющий массаж"
              >
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
              Описание
            </label>
            <textarea 
              v-model="form.description"
              rows="4"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Расскажите о своем опыте и услугах..."
            ></textarea>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Опыт работы (лет)
              </label>
              <input 
                v-model="form.experience_years"
                type="number"
                min="0"
                max="50"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Стоимость (руб/час)
              </label>
              <input 
                v-model="form.hourly_rate"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Город
              </label>
              <input 
                v-model="form.city"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
            </div>
          </div>

          <!-- Физические параметры -->
          <div class="border-t pt-4 mt-4">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Физические параметры</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
              </div>
              
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
              </div>
              
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
              </div>
              
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
              </div>
            </div>
          </div>

          <div class="flex justify-end">
            <button 
              type="submit"
              :disabled="updating"
              class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50"
            >
              {{ updating ? 'Сохранение...' : 'Сохранить профиль' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Загрузка медиа -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4">Фотографии и видео</h2>
        
        <MediaUploader 
          :master-id="master.id"
          :master-name="master.name"
          :initial-photos="master.photos || []"
          :initial-video="master.video || null"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import MediaUploader from '@/src/entities/ad/ui/AdForm/components/AdFormMediaUpload.vue'
import { useToast } from '@/src/shared/composables/useToast'

// Toast для замены alert()
const toast = useToast()

const props = defineProps({
  master: {
    type: Object,
    required: true
  }
})

const updating = ref(false)

const form = reactive({
  name: props.master.name || '',
  specialization: props.master.specialization || '',
  description: props.master.description || '',
  experience_years: props.master.experience_years || 0,
  hourly_rate: props.master.hourly_rate || 0,
  city: props.master.city || '',
  age: props.master.age || '',
  height: props.master.height || '',
  weight: props.master.weight || '',
  breast_size: props.master.breast_size || ''
})

const updateProfile = async () => {
  updating.value = true
  
  try {
    await router.put(`/masters/${props.master.id}`, form)
    toast.success('Профиль обновлен успешно!')
  } catch (error) {
    toast.error('Ошибка обновления профиля: ' + error.message)
  } finally {
    updating.value = false
  }
}
</script> 


