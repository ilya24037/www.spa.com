/**
 * Storybook истории для Breadcrumbs компонента
 */

import type { Meta, StoryObj } from '@storybook/vue3'
import Breadcrumbs from './Breadcrumbs.vue'
import type { BreadcrumbItem } from './Breadcrumbs.types'

// Моковые данные
const basicItems: BreadcrumbItem[] = [
  { title: 'Главная', href: '/' },
  { title: 'Каталог', href: '/catalog' },
  { title: 'Смартфоны', href: '/catalog/smartphones' },
  { title: 'iPhone 15 Pro', href: '/catalog/smartphones/iphone-15-pro' }
]

const longItems: BreadcrumbItem[] = [
  { title: 'Главная', href: '/' },
  { title: 'Интернет-магазин', href: '/shop' },
  { title: 'Электроника', href: '/shop/electronics' },
  { title: 'Мобильные телефоны', href: '/shop/electronics/phones' },
  { title: 'Смартфоны', href: '/shop/electronics/phones/smartphones' },
  { title: 'Apple', href: '/shop/electronics/phones/smartphones/apple' },
  { title: 'iPhone', href: '/shop/electronics/phones/smartphones/apple/iphone' },
  { title: 'iPhone 15 Pro Max 256GB Титановый', href: '/shop/electronics/phones/smartphones/apple/iphone/15-pro-max' }
]

const meta: Meta<typeof Breadcrumbs> = {
  title: 'Shared/UI/Molecules/Breadcrumbs',
  component: Breadcrumbs,
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Breadcrumbs компонент для навигационной цепочки. Поддерживает различные варианты отображения, иконки, SEO-разметку.'
      }
    }
  },
  argTypes: {
    size: {
      control: { type: 'select' },
      options: ['small', 'medium', 'large'],
      description: 'Размер компонента'
    },
    separator: {
      control: { type: 'select' },
      options: ['chevron', 'slash', 'arrow'],
      description: 'Тип разделителя'
    },
    showIcons: {
      control: { type: 'boolean' },
      description: 'Показывать иконки элементов'
    },
    showHome: {
      control: { type: 'boolean' },
      description: 'Автоматически добавлять домашнюю страницу'
    },
    maxItems: {
      control: { type: 'number', min: 0, max: 10 },
      description: 'Максимальное количество элементов'
    },
    enableJsonLd: {
      control: { type: 'boolean' },
      description: 'Включить JSON-LD разметку для SEO'
    }
  },
  args: {
    items: basicItems,
    size: 'medium',
    separator: 'chevron',
    showIcons: false,
    showHome: false,
    maxItems: 0,
    enableJsonLd: false
  }
}

export default meta
type Story = StoryObj<typeof Breadcrumbs>

// Базовая история
export const Default: Story = {
  args: {
    items: basicItems
  }
}

// Размеры
export const Sizes: Story = {
  render: () => ({
    components: { Breadcrumbs },
    setup() {
      return { basicItems }
    },
    template: `
      <div class="space-y-4">
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Small</h4>
          <Breadcrumbs :items="basicItems" size="small" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Medium</h4>
          <Breadcrumbs :items="basicItems" size="medium" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Large</h4>
          <Breadcrumbs :items="basicItems" size="large" />
        </div>
      </div>
    `
  })
}

// Типы разделителей
export const Separators: Story = {
  render: () => ({
    components: { Breadcrumbs },
    setup() {
      return { basicItems }
    },
    template: `
      <div class="space-y-4">
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Chevron (по умолчанию)</h4>
          <Breadcrumbs :items="basicItems" separator="chevron" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Slash</h4>
          <Breadcrumbs :items="basicItems" separator="slash" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Arrow</h4>
          <Breadcrumbs :items="basicItems" separator="arrow" />
        </div>
      </div>
    `
  })
}

// С домашней страницей
export const WithHomePage: Story = {
  args: {
    items: basicItems.slice(1), // Убираем первый элемент
    showHome: true
  }
}

// Ограничение количества элементов
export const MaxItems: Story = {
  render: () => ({
    components: { Breadcrumbs },
    setup() {
      return { longItems }
    },
    template: `
      <div class="space-y-4">
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Без ограничений (8 элементов)</h4>
          <Breadcrumbs :items="longItems" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Максимум 5 элементов</h4>
          <Breadcrumbs :items="longItems" :max-items="5" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Максимум 3 элемента</h4>
          <Breadcrumbs :items="longItems" :max-items="3" />
        </div>
      </div>
    `
  })
}

// С иконками
export const WithIcons: Story = {
  render: () => ({
    components: { Breadcrumbs },
    setup() {
      const itemsWithIcons: BreadcrumbItem[] = [
        { 
          title: 'Главная', 
          href: '/', 
          icon: 'HomeIcon'
        },
        { 
          title: 'Каталог', 
          href: '/catalog',
          icon: 'CatalogIcon'
        },
        { 
          title: 'Смартфоны', 
          href: '/catalog/smartphones',
          icon: 'PhoneIcon'
        },
        { 
          title: 'iPhone 15 Pro', 
          href: '/catalog/smartphones/iphone-15-pro',
          icon: 'DeviceIcon'
        }
      ]
      
      return { itemsWithIcons }
    },
    template: `
      <Breadcrumbs :items="itemsWithIcons" :show-icons="true" />
    `,
    // Мокаем иконки для Storybook
    global: {
      stubs: {
        HomeIcon: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>',
        CatalogIcon: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/></svg>',
        PhoneIcon: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>',
        DeviceIcon: '<svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fillRule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clipRule="evenodd"/></svg>'
      }
    }
  })
}

