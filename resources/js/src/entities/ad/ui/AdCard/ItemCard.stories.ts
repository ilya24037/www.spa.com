// ItemCard.stories.ts
import type { Meta, StoryObj } from '@storybook/vue3'
import ItemCard from './ItemCard.vue'
import type { ItemCardProps, Item, ItemImage } from './ItemCard.types'

const meta: Meta<typeof ItemCard> = {
  title: 'Entities/Ad/ItemCard',
  component: ItemCard,
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Карточка объявления для управления (Мои объявления). Компонент с действиями редактирования, удаления и продвижения.'
      }
    }
  },
  argTypes: {
    item: {
      control: 'object',
      description: 'Данные объявления со всеми полями и статусом'
    }
  },
  tags: ['autodocs']
}

export default meta
type Story = StoryObj<typeof ItemCard>

// Mock данные
const mockImages: ItemImage[] = [
  {
    id: 1,
    url: 'https://picsum.photos/160/200?random=1',
    thumb_url: 'https://picsum.photos/80/100?random=1',
    alt: 'Массажный кабинет'
  },
  {
    id: 2,
    url: 'https://picsum.photos/160/200?random=2', 
    thumb_url: 'https://picsum.photos/80/100?random=2',
    alt: 'Рабочее место'
  }
]

const baseItem: Item = {
  id: 1,
  title: 'Расслабляющий классический массаж',
  name: 'Профессиональный массаж',
  display_name: 'Анна - Массаж и релаксация',
  description: 'Профессиональный классический массаж для полного расслабления и восстановления сил.',
  price: 5000,
  price_from: 4000,
  status: 'active',
  rating: 4.8,
  reviews_count: 125,
  is_premium: true,
  is_verified: true,
  is_favorite: false,
  show_contacts: true,
  phone: '+7 (999) 123-45-67',
  district: 'Центральный',
  location: 'Москва, Центр',
  metro_station: 'Площадь Революции',
  images: mockImages,
  created_at: '2024-01-01T10:00:00Z',
  updated_at: '2024-01-02T15:30:00Z',
  expires_at: '2024-02-01T00:00:00Z',
  views_count: 156,
  favorites_count: 23
}

// Активное объявление
export const Active: Story = {
  args: {
    item: baseItem
  },
  parameters: {
    docs: {
      description: {
        story: 'Активное объявление со всеми доступными действиями'
      }
    }
  }
}

// Черновик
export const Draft: Story = {
  args: {
    item: {
      ...baseItem,
      status: 'draft',
      views_count: 0,
      favorites_count: 0
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Черновик объявления - можно редактировать и удалять'
      }
    }
  }
}

// Архивное объявление
export const Archived: Story = {
  args: {
    item: {
      ...baseItem,
      status: 'archived'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Архивное объявление - можно восстановить или удалить'
      }
    }
  }
}

// На модерации
export const Pending: Story = {
  args: {
    item: {
      ...baseItem,
      status: 'pending',
      views_count: 0,
      favorites_count: 0
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Объявление на модерации - ограниченные действия'
      }
    }
  }
}

// Отклоненное
export const Rejected: Story = {
  args: {
    item: {
      ...baseItem,
      status: 'rejected',
      views_count: 0,
      favorites_count: 0
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Отклоненное объявление - требует исправлений'
      }
    }
  }
}

// Без изображений
export const NoImages: Story = {
  args: {
    item: {
      ...baseItem,
      images: [],
      photos: []
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Объявление без загруженных изображений'
      }
    }
  }
}

// Одно изображение
export const SingleImage: Story = {
  args: {
    item: {
      ...baseItem,
      images: [mockImages[0]]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Объявление с одним изображением'
      }
    }
  }
}

// Без премиум статуса
export const Regular: Story = {
  args: {
    item: {
      ...baseItem,
      is_premium: false,
      is_verified: false
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Обычное объявление без премиум статуса и верификации'
      }
    }
  }
}

// Популярное объявление
export const Popular: Story = {
  args: {
    item: {
      ...baseItem,
      views_count: 1250,
      favorites_count: 89,
      rating: 5.0,
      reviews_count: 456
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Популярное объявление с высокими показателями'
      }
    }
  }
}

// Новое объявление
export const Fresh: Story = {
  args: {
    item: {
      ...baseItem,
      views_count: 12,
      favorites_count: 2,
      rating: undefined,
      reviews_count: 0,
      created_at: new Date().toISOString()
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Недавно созданное объявление с минимальной активностью'
      }
    }
  }
}

