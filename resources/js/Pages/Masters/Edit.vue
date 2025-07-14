<template>
  <Head title="Редактировать анкету" />
  
  <div class="py-6 lg:py-8">
    <div class="flex gap-6">
      <!-- Боковая панель -->
      <SidebarWrapper 
        v-model="showMobileSidebar"
        content-class="p-0"
        :show-desktop-header="false"
        :always-visible-desktop="true"
      >
        <!-- Профиль пользователя -->
        <div class="p-6 border-b">
          <div class="flex items-center gap-4">
            <div 
              class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl font-bold"
              :style="{ backgroundColor: avatarColor }"
            >
              {{ userInitial }}
            </div>
            <div>
              <h3 class="font-semibold text-lg">{{ userName }}</h3>
              <div class="flex items-center gap-2 text-sm text-gray-600">
                <span class="font-medium">{{ master.rating || '—' }}</span>
                <div class="flex">
                  <svg 
                    v-for="i in 5" 
                    :key="i"
                    class="w-4 h-4"
                    :class="i <= Math.floor(master.rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
                <span class="text-xs">{{ master.reviews_count || 0 }} отзывов</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Меню навигации -->
        <nav class="py-2">
          <div class="px-3 py-2">
            <ul class="space-y-1">
              <li>
                <Link 
                  href="/dashboard"
                  :class="menuItemClass(false)"
                >
                  📊 Дашборд
                </Link>
              </li>
              <li>
                <Link 
                  :href="`/masters/${master.slug}/${master.id}`"
                  :class="menuItemClass(false)"
                >
                  👁️ Посмотреть анкету
                </Link>
              </li>
              <li>
                <Link 
                  href="/bookings"
                  :class="menuItemClass(false)"
                >
                  📅 Бронирования
                </Link>
              </li>
              <li>
                <Link 
                  href="/additem"
                  :class="menuItemClass(false)"
                >
                  ➕ Создать объявление
                </Link>
              </li>
            </ul>
          </div>
        </nav>
      </SidebarWrapper>
      
      <!-- Основной контент -->
      <main class="flex-1">
        <ContentCard>
          <!-- Заголовок -->
          <div class="mb-8">
            <div class="flex items-center justify-between">
              <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                  ✏️ Редактировать анкету
                </h1>
                <p class="text-gray-600">
                  Обновите информацию о себе и услугах
                </p>
              </div>
              <div class="text-right">
                <div class="text-sm text-gray-500">Статус:</div>
                <span :class="master.is_active ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100'" class="px-2 py-1 rounded text-sm font-medium">
                  {{ master.is_active ? '✅ Активна' : '❌ Неактивна' }}
                </span>
              </div>
            </div>
          </div>

          <!-- Прогресс-бар -->
          <div class="mb-8">
            <ProgressBar 
              :percentage="overallProgress"
              :sections="sectionsProgress"
              title="Заполненность анкеты"
            />
          </div>

          <!-- Уведомление о восстановлении данных -->
          <div v-if="hasDraft" class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                  <p class="font-medium text-blue-800">Найдены несохранённые изменения</p>
                  <p class="text-blue-600 text-sm">
                    Последнее сохранение: {{ lastSaved ? lastSaved.toLocaleString() : 'неизвестно' }}
                  </p>
                </div>
              </div>
              <div class="flex gap-2">
                <button 
                  @click="restoreForm"
                  class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition-colors"
                >
                  Восстановить
                </button>
                <button 
                  @click="clearDraft"
                  class="px-3 py-1 border border-blue-300 text-blue-700 rounded text-sm hover:bg-blue-50 transition-colors"
                >
                  Удалить
                </button>
              </div>
            </div>
          </div>

          <!-- Индикатор автосохранения -->
          <div v-if="saving" class="mb-4 flex items-center gap-2 text-sm text-gray-600">
            <div class="w-4 h-4 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
            <span>Автосохранение...</span>
          </div>

          <!-- Форма -->
          <form @submit.prevent="submit" class="space-y-6">
            
            <!-- Личная информация -->
            <div class="bg-gray-50 rounded-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">
                👤 Личная информация
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Имя -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Имя для объявления *
                  </label>
                  <input 
                    v-model="form.display_name"
                    type="text"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.display_name }"
                    placeholder="Например: Анна"
                  >
                  <div v-if="form.errors.display_name" class="text-red-600 text-sm mt-1">
                    {{ form.errors.display_name }}
                  </div>
                </div>
                
                <!-- Возраст -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Возраст
                  </label>
                  <input 
                    v-model="form.age"
                    type="number"
                    min="18"
                    max="65"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.age }"
                  >
                  <div v-if="form.errors.age" class="text-red-600 text-sm mt-1">
                    {{ form.errors.age }}
                  </div>
                </div>
                
                <!-- Опыт работы -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Опыт работы (лет)
                  </label>
                  <input 
                    v-model="form.experience_years"
                    type="number"
                    min="0"
                    max="50"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.experience_years }"
                  >
                  <div v-if="form.errors.experience_years" class="text-red-600 text-sm mt-1">
                    {{ form.errors.experience_years }}
                  </div>
                </div>
                
                <!-- Название салона -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Название салона
                  </label>
                  <input 
                    v-model="form.salon_name"
                    type="text"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.salon_name }"
                  >
                  <div v-if="form.errors.salon_name" class="text-red-600 text-sm mt-1">
                    {{ form.errors.salon_name }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Описание -->
            <div class="bg-gray-50 rounded-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">
                📝 Описание
              </h3>
              
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Расскажите о себе и своих услугах *
                </label>
                <textarea 
                  v-model="form.description"
                  rows="5"
                  class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-300': form.errors.description }"
                ></textarea>
                <div v-if="form.errors.description" class="text-red-600 text-sm mt-1">
                  {{ form.errors.description }}
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-1">
                  <span>Минимум 50 символов</span>
                  <span>{{ form.description?.length || 0 }} символов</span>
                </div>
              </div>
            </div>

            <!-- Фотографии -->
            <div class="bg-gray-50 rounded-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">
                📸 Фотографии
              </h3>
              <PhotoUploader 
                v-model="form.photos"
                :max-photos="8"
                :max-size="5"
              />
              <div v-if="form.errors.photos" class="text-red-600 text-sm mt-2">
                {{ form.errors.photos }}
              </div>
            </div>

            <!-- Местоположение -->
            <div class="bg-gray-50 rounded-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">
                📍 Местоположение
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Город -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Город *
                  </label>
                  <select 
                    v-model="form.city"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.city }"
                  >
                    <option value="">Выберите город</option>
                    <option v-for="city in cities" :key="city" :value="city">
                      {{ city }}
                    </option>
                  </select>
                  <div v-if="form.errors.city" class="text-red-600 text-sm mt-1">
                    {{ form.errors.city }}
                  </div>
                </div>
                
                <!-- Район -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Район
                  </label>
                  <input 
                    v-model="form.district"
                    type="text"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.district }"
                  >
                  <div v-if="form.errors.district" class="text-red-600 text-sm mt-1">
                    {{ form.errors.district }}
                  </div>
                </div>
                
                <!-- Адрес -->
                <div class="md:col-span-2">
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Адрес
                  </label>
                  <input 
                    v-model="form.address"
                    type="text"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.address }"
                  >
                  <div v-if="form.errors.address" class="text-red-600 text-sm mt-1">
                    {{ form.errors.address }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Контакты -->
            <div class="bg-gray-50 rounded-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">
                📞 Контакты
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Телефон -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Телефон *
                  </label>
                  <input 
                    v-model="form.phone"
                    type="tel"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.phone }"
                  >
                  <div v-if="form.errors.phone" class="text-red-600 text-sm mt-1">
                    {{ form.errors.phone }}
                  </div>
                  <div class="flex items-center mt-2">
                    <input 
                      v-model="form.show_phone"
                      type="checkbox"
                      class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                    >
                    <label class="ml-2 text-sm text-gray-700">
                      Показывать телефон в объявлении
                    </label>
                  </div>
                </div>
                
                <!-- WhatsApp -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    WhatsApp
                  </label>
                  <input 
                    v-model="form.whatsapp"
                    type="tel"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.whatsapp }"
                  >
                  <div v-if="form.errors.whatsapp" class="text-red-600 text-sm mt-1">
                    {{ form.errors.whatsapp }}
                  </div>
                </div>
                
                <!-- Telegram -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Telegram
                  </label>
                  <input 
                    v-model="form.telegram"
                    type="text"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.telegram }"
                  >
                  <div v-if="form.errors.telegram" class="text-red-600 text-sm mt-1">
                    {{ form.errors.telegram }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Цены -->
            <div class="bg-gray-50 rounded-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">
                💰 Цены
              </h3>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Цена от -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Цена от (₽) *
                  </label>
                  <input 
                    v-model="form.price_from"
                    type="number"
                    min="500"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.price_from }"
                  >
                  <div v-if="form.errors.price_from" class="text-red-600 text-sm mt-1">
                    {{ form.errors.price_from }}
                  </div>
                </div>
                
                <!-- Цена до -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    Цена до (₽)
                  </label>
                  <input 
                    v-model="form.price_to"
                    type="number"
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    :class="{ 'border-red-300': form.errors.price_to }"
                  >
                  <div v-if="form.errors.price_to" class="text-red-600 text-sm mt-1">
                    {{ form.errors.price_to }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Кнопки -->
            <div class="flex justify-between items-center pt-6">
              <div class="flex gap-3">
                <Link 
                  href="/dashboard"
                  class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  ← Назад к дашборду
                </Link>
                <button 
                  @click="showPreview = true"
                  type="button"
                  class="px-6 py-2 border border-blue-300 text-blue-700 rounded-lg hover:bg-blue-50 transition-colors"
                >
                  👁️ Предварительный просмотр
                </button>
              </div>
              
              <button 
                type="submit"
                :disabled="form.processing"
                class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {{ form.processing ? 'Сохранение...' : '💾 Сохранить изменения' }}
              </button>
            </div>
          </form>
        </ContentCard>
      </main>
    </div>
  </div>

  <!-- Модалка предварительного просмотра -->
  <PreviewModal 
    :show="showPreview"
    :form-data="form.data()"
    :progress="overallProgress"
    @close="showPreview = false"
    @publish="handleSaveFromPreview"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, usePage, useForm } from '@inertiajs/vue3'
