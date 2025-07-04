<!-- resources/js/Components/MetaTags.vue -->
<template>
  <Head>
    <!-- ====== базовые теги ====== -->
    <title>{{ pageTitle }}</title>
    <meta name="description" :content="pageDescription" />
    <meta v-if="props.meta.keywords" name="keywords" :content="props.meta.keywords" />

    <!-- ====== Open Graph ====== -->
    <meta property="og:title"       :content="props.meta['og:title']       || pageTitle" />
    <meta property="og:description" :content="props.meta['og:description'] || pageDescription" />
    <meta v-if="props.meta['og:image']" property="og:image" :content="props.meta['og:image']" />
    <meta v-if="props.meta['og:url']"   property="og:url"   :content="props.meta['og:url']" />
    <meta property="og:type"      :content="props.meta['og:type'] || 'website'" />
    <meta property="og:site_name" :content="baseTitle" />

    <!-- ====== Twitter Card ====== -->
    <meta name="twitter:card"        content="summary_large_image" />
    <meta name="twitter:title"       :content="props.meta['og:title']       || pageTitle" />
    <meta name="twitter:description" :content="props.meta['og:description'] || pageDescription" />
    <meta v-if="props.meta['og:image']" name="twitter:image" :content="props.meta['og:image']" />

    <!-- ====== canonical ====== -->
    <link v-if="props.meta.canonical" rel="canonical" :href="props.meta.canonical" />
  </Head>
</template>

<script setup>
import { Head } from '@inertiajs/vue3'
import { computed } from 'vue'

/** Принимаем объект meta со страницы */
const props = defineProps({
  meta: {
    type: Object,
    default: () => ({}),
  },
})

/** Фиксированное название сайта */
const baseTitle = 'СПА Маркетплейс'

/** Заголовок страницы с fallback-ом на baseTitle */
const pageTitle = computed(() =>
  props.meta.title ? `${props.meta.title} | ${baseTitle}` : baseTitle,
)

/** Описание с дефолтным текстом */
const pageDescription = computed(
  () =>
    props.meta.description ||
    'Найдите лучших массажистов в вашем городе. Отзывы, рейтинги, онлайн-запись.',
)
</script>
