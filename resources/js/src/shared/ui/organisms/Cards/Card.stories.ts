/**
 * Storybook истории для Card компонента
 */

import type { Meta, StoryObj } from '@storybook/vue3'
import Card from './Card.vue'
import type { CardProps } from './Card.types'

const meta: Meta<typeof Card> = {
  title: 'Shared/UI/Organisms/Card',
  component: Card,
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Универсальный Card компонент для отображения контента в карточках.'
      }
    }
  },
  argTypes: {
    variant: {
      control: { type: 'select' },
      options: ['default', 'bordered', 'elevated', 'outlined'],
      description: 'Вариант стиля карточки'
    },
    size: {
      control: { type: 'select' },
      options: ['small', 'medium', 'large'],
      description: 'Размер карточки'
    },
    hoverable: {
      control: { type: 'boolean' },
      description: 'Эффект при наведении'
    },
    loading: {
      control: { type: 'boolean' },  
      description: 'Состояние загрузки'
    },
    disabled: {
      control: { type: 'boolean' },
      description: 'Заблокированное состояние'
    }
  },
  args: {
    variant: 'default',
    size: 'medium',
    hoverable: false,
    loading: false,
    disabled: false
  }
}

export default meta
type Story = StoryObj<typeof Card>

// Базовая карточка
export const Default: Story = {
  args: {
    title: 'Заголовок карточки'
  },
  render: (args) => ({
    components: { Card },
    setup() {
      return { args }
    },
    template: `
      <Card v-bind="args">
        <p>Это содержимое карточки. Здесь может быть любой контент.</p>
      </Card>
    `
  })
}

// Варианты стилей
export const Variants: Story = {
  render: () => ({
    components: { Card },
    template: `
      <div class="grid grid-cols-2 gap-4">
        <Card variant="default" title="Default">
          <p>Обычная карточка с базовым стилем</p>
        </Card>
        
        <Card variant="bordered" title="Bordered">
          <p>Карточка с видимой рамкой</p>
        </Card>
        
        <Card variant="elevated" title="Elevated">
          <p>Карточка с тенью</p>
        </Card>
        
        <Card variant="outlined" title="Outlined">
          <p>Карточка с акцентной рамкой</p>
        </Card>
      </div>
    `
  })
}

// Размеры
export const Sizes: Story = {
  render: () => ({
    components: { Card },
    template: `
      <div class="space-y-4">
        <Card size="small" title="Маленькая карточка">
          <p>Компактная карточка с уменьшенными отступами</p>
        </Card>
        
        <Card size="medium" title="Средняя карточка">
          <p>Стандартная карточка со стандартными отступами</p>
        </Card>
        
        <Card size="large" title="Большая карточка">
          <p>Просторная карточка с увеличенными отступами</p>
        </Card>
      </div>
    `
  })
}

// Интерактивная карточка
export const Hoverable: Story = {
  args: {
    title: 'Интерактивная карточка',
    variant: 'elevated',
    hoverable: true
  },
  render: (args) => ({
    components: { Card },
    setup() {
      const handleClick = () => {
        alert('Карточка нажата!')
      }
      return { args, handleClick }
    },
    template: `
      <Card v-bind="args" @click="handleClick">
        <p>Наведите мышь на эту карточку и кликните по ней</p>
      </Card>
    `
  })
}

// Состояние загрузки
export const Loading: Story = {
  args: {
    title: 'Загружающаяся карточка',
    loading: true
  },
  render: (args) => ({
    components: { Card },
    setup() {
      return { args }
    },
    template: `
      <Card v-bind="args">
        <p>Этот контент скрыт под оверлеем загрузки</p>
      </Card>
    `
  })
}

// Заблокированная карточка
export const Disabled: Story = {
  args: {
    title: 'Заблокированная карточка',
    disabled: true,
    hoverable: true
  },
  render: (args) => ({
    components: { Card },
    setup() {
      const handleClick = () => {
        alert('Это не должно сработать!')
      }
      return { args, handleClick }
    },
    template: `
      <Card v-bind="args" @click="handleClick">
        <p>Эта карточка заблокирована и не реагирует на клики</p>
      </Card>
    `
  })
}

// С кастомными слотами
export const WithSlots: Story = {
  render: () => ({
    components: { Card },
    template: `
      <Card variant="bordered">
        <template #header>
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold">Кастомный заголовок</h3>
            <span class="text-sm text-gray-500">Статус</span>
          </div>
        </template>
        
        <div class="space-y-4">
          <p>Основной контент карточки с кастомными слотами.</p>
          <div class="flex gap-2">
            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
            <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
          </div>
        </div>
        
        <template #footer>
          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Обновлено: 5 мин назад</span>
            <div class="space-x-2">
              <button class="px-3 py-1 text-sm border rounded">Отмена</button>
              <button class="px-3 py-1 text-sm bg-blue-500 text-white rounded">Сохранить</button>
            </div>
          </div>
        </template>
      </Card>
    `
  })
}

// Карточка продукта (пример использования)
export const ProductCard: Story = {
  render: () => ({
    components: { Card },
    template: `
      <div class="max-w-sm">
        <Card variant="elevated" hoverable>
          <div class="space-y-4">
            <img 
              src="https://via.placeholder.com/300x200" 
              alt="Продукт" 
              class="w-full h-48 object-cover rounded"
            />
            
            <div>
              <h3 class="font-semibold text-lg">Название продукта</h3>
              <p class="text-gray-600 text-sm mt-1">Краткое описание продукта</p>
            </div>
            
            <div class="flex justify-between items-center">
              <span class="text-xl font-bold text-green-600">1 299 ₽</span>
              <button class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                В корзину
              </button>
            </div>
          </div>
        </Card>
      </div>
    `
  })
}

// Карточка статистики
export const StatsCard: Story = {
  render: () => ({
    components: { Card },
    template: `
      <div class="grid grid-cols-3 gap-4">
        <Card variant="bordered" size="small">
          <div class="text-center">
            <div class="text-2xl font-bold text-blue-600">1,234</div>
            <div class="text-sm text-gray-600">Пользователи</div>
          </div>
        </Card>
        
        <Card variant="bordered" size="small">
          <div class="text-center">
            <div class="text-2xl font-bold text-green-600">5,678</div>
            <div class="text-sm text-gray-600">Продажи</div>
          </div>
        </Card>
        
        <Card variant="bordered" size="small">
          <div class="text-center">
            <div class="text-2xl font-bold text-red-600">9.1%</div>
            <div class="text-sm text-gray-600">Конверсия</div>
          </div>
        </Card>
      </div>
    `
  })
}

// Без заголовка
export const WithoutHeader: Story = {
  render: () => ({
    components: { Card },
    template: `
      <Card variant="elevated">
        <div class="text-center space-y-4">
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          
          <div>
            <h3 class="text-lg font-semibold">Успешно!</h3>
            <p class="text-gray-600">Операция выполнена успешно</p>
          </div>
          
          <button class="px-4 py-2 bg-green-500 text-white rounded">
            Продолжить
          </button>
        </div>
      </Card>
    `
  })
}