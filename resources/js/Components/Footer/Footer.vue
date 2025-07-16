<template>
    <footer class="border-t">
        <!-- Верхняя секция с работой -->
        <div class="border-b border-gray-200 py-6">
            <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                <!-- Лого и текст -->
                <div class="flex items-center gap-4">
                    <div class="bg-blue-600 text-white font-bold text-xl px-4 py-2 rounded-lg">
                        SPA.COM
                    </div>
                    <div class="text-gray-600">
                        <p class="font-medium">Работа для мастеров</p>
                        <p class="text-sm">Присоединяйтесь к нашей платформе</p>
                    </div>
                </div>
                
                <!-- Быстрые ссылки на работу -->
                <div class="flex flex-wrap gap-3">
                    <a href="/additem" class="inline-flex items-center gap-2 text-sm text-gray-700 hover:text-blue-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span>Стать мастером</span>
                    </a>
                    <a href="/pricing" class="inline-flex items-center gap-2 text-sm text-gray-700 hover:text-blue-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Тарифы</span>
                    </a>
                    <a href="/masters/benefits" class="inline-flex items-center gap-2 text-sm text-gray-700 hover:text-blue-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                        <span>Преимущества</span>
                    </a>
                    <a href="/support" class="inline-flex items-center gap-2 text-sm text-gray-700 hover:text-blue-600 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                        </svg>
                        <span>Поддержка</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Основной контент футера -->
        <div class="py-8">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 mb-8">
                <!-- Колонка с QR и приложениями -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg p-4 text-center shadow-sm">
                        <!-- QR код -->
                        <img src="/images/qr-code.svg" alt="QR код" class="w-24 h-24 mx-auto mb-3">
                        <p class="text-sm text-gray-600 mb-4">
                            Наведите камеру и скачайте приложение
                        </p>
                        
                        <!-- Ссылки на сторы -->
                        <div class="space-y-2">
                            <a href="#" class="block">
                                <img src="/images/app-store-badge.svg" alt="App Store" class="h-10 mx-auto">
                            </a>
                            <a href="#" class="block">
                                <img src="/images/google-play-badge.svg" alt="Google Play" class="h-10 mx-auto">
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Основные колонки с ссылками -->
                <div class="lg:col-span-4 grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Колонка 1 - SPA.COM -->
                    <div>
                        <h3 class="font-semibold mb-4 text-gray-900">SPA.COM</h3>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/about" class="text-gray-600 hover:text-blue-600 transition">О платформе</a></li>
                            <li><a href="/how-it-works" class="text-gray-600 hover:text-blue-600 transition">Как это работает</a></li>
                            <li><a href="/safety" class="text-gray-600 hover:text-blue-600 transition">Безопасность</a></li>
                            <li><a href="/blog" class="text-gray-600 hover:text-blue-600 transition">Блог о массаже</a></li>
                            <li><a href="/reviews" class="text-gray-600 hover:text-blue-600 transition">Отзывы о сервисе</a></li>
                            <li><a href="/contacts" class="text-gray-600 hover:text-blue-600 transition">Контакты</a></li>
                        </ul>
                    </div>

                    <!-- Колонка 2 - Клиентам -->
                    <div>
                        <h3 class="font-semibold mb-4 text-gray-900">Клиентам</h3>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/search" class="text-gray-600 hover:text-blue-600 transition">Найти мастера</a></li>
                            <li><a href="/categories" class="text-gray-600 hover:text-blue-600 transition">Виды массажа</a></li>
                            <li><a href="/gift-certificates" class="text-gray-600 hover:text-blue-600 transition">Подарочные сертификаты</a></li>
                            <li><a href="/loyalty" class="text-gray-600 hover:text-blue-600 transition">Программа лояльности</a></li>
                            <li><a href="/faq" class="text-gray-600 hover:text-blue-600 transition">Частые вопросы</a></li>
                            <li><a href="/booking-help" class="text-gray-600 hover:text-blue-600 transition">Как забронировать</a></li>
                        </ul>
                    </div>

                    <!-- Колонка 3 - Мастерам -->
                    <div>
                        <h3 class="font-semibold mb-4 text-gray-900">Мастерам</h3>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/additem" class="text-gray-600 hover:text-blue-600 transition">Разместить анкету</a></li>
                            <li><a href="/pricing" class="text-gray-600 hover:text-blue-600 transition">Тарифы и цены</a></li>
                            <li><a href="/promotion" class="text-gray-600 hover:text-blue-600 transition">Продвижение анкеты</a></li>
                            <li><a href="/education" class="text-gray-600 hover:text-blue-600 transition">Обучение</a></li>
                            <li><a href="/masters/success" class="text-gray-600 hover:text-blue-600 transition">Истории успеха</a></li>
                            <li><a href="/rules" class="text-gray-600 hover:text-blue-600 transition">Правила платформы</a></li>
                        </ul>
                    </div>

                    <!-- Колонка 4 - Помощь -->
                    <div>
                        <h3 class="font-semibold mb-4 text-gray-900">Помощь</h3>
                        <ul class="space-y-2 text-sm">
                            <li><a href="/support" class="text-gray-600 hover:text-blue-600 transition">Служба поддержки</a></li>
                            <li><a href="/payment-methods" class="text-gray-600 hover:text-blue-600 transition">Способы оплаты</a></li>
                            <li><a href="/cancellation" class="text-gray-600 hover:text-blue-600 transition">Отмена и возврат</a></li>
                            <li><a href="/disputes" class="text-gray-600 hover:text-blue-600 transition">Решение споров</a></li>
                            <li><a href="/mobile-app" class="text-gray-600 hover:text-blue-600 transition">Мобильное приложение</a></li>
                            <li><a href="/api" class="text-gray-600 hover:text-blue-600 transition">API для бизнеса</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Нижняя часть с соцсетями и копирайтом -->
            <div class="border-t pt-6">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <!-- Социальные сети -->
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600">Мы в соцсетях:</span>
                        <div class="flex gap-2">
                            <a href="#" class="w-10 h-10 bg-white rounded-full flex items-center justify-center hover:bg-gray-100 transition border border-gray-200">
                                <svg class="w-5 h-5 text-[#0077FF]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.862-.523-2.049-1.71-1.033-1.033-1.49-1.173-1.744-1.173-.357 0-.458.102-.458.595v1.575c0 .424-.135.68-1.253.68-1.846 0-3.896-1.12-5.333-3.202C4.624 10.857 4 8.57 4 8.096c0-.254.102-.491.595-.491H6.34c.44 0 .61.203.78.677.863 2.49 2.303 4.675 2.896 4.675.22 0 .322-.102.322-.66V9.721c-.068-1.186-.695-1.287-.695-1.71 0-.204.17-.407.44-.407h2.744c.373 0 .508.203.508.643v3.473c0 .372.17.508.271.508.22 0 .407-.136.813-.542 1.27-1.422 2.18-3.61 2.18-3.61.119-.254.305-.491.729-.491h1.744c.525 0 .643.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.254.44 0 .78.186.254.796.779 1.203 1.253.745.847 1.32 1.558 1.473 2.05.17.49-.085.744-.576.744z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white rounded-full flex items-center justify-center hover:bg-gray-100 transition border border-gray-200">
                                <svg class="w-5 h-5 text-[#2AABEE]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
                                </svg>
                            </a>
                            <a href="#" class="w-10 h-10 bg-white rounded-full flex items-center justify-center hover:bg-gray-100 transition border border-gray-200">
                                <svg class="w-5 h-5 text-[#E4405F]" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zM5.838 12a6.162 6.162 0 1112.324 0 6.162 6.162 0 01-12.324 0zM12 16a4 4 0 110-8 4 4 0 010 8zm4.965-10.405a1.44 1.44 0 112.881.001 1.44 1.44 0 01-2.881-.001z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Копирайт и дополнительные ссылки -->
                    <div class="flex flex-col md:flex-row items-center gap-4 text-sm text-gray-600">
                        <div class="flex items-center gap-4">
                            <a href="/privacy" class="hover:text-gray-900 transition">Конфиденциальность</a>
                            <a href="/terms" class="hover:text-gray-900 transition">Условия использования</a>
                            <a href="/sitemap" class="hover:text-gray-900 transition">Карта сайта</a>
                        </div>
                        <div class="flex items-center gap-4">
                            <span>© {{ new Date().getFullYear() }} SPA.COM</span>
                            <button 
                                class="inline-flex items-center gap-2 bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-blue-700 transition"
                                @click="enableAccessibility"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/>
                                </svg>
                                <span>Для слабовидящих</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
const enableAccessibility = () => {
    // Функция для включения режима для слабовидящих
    console.log('Включение режима для слабовидящих')
}
</script>