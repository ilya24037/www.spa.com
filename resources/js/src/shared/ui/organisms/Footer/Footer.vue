<!-- Упрощенный Footer компонент -->
<template>
  <footer role="contentinfo" class="bg-gray-50 border-t border-gray-200 -mx-4 px-4 mt-8">
    <!-- Основной контент футера -->
    <div class="py-8">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
        <!-- Секции футера -->
        <div v-for="section in config?.sections || []" :key="section.title" class="space-y-3">
          <h3 class="font-semibold text-gray-900">{{ section.title }}</h3>
          <ul class="space-y-2">
            <li v-for="link in section.links" :key="link.text">
              <a 
                :href="link.href" 
                class="text-gray-600 hover:text-blue-600 transition-colors text-sm"
              >
                {{ link.text }}
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Нижняя часть футера -->
      <div class="pt-8 border-t border-gray-200">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
          <!-- Копирайт -->
          <div class="text-sm text-gray-600">
            © {{ config?.company?.year || new Date().getFullYear() }} {{ config?.company?.name || 'MASSAGIST' }}. {{ config?.company?.description || 'Все права защищены' }}
          </div>

          <!-- Социальные сети -->
          <div class="flex gap-4">
            <a
              v-for="(url, platform) in config?.social || {}" 
              :key="platform"
              :href="url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-gray-500 hover:text-blue-600 transition-colors"
              :aria-label="`Мы в ${platform}`"
            >
              <span class="capitalize">{{ platform }}</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import type { FooterConfig } from './model/footer.config'

interface Props {
  config?: FooterConfig
}

const props = withDefaults(defineProps<Props>(), {
  config: () => ({
    sections: [],
    company: {
      name: 'MASSAGIST',
      year: new Date().getFullYear(),
      description: 'Все права защищены'
    },
    social: {}
  })
})
</script>

<style scoped>
/* Стили футера */
footer {
  margin-top: auto;
}
</style>