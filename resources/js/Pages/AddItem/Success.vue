<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'
import MainLayout from '@/src/shared/layouts/MainLayout/MainLayout.vue'
import ContentCard from '@/src/shared/layouts/components/ContentCard.vue'

// Типизация данных объявления
interface AdData {
  id: number
  title: string
  description: string
  status: string
  is_published: boolean
  moderated_at: string | null
  created_at: string
}

interface Props {
  ad: AdData
}

const props = defineProps<Props>()

// Вычисляемые свойства
const adTitle = computed(() => {
  return props.ad.title || 'Ваше объявление'
})

// Методы для действий
const promoteAd = () => {
  router.post(`/ads/${props.ad.id}/promote`, {
    type: 'boost',
    duration: 7,
    price: 500
  })
}

const goToProfile = () => {
  router.visit('/profile/items/active/all')
}
</script>

<template>
  <MainLayout>
    <div class="container mx-auto px-4 py-8 max-w-5xl">
      <!-- Основной заголовок -->
      <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 mb-3">
          Ваше объявление успешно добавлено на сайт
        </h1>
        <p class="text-gray-600">
          Объявление <Link :href="`/ads/${props.ad.id}`" class="text-blue-600 hover:underline">"{{ adTitle }}"</Link> отправлено на модерацию
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Левая колонка - основной контент -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Блок продвижения -->
          <ContentCard>
            <div class="p-6">
              <h2 class="text-xl font-semibold mb-4">Получайте больше просмотров</h2>
              <p class="text-gray-600 mb-6">
                Потенциальные клиенты увидят ваше объявление в поиске выше остальных похожих предложений
              </p>

              <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                  <div>
                    <div class="text-2xl font-bold mb-1">Поднять в поиске</div>
                    <div class="text-blue-100">на 7 дней</div>
                  </div>
                  <div class="text-right">
                    <div class="text-3xl font-bold">500 ₽</div>
                  </div>
                </div>
                <button
                  @click="promoteAd"
                  class="w-full py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-50 transition"
                >
                  Поднять просмотры
                </button>
              </div>

              <button
                @click="goToProfile"
                class="w-full mt-4 py-3 text-gray-600 hover:text-gray-800 transition"
              >
                Пропустить
              </button>
            </div>
          </ContentCard>

          <!-- Блок "Знак качества" -->
          <ContentCard>
            <div class="p-6">
              <h3 class="text-lg font-semibold mb-3">Получите «Знак качества»</h3>
              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                  <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                  </div>
                </div>
                <div class="flex-1">
                  <p class="text-gray-600 mb-3">
                    Подтвердите личность и получите специальный знак на объявлении.
                    Это повысит доверие клиентов.
                  </p>
                  <button class="text-blue-600 hover:underline font-medium">
                    Подтвердить личность →
                  </button>
                </div>
              </div>
            </div>
          </ContentCard>
        </div>

        <!-- Правая колонка - информация -->
        <div class="space-y-6">
          <!-- Статус модерации -->
          <ContentCard>
            <div class="p-6">
              <h3 class="font-semibold text-gray-900 mb-3">Статус объявления</h3>
              <div class="flex items-center space-x-2 text-yellow-600">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium">На модерации</span>
              </div>
              <p class="text-sm text-gray-600 mt-2">
                Обычно проверка занимает несколько минут.
                Мы отправим вам уведомление о результате.
              </p>
            </div>
          </ContentCard>

          <!-- Уведомления -->
          <ContentCard>
            <div class="p-6">
              <h3 class="font-semibold text-gray-900 mb-3">Уведомления</h3>
              <div class="space-y-3">
                <label class="flex items-start space-x-3">
                  <input type="checkbox" checked class="mt-1 rounded text-blue-600">
                  <div>
                    <div class="font-medium text-gray-700">SMS-уведомления</div>
                    <div class="text-sm text-gray-500">О новых сообщениях и звонках</div>
                  </div>
                </label>
                <label class="flex items-start space-x-3">
                  <input type="checkbox" checked class="mt-1 rounded text-blue-600">
                  <div>
                    <div class="font-medium text-gray-700">Email-уведомления</div>
                    <div class="text-sm text-gray-500">О статусе объявления</div>
                  </div>
                </label>
              </div>
            </div>
          </ContentCard>

          <!-- Полезные ссылки -->
          <ContentCard>
            <div class="p-6">
              <h3 class="font-semibold text-gray-900 mb-3">Управление</h3>
              <div class="space-y-2">
                <Link
                  :href="`/ads/${props.ad.id}/edit`"
                  class="block text-blue-600 hover:underline"
                >
                  Редактировать объявление
                </Link>
                <Link
                  href="/profile/items/active/all"
                  class="block text-blue-600 hover:underline"
                >
                  Мои объявления
                </Link>
                <Link
                  href="/profile"
                  class="block text-blue-600 hover:underline"
                >
                  Личный кабинет
                </Link>
              </div>
            </div>
          </ContentCard>
        </div>
      </div>
    </div>
  </MainLayout>
</template>