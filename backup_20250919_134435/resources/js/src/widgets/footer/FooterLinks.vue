<!-- resources/js/src/widgets/footer/FooterLinks.vue -->
<template>
  <div class="footer-links">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
      <!-- О компании -->
      <div>
        <h3 class="text-lg font-semibold text-gray-500 mb-4">
          О компании
        </h3>
        <ul class="space-y-2">
          <li>
            <Link href="/about" class="text-gray-500 hover:text-blue-600">
              О нас
            </Link>
          </li>
          <li>
            <Link href="/careers" class="text-gray-500 hover:text-blue-600">
              Вакансии
            </Link>
          </li>
          <li>
            <Link href="/press" class="text-gray-500 hover:text-blue-600">
              Пресс-центр
            </Link>
          </li>
        </ul>
      </div>
      
      <!-- Покупателям -->
      <div>
        <h3 class="text-lg font-semibold text-gray-500 mb-4">
          Покупателям
        </h3>
        <ul class="space-y-2">
          <li>
            <Link href="/how-it-works" class="text-gray-500 hover:text-blue-600">
              Как это работает
            </Link>
          </li>
          <li>
            <Link href="/safety" class="text-gray-500 hover:text-blue-600">
              Безопасность
            </Link>
          </li>
          <li>
            <Link href="/reviews" class="text-gray-500 hover:text-blue-600">
              Отзывы
            </Link>
          </li>
        </ul>
      </div>
      
      <!-- Мастерам -->
      <div>
        <h3 class="text-lg font-semibold text-gray-500 mb-4">
          Мастерам
        </h3>
        <ul class="space-y-2">
          <li>
            <Link href="/masters/register" class="text-gray-500 hover:text-blue-600">
              Стать мастером
            </Link>
          </li>
          <li>
            <Link href="/masters/earnings" class="text-gray-500 hover:text-blue-600">
              Заработок
            </Link>
          </li>
          <li>
            <Link href="/masters/support" class="text-gray-500 hover:text-blue-600">
              Поддержка мастеров
            </Link>
          </li>
        </ul>
      </div>
      
      <!-- Поддержка -->
      <div>
        <h3 class="text-lg font-semibold text-gray-500 mb-4">
          Поддержка
        </h3>
        <ul class="space-y-2">
          <li>
            <Link href="/help" class="text-gray-500 hover:text-blue-600">
              Помощь
            </Link>
          </li>
          <li>
            <Link href="/contact" class="text-gray-500 hover:text-blue-600">
              Контакты
            </Link>
          </li>
          <li>
            <Link href="/feedback" class="text-gray-500 hover:text-blue-600">
              Обратная связь
            </Link>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
</script> 