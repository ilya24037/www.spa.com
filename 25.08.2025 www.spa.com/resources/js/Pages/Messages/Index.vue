<template>
  <Head title="Сообщения" />
    
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
              <Link 
                href="/profile/items/inactive/all"
                class="flex items-center justify-between px-3 py-2 text-sm rounded-md transition-colors text-gray-500 hover:bg-gray-500"
              >
                <span>Мои объявления</span>
              </Link>
            </div>
                        
            <div class="px-4 mt-2 space-y-1">
              <Link 
                href="/bookings"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
                Заказы
              </Link>
                            
              <Link 
                href="/profile/reviews"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
                Мои отзывы
              </Link>
                            
              <Link 
                href="/favorites"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
                Избранное
              </Link>
                            
              <Link 
                href="/messages"
                class="flex items-center px-3 py-2 text-sm bg-blue-50 text-blue-700 font-medium rounded-md transition-colors"
              >
                Сообщения
              </Link>
                            
              <Link 
                href="/notifications"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
                Уведомления
              </Link>
                            
              <Link 
                href="/wallet"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
                Кошелёк
              </Link>
                            
              <Link 
                href="/profile/addresses"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
                Адреса
              </Link>
                            
              <Link 
                href="/profile/edit"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
                Управление профилем
              </Link>
                            
              <Link 
                href="/profile/security"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
                Защита профиля
              </Link>
                            
              <Link 
                href="/settings"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
                Настройки
              </Link>
                            
              <Link 
                href="/services"
                class="flex items-center px-3 py-2 text-sm text-gray-500 hover:bg-gray-500 rounded-md transition-colors"
              >
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
                  d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                />
              </svg>
            </div>
            <h3 class="text-xl font-medium text-gray-500 mb-3">
              Сообщения
            </h3>
            <p class="text-gray-500 mb-8 leading-relaxed">
              Здесь будут отображаться ваши сообщения с другими пользователями.<br>
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