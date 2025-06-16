<!-- resources/js/Components/Header/CatalogDropdown.vue -->
<template>
  <div class="bg-white shadow-lg border-t">
    <div class="container mx-auto px-4 py-8">
      <div class="grid grid-cols-4 gap-8">
        <!-- Массаж -->
        <div>
          <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">💆‍♀️</span>
            Массаж
          </h3>
          <ul class="space-y-2">
            <li>
              <Link 
                href="/category/classic-massage"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Классический массаж
              </Link>
            </li>
            <li>
              <Link 
                href="/category/thai-massage"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Тайский массаж
              </Link>
            </li>
            <li>
              <Link 
                href="/category/sport-massage"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Спортивный массаж
              </Link>
            </li>
            <li>
              <Link 
                href="/category/medical-massage"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Лечебный массаж
              </Link>
            </li>
            <li>
              <Link 
                href="/category/anti-cellulite"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Антицеллюлитный
              </Link>
            </li>
            <li>
              <Link 
                href="/category/relax-massage"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Релакс массаж
              </Link>
            </li>
          </ul>
        </div>

        <!-- СПА процедуры -->
        <div>
          <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">🧖‍♀️</span>
            СПА процедуры
          </h3>
          <ul class="space-y-2">
            <li>
              <Link 
                href="/category/body-wrap"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Обёртывания
              </Link>
            </li>
            <li>
              <Link 
                href="/category/scrub"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Скрабирование
              </Link>
            </li>
            <li>
              <Link 
                href="/category/stone-therapy"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Стоун-терапия
              </Link>
            </li>
            <li>
              <Link 
                href="/category/aromatherapy"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Ароматерапия
              </Link>
            </li>
            <li>
              <Link 
                href="/category/bath-procedures"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Банные процедуры
              </Link>
            </li>
          </ul>
        </div>

        <!-- Косметология -->
        <div>
          <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">💅</span>
            Косметология
          </h3>
          <ul class="space-y-2">
            <li>
              <Link 
                href="/category/facial"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Уход за лицом
              </Link>
            </li>
            <li>
              <Link 
                href="/category/peeling"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Пилинги
              </Link>
            </li>
            <li>
              <Link 
                href="/category/masks"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Маски для лица
              </Link>
            </li>
            <li>
              <Link 
                href="/category/anti-age"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Anti-age процедуры
              </Link>
            </li>
            <li>
              <Link 
                href="/category/cleaning"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Чистка лица
              </Link>
            </li>
          </ul>
        </div>

        <!-- Дополнительные услуги -->
        <div>
          <h3 class="font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <span class="text-2xl">✨</span>
            Дополнительно
          </h3>
          <ul class="space-y-2">
            <li>
              <Link 
                href="/masters/at-home"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Выезд на дом
              </Link>
            </li>
            <li>
              <Link 
                href="/salons"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Салоны и студии
              </Link>
            </li>
            <li>
              <Link 
                href="/gift-certificates"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Подарочные сертификаты
              </Link>
            </li>
            <li>
              <Link 
                href="/promotions"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Акции и скидки
              </Link>
            </li>
            <li>
              <Link 
                href="/blog"
                class="text-gray-600 hover:text-blue-600 text-sm block py-1 transition"
                @click="$emit('close')"
              >
                Блог о здоровье
              </Link>
            </li>
          </ul>
        </div>
      </div>

      <!-- Популярные запросы -->
      <div class="mt-8 pt-6 border-t">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Популярные запросы:</h4>
        <div class="flex flex-wrap gap-2">
          <Link 
            v-for="tag in popularTags"
            :key="tag"
            :href="`/search?q=${encodeURIComponent(tag)}`"
            class="px-3 py-1 bg-gray-100 text-sm rounded-full hover:bg-gray-200 transition"
            @click="$emit('close')"
          >
            {{ tag }}
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'

defineEmits(['close'])

const popularTags = [
  'Массаж спины',
  'Расслабляющий массаж', 
  'Массаж в 4 руки',
  'Лимфодренаж',
  'Массаж для беременных',
  'Детский массаж',
  'Массаж лица',
  'Горячие камни'
]
</script>