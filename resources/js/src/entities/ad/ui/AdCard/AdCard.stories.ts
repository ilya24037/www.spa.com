// AdCard.stories.ts
import type { Meta, StoryObj } from '@storybook/vue3'
import AdCard from './AdCard.vue'
import type { AdCardProps, Ad, AdImage } from './AdCard.types'

const meta: Meta<typeof AdCard> = {
  title: 'Entities/Ad/AdCard',
  component: AdCard,
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Карточка объявления с галереей изображений, ценой, рейтингом и действиями. Компонент с TypeScript типизацией и интерактивной галереей.'
      }
    }
  },
  argTypes: {
    ad: {
      control: 'object',
      description: 'Данные объявления со всеми необходимыми полями'
    }
  },
  tags: ['autodocs']
}

export default meta
type Story = StoryObj<typeof AdCard>

// Mock данные
const mockImages: AdImage[] = [
  {
    id: 1,
    url: 'https://picsum.photos/400/300?random=1',
    thumb_url: 'https://picsum.photos/200/150?random=1',
    alt: 'Массажный кабинет'
  },
  {
    id: 2,
    url: 'https://picsum.photos/400/300?random=2',
    thumb_url: 'https://picsum.photos/200/150?random=2',
    alt: 'Рабочее место мастера'
  },
  {
    id: 3,
    url: 'https://picsum.photos/400/300?random=3',
    thumb_url: 'https://picsum.photos/200/150?random=3',
    alt: 'Атмосфера салона'
  },
  {
    id: 4,
    url: 'https://picsum.photos/400/300?random=4',
    thumb_url: 'https://picsum.photos/200/150?random=4',
    alt: 'Процедуры'
  }
]

const baseAd: Ad = {
  id: 1,
  title: 'Расслабляющий классический массаж',
  name: 'Профессиональный массаж',
  display_name: 'Анна - Массаж и релаксация',
  description: 'Профессиональный классический массаж для полного расслабления и восстановления. Опытный мастер с медицинским образованием.',
  specialty: 'Классический, спортивный и лечебный массаж',
  price: 5000,
  price_from: 4000,
  old_price: 6000,
  discount: 15,
  discount_percent: 15,
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
  images: mockImages
}

// Базовая карточка
export const Default: Story = {
  args: {
    ad: baseAd
  },
  parameters: {
    docs: {
      description: {
        story: 'Стандартная карточка объявления со всеми данными и галереей'
      }
    }
  }
}

// Карточка в избранном
export const Favorite: Story = {
  args: {
    ad: {
      ...baseAd,
      is_favorite: true
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка объявления, добавленного в избранное'
      }
    }
  }
}

// Карточка со скидкой
export const WithDiscount: Story = {
  args: {
    ad: {
      ...baseAd,
      discount: 25,
      discount_percent: 25,
      old_price: 8000,
      price: 6000
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка с акцией и скидкой 25%'
      }
    }
  }
}

// Обычное объявление (не премиум)
export const Regular: Story = {
  args: {
    ad: {
      ...baseAd,
      is_premium: false,
      is_verified: false,
      discount: undefined,
      discount_percent: undefined,
      old_price: undefined
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Обычная карточка без премиум статуса и скидок'
      }
    }
  }
}

// Новое объявление без рейтинга
export const NewAd: Story = {
  args: {
    ad: {
      ...baseAd,
      rating: undefined,
      reviews_count: 0,
      is_verified: false,
      is_premium: false
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Новое объявление без отзывов и рейтинга'
      }
    }
  }
}

// Объявление без изображений
export const NoImages: Story = {
  args: {
    ad: {
      ...baseAd,
      images: [],
      photos: []
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка без загруженных изображений (показывается placeholder)'
      }
    }
  }
}

// Одно изображение
export const SingleImage: Story = {
  args: {
    ad: {
      ...baseAd,
      images: [mockImages[0]]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка с одним изображением (без индикаторов галереи)'
      }
    }
  }
}

// Длинный заголовок
export const LongTitle: Story = {
  args: {
    ad: {
      ...baseAd,
      title: 'Профессиональный расслабляющий классический массаж всего тела с использованием натуральных масел и ароматерапии',
      description: 'Очень подробное описание услуги массажа с перечислением всех техник и преимуществ для здоровья и самочувствия клиента'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка с длинным заголовком и описанием (проверка line-clamp)'
      }
    }
  }
}

