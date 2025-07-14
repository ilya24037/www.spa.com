<template>
  <Head title="Создать анкету массажиста" />
  
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
                <span class="font-medium">{{ userStats?.rating || '—' }}</span>
                <div class="flex">
                  <svg 
                    v-for="i in 5" 
                    :key="i"
                    class="w-4 h-4"
                    :class="i <= Math.floor(userStats?.rating || 0) ? 'text-yellow-400' : 'text-gray-300'"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                  </svg>
                </div>
                <span class="text-xs">{{ userStats?.reviewsCount || 0 }} отзывов</span>
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
                  href="/profile"
                  :class="menuItemClass(isCurrentRoute('profile'))"
                >
                  Мои анкеты
                  <span v-if="counts?.profiles > 0" class="ml-auto text-xs bg-gray-100 px-2 py-0.5 rounded">
                    {{ counts.profiles }}
                  </span>
                </Link>
              </li>
              <li>
                <Link 
                  href="/bookings"
                  :class="menuItemClass(isCurrentRoute('bookings'))"
                >
                  Бронирования
                  <span v-if="counts?.bookings > 0" class="ml-auto text-xs bg-blue-100 text-blue-600 px-2 py-0.5 rounded">
                    {{ counts.bookings }}
                  </span>
                </Link>
              </li>
              <li>
                <Link 
                  href="/favorites"
                  :class="menuItemClass(isCurrentRoute('favorites'))"
                >
                  Избранное
                  <span v-if="counts?.favorites > 0" class="ml-auto text-xs bg-gray-100 px-2 py-0.5 rounded">
                    {{ counts.favorites }}
                  </span>
                </Link>
              </li>
              <li>
                <Link 
                  href="/additem"
                  :class="menuItemClass(isCurrentRoute('additem'))"
                >
                  Создать объявление
                </Link>
              </li>
              <li>
                <Link 
                  href="/profile/edit"
                  :class="menuItemClass(isCurrentRoute('profile/edit'))"
                >
                  Настройки профиля
                </Link>
              </li>
            </ul>
          </div>
        </nav>
      </SidebarWrapper>
      
      <!-- Основной контент -->
      <main class="flex-1">
        <ContentCard>
          <template #header>
            <!-- Хлебные крошки -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
              <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li v-for="(breadcrumb, index) in breadcrumbs" :key="index" class="inline-flex items-center">
                  <Link 
                    v-if="breadcrumb.url" 
                    :href="breadcrumb.url"
                    class="text-gray-500 hover:text-gray-700 transition-colors"
                  >
                    {{ breadcrumb.name }}
                  </Link>
                  <span v-else class="text-gray-900 font-medium">{{ breadcrumb.name }}</span>
                  
                  <svg 
                    v-if="index < breadcrumbs.length - 1"
                    class="w-4 h-4 text-gray-400 mx-2" 
                    fill="currentColor" 
                    viewBox="0 0 20 20"
                  >
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                  </svg>
                </li>
              </ol>
            </nav>
          </template>

          <!-- Заголовок -->
          <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              💆‍♀️ Создать анкету массажиста
            </h1>
            <p class="text-gray-600">
              Заполните информацию о себе и услугах
            </p>
          </div>

          <!-- Прогресс-бар -->
          <div class="mb-8">
            <ProgressBar 
              :percentage="overallProgress"
              :sections="sectionsProgress"
              title="Прогресс заполнения анкеты"
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
                  <p class="font-medium text-blue-800">Найдены несохранённые данные</p>
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
                  <p class="text-xs text-gray-500 mt-1">Это имя будут видеть клиенты</p>
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
                    placeholder="25"
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
                    placeholder="5"
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
                    placeholder="Салон красоты 'Релакс'"
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
                  placeholder="Профессиональный массажист с медицинским образованием. Специализируюсь на лечебном и расслабляющем массаже. Работаю с натуральными маслами..."
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
                    placeholder="Центральный"
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
                    placeholder="ул. Примерная, д. 10"
                  >
                  <div v-if="form.errors.address" class="text-red-600 text-sm mt-1">
                    {{ form.errors.address }}
                  </div>
                  <p class="text-xs text-gray-500 mt-1">Точный адрес будет виден только при бронировании</p>
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
                    placeholder="+7 (999) 123-45-67"
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
                    placeholder="+7 (999) 123-45-67"
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
                    placeholder="@username"
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
                    placeholder="2000"
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
                    placeholder="5000"
                  >
                  <div v-if="form.errors.price_to" class="text-red-600 text-sm mt-1">
                    {{ form.errors.price_to }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Услуги -->
            <div class="bg-gray-50 rounded-lg p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">
                🔧 Услуги
              </h3>
              
              <div class="space-y-4">
                <div 
                  v-for="(service, index) in form.services" 
                  :key="index"
                  class="bg-white rounded-lg p-4 border border-gray-200"
                >
                  <div class="flex items-center justify-between mb-3">
                    <h4 class="font-medium text-gray-900">Услуга {{ index + 1 }}</h4>
                    <button 
                      v-if="form.services.length > 1"
                      @click="removeService(index)"
                      type="button"
                      class="text-red-600 hover:text-red-800"
                    >
                      Удалить
                    </button>
                  </div>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Категория -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Категория *
                      </label>
                      <select 
                        v-model="service.category_id"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :class="{ 'border-red-300': form.errors[`services.${index}.category_id`] }"
                      >
                        <option value="">Выберите категорию</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                          {{ category.name }}
                        </option>
                      </select>
                      <div v-if="form.errors[`services.${index}.category_id`]" class="text-red-600 text-sm mt-1">
                        {{ form.errors[`services.${index}.category_id`] }}
                      </div>
                    </div>
                    
                    <!-- Название -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Название услуги *
                      </label>
                      <input 
                        v-model="service.name"
                        type="text"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :class="{ 'border-red-300': form.errors[`services.${index}.name`] }"
                        placeholder="Классический массаж"
                      >
                      <div v-if="form.errors[`services.${index}.name`]" class="text-red-600 text-sm mt-1">
                        {{ form.errors[`services.${index}.name`] }}
                      </div>
                    </div>
                    
                    <!-- Цена -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Цена (₽) *
                      </label>
                      <input 
                        v-model="service.price"
                        type="number"
                        min="100"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :class="{ 'border-red-300': form.errors[`services.${index}.price`] }"
                        placeholder="2000"
                      >
                      <div v-if="form.errors[`services.${index}.price`]" class="text-red-600 text-sm mt-1">
                        {{ form.errors[`services.${index}.price`] }}
                      </div>
                    </div>
                    
                    <!-- Длительность -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700 mb-2">
                        Длительность (мин) *
                      </label>
                      <input 
                        v-model="service.duration"
                        type="number"
                        min="15"
                        max="480"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        :class="{ 'border-red-300': form.errors[`services.${index}.duration`] }"
                        placeholder="60"
                      >
                      <div v-if="form.errors[`services.${index}.duration`]" class="text-red-600 text-sm mt-1">
                        {{ form.errors[`services.${index}.duration`] }}
                      </div>
                    </div>
                  </div>
                </div>
                
                <!-- Кнопка добавить услугу -->
                <button 
                  @click="addService"
                  type="button"
                  class="w-full py-2 px-4 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  + Добавить услугу
                </button>
              </div>
              <div v-if="form.errors.services" class="text-red-600 text-sm mt-2">
                {{ form.errors.services }}
              </div>
            </div>

            <!-- Кнопки -->
            <div class="flex justify-between items-center pt-6">
              <div class="flex gap-3">
                <Link 
                  href="/additem"
                  class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                >
                  Назад
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
                :disabled="form.processing || overallProgress < 70"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
              >
                {{ form.processing ? 'Создание...' : 'Создать анкету' }}
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
    @publish="handlePublishFromPreview"
  />
</template>

<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { Head, Link, usePage, useForm } from '@inertiajs/vue3'
import { useAutoSave } from '@/Composables/useAutoSave'
import { useFormProgress, massageFormSections } from '@/Composables/useFormProgress'
import { useToast } from '@/Composables/useToast'

// Компоненты
import AppLayout from '@/Layouts/AppLayout.vue'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import ContentCard from '@/Components/Layout/ContentCard.vue'
import PhotoUploader from '@/Components/Upload/PhotoUploader.vue'
import ProgressBar from '@/Components/Forms/ProgressBar.vue'
import PreviewModal from '@/Components/AddItem/PreviewModal.vue'

// Пропсы
const props = defineProps({
  categories: { type: Array, required: true },
  cities: { type: Array, required: true },
  breadcrumbs: { type: Array, required: true },
  counts: { type: Object, default: () => ({}) },
  userStats: { type: Object, default: () => ({}) }
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

// Проверка текущего роута
const isCurrentRoute = (routeName) => page.url.includes(routeName)

// Класс для пунктов меню
const menuItemClass = (isActive) => [
  'flex items-center justify-between px-3 py-2 text-sm rounded-lg transition',
  isActive ? 'bg-blue-50 text-blue-600 font-medium' : 'text-gray-700 hover:bg-gray-50'
]

// Форма
const form = useForm({
  display_name: '',
  description: '',
  age: '',
  experience_years: '',
  city: '',
  district: '',
  address: '',
  salon_name: '',
  phone: '',
  whatsapp: '',
  telegram: '',
  price_from: '',
  price_to: '',
  show_phone: false,
  photos: [],
  services: [{
    category_id: '',
    name: '',
    price: '',
    duration: ''
  }]
})

// Автосохранение
const { 
  lastSaved, 
  saving, 
  hasDraft, 
  restoreData, 
  clearData 
} = useAutoSave(form.data(), {
  key: 'massage_form_draft',
  interval: 30000,
  exclude: ['photos'] // фото не сохраняем в localStorage
})

// Прогресс формы
const { 
  overallProgress, 
  sectionsProgress,
  isFormReady,
  validateRequired 
} = useFormProgress(form.data(), massageFormSections)

// Методы для услуг
const addService = () => {
  form.services.push({
    category_id: '',
    name: '',
    price: '',
    duration: ''
  })
}

const removeService = (index) => {
  if (form.services.length > 1) {
    form.services.splice(index, 1)
  }
}

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

  if (overallProgress.value < 70) {
    showError('Заполните форму минимум на 70% для публикации')
    return
  }

  // Подготавливаем данные для отправки
  const formData = new FormData()
  
  // Обычные поля
  Object.keys(form.data()).forEach(key => {
    if (key === 'photos' || key === 'services') return
    
    const value = form[key]
    if (value !== null && value !== '') {
      formData.append(key, value)
    }
  })

  // Фотографии
  form.photos.forEach((photo, index) => {
    if (photo.file) {
      formData.append(`photos[${index}]`, photo.file)
      formData.append(`photos[${index}][is_main]`, photo.is_main ? '1' : '0')
    }
  })

  // Услуги
  form.services.forEach((service, index) => {
    Object.keys(service).forEach(field => {
      if (service[field]) {
        formData.append(`services[${index}][${field}]`, service[field])
      }
    })
  })

  // Отправляем
  form.post('/additem/massage', {
    data: formData,
    onSuccess: () => {
      clearData() // Очищаем черновик после успешной отправки
      showSuccess('Анкета успешно создана!')
    },
    onError: () => {
      showError('Ошибка при создании анкеты')
    }
  })
}

const handlePublishFromPreview = () => {
  showPreview.value = false
  submit()
}

// Предупреждение при закрытии
onBeforeUnmount(() => {
  // Автосохранение обработает это автоматически
})
</script>