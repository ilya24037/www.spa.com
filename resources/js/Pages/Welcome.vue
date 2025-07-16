<script setup>
import { Head } from '@inertiajs/vue3';


defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});
</script>

<template>

        <Head title="SPA.COM - Платформа услуг массажа" />

        <!-- Главная страница -->
        <div>
            <!-- Hero секция -->
            <section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white py-20">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center">
                        <h1 class="text-4xl md:text-6xl font-bold mb-6">
                            Найдите лучшего мастера массажа
                        </h1>
                        <p class="text-xl md:text-2xl mb-8 text-blue-100">
                            Более 1000 проверенных специалистов в вашем городе
                        </p>
                        
                        <!-- Поиск -->
                        <div class="max-w-2xl mx-auto">
                            <div class="flex bg-white rounded-lg shadow-lg overflow-hidden">
                                <input 
                                    type="text" 
                                    placeholder="Найти мастера или услугу..."
                                    class="flex-1 px-6 py-4 text-gray-900 focus:outline-none"
                                >
                                <button class="bg-blue-600 text-white px-8 py-4 hover:bg-blue-700 transition-colors font-medium">
                                    Найти
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Категории услуг -->
            <section class="py-16">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">
                        Популярные услуги
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white rounded-lg shadow-sm p-6 text-center hover:shadow-md transition-shadow">
                            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Классический массаж</h3>
                            <p class="text-gray-600">Расслабляющий массаж всего тела</p>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-6 text-center hover:shadow-md transition-shadow">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">СПА процедуры</h3>
                            <p class="text-gray-600">Комплексные процедуры для красоты</p>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-6 text-center hover:shadow-md transition-shadow">
                            <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">На дому</h3>
                            <p class="text-gray-600">Выезд мастера к вам домой</p>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow-sm p-6 text-center hover:shadow-md transition-shadow">
                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Сертификаты</h3>
                            <p class="text-gray-600">Подарочные сертификаты</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Преимущества -->
            <section class="py-16 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">
                        Почему выбирают нас
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <div class="text-center">
                            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Проверенные мастера</h3>
                            <p class="text-gray-600">Все мастера проходят верификацию и имеют положительные отзывы</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Безопасная оплата</h3>
                            <p class="text-gray-600">Оплата только после оказания услуг через защищенную систему</p>
                        </div>
                        
                        <div class="text-center">
                            <div class="w-20 h-20 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-semibold text-gray-900 mb-4">Быстрое бронирование</h3>
                            <p class="text-gray-600">Забронируйте услугу за 2 минуты через удобный интерфейс</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA для мастеров -->
            <section class="py-16 bg-gray-900 text-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <h2 class="text-3xl font-bold mb-6">
                        Вы мастер массажа?
                    </h2>
                    <p class="text-xl text-gray-300 mb-8">
                        Присоединяйтесь к нашей платформе и начните зарабатывать уже сегодня
                    </p>
                    <a 
                        href="/register" 
                        class="inline-block bg-blue-600 text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-blue-700 transition-colors"
                    >
                        Стать мастером
                    </a>
                </div>
            </section>
        </div>

</template>
