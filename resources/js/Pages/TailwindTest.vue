<template>
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12">
        <div class="spa-container">
            <!-- Заголовок с градиентом -->
            <h1 class="text-5xl font-bold text-center mb-12 bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                Tailwind CSS v4 Test
            </h1>

            <!-- Grid с карточками -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <!-- Карточка 1 -->
                <div class="spa-card hover:shadow-xl transition-shadow">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-3">Быстрая сборка</h2>
                    <p class="text-gray-600">Сборка в 5x быстрее благодаря новому движку</p>
                    <div class="mt-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            Оптимизировано
                        </span>
                    </div>
                </div>

                <!-- Карточка 2 -->
                <div class="spa-card hover:shadow-xl transition-shadow">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-3">CSS конфигурация</h2>
                    <p class="text-gray-600">Настройка темы через CSS вместо JS</p>
                    <div class="mt-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            Современно
                        </span>
                    </div>
                </div>

                <!-- Карточка 3 -->
                <div class="spa-card hover:shadow-xl transition-shadow">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-3">Автоопределение</h2>
                    <p class="text-gray-600">Автоматическое обнаружение файлов шаблонов</p>
                    <div class="mt-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                            Удобно
                        </span>
                    </div>
                </div>
            </div>

            <!-- Кнопки с кастомными стилями -->
            <div class="text-center space-y-4">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Кастомные утилиты</h3>
                <div class="flex flex-wrap gap-4 justify-center">
                    <button class="spa-button bg-blue-600 text-white hover:bg-blue-700">
                        Основная кнопка
                    </button>
                    <button class="spa-button bg-gray-200 text-gray-800 hover:bg-gray-300">
                        Вторичная кнопка
                    </button>
                    <button class="spa-button bg-green-600 text-white hover:bg-green-700">
                        Успешная кнопка
                    </button>
                    <button class="spa-button bg-red-600 text-white hover:bg-red-700" disabled>
                        Отключенная кнопка
                    </button>
                </div>
            </div>

            <!-- Адаптивная сетка -->
            <div class="mt-12">
                <h3 class="text-xl font-semibold text-gray-700 mb-4 text-center">Адаптивная сетка</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <div v-for="i in 12" :key="i"
                         :class="[
                             'aspect-square rounded-lg flex items-center justify-center font-bold text-white',
                             i % 3 === 1 ? 'bg-blue-500' :
                             i % 3 === 2 ? 'bg-indigo-500' :
                             'bg-purple-500'
                         ]">
                        {{ i }}
                    </div>
                </div>
            </div>

            <!-- Анимации -->
            <div class="mt-12">
                <h3 class="text-xl font-semibold text-gray-700 mb-4 text-center">Анимации</h3>
                <div class="flex justify-center space-x-8">
                    <div class="text-center">
                        <div class="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
                        <p class="mt-2 text-sm text-gray-600">spin</p>
                    </div>
                    <div class="text-center">
                        <div class="animate-pulse h-12 w-12 bg-blue-600 rounded-full mx-auto"></div>
                        <p class="mt-2 text-sm text-gray-600">pulse</p>
                    </div>
                    <div class="text-center">
                        <div class="animate-bounce h-12 w-12 bg-blue-600 rounded-full mx-auto"></div>
                        <p class="mt-2 text-sm text-gray-600">bounce</p>
                    </div>
                    <div class="text-center relative">
                        <div class="h-12 w-12 bg-blue-600 rounded-full mx-auto"></div>
                        <div class="animate-ping absolute inset-0 h-12 w-12 bg-blue-600 rounded-full opacity-75"></div>
                        <p class="mt-2 text-sm text-gray-600">ping</p>
                    </div>
                </div>
            </div>

            <!-- Проверка дизайн-токенов -->
            <div class="mt-12 p-6 bg-white rounded-lg shadow-lg">
                <h3 class="text-xl font-semibold text-gray-700 mb-4">Проверка дизайн-токенов</h3>
                <div class="space-y-2">
                    <p class="text-gray-600">Основной цвет: <span class="font-mono text-blue-600">var(--color-primary)</span></p>
                    <p class="text-gray-600">Шрифт: <span class="font-mono">Onest</span></p>
                    <p class="text-gray-600">Радиус: <span class="font-mono">var(--radius-lg)</span></p>
                    <p class="text-gray-600">Тень: <span class="font-mono">var(--shadow-md)</span></p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'

onMounted(() => {
    console.log('✅ Tailwind CSS v4 Test Page загружена')
    console.log('📦 Проверка классов Tailwind...')

    // Проверка наличия стилей Tailwind
    const testElement = document.createElement('div')
    testElement.className = 'hidden'
    document.body.appendChild(testElement)
    const styles = window.getComputedStyle(testElement)

    if (styles.display === 'none') {
        console.log('✅ Tailwind CSS работает корректно')
    } else {
        console.error('❌ Tailwind CSS не загружен')
    }

    document.body.removeChild(testElement)
})
</script>