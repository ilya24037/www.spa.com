<template>
  <AppLayout>
    <!-- Hero секция с градиентом -->
    <section class="relative h-[600px] overflow-hidden">
      <!-- Фоновое изображение с градиентом -->
      <div class="absolute inset-0">
        <img 
          src="/images/hero-spa-bg.jpg" 
          alt="СПА услуги" 
          class="w-full h-full object-cover"
        >
        <div class="absolute inset-0 bg-gradient-to-r from-indigo-900/90 via-purple-900/80 to-transparent"></div>
      </div>
      
      <!-- Контент Hero -->
      <div class="relative max-w-7xl mx-auto px-4 h-full flex items-center">
        <div class="max-w-2xl">
          <h1 class="text-5xl md:text-6xl font-bold text-white mb-6">
            Найдите идеального 
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-pink-400 to-violet-400">
              мастера массажа
            </span>
          </h1>
          <p class="text-xl text-gray-200 mb-8">
            Профессиональные массажисты в вашем городе. 
            Более 5000 проверенных специалистов
          </p>
          
          <!-- Поисковая строка -->
          <div class="bg-white/10 backdrop-blur-md rounded-2xl p-2">
            <form class="flex gap-2">
              <div class="flex-1 relative">
                <input 
                  type="text" 
                  placeholder="Какой массаж вы ищете?"
                  class="w-full px-6 py-4 rounded-xl bg-white/90 backdrop-blur placeholder-gray-500 focus:outline-none focus:ring-4 focus:ring-purple-500/20"
                >
                <svg class="absolute left-4 top-4.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <button class="px-8 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all transform hover:scale-105">
                Найти мастера
              </button>
            </form>
          </div>
          
          <!-- Популярные запросы -->
          <div class="mt-4 flex flex-wrap gap-2">
            <span class="text-gray-300 text-sm">Популярное:</span>
            <button 
              v-for="tag in popularTags" 
              :key="tag"
              class="text-sm px-3 py-1 bg-white/10 backdrop-blur text-white rounded-full hover:bg-white/20 transition"
            >
              {{ tag }}
            </button>
          </div>
        </div>
      </div>
      
      <!-- Декоративные элементы -->
      <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 120" class="w-full h-24 text-gray-50">
          <path fill="currentColor" d="M0,64L48,58.7C96,53,192,43,288,48C384,53,480,75,576,80C672,85,768,75,864,58.7C960,43,1056,21,1152,21.3C1248,21,1344,43,1392,53.3L1440,64L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"></path>
        </svg>
      </div>
    </section>

    <!-- Категории услуг -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-bold text-gray-900 mb-4">
            Виды массажа
          </h2>
          <p class="text-gray-600">Выберите подходящую услугу</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
          <button 
            v-for="category in categories" 
            :key="category.id"
            class="group relative bg-white rounded-2xl p-6 hover:shadow-xl transition-all duration-300 hover:-translate-y-1"
          >
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/0 to-purple-500/0 group-hover:from-indigo-500/10 group-hover:to-purple-500/10 rounded-2xl transition-all duration-300"></div>
            <div class="relative">
              <div class="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-indigo-100 to-purple-100 rounded-full flex items-center justify-center group-hover:scale-110 transition-transform">
                <span class="text-2xl">{{ category.icon }}</span>
              </div>
              <h3 class="font-medium text-gray-900">{{ category.name }}</h3>
              <p class="text-sm text-gray-500 mt-1">от {{ category.minPrice }} ₽</p>
            </div>
          </button>
        </div>
      </div>
    </section>

    <!-- Популярные мастера -->
    <section class="py-16">
      <div class="max-w-7xl mx-auto px-4">
        <div class="flex items-center justify-between mb-12">
          <div>
            <h2 class="text-3xl font-bold text-gray-900 mb-2">
              Топ мастеров месяца
            </h2>
            <p class="text-gray-600">Самые востребованные специалисты</p>
          </div>
          <Link 
            href="/masters" 
            class="text-indigo-600 hover:text-indigo-700 font-medium flex items-center gap-2"
          >
            Все мастера
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </Link>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <MasterCard 
            v-for="master in topMasters" 
            :key="master.id" 
            :master="master"
            premium-style
          />
        </div>
      </div>
    </section>

    <!-- Преимущества -->
    <section class="py-16 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50">
      <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">
          Почему выбирают нас
        </h2>
        
        <div class="grid md:grid-cols-3 gap-8">
          <div 
            v-for="feature in features" 
            :key="feature.title"
            class="text-center group"
          >
            <div class="w-20 h-20 mx-auto mb-6 bg-white rounded-2xl shadow-lg flex items-center justify-center group-hover:shadow-xl transition-shadow">
              <div 
                class="w-12 h-12 rounded-xl flex items-center justify-center"
                :class="feature.bgColor"
              >
                <span class="text-2xl">{{ feature.icon }}</span>
              </div>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 mb-3">{{ feature.title }}</h3>
            <p class="text-gray-600">{{ feature.description }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Статистика -->
    <section class="py-16 bg-gray-900 text-white">
      <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
          <div v-for="stat in stats" :key="stat.label">
            <div class="text-4xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400 mb-2">
              {{ stat.value }}
            </div>
            <div class="text-gray-400">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA для мастеров -->
    <section class="py-16">
      <div class="max-w-4xl mx-auto px-4">
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-3xl p-12 text-white text-center relative overflow-hidden">
          <!-- Декоративные круги -->
          <div class="absolute -top-24 -right-24 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
          <div class="absolute -bottom-24 -left-24 w-48 h-48 bg-white/10 rounded-full blur-3xl"></div>
          
          <div class="relative">
            <h2 class="text-3xl font-bold mb-4">
              Вы мастер массажа?
            </h2>
            <p class="text-xl mb-8 text-indigo-100">
              Присоединяйтесь к нашей платформе и находите новых клиентов
            </p>
            <Link 
              href="/master/register" 
              class="inline-flex items-center px-8 py-4 bg-white text-indigo-600 rounded-xl font-semibold hover:bg-gray-100 transition-all transform hover:scale-105"
            >
              Начать работать
              <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
              </svg>
            </Link>
          </div>
        </div>
      </div>
    </section>

    <!-- Отзывы -->
    <section class="py-16 bg-gray-50">
      <div class="max-w-7xl mx-auto px-4">
        <h2 class="text-3xl font-bold text-center text-gray-900 mb-12">
          Отзывы клиентов
        </h2>
        
        <div class="grid md:grid-cols-3 gap-8">
          <div 
            v-for="review in reviews" 
            :key="review.id"
            class="bg-white rounded-2xl p-6 shadow-sm hover:shadow-lg transition-shadow"
          >
            <div class="flex items-center mb-4">
              <img 
                :src="review.avatar" 
                :alt="review.name"
                class="w-12 h-12 rounded-full object-cover mr-4"
              >
              <div>
                <h4 class="font-semibold text-gray-900">{{ review.name }}</h4>
                <div class="flex items-center">
                  <div class="flex text-yellow-400">
                    <span v-for="i in 5" :key="i">★</span>
                  </div>
                  <span class="text-sm text-gray-500 ml-2">{{ review.date }}</span>
                </div>
              </div>
            </div>
            <p class="text-gray-600">{{ review.text }}</p>
          </div>
        </div>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MasterCard from '@/Components/Masters/MasterCard.vue'

// Данные для главной страницы
const popularTags = [
  'Классический массаж',
  'Тайский массаж', 
  'Антицеллюлитный',
  'Релакс массаж'
]

const categories = [
  { id: 1, name: 'Классический', icon: '💆', minPrice: 2000 },
  { id: 2, name: 'Тайский', icon: '🧘', minPrice: 3000 },
  { id: 3, name: 'Спортивный', icon: '🏃', minPrice: 2500 },
  { id: 4, name: 'Лечебный', icon: '🏥', minPrice: 3500 },
  { id: 5, name: 'Релакс', icon: '🌸', minPrice: 2000 },
  { id: 6, name: 'Детский', icon: '👶', minPrice: 1500 }
]

const topMasters = [
  {
    id: 1,
    name: 'Анна Петрова',
    avatar: '/images/masters/anna.jpg',
    rating: 4.9,
    reviews_count: 234,
    price_from: 3000,
    services: ['Классический массаж', 'Релакс'],
    experience: 7,
    address: 'м. Арбатская'
  },
  // ... еще мастера
]

const features = [
  {
    icon: '✓',
    title: 'Проверенные мастера',
    description: 'Все специалисты проходят тщательную проверку документов',
    bgColor: 'bg-green-100'
  },
  {
    icon: '🛡️',
    title: 'Безопасные сделки',
    description: 'Оплата через платформу с гарантией возврата средств',
    bgColor: 'bg-blue-100'
  },
  {
    icon: '⭐',
    title: 'Реальные отзывы',
    description: 'Только проверенные отзывы от реальных клиентов',
    bgColor: 'bg-yellow-100'
  }
]

const stats = [
  { value: '5000+', label: 'Мастеров' },
  { value: '50 000+', label: 'Довольных клиентов' },
  { value: '4.8', label: 'Средний рейтинг' },
  { value: '24/7', label: 'Поддержка' }
]

const reviews = [
  {
    id: 1,
    name: 'Мария Иванова',
    avatar: '/images/clients/1.jpg',
    date: '2 дня назад',
    text: 'Отличный сервис! Нашла прекрасного мастера рядом с домом. Теперь хожу регулярно.'
  },
  // ... еще отзывы
]
</script>