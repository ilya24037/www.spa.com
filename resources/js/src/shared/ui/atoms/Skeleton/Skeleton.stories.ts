/**
 * Storybook истории для Skeleton компонента
 */

import type { Meta, StoryObj } from '@storybook/vue3'
import Skeleton from './Skeleton.vue'
import SkeletonGroup from './SkeletonGroup.vue'
import type { SkeletonProps } from './Skeleton.types'

const meta: Meta<typeof Skeleton> = {
  title: 'Shared/UI/Atoms/Skeleton',
  component: Skeleton,
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Skeleton компонент для показа состояния загрузки контента. Поддерживает различные варианты и анимации.'
      }
    }
  },
  argTypes: {
    variant: {
      control: { type: 'select' },
      options: ['text', 'heading', 'paragraph', 'button', 'avatar', 'image', 'card', 'circular'],
      description: 'Тип скелетона'
    },
    size: {
      control: { type: 'select' },
      options: ['small', 'medium', 'large'],
      description: 'Размер скелетона'
    },
    animated: {
      control: { type: 'boolean' },
      description: 'Анимированный эффект'
    },
    rounded: {
      control: { type: 'boolean' },
      description: 'Закругленные углы'
    },
    width: {
      control: { type: 'text' },
      description: 'Кастомная ширина'
    },
    height: {
      control: { type: 'text' },
      description: 'Кастомная высота'
    }
  },
  args: {
    variant: 'text',
    size: 'medium',
    animated: true,
    rounded: false
  }
}

export default meta
type Story = StoryObj<typeof Skeleton>

// Базовый скелетон
export const Default: Story = {
  args: {
    variant: 'text'
  }
}

// Варианты скелетонов
export const Variants: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="space-y-6">
        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-2">Текстовые элементы</h3>
          <div class="space-y-2">
            <Skeleton variant="text" />
            <Skeleton variant="heading" />
            <Skeleton variant="paragraph" />
          </div>
        </div>
        
        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-2">UI элементы</h3>
          <div class="space-y-3">
            <Skeleton variant="button" />
            <Skeleton variant="avatar" />
            <Skeleton variant="circular" width="60px" height="60px" />
          </div>
        </div>
        
        <div>
          <h3 class="text-sm font-medium text-gray-700 mb-2">Контейнеры</h3>
          <div class="space-y-3">
            <Skeleton variant="image" />
            <Skeleton variant="card" />
          </div>
        </div>
      </div>
    `
  })
}

// Размеры
export const Sizes: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="space-y-4">
        <div>
          <h4 class="text-xs text-gray-500 mb-1">Small</h4>
          <Skeleton variant="text" size="small" />
          <Skeleton variant="button" size="small" />
          <Skeleton variant="avatar" size="small" />
        </div>
        
        <div>
          <h4 class="text-xs text-gray-500 mb-1">Medium</h4>
          <Skeleton variant="text" size="medium" />
          <Skeleton variant="button" size="medium" />
          <Skeleton variant="avatar" size="medium" />
        </div>
        
        <div>
          <h4 class="text-xs text-gray-500 mb-1">Large</h4>
          <Skeleton variant="text" size="large" />
          <Skeleton variant="button" size="large" />
          <Skeleton variant="avatar" size="large" />
        </div>
      </div>
    `
  })
}

// Кастомные размеры
export const CustomSizes: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="space-y-4">
        <div>
          <h4 class="text-xs text-gray-500 mb-2">Кастомная ширина</h4>
          <Skeleton variant="text" width="200px" />
          <Skeleton variant="text" width="75%" />
          <Skeleton variant="text" width="150px" />
        </div>
        
        <div>
          <h4 class="text-xs text-gray-500 mb-2">Кастомная высота</h4>
          <Skeleton variant="paragraph" height="60px" />
          <Skeleton variant="image" width="300px" height="150px" />
        </div>
        
        <div>
          <h4 class="text-xs text-gray-500 mb-2">Круглые элементы</h4>
          <div class="flex gap-3">
            <Skeleton variant="circular" width="30px" height="30px" />
            <Skeleton variant="circular" width="50px" height="50px" />
            <Skeleton variant="circular" width="80px" height="80px" />
          </div>
        </div>
      </div>
    `
  })
}

// Без анимации
export const WithoutAnimation: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="space-y-3">
        <h4 class="text-xs text-gray-500">Без анимации</h4>
        <Skeleton variant="text" :animated="false" />
        <Skeleton variant="heading" :animated="false" />
        <Skeleton variant="button" :animated="false" />
        <Skeleton variant="avatar" :animated="false" />
      </div>
    `
  })
}