import { useAutoSave } from '@/Composables/useAutoSave'
import { useFormProgress, massageFormSections } from '@/Composables/useFormProgress'
import { useToast } from '@/Composables/useToast'

// Компоненты
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import ContentCard from '@/Components/Layout/ContentCard.vue'
import PhotoUploader from '@/Components/Upload/PhotoUploader.vue'
import ProgressBar from '@/Components/Forms/ProgressBar.vue'
import PreviewModal from '@/Components/AddItem/PreviewModal.vue'

// Пропсы
const props = defineProps({
  master: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
  cities: { type: Array, required: true }
})

// Состояние
const showMobileSidebar = ref(false)
const showPreview = ref(false)

// Пользователь
const page = usePage()
const { showSuccess, showError } = useToast()
const user = computed(() => page.props.auth?.user || {})
const userName = computed(() => user.value.name || 'Пользователь')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())

// Цвет аватара
const colors = ['#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#00bcd4', '#009688', '#4caf50', '#ff9800', '#ff5722']
const avatarColor = computed(() => {
  const charCode = userName.value.charCodeAt(0) || 85
  return colors[charCode % colors.length]
})

// Класс для пунктов меню
const menuItemClass = (isActive) => [
  'flex items-center justify-between px-3 py-2 text-sm rounded-lg transition',
  isActive ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-50'
]

