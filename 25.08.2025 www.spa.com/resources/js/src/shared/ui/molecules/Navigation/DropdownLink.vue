<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

// TypeScript интерфейс для props
interface DropdownLinkProps {
  href: string
}

defineProps<DropdownLinkProps>();
</script>

<template>
  <Link
    :href="href"
    class="block w-full px-4 py-2 text-start text-sm leading-5 text-gray-500 transition duration-150 ease-in-out hover:bg-gray-500 focus:bg-gray-500 focus:outline-none"
  >
    <slot />
  </Link>
</template>
