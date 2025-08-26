<!-- resources/js/src/widgets/header/TopMenu.vue -->
<template>
  <nav class="top-menu">
    <ul class="flex items-center space-x-6 text-sm">
      <li>
        <Link href="/delivery" class="text-gray-500 hover:text-blue-600">
          Доставка
        </Link>
      </li>
      <li>
        <Link href="/payment" class="text-gray-500 hover:text-blue-600">
          Оплата
        </Link>
      </li>
      <li>
        <Link href="/guarantee" class="text-gray-500 hover:text-blue-600">
          Гарантии
        </Link>
      </li>
      <li>
        <Link href="/help" class="text-gray-500 hover:text-blue-600">
          Помощь
        </Link>
      </li>
    </ul>
  </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
</script> 