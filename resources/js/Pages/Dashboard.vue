<template>
    <Head title="Личный кабинет" />
    
    <div class="py-12">
        <div class="max-w-7xl mx-auto">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h1 class="text-2xl font-bold mb-6">Личный кабинет</h1>
                
                <div class="grid md:grid-cols-3 gap-6">
                    <!-- Карточка профиля -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h2 class="font-semibold mb-4">Мой профиль</h2>
                        <div class="space-y-2 text-sm">
                            <p>{{ $page.props.auth.user.name }}</p>
                            <p class="text-gray-600">{{ $page.props.auth.user.email }}</p>
                        </div>
                        <Link 
                            href="/profile" 
                            class="inline-block mt-4 text-blue-600 hover:text-blue-700"
                        >
                            Редактировать профиль →
                        </Link>
                    </div>
                    
                    <!-- Быстрые действия -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h2 class="font-semibold mb-4">Быстрые действия</h2>
                        <div class="space-y-3">
                            <Link 
                                href="/masters/create" 
                                class="block text-blue-600 hover:text-blue-700"
                            >
                                📝 Создать анкету мастера
                            </Link>
                            <Link 
                                href="/bookings" 
                                class="block text-blue-600 hover:text-blue-700"
                            >
                                📅 Мои бронирования
                            </Link>
                            <Link 
                                href="/favorites" 
                                class="block text-blue-600 hover:text-blue-700"
                            >
                                ❤️ Избранное
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Статистика -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h2 class="font-semibold mb-4">Статистика</h2>
                        <div class="space-y-2 text-sm">
                            <p>Просмотров анкеты: <span class="font-semibold">0</span></p>
                            <p>Бронирований: <span class="font-semibold">0</span></p>
                            <p>Отзывов: <span class="font-semibold">0</span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
// БЕЗ импорта AppLayout!
</script>