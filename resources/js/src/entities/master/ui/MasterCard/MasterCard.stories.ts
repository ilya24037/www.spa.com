// MasterCard.stories.ts
import type { Meta, StoryObj } from '@storybook/vue3'
import MasterCard from './MasterCard.vue'
import type { MasterCardProps, Master, Service } from './MasterCard.types'

const meta: Meta<typeof MasterCard> = {
  title: 'Entities/Master/MasterCard',
  component: MasterCard,
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Карточка мастера с полной информацией, рейтингом, услугами и действиями. Компонент с TypeScript типизацией и современным дизайном.'
      }
    }
  },
  argTypes: {
    master: {
      control: 'object',
      description: 'Данные мастера со всеми необходимыми полями'
    }
  },
  tags: ['autodocs']
}

export default meta
type Story = StoryObj<typeof MasterCard>

// Mock данные
const mockServices: Service[] = [
  {
    id: 1,
    name: 'Классический массаж',
    description: 'Расслабляющий массаж всего тела',
    price: 5000,
    duration: 60
  },
  {
    id: 2,
    name: 'Спортивный массаж',
    description: 'Глубокий массаж для восстановления',
    price: 6000,
    duration: 90
  },
  {
    id: 3,
    name: 'Антицеллюлитный массаж',
    description: 'Коррекция фигуры',
    price: 7000,
    duration: 75
  }
]

const baseMaster: Master = {
  id: 1,
  name: 'Анна Иванова',
  display_name: 'Анна',
  avatar: 'https://picsum.photos/300/400?random=1',
  specialty: 'Классический и спортивный массаж',
  price_from: 5000,
  rating: 4.8,
  reviews_count: 125,
  is_premium: true,
  is_verified: true,
  is_online: true,
  is_available_now: true,
  is_favorite: false,
  show_contacts: true,
  phone: '+7 (999) 123-45-67',
  district: 'Центральный',
  metro_station: 'Площадь Революции',
  services: mockServices
}

// Базовая карточка
export const Default: Story = {
  args: {
    master: baseMaster
  },
  parameters: {
    docs: {
      description: {
        story: 'Стандартная карточка мастера со всеми данными'
      }
    }
  }
}

// Карточка в избранном
export const Favorite: Story = {
  args: {
    master: {
      ...baseMaster,
      is_favorite: true
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка мастера, добавленного в избранное'
      }
    }
  }
}

// Мастер оффлайн
export const Offline: Story = {
  args: {
    master: {
      ...baseMaster,
      is_online: false,
      is_available_now: false
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка мастера, который сейчас не в сети'
      }
    }
  }
}

// Обычный мастер (не премиум)
export const Regular: Story = {
  args: {
    master: {
      ...baseMaster,
      is_premium: false,
      is_verified: false
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка обычного мастера без премиум статуса'
      }
    }
  }
}

// Новый мастер без рейтинга
export const NewMaster: Story = {
  args: {
    master: {
      ...baseMaster,
      rating: undefined,
      reviews_count: 0,
      is_verified: false
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка нового мастера без отзывов'
      }
    }
  }
}

// Мастер без фото
export const NoPhoto: Story = {
  args: {
    master: {
      ...baseMaster,
      avatar: undefined,
      main_photo: undefined,
      photo: undefined
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка мастера без загруженного фото (показывается placeholder)'
      }
    }
  }
}

// Мастер с длинным именем
export const LongName: Story = {
  args: {
    master: {
      ...baseMaster,
      name: 'Анна-Мария Екатерина Александровна Иванова-Петрова',
      display_name: 'Анна-Мария'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка мастера с длинным именем (проверка truncate)'
      }
    }
  }
}

// Мастер без услуг
export const NoServices: Story = {
  args: {
    master: {
      ...baseMaster,
      specialty: undefined,
      services: []
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка мастера без указанных услуг (показывается дефолтный текст)'
      }
    }
  }
}

// Мастер с закрытыми контактами
export const HiddenContacts: Story = {
  args: {
    master: {
      ...baseMaster,
      show_contacts: false,
      phone: undefined
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка мастера со скрытыми контактами'
      }
    }
  }
}

// Мастер без метро
export const NoMetro: Story = {
  args: {
    master: {
      ...baseMaster,
      metro_station: undefined
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка мастера без указания станции метро'
      }
    }
  }
}

// Высокий рейтинг
export const HighRating: Story = {
  args: {
    master: {
      ...baseMaster,
      rating: 5.0,
      reviews_count: 500
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка популярного мастера с максимальным рейтингом'
      }
    }
  }
}

// Низкая цена
export const LowPrice: Story = {
  args: {
    master: {
      ...baseMaster,
      price_from: 2000
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка мастера с демократичными ценами'
      }
    }
  }
}

// Высокая цена
export const ExpensiveMaster: Story = {
  args: {
    master: {
      ...baseMaster,
      price_from: 15000,
      is_premium: true
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка премиум мастера с высокими ценами'
      }
    }
  }
}

// Мобильная версия
export const Mobile: Story = {
  args: {
    master: baseMaster
  },
  parameters: {
    viewport: {
      defaultViewport: 'mobile1'
    },
    docs: {
      description: {
        story: 'Карточка мастера в мобильной версии'
      }
    }
  }
}

// Интерактивная версия для тестирования
export const Interactive: Story = {
  args: {
    master: baseMaster
  },
  parameters: {
    docs: {
      description: {
        story: 'Интерактивная карточка для тестирования всех функций'
      }
    }
  },
  play: async ({ canvasElement, args }) => {
    console.log('MasterCard Interactive story loaded')
    console.log('Master data:', args.master)
    
    // Находим элементы для демонстрации
    const card = canvasElement.querySelector('[data-testid="master-card"]')
    const favoriteButton = canvasElement.querySelector('[data-testid="favorite-button"]')
    const phoneButton = canvasElement.querySelector('[data-testid="phone-button"]')
    const bookingButton = canvasElement.querySelector('[data-testid="booking-button"]')
    
    if (card && favoriteButton && phoneButton && bookingButton) {
      console.log('All interactive elements found and ready for testing')
    }
  }
}

// Карточка с ошибкой изображения
export const ImageError: Story = {
  args: {
    master: {
      ...baseMaster,
      avatar: 'https://broken-image-url.jpg'
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Карточка с поломанной ссылкой на изображение (покажется placeholder)'
      }
    }
  }
}

// Сетка карточек для демонстрации
export const GridLayout: Story = {
  render: () => ({
    components: { MasterCard },
    template: `
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <MasterCard :master="masters[0]" />
        <MasterCard :master="masters[1]" />
        <MasterCard :master="masters[2]" />
        <MasterCard :master="masters[3]" />
      </div>
    `,
    data() {
      return {
        masters: [
          baseMaster,
          { ...baseMaster, id: 2, name: 'Елена Петрова', avatar: 'https://picsum.photos/300/400?random=2', is_favorite: true },
          { ...baseMaster, id: 3, name: 'Мария Сидорова', avatar: 'https://picsum.photos/300/400?random=3', is_online: false },
          { ...baseMaster, id: 4, name: 'Ольга Кузнецова', avatar: 'https://picsum.photos/300/400?random=4', is_premium: false }
        ]
      }
    }
  }),
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация сетки карточек мастеров'
      }
    }
  }
}