// Без контактов
export const HiddenContacts: Story = {
  args: {
    ad: {
      ...baseAd,
      show_contacts: false,
      phone: undefined
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка со скрытыми контактами мастера'
      }
    }
  }
}

// Высокая цена
export const ExpensiveService: Story = {
  args: {
    ad: {
      ...baseAd,
      price: 15000,
      price_from: 12000,
      title: 'VIP массаж премиум класса'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка дорогой премиум услуги'
      }
    }
  }
}

// Низкая цена
export const BudgetService: Story = {
  args: {
    ad: {
      ...baseAd,
      price: 2000,
      price_from: 2000,
      is_premium: false,
      title: 'Доступный массаж'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка бюджетной услуги'
      }
    }
  }
}

// Без локации
export const NoLocation: Story = {
  args: {
    ad: {
      ...baseAd,
      district: undefined,
      location: undefined,
      metro_station: undefined
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка без указания локации (показывается дефолт)'
      }
    }
  }
}

// Высокий рейтинг
export const HighRating: Story = {
  args: {
    ad: {
      ...baseAd,
      rating: 5.0,
      reviews_count: 500
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка популярной услуги с максимальным рейтингом'
      }
    }
  }
}

// Мобильная версия
export const Mobile: Story = {
  args: {
    ad: baseAd
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

// Интерактивная версия для тестирования
export const Interactive: Story = {
  args: {
    ad: baseAd
  },
  parameters: {
    docs: {
      description: {
        story: 'Интерактивная карточка для тестирования галереи и всех функций'
      }
    }
  },
  play: async ({ canvasElement, args }) => {
    console.log('AdCard Interactive story loaded')
    console.log('Ad data:', args.ad)
    
    // Находим элементы для демонстрации
    const card = canvasElement.querySelector('[data-testid="ad-card"]')
    const favoriteButton = canvasElement.querySelector('[data-testid="favorite-button"]')
    const contactButton = canvasElement.querySelector('[data-testid="contact-button"]')
    const bookingButton = canvasElement.querySelector('[data-testid="booking-button"]')
    const adImage = canvasElement.querySelector('[data-testid="ad-image"]')
    
    if (card && favoriteButton && contactButton && bookingButton && adImage) {
      console.log('All interactive elements found and ready for testing')
      console.log('Hover over the image to see gallery in action')
    }
  }
}

// Состояние ошибки изображения
export const ImageError: Story = {
  args: {
    ad: {
      ...baseAd,
      images: [
        { url: 'https://broken-image-url.jpg' },
        { url: 'https://another-broken-url.jpg' }
      ]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка с поломанными ссылками на изображения'
      }
    }
  }
}

// Максимальная скидка
export const BigDiscount: Story = {
  args: {
    ad: {
      ...baseAd,
      discount: 50,
      discount_percent: 50,
      old_price: 10000,
      price: 5000,
      title: 'Распродажа! Массаж со скидкой 50%'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка с большой скидкой 50%'
      }
    }
  }
}

// Смешанные форматы изображений
export const MixedImageFormats: Story = {
  args: {
    ad: {
      ...baseAd,
      images: [
        { url: 'https://picsum.photos/400/300?random=10' },
        { path: 'https://picsum.photos/400/300?random=11' },
        'https://picsum.photos/400/300?random=12' // string format
      ]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка с разными форматами данных изображений'
      }
    }
  }
}

// Сетка карточек для демонстрации
export const GridLayout: Story = {
  render: () => ({
    components: { AdCard },
    template: `
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <AdCard :ad="ads[0]" />
        <AdCard :ad="ads[1]" />
        <AdCard :ad="ads[2]" />
        <AdCard :ad="ads[3]" />
      </div>
    `,
    data() {
      return {
        ads: [
          baseAd,
          { ...baseAd, id: 2, title: 'Спортивный массаж', price: 6000, is_favorite: true, discount: undefined },
          { ...baseAd, id: 3, title: 'Антицеллюлитный массаж', price: 7000, is_premium: false, images: [mockImages[0]] },
          { ...baseAd, id: 4, title: 'Лечебный массаж', price: 8000, discount: 20, old_price: 10000 }
        ]
      }
    }
  }),
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация сетки карточек объявлений'
      }
    }
  }
}