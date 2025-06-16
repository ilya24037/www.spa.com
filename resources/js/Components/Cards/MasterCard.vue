<!-- resources/js/Components/Cards/MasterCard.vue -->
<template>
  <article class="relative rounded-lg bg-white p-3 shadow-sm hover:shadow-md transition">
    <!-- Избранное -->
    <button 
      @click.stop="toggleFavorite"
      class="absolute top-2 right-2 z-10"
    >
      <HeartIcon 
        :class="[
          'w-5 h-5',
          isFavorite ? 'fill-red-500 text-red-500' : 'text-gray-400 hover:text-red-500'
        ]"
      />
    </button>
    
    <!-- Бейджи -->
    <div class="absolute top-2 left-2 flex gap-1 z-10">
      <span 
        v-if="master.is_premium"
        class="bg-purple-600 text-white text-xs px-2 py-0.5 rounded"
      >
        PREMIUM
      </span>
      <span 
        v-if="master.is_verified"
        class="bg-green-500 text-white text-xs px-2 py-0.5 rounded"
      >
        Проверен
      </span>
    </div>
    
    <!-- Фото -->
    <img 
      :src="master.avatar"
      :alt="master.display_name"
      class="h-52 w-full object-cover rounded mb-3"
      @click="goToProfile"
    />
    
    <!-- Контент -->
    <div>
      <!-- Цена -->
      <div class="mb-2">
        <span class="font-bold text-xl">{{ master.price_from }} ₽</span>
        <span class="text-gray-500 text-sm">/час</span>
      </div>
      
      <!-- Имя -->
      <h3 class="font-medium mb-1">{{ master.display_name }}</h3>
      
      <!-- Рейтинг -->
      <div class="flex items-center gap-1 text-sm mb-2">
        <StarIcon class="w-4 h-4 text-yellow-400 fill-current" />
        <span>{{ master.rating }}</span>
        <span class="text-gray-400">({{ master.reviews_count }})</span>
      </div>
      
      <!-- Локация -->
      <p class="text-sm text-gray-600 mb-3">
        {{ master.district }}, м. {{ master.metro_station }}
      </p>
      
      <!-- Кнопки -->
      <div class="flex gap-2">
        <button 
          @click="openBooking"
          class="flex-1 bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition text-sm"
        >
          Записаться
        </button>
        <button 
          @click="showPhone"
          class="px-3 py-2 border rounded hover:bg-gray-50 transition"
        >
          <PhoneIcon class="w-4 h-4" />
        </button>
      </div>
    </div>
  </article>
</template>