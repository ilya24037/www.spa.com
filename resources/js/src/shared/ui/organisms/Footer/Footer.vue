<template>
  <footer class="border-t bg-white" role="contentinfo">
    <!-- Верхняя секция с призывом к действию -->
    <div class="border-b border-gray-200 py-6">
      <div class="container mx-auto px-4">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
          <!-- Лого и описание компании -->
          <div class="flex items-center gap-4">
            <div class="bg-blue-600 text-white font-bold text-xl px-4 py-2 rounded-lg">
              {{ config.companyInfo.logo }}
            </div>
            <div class="text-gray-600">
              <p class="font-medium">{{ config.companyInfo.description }}</p>
              <p class="text-sm">Присоединяйтесь к нашей платформе</p>
            </div>
          </div>
          
          <!-- Быстрые действия -->
          <div class="flex flex-wrap gap-3">
            <Link
              v-for="action in config.quickActions"
              :key="action.id"
              :href="action.href"
              class="inline-flex items-center gap-2 text-sm text-gray-700 hover:text-blue-600 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md px-2 py-1"
              :aria-label="action.description || action.text"
            >
              <FooterIcon :name="action.icon || 'default'" class="w-5 h-5" />
              <span>{{ action.text }}</span>
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Основной контент футера -->
    <div class="py-8">
      <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8 mb-8">
          <!-- Колонка с QR кодом и приложениями -->
          <div class="lg:col-span-1">
            <AppDownload :app-stores="config.appStores" />
          </div>

          <!-- Основные колонки с навигацией -->
          <div class="lg:col-span-4 grid grid-cols-1 md:grid-cols-4 gap-6">
            <FooterSection
              v-for="section in sortedSections"
              :key="section.id"
              :section="section"
            />
          </div>
        </div>
        
        <!-- Нижняя часть с соцсетями и копирайтом -->
        <div class="border-t pt-6">
          <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <!-- Социальные сети -->
            <div class="flex items-center gap-4">
              <span class="text-sm text-gray-600">Мы в соцсетях:</span>
              <SocialIcons :social-links="config.socialLinks" />
            </div>

            <!-- Копирайт и дополнительные ссылки -->
            <div class="flex flex-col md:flex-row items-center gap-4 text-sm text-gray-600">
              <div class="flex items-center gap-4">
                <Link
                  v-for="link in config.legalLinks"
                  :key="link.id"
                  :href="link.href"
                  class="hover:text-gray-900 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md px-1"
                >
                  {{ link.text }}
                </Link>
              </div>
              <div class="flex items-center gap-4">
                <span>© {{ config.companyInfo.currentYear }} {{ config.companyInfo.name }}</span>
                <button
                  v-if="config.accessibility.enabled"
                  @click="handleAccessibilityToggle"
                  class="inline-flex items-center gap-2 bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm hover:bg-blue-700 focus:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                  :aria-label="`Переключить режим ${config.accessibility.buttonText.toLowerCase()}`"
                >
                  <FooterIcon name="accessibility" class="w-4 h-4" />
                  <span>{{ config.accessibility.buttonText }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import type { FooterConfig } from './model/footer.config'
import { getSortedFooterSections } from './model/footer.config'
import FooterSection from './components/FooterSection.vue'
import SocialIcons from './components/SocialIcons.vue'
import AppDownload from './components/AppDownload.vue'
import FooterIcon from './components/FooterIcon.vue'

interface Props {
  config: FooterConfig
}

const props = withDefaults(defineProps<Props>(), {})

const emit = defineEmits<{
  accessibilityToggle: []
}>()

// Отсортированные секции для правильного отображения
const sortedSections = computed(() => {
  return getSortedFooterSections(props.config.sections)
})

// Обработчик переключения режима доступности
const handleAccessibilityToggle = () => {
  if (props.config.accessibility.callback) {
    props.config.accessibility.callback()
  }
  emit('accessibilityToggle')
}
</script>

<style scoped>
.container {
  max-width: 1200px;
}

/* Анимации для hover эффектов */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Фокус для accessibility */
.focus\:ring-2:focus {
  --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color);
  --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color);
  box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000);
}

/* Адаптивность для мобильных */
@media (max-width: 640px) {
  .container {
    padding-left: 1rem;
    padding-right: 1rem;
  }
}
</style>