// Внешние ссылки
export const ExternalLinks: Story = {
  args: {
    items: [
      { title: 'Главная', href: '/' },
      { title: 'Документация', href: 'https://docs.example.com', external: true },
      { title: 'API Reference', href: 'https://api.example.com/docs', external: true },
      { title: 'Текущая страница', href: '/current' }
    ]
  }
}

// Router Links (для Vue Router)
export const RouterLinks: Story = {
  render: () => ({
    components: { Breadcrumbs },
    setup() {
      const routerItems: BreadcrumbItem[] = [
        { title: 'Главная', to: { name: 'home' } },
        { title: 'Профиль', to: { name: 'profile' } },
        { title: 'Настройки', to: { name: 'settings' } },
        { title: 'Безопасность', to: { name: 'security' } }
      ]
      
      return { routerItems }
    },
    template: `
      <Breadcrumbs :items="routerItems" />
    `,
    global: {
      stubs: {
        'router-link': {
          template: '<a class="router-link" @click="handleClick"><slot /></a>',
          methods: {
            handleClick() {
              console.log('Router navigation:', this.to)
            }
          },
          props: ['to']
        }
      }
    }
  })
}

// Интерактивные breadcrumbs
export const Interactive: Story = {
  render: () => ({
    components: { Breadcrumbs },
    setup() {
      const handleItemClick = (event: any) => {
        alert(`Клик по: ${event.item.title} (индекс: ${event.index})`)
      }
      
      return { 
        basicItems,
        handleItemClick 
      }
    },
    template: `
      <div>
        <p class="text-sm text-gray-600 mb-4">
          Кликните по элементам навигации (кроме последнего)
        </p>
        <Breadcrumbs 
          :items="basicItems" 
          @item-click="handleItemClick"
        />
      </div>
    `
  })
}

// SEO-оптимизированные breadcrumbs
export const SEOOptimized: Story = {
  args: {
    items: basicItems,
    enableJsonLd: true,
    ariaLabel: 'Навигационная цепочка по сайту'
  },
  parameters: {
    docs: {
      description: {
        story: 'Breadcrumbs с включенной JSON-LD разметкой для улучшения SEO. Откройте DevTools и посмотрите на элемент <script type="application/ld+json">.'
      }
    }
  }
}

// Мобильная версия
export const Mobile: Story = {
  render: () => ({
    components: { Breadcrumbs },
    setup() {
      return { longItems }
    },
    template: `
      <div class="max-w-sm mx-auto border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-medium text-gray-700 mb-4">Мобильная версия</h4>
        <Breadcrumbs :items="longItems" :max-items="3" size="small" />
      </div>
    `
  })
}

// Кастомизация
export const Customization: Story = {
  render: () => ({
    components: { Breadcrumbs },
    setup() {
      return { basicItems }
    },
    template: `
      <div class="space-y-6">
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">С кастомным классом</h4>
          <Breadcrumbs 
            :items="basicItems" 
            custom-class="text-blue-600 bg-blue-50 p-2 rounded"
          />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Большой размер с домашней страницей</h4>
          <Breadcrumbs 
            :items="basicItems.slice(1)" 
            size="large"
            :show-home="true"
            separator="arrow"
          />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Компактная версия</h4>
          <Breadcrumbs 
            :items="longItems" 
            size="small"
            separator="slash"
            :max-items="4"
          />
        </div>
      </div>
    `
  })
}

// Состояния загрузки и ошибок
export const States: Story = {
  render: () => ({
    components: { Breadcrumbs },
    setup() {
      const loadingItems: BreadcrumbItem[] = [
        { title: 'Главная', href: '/' },
        { title: 'Загрузка...', href: '#' }
      ]
      
      const errorItems: BreadcrumbItem[] = [
        { title: 'Главная', href: '/' },
        { title: 'Ошибка загрузки', href: '#' }
      ]
      
      return { loadingItems, errorItems, basicItems }
    },
    template: `
      <div class="space-y-6">
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Обычное состояние</h4>
          <Breadcrumbs :items="basicItems" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Состояние загрузки</h4>
          <Breadcrumbs :items="loadingItems" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Состояние ошибки</h4>
          <Breadcrumbs :items="errorItems" />
        </div>
        
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">Пустое состояние</h4>
          <Breadcrumbs :items="[]" />
        </div>
      </div>
    `
  })
}

// Длинные заголовки
export const LongTitles: Story = {
  args: {
    items: [
      { title: 'Главная', href: '/' },
      { title: 'Очень длинный заголовок категории товаров', href: '/category' },
      { title: 'Еще более длинный заголовок подкategории с множеством слов', href: '/subcategory' },
      { title: 'Невероятно длинное название товара которое не помещается в одну строку и требует сокращения', href: '/product' }
    ],
    maxItems: 4
  }
}