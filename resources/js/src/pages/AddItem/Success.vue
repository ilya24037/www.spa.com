<template>
  <div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Карточка успеха -->
      <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <!-- Заголовок с иконкой -->
        <div class="text-center py-8 px-6 bg-gradient-to-b from-green-50 to-white">
          <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-4">
            <svg class="w-12 h-12 text-green-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
          </div>
          <h1 class="text-3xl font-bold text-gray-900 mb-2">
            Ваше объявление успешно добавлено!
          </h1>
          <p class="text-lg text-gray-600">
            Спасибо за размещение на нашем сайте
          </p>
        </div>

        <!-- Информация о модерации -->
        <div class="px-6 py-6 border-b border-gray-200">
          <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 mb-1">
                  Объявление проходит модерацию
                </h3>
                <div class="text-sm text-blue-700">
                  <p>
                    Объявление <strong>"{{ adTitle }}"</strong> добавлено на сайт.
                  </p>
                  <p class="mt-2">
                    Сейчас мы его проверяем. Обычно это занимает <strong>несколько минут</strong>,
                    но в некоторых случаях нам нужно больше времени на проверку.
                  </p>
                  <p class="mt-2">
                    Вы можете отредактировать или удалить своё объявление в
                    <Link href="/profile" class="font-medium underline hover:text-blue-900">
                      Личном кабинете
                    </Link>.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Блок продвижения -->
        <div class="px-6 py-6 bg-gradient-to-br from-purple-50 to-indigo-50">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="w-8 h-8 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
            <div class="ml-4 flex-1">
              <h3 class="text-lg font-semibold text-gray-900">
                Хотите получать больше откликов?
              </h3>
              <p class="mt-1 text-sm text-gray-600">
                Поднимите объявление в ТОП и получите в <strong>10 раз больше просмотров</strong>.
                Ваше объявление будет выделено и показано первым в результатах поиска.
              </p>

              <!-- Преимущества -->
              <div class="mt-4 space-y-2">
                <div class="flex items-center text-sm text-gray-700">
                  <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Приоритет в результатах поиска
                </div>
                <div class="flex items-center text-sm text-gray-700">
                  <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Выделение цветом в списке
                </div>
                <div class="flex items-center text-sm text-gray-700">
                  <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Больше места на странице
                </div>
              </div>

              <!-- Кнопки действий -->
              <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <button
                  @click="promoteAd"
                  class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-semibold rounded-lg shadow-lg hover:from-purple-700 hover:to-indigo-700 transition-all transform hover:scale-105"
                >
                  <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5 4a2 2 0 012-2h6a2 2 0 012 2v14l-5-2.5L5 18V4z"/>
                  </svg>
                  Поднять в ТОП за 500₽
                </button>
                <button
                  @click="skipPromotion"
                  class="inline-flex items-center justify-center px-6 py-3 bg-white text-gray-700 font-semibold rounded-lg border-2 border-gray-300 hover:bg-gray-50 transition"
                >
                  Пропустить
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Нижние действия -->
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
          <div class="flex justify-center">
            <Link
              href="/profile/items/active"
              class="inline-flex items-center px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition shadow-md"
            >
              <svg class="w-5 h-5 mr-2"
                   fill="none"
                   stroke="currentColor"
                   viewBox="0 0 24 24">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              Перейти в Личный кабинет
            </Link>
          </div>
        </div>
      </div>

      <!-- Дополнительная информация -->
      <div class="mt-8 text-center text-sm text-gray-500">
        <p>
          Есть вопросы? Обратитесь в
          <Link href="/support" class="text-blue-600 hover:underline">
            службу поддержки
          </Link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { computed } from 'vue'

// Props от backend
interface Props {
  ad?: {
    id: number
    title: string
    status: string
    is_published: boolean
  }
}

const props = withDefaults(defineProps<Props>(), {
  ad: undefined
})

// Вычисляемые свойства
const adTitle = computed(() => props.ad?.title || 'Ваше объявление')

// Методы для кнопок
const promoteAd = () => {
  if (!props.ad?.id) {
    console.warn('ID объявления не найден')
    return
  }

  // Переход на страницу оплаты продвижения
  router.post(`/ads/${props.ad.id}/promote`, {
    type: 'boost',
    duration: 7,
    price: 500
  })
}

const skipPromotion = () => {
  // Переход в личный кабинет
  router.visit('/profile/items/active')
}
</script>

<style scoped>
/* Анимация для иконки успеха */
@keyframes checkmark {
  0% {
    transform: scale(0) rotate(45deg);
  }
  50% {
    transform: scale(1.2) rotate(45deg);
  }
  100% {
    transform: scale(1) rotate(45deg);
  }
}

.bg-green-100 svg {
  animation: checkmark 0.5s ease-out;
}

/* Анимация появления блоков */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.bg-white > div {
  animation: fadeInUp 0.6s ease-out;
}

.bg-white > div:nth-child(2) {
  animation-delay: 0.1s;
}

.bg-white > div:nth-child(3) {
  animation-delay: 0.2s;
}

.bg-white > div:nth-child(4) {
  animation-delay: 0.3s;
}
</style>