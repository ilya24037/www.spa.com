<template>
  <!-- Компонент не рендерит ничего визуального -->
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  meta: {
    type: Object,
    default: () => ({})
  }
})

// Базовый title сайта
const baseTitle = 'СПА Маркетплейс'

// Вычисляемый title с fallback
const pageTitle = computed(() => {
  return props.meta.title ? `${props.meta.title} | ${baseTitle}` : baseTitle
})

// Описание с fallback
const pageDescription = computed(() => {
  return props.meta.description || 'Найдите лучших массажистов в вашем городе. Отзывы, рейтинги, онлайн-запись.'
})
</script>

<template>
  <Head>
    <!-- Основные meta теги -->
    <title>{{ pageTitle }}</title>
    <meta name="description" :content="pageDescription">
    <meta v-if="meta.keywords" name="keywords" :content="meta.keywords">
    
    <!-- Open Graph теги для соцсетей -->
    <meta property="og:title" :content="meta['og:title'] || pageTitle">
    <meta property="og:description" :content="meta['og:description'] || pageDescription">
    <meta v-if="meta['og:image']" property="og:image" :content="meta['og:image']">
    <meta v-if="meta['og:url']" property="og:url" :content="meta['og:url']">
    <meta property="og:type" :content="meta['og:type'] || 'website'">
    <meta property="og:site_name" :content="baseTitle">
    
    <!-- Twitter Card теги -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" :content="meta['og:title'] || pageTitle">
    <meta name="twitter:description" :content="meta['og:description'] || pageDescription">
    <meta v-if="meta['og:image']" name="twitter:image" :content="meta['og:image']">
    
    <!-- Дополнительные теги -->
    <link v-if="meta.canonical" rel="canonical" :href="meta.canonical">
  </Head>
</template>