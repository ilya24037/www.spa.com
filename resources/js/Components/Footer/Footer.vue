<template>
    <footer class="bg-gray-50 border-t mt-auto">
        <div class="px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <!-- Колонка 1 - О компании -->
                <div>
                    <h3 class="font-semibold mb-4 text-gray-900">О компании</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/about" class="text-gray-600 hover:text-gray-900 transition">О нас</a></li>
                        <li><a href="/contacts" class="text-gray-600 hover:text-gray-900 transition">Контакты</a></li>
                        <li><a href="/blog" class="text-gray-600 hover:text-gray-900 transition">Блог</a></li>
                        <li><a href="/reviews" class="text-gray-600 hover:text-gray-900 transition">Отзывы о сервисе</a></li>
                    </ul>
                </div>

                <!-- Колонка 2 - Клиентам -->
                <div>
                    <h3 class="font-semibold mb-4 text-gray-900">Клиентам</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/how-it-works" class="text-gray-600 hover:text-gray-900 transition">Как это работает</a></li>
                        <li><a href="/safety" class="text-gray-600 hover:text-gray-900 transition">Безопасность</a></li>
                        <li><a href="/faq" class="text-gray-600 hover:text-gray-900 transition">Частые вопросы</a></li>
                        <li><a href="/support" class="text-gray-600 hover:text-gray-900 transition">Поддержка</a></li>
                    </ul>
                </div>

                <!-- Колонка 3 - Мастерам -->
                <div>
                    <h3 class="font-semibold mb-4 text-gray-900">Мастерам</h3>
                    <ul class="space-y-2 text-sm">
                        <li><a href="/masters/create" class="text-gray-600 hover:text-gray-900 transition">Разместить анкету</a></li>
                        <li><a href="/pricing" class="text-gray-600 hover:text-gray-900 transition">Тарифы</a></li>
                        <li><a href="/promotion" class="text-gray-600 hover:text-gray-900 transition">Продвижение</a></li>
                        <li><a href="/rules" class="text-gray-600 hover:text-gray-900 transition">Правила</a></li>
                    </ul>
                </div>

                <!-- Колонка 4 - Приложения и соцсети -->
                <div>
                    <h3 class="font-semibold mb-4 text-gray-900">Мобильные приложения</h3>
                    <div class="flex flex-col gap-3 mb-6">
                        <a href="#" class="inline-block">
                            <img src="/images/app-store-badge.svg" alt="App Store" class="h-10">
                        </a>
                        <a href="#" class="inline-block">
                            <img src="/images/google-play-badge.svg" alt="Google Play" class="h-10">
                        </a>
                    </div>
                    
                    <h4 class="font-medium mb-3 text-gray-900">Мы в соцсетях</h4>
                    <div class="flex gap-3">
                        <a href="#" class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <!-- VK icon -->
                            </svg>
                        </a>
                        <a href="#" class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center hover:bg-gray-300 transition">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <!-- Telegram icon -->
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Нижняя часть -->
            <div class="border-t pt-6 flex flex-col md:flex-row justify-between items-center text-sm text-gray-600">
                <div>© {{ new Date().getFullYear() }} SPA.COM — Все права защищены</div>
                <div class="flex gap-6 mt-4 md:mt-0">
                    <a href="/privacy" class="hover:text-gray-900 transition">Конфиденциальность</a>
                    <a href="/terms" class="hover:text-gray-900 transition">Условия использования</a>
                </div>
            </div>
        </div>
    </footer>
</template>

<script setup>
// Без изменений
</script>