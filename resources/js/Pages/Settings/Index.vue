<template>
  <Head title="Настройки" />
    
  <div class="py-6 lg:py-8">
    <div class="flex gap-6">
      <!-- Боковая панель -->
      <SidebarWrapper 
        v-model="showSidebar"
        content-class="p-0"
        :show-desktop-header="false"
        :always-visible-desktop="true"
      >
        <!-- Профиль пользователя -->
        <div class="p-6 border-b">
          <div class="flex items-center space-x-3">
            <div 
              class="w-12 h-12 rounded-full flex items-center justify-center text-white font-medium text-lg"
              :style="{ backgroundColor: avatarColor }"
            >
              {{ userInitial }}
            </div>
            <div>
              <div class="font-medium text-gray-500">
                {{ userName }}
              </div>
              <div class="text-sm text-gray-500">
                ★ 4.2 • 5 отзывов
              </div>
            </div>
          </div>
        </div>
                
        <!-- Меню -->
        <nav class="flex-1">
          <div class="py-2">
            <div class="px-4">
              <Link href="/profile/items/inactive/all" class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors text-gray-500 hover:bg-gray-500">
                <span>Мои объявления</span>
              </Link>
            </div>
                        
            <div class="px-4 mt-2 space-y-1">
              <Link href="/bookings" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Заказы
              </Link>
              <Link href="/profile/reviews" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Мои отзывы
              </Link>
              <Link href="/favorites" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Избранное
              </Link>
              <Link href="/messages" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Сообщения
              </Link>
              <Link href="/notifications" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Уведомления
              </Link>
              <Link href="/wallet" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Кошелёк
              </Link>
              <Link href="/profile/addresses" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Адреса
              </Link>
              <Link href="/profile/edit" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Управление профилем
              </Link>
              <Link href="/profile/security" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Защита профиля
              </Link>
              <Link href="/settings" class="flex items-center px-3 py-2 text-sm bg-blue-50 text-blue-700 font-medium rounded-md transition-colors">
                Настройки
              </Link>
              <Link href="/services" class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors">
                Платные услуги
              </Link>
            </div>
          </div>
        </nav>
      </SidebarWrapper>
            
      <!-- Контент -->
      <section class="flex-1">
        <ContentCard>
          <div class="text-center py-16">
            <div class="w-20 h-20 mx-auto mb-6 bg-gray-500 rounded-full flex items-center justify-center">
              <svg
                class="w-10 h-10 text-gray-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                />
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
            </div>
            <h3 class="text-xl font-medium text-gray-500 mb-3">
              Настройки
            </h3>
            <p class="text-gray-500 mb-8 leading-relaxed">
              Здесь будут настройки вашего профиля, уведомлений и приватности.<br>
              Функционал находится в разработке.
            </p>
          </div>
        </ContentCard>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
// 🎯 FSD Импорты
import SidebarWrapper from '@/src/shared/ui/organisms/SidebarWrapper/SidebarWrapper.vue'
import ContentCard from '@/src/shared/ui/organisms/ContentCard/ContentCard.vue'

const showSidebar = ref(false)
const page = usePage()
const user = computed(() => page.props.auth?.user || { name: 'Пользователь' })

const userName = computed(() => user.value.name || 'Пользователь')
const userInitial = computed(() => userName.value.charAt(0).toUpperCase())
const avatarColor = computed(() => {
    const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899']
    const index = userName.value.charCodeAt(0) % colors.length
    return colors[index]
})
</script> 