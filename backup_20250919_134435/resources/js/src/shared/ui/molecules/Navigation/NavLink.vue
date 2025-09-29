<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

// TypeScript РёРЅС‚РµСЂС„РµР№СЃ РґР»СЏ props
interface NavLinkProps {
  href: string
  active?: boolean
}

const props = withDefaults(defineProps<NavLinkProps>(), {
    active: false
});

const classes = computed((): string =>
    props.active
        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-400 text-sm font-medium leading-5 text-gray-500 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out'
        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-500 hover:border-gray-500 focus:outline-none focus:text-gray-500 focus:border-gray-500 transition duration-150 ease-in-out',
);
</script>

<template>
  <Link :href="href" :class="classes">
    <slot />
  </Link>
</template>

