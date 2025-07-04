<!-- resources/js/Pages/Masters/Show.vue -->
<template>
  <!-- 1. Общий макет приложения -->
  <AppLayout>
    <!-- 2. SEO-теги -->
    <Head :title="`Мастер ${master.full_name}`" />

    <!-- 3. Хлебные крошки -->
    <nav class="text-sm text-gray-500 mb-6">
      <Link class="hover:underline" href="/">Главная</Link>
      <span class="mx-2">/</span>
      <Link class="hover:underline" href="/masters">Мастера</Link>
      <span class="mx-2">/</span>
      <span class="font-medium text-gray-700">{{ master.full_name }}</span>
    </nav>

    <!-- 4. «Карточка» мастера (визуально как товар на Ozon) -->
    <section class="bg-white rounded-2xl shadow-sm p-6 md:flex gap-8">
      <!-- 4.1 Галерея -->
      <div class="shrink-0 w-full md:w-1/2">
        <img
          :src="master.avatar_large"
          :alt="`Фото ${master.full_name}`"
          class="w-full rounded-xl object-cover aspect-square"
        />
      </div>

      <!-- 4.2 Информация -->
      <div class="flex-1 mt-6 md:mt-0 flex flex-col">
        <!-- Имя + бейджи -->
        <h1 class="text-2xl font-semibold flex items-center gap-2">
          {{ master.full_name }}
          <Badge v-if="master.is_verified" label="Проверен" variant="success" />
          <Badge v-if="master.is_top" label="ТОП" variant="primary" />
        </h1>

        <!-- Рейтинг -->
        <div class="mt-2 flex items-center gap-2">
          <RatingStars :value="master.rating" />
          <span class="text-sm text-gray-500">({{ master.reviews_count }})</span>
        </div>

        <!-- Цена и кнопки -->
        <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center">
          <div class="text-3xl font-bold text-green-600">
            от {{ formatPrice(master.price_from) }} ₽
          </div>

          <button
            @click="openBooking"
            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white py-3 px-6 rounded-lg transition-colors"
          >
            Записаться
          </button>

          <button
            @click="toggleFavorite"
            :class="[
              'p-3 border rounded-lg transition-colors',
              isFavorite ? 'border-red-500 text-red-500' : 'border-gray-300 text-gray-400 hover:text-gray-600'
            ]"
          >
            <HeartIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Описание -->
        <p class="mt-6 text-gray-700 leading-relaxed">
          {{ master.description }}
        </p>

        <!-- Быстрые ссылки -->
        <ul class="mt-8 grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
          <li>
            <Link
              class="block bg-gray-50 hover:bg-gray-100 rounded-lg py-3 px-4 text-center"
              href="#services"
            >
              Услуги
            </Link>
          </li>
          <li>
            <Link
              class="block bg-gray-50 hover:bg-gray-100 rounded-lg py-3 px-4 text-center"
              href="#reviews"
            >
              Отзывы
            </Link>
          </li>
          <li>
            <Link
              class="block bg-gray-50 hover:bg-gray-100 rounded-lg py-3 px-4 text-center"
              href="#contacts"
            >
              Контакты
            </Link>
          </li>
        </ul>
      </div>
    </section>

    <!-- 5. Таб «Услуги» -->
    <section id="services" class="mt-10">
      <h2 class="text-xl font-semibold mb-4">Услуги и цены</h2>

      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="service in master.services"
          :key="service.id"
          class="border rounded-xl p-4 hover:shadow transition-shadow"
        >
          <div class="flex items-center justify-between">
            <span class="font-medium">{{ service.title }}</span>
            <span class="font-semibold text-green-600">
              {{ formatPrice(service.price) }} ₽
            </span>
          </div>
          <p class="mt-2 text-sm text-gray-500">{{ service.duration }} мин</p>
        </div>
      </div>
    </section>

    <!-- 6. Таб «Отзывы» -->
    <section id="reviews" class="mt-10">
      <h2 class="text-xl font-semibold mb-4">Отзывы клиентов</h2>
      <p v-if="!master.reviews_count" class="text-gray-500">Пока нет отзывов.</p>

      <div v-else class="space-y-6">
        <article
          v-for="review in master.reviews"
          :key="review.id"
          class="bg-white p-6 rounded-xl shadow-sm"
        >
          <div class="flex items-center gap-2 mb-2">
            <RatingStars :value="review.rating" />
            <span class="text-xs text-gray-500">{{ review.created_at_human }}</span>
          </div>
          <p class="text-gray-700">{{ review.comment }}</p>
        </article>
      </div>
    </section>
  </AppLayout>
</template>

<script setup>
/* -------------------------------- imports -------------------------------- */
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { HeartIcon } from 'lucide-vue-next'

import AppLayout    from '@/Layouts/AppLayout.vue'
import Badge        from '@/Components/Common/Badge.vue'
import RatingStars  from '@/Components/Common/RatingStars.vue'

/* ----------------------------- page props --------------------------------- */
const { props } = usePage()
const master = computed(() => props.master)

/* ----------------------------- helpers ----------------------------------- */
function formatPrice(value) {
  return Intl.NumberFormat('ru-RU').format(value)
}

/* ------------------------- избранное (Pinia) ----------------------------- */
import { useFavoriteStore } from '@/stores/favorites'
const store      = useFavoriteStore()
const isFavorite = computed(() => store.isFavorite(master.value.id))
const toggleFavorite = () => store.toggle(master.value.id)

/* --------------------------- бронирование -------------------------------- */
function openBooking() {
  // TODO: открыть модальное окно/маршрут бронирования
  // this.$inertia.visit(...)
}
</script>
