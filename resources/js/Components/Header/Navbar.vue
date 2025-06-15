<template>
  <nav class="bg-white rounded-b-2xl">
    <div class="container mx-auto max-w-[1400px] px-4">
      <div class="flex items-center h-16 gap-4">
        <!-- Логотип -->
        <Link :href="route('home')" class="flex items-center">
          <div class="flex items-center gap-2">
            <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
              <span class="text-white font-bold text-lg">S</span>
            </div>
            <span class="font-bold text-xl hidden sm:block">SPA.COM</span>
          </div>
        </Link>

        <!-- Кнопка каталога -->
        <button 
          @click="toggleCatalog"
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
          <span class="hidden md:block">Каталог</span>
        </button>

        <!-- Поиск (основной элемент) -->
        <div class="flex-1 max-w-2xl">
          <div class="relative">
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="Найти массажиста или услугу"
              class="w-full px-4 py-2 pl-10 pr-20 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"
            >
            <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <button class="absolute right-1 top-1 px-4 py-1.5 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
              Найти
            </button>
          </div>
        </div>

        <!-- Правая часть -->
        <div class="flex items-center gap-3">
          <!-- Город -->
          <button class="flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            </svg>
            <span>Москва</span>
          </button>

          <!-- Избранное -->
          <Link :href="route('favorites.index')" class="relative p-2 hover:bg-gray-100 rounded-lg transition">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
            </svg>
            <span v-if="favoritesCount > 0" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
              {{ favoritesCount }}
            </span>
          </Link>

          <!-- Сравнение -->
          <Link :href="route('compare.index')" class="relative p-2 hover:bg-gray-100 rounded-lg transition">
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
          </Link>

          <!-- Войти/Профиль -->
          <div v-if="!user">
            <Link :href="route('login')" class="px-4 py-2 text-blue-600 hover:bg-blue-50 rounded-lg transition">
              Войти
            </Link>
          </div>
          <div v-else class="relative">
            <Dropdown align="right" width="48">
              <template #trigger>
                <button class="flex items-center gap-2 p-2 hover:bg-gray-100 rounded-lg transition">
                  <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium">{{ user.name.charAt(0).toUpperCase() }}</span>
                  </div>
                </button>
              </template>
              <template #content>
                <DropdownLink :href="route('profile.edit')">Профиль</DropdownLink>
                <DropdownLink :href="route('logout')" method="post" as="button">Выйти</DropdownLink>
              </template>
            </Dropdown>
          </div>

          <!-- Кнопка "Разместить объявление" -->
          <Link 
            :href="route('master.register')"
            class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition hidden lg:block"
          >
            Разместить объявление
          </Link>
        </div>
      </div>
    </div>
  </nav>
</template>