// Форма с данными мастера
const form = useForm({
  display_name: props.master.display_name || '',
  description: props.master.bio || '',
  age: props.master.age || '',
  experience_years: props.master.experience_years || '',
  city: props.master.city || '',
  district: props.master.district || '',
  address: props.master.address || '',
  salon_name: props.master.salon_name || '',
  phone: props.master.phone || '',
  whatsapp: props.master.whatsapp || '',
  telegram: props.master.telegram || '',
  price_from: props.master.price_from || '',
  price_to: props.master.price_to || '',
  show_phone: props.master.show_phone || false,
  photos: props.master.photos ? props.master.photos.map(photo => ({
    id: photo.id,
    url: photo.path,
    preview: photo.path,
    name: photo.alt || 'Фото',
    is_main: photo.is_main || false
  })) : []
})

// Автосохранение
const { 
  lastSaved, 
  saving, 
  hasDraft, 
  restoreData, 
  clearData 
} = useAutoSave(form.data(), {
  key: `edit_master_${props.master.id}_draft`,
  interval: 30000,
  exclude: ['photos']
})

// Прогресс формы
const { 
  overallProgress, 
  sectionsProgress,
  validateRequired 
} = useFormProgress(form.data(), massageFormSections)

// Восстановление формы
const restoreForm = () => {
  if (restoreData()) {
    showSuccess('Данные восстановлены из черновика')
  }
}

const clearDraft = () => {
  clearData()
  showSuccess('Черновик удалён')
}

// Отправка формы
const submit = () => {
  // Валидация обязательных полей
  const validation = validateRequired()
  
  if (!validation.valid) {
    showError('Заполните обязательные поля')
    return
  }

  // Подготавливаем данные для отправки
  const formData = new FormData()
  
  // Обычные поля
  Object.keys(form.data()).forEach(key => {
    if (key === 'photos') return
    
    const value = form[key]
    if (value !== null && value !== '') {
      formData.append(key, value)
    }
  })

  // Фотографии (если есть новые)
  form.photos.forEach((photo, index) => {
    if (photo.file) {
      formData.append(`photos[${index}]`, photo.file)
      formData.append(`photos[${index}][is_main]`, photo.is_main ? '1' : '0')
    }
  })

  // Отправляем
  form.put(`/masters/${props.master.id}`, {
    data: formData,
    onSuccess: () => {
      clearData() // Очищаем черновик после успешной отправки
      showSuccess('Анкета успешно обновлена!')
    },
    onError: () => {
      showError('Ошибка при обновлении анкеты')
    }
  })
}

const handleSaveFromPreview = () => {
  showPreview.value = false
  submit()
}
</script>