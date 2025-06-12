<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Навбар -->
        <Navbar />

        <!-- Основной контент -->
        <main>
            <slot />
        </main>

        <!-- Футер (опционально) -->
        <footer class="bg-gray-800 text-white py-8 mt-16">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- О компании -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">О нас</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white">О платформе</a></li>
                            <li><a href="#" class="hover:text-white">Как это работает</a></li>
                            <li><a href="#" class="hover:text-white">Безопасность</a></li>
                            <li><a href="#" class="hover:text-white">Отзывы</a></li>
                        </ul>
                    </div>

                    <!-- Для мастеров -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Мастерам</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white">Разместить объявление</a></li>
                            <li><a href="#" class="hover:text-white">Тарифы</a></li>
                            <li><a href="#" class="hover:text-white">Правила</a></li>
                            <li><a href="#" class="hover:text-white">Поддержка</a></li>
                        </ul>
                    </div>

                    <!-- Клиентам -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Клиентам</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="#" class="hover:text-white">Найти мастера</a></li>
                            <li><a href="#" class="hover:text-white">Категории услуг</a></li>
                            <li><a href="#" class="hover:text-white">Гарантии</a></li>
                            <li><a href="#" class="hover:text-white">FAQ</a></li>
                        </ul>
                    </div>

                    <!-- Контакты -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Контакты</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                +7 (495) 123-45-67
                            </li>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                info@spa.com
                            </li>
                        </ul>
                        
                        <!-- Социальные сети -->
                        <div class="flex space-x-4 mt-4">
                            <a href="#" class="text-gray-400 hover:text-white">
                                <span class="sr-only">ВКонтакте</span>
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M15.684 0H8.316C1.592 0 0 1.592 0 8.316v7.368C0 22.408 1.592 24 8.316 24h7.368C22.408 24 24 22.408 24 15.684V8.316C24 1.592 22.408 0 15.684 0zm3.692 17.123h-1.744c-.66 0-.862-.523-2.049-1.712-1.033-1.033-1.49-1.173-1.742-1.173-.356 0-.458.102-.458.597v1.56c0 .424-.135.682-1.253.682-1.846 0-3.896-1.12-5.339-3.202-2.17-3.04-2.764-5.323-2.764-5.798 0-.252.102-.49.596-.49h1.744c.44 0 .61.203.78.677.863 2.49 2.303 4.675 2.896 4.675.22 0 .322-.102.322-.66V9.72c-.068-1.186-.695-1.287-.695-1.71 0-.204.17-.407.44-.407h2.744c.373 0 .508.203.508.643v3.473c0 .372.17.508.271.508.22 0 .407-.136.813-.542 1.254-1.406 2.151-3.574 2.151-3.574.119-.254.322-.491.763-.491h1.744c.525 0 .644.27.525.643-.22 1.017-2.354 4.031-2.354 4.031-.186.305-.254.44 0 .78.186.254.796.779 1.203 1.253.745.847 1.32 1.56 1.473 2.05.17.49-.085.744-.576.744z"/>
                                </svg>
                            </a>
                            <a href="#" class="text-gray-400 hover:text-white">
                                <span class="sr-only">Telegram</span>
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400 text-sm">
                    <p>&copy; 2025 SPA.COM. Все права защищены.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import Navbar from '@/Components/Navbar.vue'
</script>