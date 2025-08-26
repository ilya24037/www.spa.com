<template>
  <nav v-if="links && links.length > 3" class="flex items-center justify-between px-4 py-3 bg-white border-t border-gray-200 sm:px-6">
    <div class="flex justify-between flex-1 sm:hidden">
      <Link
        v-if="links[0].url"
        :href="links[0].url"
        class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
      >
        Предыдущая
      </Link>
      <Link
        v-if="links[links.length - 1].url"
        :href="links[links.length - 1].url"
        class="relative ml-3 inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
      >
        Следующая
      </Link>
    </div>
    
    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
      <div>
        <p class="text-sm text-gray-700">
          Показано <span class="font-medium">{{ from }}</span> до <span class="font-medium">{{ to }}</span> из <span class="font-medium">{{ total }}</span> результатов
        </p>
      </div>
      
      <div>
        <nav class="relative z-0 inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <Link
            v-for="(link, index) in links"
            :key="index"
            :href="link.url"
            :class="[
              'relative inline-flex items-center px-4 py-2 text-sm font-medium',
              link.active 
                ? 'bg-blue-50 border-blue-500 text-blue-600 z-10' 
                : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
              index === 0 ? 'rounded-l-md' : '',
              index === links.length - 1 ? 'rounded-r-md' : '',
              !link.url ? 'cursor-not-allowed opacity-50' : 'hover:bg-gray-50'
            ]"
            :aria-current="link.active ? 'page' : undefined"
            v-html="link.label"
          />
        </nav>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'

interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

interface Props {
  links: PaginationLink[]
  from?: number
  to?: number
  total?: number
}

const props = defineProps<Props>()

// Extract pagination info from links or props
const from = computed(() => props.from || 1)
const to = computed(() => props.to || props.links?.length || 0)
const total = computed(() => props.total || props.links?.length || 0)
</script>