// Закругленные углы
export const Rounded: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="space-y-3">
        <h4 class="text-xs text-gray-500 mb-2">Закругленные углы</h4>
        <Skeleton variant="text" :rounded="true" />
        <Skeleton variant="button" :rounded="true" />
        <Skeleton variant="image" :rounded="true" />
        <Skeleton variant="card" :rounded="true" />
      </div>
    `
  })
}

// Группы скелетонов
export const Groups: Story = {
  render: () => ({
    components: { SkeletonGroup },
    template: `
      <div class="space-y-6">
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Текстовый блок (3 строки)</h4>
          <SkeletonGroup :lines="3" variant="text" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Параграфы (5 строк)</h4>
          <SkeletonGroup :lines="5" variant="paragraph" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Без случайной ширины</h4>
          <SkeletonGroup :lines="4" variant="text" :random-width="false" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-3">Заголовки</h4>
          <SkeletonGroup :lines="2" variant="heading" />
        </div>
      </div>
    `
  })
}

// Карточка пользователя (пример использования)
export const UserCard: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="max-w-sm p-4 bg-white border border-gray-200 rounded-lg">
        <div class="flex items-start gap-4">
          <!-- Аватар -->
          <Skeleton variant="avatar" size="large" />
          
          <!-- Информация -->
          <div class="flex-1 space-y-2">
            <Skeleton variant="heading" size="medium" width="60%" />
            <Skeleton variant="text" size="small" width="80%" />
            <Skeleton variant="text" size="small" width="70%" />
          </div>
        </div>
        
        <!-- Кнопки действий -->
        <div class="mt-4 flex gap-2">
          <Skeleton variant="button" size="small" />
          <Skeleton variant="button" size="small" />
        </div>
      </div>
    `
  })
}

// Список статей
export const ArticleList: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="space-y-6">
        <!-- Статья 1 -->
        <div class="border-b border-gray-200 pb-6">
          <Skeleton variant="heading" size="large" width="75%" class="mb-3" />
          <Skeleton variant="image" height="200px" class="mb-3" />
          <div class="space-y-2">
            <Skeleton variant="paragraph" width="100%" />
            <Skeleton variant="paragraph" width="95%" />
            <Skeleton variant="paragraph" width="87%" />
            <Skeleton variant="paragraph" width="68%" />
          </div>
        </div>
        
        <!-- Статья 2 -->
        <div class="border-b border-gray-200 pb-6">
          <Skeleton variant="heading" size="large" width="68%" class="mb-3" />
          <Skeleton variant="image" height="200px" class="mb-3" />
          <div class="space-y-2">
            <Skeleton variant="paragraph" width="100%" />
            <Skeleton variant="paragraph" width="92%" />
            <Skeleton variant="paragraph" width="76%" />
          </div>
        </div>
        
        <!-- Статья 3 -->
        <div>
          <Skeleton variant="heading" size="large" width="82%" class="mb-3" />
          <Skeleton variant="image" height="200px" class="mb-3" />
          <div class="space-y-2">
            <Skeleton variant="paragraph" width="100%" />
            <Skeleton variant="paragraph" width="88%" />
            <Skeleton variant="paragraph" width="91%" />
            <Skeleton variant="paragraph" width="72%" />
          </div>
        </div>
      </div>
    `
  })
}

// Таблица
export const Table: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="overflow-hidden border border-gray-200 rounded-lg">
        <!-- Заголовок таблицы -->
        <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
          <div class="grid grid-cols-4 gap-4">
            <Skeleton variant="text" size="small" width="60%" />
            <Skeleton variant="text" size="small" width="70%" />
            <Skeleton variant="text" size="small" width="50%" />
            <Skeleton variant="text" size="small" width="40%" />
          </div>
        </div>
        
        <!-- Строки таблицы -->
        <div class="divide-y divide-gray-200">
          <div v-for="n in 5" :key="n" class="px-6 py-4">
            <div class="grid grid-cols-4 gap-4 items-center">
              <Skeleton variant="text" size="small" width="80%" />
              <Skeleton variant="text" size="small" width="60%" />
              <Skeleton variant="text" size="small" width="90%" />
              <Skeleton variant="button" size="small" width="80px" />
            </div>
          </div>
        </div>
      </div>
    `
  })
}

// Мобильный список
export const MobileList: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="max-w-sm mx-auto divide-y divide-gray-200">
        <div v-for="n in 4" :key="n" class="py-4 flex items-center gap-3">
          <Skeleton variant="circular" width="40px" height="40px" />
          <div class="flex-1 space-y-1">
            <Skeleton variant="text" size="medium" width="70%" />
            <Skeleton variant="text" size="small" width="50%" />
          </div>
          <Skeleton variant="circular" width="20px" height="20px" />
        </div>
      </div>
    `
  })
}

// Дашборд
export const Dashboard: Story = {
  render: () => ({
    components: { Skeleton },
    template: `
      <div class="space-y-6">
        <!-- Статистические карточки -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div v-for="n in 4" :key="n" class="p-4 bg-white border border-gray-200 rounded-lg">
            <Skeleton variant="text" size="small" width="60%" class="mb-2" />
            <Skeleton variant="heading" size="large" width="40%" />
          </div>
        </div>
        
        <!-- График -->
        <div class="p-6 bg-white border border-gray-200 rounded-lg">
          <Skeleton variant="heading" size="medium" width="30%" class="mb-4" />
          <Skeleton variant="image" height="300px" />
        </div>
        
        <!-- Таблица -->
        <div class="bg-white border border-gray-200 rounded-lg">
          <div class="p-6 border-b border-gray-200">
            <Skeleton variant="heading" size="medium" width="25%" />
          </div>
          <div class="p-6">
            <div class="space-y-3">
              <div v-for="n in 6" :key="n" class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <Skeleton variant="circular" width="32px" height="32px" />
                  <div class="space-y-1">
                    <Skeleton variant="text" size="medium" width="120px" />
                    <Skeleton variant="text" size="small" width="80px" />
                  </div>
                </div>
                <Skeleton variant="button" size="small" />
              </div>
            </div>
          </div>
        </div>
      </div>
    `
  })
}