<!-- resources/js/Components/Header/AuthBlock.vue -->
<template>
  <div v-if="!user" class="flex items-center">
    <!-- Для неавторизованных показываем только кнопку входа в кнопке "Разместить объявление" -->
    <!-- Основная кнопка входа будет в мобильном меню -->
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const user = computed(() => usePage().props.auth?.user)
</script>