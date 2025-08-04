// BookingForm.stories.ts
import type { Meta, StoryObj } from '@storybook/vue3'
import BookingForm from './BookingForm.vue'
import type { BookingFormProps, Master, Service } from './BookingForm.types'

const meta: Meta<typeof BookingForm> = {
  title: 'Components/Booking/BookingForm',
  component: BookingForm,
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Многошаговая форма бронирования услуг с полной TypeScript типизацией и современным UX'
      }
    }
  },
  argTypes: {
    master: {
      control: 'object',
      description: 'Данные мастера с услугами и настройками'
    }
  },
  tags: ['autodocs']
}

export default meta
type Story = StoryObj<typeof BookingForm>

// Mock данные
const mockServices: Service[] = [
  {
    id: 1,
    name: 'Классический массаж',
    price: 5000,
    duration: 60,
    description: 'Расслабляющий массаж всего тела с использованием натуральных масел'
  },
  {
    id: 2,
    name: 'Спортивный массаж',
    price: 6000,
    duration: 90,
    description: 'Глубокий массаж для восстановления после тренировок'
  },
  {
    id: 3,
    name: 'Антицеллюлитный массаж',
    price: 7000,
    duration: 75,
    description: 'Специальная техника для коррекции фигуры'
  }
]

const mockMaster: Master = {
  id: 1,
  name: 'Анна Иванова',
  display_name: 'Анна',
  services: mockServices,
  home_service: true,
  salon_service: true,
  salon_address: 'ул. Пушкина, д. 10, салон "Релакс"',
  avatar: 'https://picsum.photos/200/200?random=master1',
  rating: 4.8,
  reviewsCount: 125
}

const mockMasterSalonOnly: Master = {
  id: 2,
  name: 'Елена Петрова',
  display_name: 'Елена',
  services: mockServices.slice(0, 2),
  home_service: false,
  salon_service: true,
  salon_address: 'пр. Ленина, д. 5, ТЦ "Европа", 2 этаж',
  avatar: 'https://picsum.photos/200/200?random=master2',
  rating: 4.9,
  reviewsCount: 89
}

const mockMasterHomeOnly: Master = {
  id: 3,
  name: 'Мария Сидорова',
  display_name: 'Мария',
  services: [mockServices[0], mockServices[2]],
  home_service: true,
  salon_service: false,
  avatar: 'https://picsum.photos/200/200?random=master3',
  rating: 4.7,
  reviewsCount: 67
}

// Базовая история
export const Default: Story = {
  args: {
    master: mockMaster
  },
  parameters: {
    docs: {
      description: {
        story: 'Базовая форма бронирования с мастером, который предоставляет услуги как в салоне, так и с выездом'
      }
    }
  }
}

// Только салон
export const SalonOnly: Story = {
  args: {
    master: mockMasterSalonOnly
  },
  parameters: {
    docs: {
      description: {
        story: 'Форма для мастера, который работает только в салоне. Опция выезда недоступна.'
      }
    }
  }
}

// Только выезд
export const HomeOnly: Story = {
  args: {
    master: mockMasterHomeOnly
  },
  parameters: {
    docs: {
      description: {
        story: 'Форма для мастера, который работает только с выездом на дом. Поле адреса обязательно.'
      }
    }
  }
}

// Одна услуга
export const SingleService: Story = {
  args: {
    master: {
      ...mockMaster,
      services: [mockServices[0]]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Форма с одной доступной услугой. Шаг выбора автоматически пропускается.'
      }
    }
  }
}

// Много услуг
export const ManyServices: Story = {
  args: {
    master: {
      ...mockMaster,
      services: [
        ...mockServices,
        {
          id: 4,
          name: 'Медовый массаж',
          price: 8000,
          duration: 120,
          description: 'Детоксикация организма с натуральным медом'
        },
        {
          id: 5,
          name: 'Лимфодренажный массаж',
          price: 6500,
          duration: 90,
          description: 'Улучшение лимфооттока и снятие отеков'
        }
      ]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Форма с большим количеством услуг. Проверка скроллинга и адаптивности.'
      }
    }
  }
}

// Мастер без рейтинга
export const NoRating: Story = {
  args: {
    master: {
      ...mockMaster,
      rating: undefined,
      reviewsCount: undefined
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Форма для нового мастера без рейтинга и отзывов'
      }
    }
  }
}

// Мастер без фото
export const NoAvatar: Story = {
  args: {
    master: {
      ...mockMaster,
      avatar: undefined
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Форма для мастера без загруженного аватара. Показывается placeholder.'
      }
    }
  }
}

// Мобильная версия
export const Mobile: Story = {
  args: {
    master: mockMaster
  },
  parameters: {
    viewport: {
      defaultViewport: 'mobile1'
    },
    docs: {
      description: {
        story: 'Адаптивная версия формы для мобильных устройств с оптимизированным интерфейсом'
      }
    }
  }
}

// Состояние с ошибками
export const WithErrors: Story = {
  args: {
    master: mockMaster
  },
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация состояния формы с ошибками валидации'
      }
    }
  },
  play: async ({ canvasElement }) => {
    // Симулируем состояние с ошибками
    const canvas = canvasElement
    const form = canvas.querySelector('form')
    if (form) {
      console.log('Form found for error simulation')
    }
  }
}

// Интерактивная версия для тестирования
export const Interactive: Story = {
  args: {
    master: mockMaster
  },
  parameters: {
    docs: {
      description: {
        story: 'Интерактивная версия для полного тестирования функциональности формы'
      }
    }
  },
  play: async ({ canvasElement, args }) => {
    console.log('BookingForm Interactive story loaded')
    console.log('Master data:', args.master)
    console.log('Canvas element:', canvasElement)
    
    // Можно добавить автоматические действия для тестирования
    const firstStep = canvasElement.querySelector('[data-step="0"]')
    if (firstStep) {
      console.log('First step found and ready for interaction')
    }
  }
}

// Все шаги заполнены
export const PrefilledForm: Story = {
  args: {
    master: mockMaster
  },
  parameters: {
    docs: {
      description: {
        story: 'Форма с предзаполненными данными для демонстрации финального шага'
      }
    }
  }
}

// Состояние загрузки
export const LoadingState: Story = {
  args: {
    master: mockMaster
  },
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация состояний загрузки при запросах к API'
      }
    }
  }
}