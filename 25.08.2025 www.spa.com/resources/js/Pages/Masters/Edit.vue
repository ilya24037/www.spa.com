<template>
  <div class="py-8">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
      <!-- Заголовок -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-500">
          Редактирование профиля
        </h1>
        <p class="mt-2 text-gray-500">
          Обновите информацию о себе и загрузите фотографии
        </p>
      </div>

      <!-- Форма профиля -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <form class="space-y-4" @submit.prevent="updateProfile">
          <!-- Обо мне (физические параметры) - используем компонент -->
          <div class="mb-6">
            <ParametersSection 
              v-model:age="form.age"
              v-model:height="form.height"
              v-model:weight="form.weight"
              v-model:breast-size="form.breast_size"
              :show-age="true"
              :show-breast-size="true"
              :show-hair-color="false"
              :show-eye-color="false"
              :show-nationality="false"
              :errors="{}"
            />
          </div>

          <!-- Основная информация -->
          <div class="mb-6 border-t pt-6">
            <h2 class="text-xl font-semibold text-gray-500 mb-4">
              Основная информация
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <BaseInput
                v-model="form.name"
                type="text"
                label="Имя"
                required
              />
              
              <BaseInput
                v-model="form.specialization"
                type="text"
                label="Специализация"
                placeholder="Например: Массаж спины, расслабляющий массаж"
              />
            </div>

            <BaseTextarea
              v-model="form.description"
              label="Описание"
              placeholder="Расскажите о своем опыте и услугах..."
              :rows="4"
            />
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <BaseInput
              v-model="form.experience_years"
              type="number"
              label="Опыт работы (лет)"
              :min="0"
              :max="50"
            />
            
            <BaseInput
              v-model="form.hourly_rate"
              type="number"
              label="Стоимость (руб/час)"
              :min="0"
              suffix="₽"
            />
            
            <BaseInput
              v-model="form.city"
              type="text"
              label="Город"
              placeholder="Название города"
            />
          </div>

          <div class="flex justify-end">
            <PrimaryButton
              type="submit"
              :disabled="updating"
              :loading="updating"
            >
              {{ updating ? 'Сохранение...' : 'Сохранить профиль' }}
            </PrimaryButton>
          </div>
        </form>
      </div>

      <!-- Загрузка медиа -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-xl font-semibold text-gray-500 mb-4">
          Фотографии и видео
        </h2>
        
        <MediaForm 
          v-model:photos="master.photos"
          v-model:video="master.video"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { router } from '@inertiajs/vue3'
import MediaForm from '@/src/shared/ui/molecules/Forms/features/MediaForm/MediaForm.vue'
import ParametersSection from '@/src/features/AdSections/ParametersSection/ui/ParametersSection.vue'
import BaseInput from '@/src/shared/ui/atoms/BaseInput/BaseInput.vue'
import BaseTextarea from '@/src/shared/ui/atoms/BaseTextarea/BaseTextarea.vue'
import PrimaryButton from '@/src/shared/ui/atoms/PrimaryButton/PrimaryButton.vue'
import { useToast } from '@/src/shared/composables/useToast'

// Toast для замены (window as any).// Removed // Removed // Removed alert() - use toast notifications instead - use toast notifications instead - use toast notifications instead
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


