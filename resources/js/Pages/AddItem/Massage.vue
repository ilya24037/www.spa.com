<!-- Форма создания анкеты массажиста (/additem/massage) -->
<template>
        <Head title="Создать анкету массажиста" />
        
        <!-- Обертка с правильными отступами как в Dashboard -->
        <div class="py-6 lg:py-8">
            
            <!-- Основной контент с гэпом между блоками -->
            <div class="flex gap-6">
                
                <!-- Боковая панель через SidebarWrapper -->
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Например: Анна"
                                            
                                        >
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="25"
                                        >
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="5"
                                        >
                                    </div>
                                    
                                    <!-- Название салона -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Название салона
                                        </label>
                                        <input 
                                            v-model="form.salon_name"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Салон красоты 'Релакс'"
                                        >
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
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Профессиональный массажист с медицинским образованием. Специализируюсь на лечебном и расслабляющем массаже. Работаю с натуральными маслами..."
                                        
                                    ></textarea>
                                    <p class="text-xs text-gray-500 mt-1">Минимум 50 символов</p>
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            
                                        >
                                            <option value="">Выберите город</option>
                                            <option v-for="city in cities" :key="city" :value="city">
                                                {{ city }}
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <!-- Район -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Район
                                        </label>
                                        <input 
                                            v-model="form.district"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Центральный"
                                        >
                                    </div>
                                    
                                    <!-- Адрес -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Адрес
                                        </label>
                                        <input 
                                            v-model="form.address"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="ул. Примерная, д. 10"
                                        >
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="+7 (999) 123-45-67"
                                            
                                        >
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="+7 (999) 123-45-67"
                                        >
                                    </div>
                                    
                                    <!-- Telegram -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Telegram
                                        </label>
                                        <input 
                                            v-model="form.telegram"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="@username"
                                        >
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
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="2000"
                                            
                                        >
                                    </div>
                                    
                                    <!-- Цена до -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">
                                            Цена до (₽)
                                        </label>
                                        <input 
                                            v-model="form.price_to"
                                            type="number"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="5000"
                                        >
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
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    
                                                >
                                                    <option value="">Выберите категорию</option>
                                                    <option v-for="category in categories" :key="category.id" :value="category.id">
                                                        {{ category.name }}
                                                    </option>
                                                </select>
                                            </div>
                                            
                                            <!-- Название -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                                    Название услуги *
                                                </label>
                                                <input 
                                                    v-model="service.name"
                                                    type="text"
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Классический массаж"
                                                    
                                                >
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
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="2000"
                                                    
                                                >
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
                                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="60"
                                                    
                                                >
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
                            </div>

                            <!-- Кнопки -->
                            <div class="flex justify-end gap-4 pt-6">
                                <Link 
                                    href="/additem"
                                    class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                                >
                                    Назад
                                </Link>
                                <button 
                                    type="submit"
                                    :disabled="form.processing"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors"
                                >
                                    {{ form.processing ? 'Создание...' : 'Создать анкету' }}
                                </button>
                            </div>
                        </form>
                    </ContentCard>
                </main>
            </div>
        </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head, Link, usePage, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import SidebarWrapper from '@/Components/Layout/SidebarWrapper.vue'
import ContentCard from '@/Components/Layout/ContentCard.vue'

// Получаем данные от контроллера
const props = defineProps({
    categories: {
        type: Array,
        required: true  // ← ИСПРАВИТЬ
    },
    cities: {
        type: Array,
        required: true  // ← ИСПРАВИТЬ
    },
    breadcrumbs: {
        type: Array,
        required: true  // ← ИСПРАВИТЬ
    },
    counts: {
        type: Object,
        default: () => ({})
    },
    userStats: {
        type: Object,
        default: () => ({})
    }
})
// Состояние для мобильного меню
const showMobileSidebar = ref(false)

// Пользователь
const page = usePage()
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
const isCurrentRoute = (routeName) => {
    return page.url.includes(routeName)
}

// Класс для пунктов меню
const menuItemClass = (isActive) => [
    'flex items-center justify-between px-3 py-2 text-sm rounded-lg transition',
    isActive 
        ? 'bg-blue-50 text-blue-600 font-medium' 
        : 'text-gray-700 hover:bg-gray-50'
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
    services: [
        {
            category_id: '',
            name: '',
            price: '',
            duration: ''
        }
    ]
})

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

// Отправка формы
const submit = () => {
    alert('Кнопка нажата! Отправляем форму...') // Проверяем, что кнопка работает
    console.log('Данные формы:', form.data()) // Показываем данные в консоли
    form.post('/additem/massage') // Отправляем на сервер (БЕЗ route)
}
</script>