// Длинный заголовок
export const LongTitle: Story = {
  args: {
    item: {
      ...baseItem,
      title: 'Профессиональный расслабляющий классический массаж всего тела с использованием натуральных масел и ароматерапии в комфортных условиях',
      description: 'Очень подробное описание услуги массажа с перечислением всех техник, преимуществ для здоровья, используемых материалов и инструментов, а также дополнительных услуг которые могут быть предоставлены клиенту'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Объявление с длинным заголовком и описанием'
      }
    }
  }
}

// Высокая цена
export const Expensive: Story = {
  args: {
    item: {
      ...baseItem,
      price: 15000,
      price_from: 12000,
      title: 'VIP массаж премиум класса'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Дорогое премиум объявление'
      }
    }
  }
}

// Минимальные данные
export const Minimal: Story = {
  args: {
    item: {
      id: 999,
      status: 'active',
      title: 'Массаж'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Объявление с минимальным набором данных'
      }
    }
  }
}

// Скоро истекает
export const ExpiringSoon: Story = {
  args: {
    item: {
      ...baseItem,
      expires_at: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000).toISOString() // через 2 дня
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Объявление, которое скоро истекает'
      }
    }
  }
}

// Интерактивная версия для тестирования
export const Interactive: Story = {
  args: {
    item: baseItem
  },
  parameters: {
    docs: {
      description: {
        story: 'Интерактивная карточка для тестирования всех действий'
      }
    }
  },
  play: async ({ canvasElement, args }) => {
    console.log('ItemCard Interactive story loaded')
    console.log('Item data:', args.item)
    
    // Находим элементы для демонстрации
    const card = canvasElement.querySelector('[data-testid="item-card"]')
    const imageLink = canvasElement.querySelector('[data-testid="item-image-link"]')
    const contentLink = canvasElement.querySelector('[data-testid="item-content-link"]')
    const actionsContainer = canvasElement.querySelector('[data-testid="item-actions"]')
    
    if (card && imageLink && contentLink && actionsContainer) {
      console.log('All interactive elements found and ready for testing')
      console.log('Available actions: pay, promote, edit, deactivate, delete')
    }
  }
}

// Состояние с модальным окном
export const WithDeleteModal: Story = {
  args: {
    item: baseItem
  },
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация модального окна подтверждения удаления'
      }
    }
  },
  play: async ({ canvasElement }) => {
    // Симулируем открытие модального окна
    setTimeout(() => {
      const deleteButton = canvasElement.querySelector('[data-testid="delete-button"]')
      if (deleteButton) {
        (deleteButton as HTMLElement).click()
      }
    }, 1000)
  }
}

// Мобильная версия
export const Mobile: Story = {
  args: {
    item: baseItem
  },
  parameters: {
    viewport: {
      defaultViewport: 'mobile1'
    },
    docs: {
      description: {
        story: 'Карточка объявления в мобильной версии'
      }
    }
  }
}

// Сетка карточек (для демонстрации списка)
export const GridLayout: Story = {
  render: () => ({
    components: { ItemCard },
    template: `
      <div class="space-y-4">
        <ItemCard :item="items[0]" />
        <ItemCard :item="items[1]" />
        <ItemCard :item="items[2]" />
        <ItemCard :item="items[3]" />
      </div>
    `,
    data() {
      return {
        items: [
          { ...baseItem, id: 1, status: 'active' },
          { ...baseItem, id: 2, status: 'draft', title: 'Черновик массажа' },
          { ...baseItem, id: 3, status: 'archived', title: 'Архивный массаж' },
          { ...baseItem, id: 4, status: 'pending', title: 'На модерации' }
        ]
      }
    }
  }),
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация списка карточек с разными статусами'
      }
    }
  }
}

// Различные статусы в одном списке
export const AllStatuses: Story = {
  render: () => ({
    components: { ItemCard },
    template: `
      <div class="grid grid-cols-1 gap-4">
        <div class="text-lg font-semibold mb-2">Все статусы объявлений:</div>
        <ItemCard v-for="item in items" :key="item.id" :item="item" />
      </div>
    `,
    data() {
      return {
        items: [
          { ...baseItem, id: 1, status: 'active', title: 'Активное объявление' },
          { ...baseItem, id: 2, status: 'draft', title: 'Черновик объявления' },
          { ...baseItem, id: 3, status: 'archived', title: 'Архивное объявление' },
          { ...baseItem, id: 4, status: 'pending', title: 'На модерации' },
          { ...baseItem, id: 5, status: 'rejected', title: 'Отклоненное объявление' }
        ]
      }
    }
  }),
  parameters: {
    docs: {
      description: {
        story: 'Сравнение всех возможных статусов объявлений'
      }
    }
  }
}