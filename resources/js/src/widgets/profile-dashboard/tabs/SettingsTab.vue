<!-- resources/js/src/widgets/profile-dashboard/tabs/SettingsTab.vue -->
<template>
  <div :class="CONTAINER_CLASSES">
    <div :class="SETTINGS_GRID_CLASSES">
      <a
        v-for="setting in settings"
        :key="setting.key"
        :href="setting.href"
        :class="SETTING_LINK_CLASSES"
      >
        <component :is="setting.icon" :class="SETTING_ICON_CLASSES" />
        <div>
          <h4 :class="SETTING_TITLE_CLASSES">{{ setting.title }}</h4>
          <p :class="SETTING_DESCRIPTION_CLASSES">{{ setting.description }}</p>
        </div>
        <ChevronRightIcon :class="CHEVRON_ICON_CLASSES" />
      </a>
    </div>
  </div>
</template>

<script setup>
import { 
    UserIcon,
    LockClosedIcon,
    BellIcon,
    CreditCardIcon,
    ChevronRightIcon
} from '@heroicons/vue/outline'

// 🎯 Стили
const CONTAINER_CLASSES = 'space-y-6'
const SETTINGS_GRID_CLASSES = 'space-y-4'
const SETTING_LINK_CLASSES = 'flex items-center gap-4 p-4 bg-gray-500 hover:bg-gray-500 rounded-lg transition-colors'
const SETTING_ICON_CLASSES = 'w-10 h-10 text-gray-500 bg-white p-2 rounded-lg'
const SETTING_TITLE_CLASSES = 'font-medium text-gray-500'
const SETTING_DESCRIPTION_CLASSES = 'text-sm text-gray-500'
const CHEVRON_ICON_CLASSES = 'w-5 h-5 text-gray-500 ml-auto'

// Конфигурация настроек
const settings = [
    {
        key: 'profile',
        title: 'Профиль',
        description: 'Личная информация и контакты',
        icon: UserIcon,
        href: '/settings/profile'
    },
    {
        key: 'security',
        title: 'Безопасность',
        description: 'Пароль и настройки входа',
        icon: LockClosedIcon,
        href: '/settings/security'
    },
    {
        key: 'notifications',
        title: 'Уведомления',
        description: 'Email и push-уведомления',
        icon: BellIcon,
        href: '/settings/notifications'
    },
    {
        key: 'payments',
        title: 'Платежи',
        description: 'Способы оплаты и вывод средств',
        icon: CreditCardIcon,
        href: '/settings/payments'
    }
]
</script>