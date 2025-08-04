// BookingModal.stories.ts
import type { Meta, StoryObj } from '@storybook/vue3'
import BookingModal from './BookingModal.vue'
import type { BookingModalProps, Master, Service } from './BookingModal.types'

const meta: Meta<typeof BookingModal> = {
  title: 'Entities/Booking/BookingModal',
  component: BookingModal,
  parameters: {
    layout: 'fullscreen',
    docs: {
      description: {
        component: 'Модальное окно для создания бронирования услуги у мастера. Поддерживает выбор даты, времени, типа услуги (дом/салон) и всех необходимых контактных данных.'
      }
    }
  },
  argTypes: {
    master: {
      control: 'object',
      description: 'Данные мастера с услугами и настройками'
    },
    service: {
      control: 'object',
      description: 'Предварительно выбранная услуга (опционально)'
    },
    onClose: {
      action: 'close',
      description: 'Событие закрытия модального окна'
    },
    onSuccess: {
      action: 'success',
      description: 'Событие успешного создания бронирования'
    }
  },
  tags: ['autodocs']
}

export default meta
type Story = StoryObj<typeof BookingModal>

// Mock данные
const mockServices: Service[] = [
  {
    id: 1,
    name: 'Классический массаж',
    price: 3000,
    duration: 60,
    description: 'Расслабляющий массаж всего тела'
  },
  {
    id: 2,
    name: 'Антицеллюлитный массаж',
    price: 4000,
    duration: 90,
    description: 'Коррекция фигуры и борьба с целлюлитом'
  },
  {
    id: 3,
    name: 'Спортивный массаж',
    price: 3500,
    duration: 75,
    description: 'Восстановление после тренировок'
  },
  {
    id: 4,
    name: 'Лимфодренажный массаж',
    price: 4500,
    duration: 80,
    description: 'Детокс и улучшение лимфотока'
  }
]

const baseMaster: Master = {
  id: 1,
  display_name: 'Анна Петрова',
  avatar: 'https://images.unsplash.com/photo-1594824804732-ca8db0fa4460?w=150&h=150&fit=crop&crop=face',
  district: 'Центральный район',
  home_service: true,
  salon_service: true,
  salon_address: 'ул. Пушкина, д. 10, оф. 205',
  services: mockServices
}

// Базовое модальное окно
export const Default: Story = {
  args: {
    master: baseMaster,
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Стандартное модальное окно бронирования с предварительно выбранной услугой'
      }
    }
  }
}

// Без предварительно выбранной услуги
export const NoPreselectedService: Story = {
  args: {
    master: baseMaster
  },
  parameters: {
    docs: {
      description: {
        story: 'Модальное окно без предварительно выбранной услуги - пользователь сам выбирает из списка'
      }
    }
  }
}

// Только домашние услуги
export const HomeServiceOnly: Story = {
  args: {
    master: {
      ...baseMaster,
      home_service: true,
      salon_service: false,
      salon_address: undefined
    },
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Мастер, работающий только на выезде'
      }
    }
  }
}

// Только салонные услуги
export const SalonServiceOnly: Story = {
  args: {
    master: {
      ...baseMaster,
      home_service: false,
      salon_service: true
    },
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Мастер, работающий только в салоне'
      }
    }
  }
}

// Мастер с одной услугой
export const SingleService: Story = {
  args: {
    master: {
      ...baseMaster,
      services: [mockServices[0]]
    },
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Мастер с одной услугой - выбор услуги автоматически заблокирован'
      }
    }
  }
}

// Мастер с множеством услуг
export const ManyServices: Story = {
  args: {
    master: {
      ...baseMaster,
      services: [
        ...mockServices,
        {
          id: 5,
          name: 'Тайский массаж',
          price: 5000,
          duration: 120,
          description: 'Традиционный тайский массаж'
        },
        {
          id: 6,
          name: 'Стоун-терапия',
          price: 4800,
          duration: 90,
          description: 'Массаж горячими камнями'
        },
        {
          id: 7,
          name: 'Арома-массаж',
          price: 3800,
          duration: 70,
          description: 'Расслабляющий массаж с эфирными маслами'
        }
      ]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Мастер с большим выбором услуг'
      }
    }
  }
}

// Дорогие услуги
export const ExpensiveServices: Story = {
  args: {
    master: {
      ...baseMaster,
      display_name: 'Елена Николаевна (VIP)',
      services: [
        {
          id: 1,
          name: 'VIP массаж премиум',
          price: 8000,
          duration: 120,
          description: 'Эксклюзивный массаж с индивидуальным подходом'
        },
        {
          id: 2,
          name: 'Королевский релакс',
          price: 12000,
          duration: 180,
          description: 'Полный спа-комплекс с массажем'
        }
      ]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Премиум мастер с дорогими услугами'
      }
    }
  }
}

// Бюджетные услуги
export const BudgetServices: Story = {
  args: {
    master: {
      ...baseMaster,
      display_name: 'Мария Студентка',
      services: [
        {
          id: 1,
          name: 'Базовый массаж',
          price: 1500,
          duration: 45,
          description: 'Простой расслабляющий массаж'
        },
        {
          id: 2,
          name: 'Экспресс-массаж',
          price: 1000,
          duration: 30,
          description: 'Быстрый массаж для снятия напряжения'
        }
      ]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Начинающий мастер с доступными ценами'
      }
    }
  }
}

// Без аватара
export const NoAvatar: Story = {
  args: {
    master: {
      ...baseMaster,
      avatar: undefined
    },
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Мастер без загруженного аватара - отображается заглушка'
      }
    }
  }
}

// Длинные названия
export const LongNames: Story = {
  args: {
    master: {
      ...baseMaster,
      display_name: 'Анастасия Александровна Долгофамилиева',
      district: 'Северо-Западный административный округ, район Щукино',
      salon_address: 'ул. Очень Длинное Название Улицы, д. 123, корп. 4, стр. 2, оф. 205А',
      services: [
        {
          id: 1,
          name: 'Профессиональный лечебно-оздоровительный массаж с элементами мануальной терапии',
          price: 5000,
          duration: 120,
          description: 'Очень подробное описание услуги с перечислением всех техник и методик'
        }
      ]
    }
  },
  parameters: {
    docs: {
      description: {
        story: 'Тестирование с длинными названиями и описаниями'
      }
    }
  }
}

// Загрузка слотов времени
export const LoadingSlots: Story = {
  args: {
    master: baseMaster,
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Состояние загрузки доступных слотов времени'
      }
    }
  },
  play: async ({ canvasElement }) => {
    // Имитация загрузки слотов
    setTimeout(() => {
      const timeSelect = canvasElement.querySelector('[data-testid="time-select"]')
      if (timeSelect) {
        console.log('Загрузка слотов времени...')
      }
    }, 1000)
  }
}

// Форма с ошибками валидации
export const WithValidationErrors: Story = {
  args: {
    master: baseMaster,
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация ошибок валидации при неправильном заполнении формы'
      }
    }
  },
  play: async ({ canvasElement }) => {
    setTimeout(() => {
      // Имитация попытки отправки пустой формы
      const submitButton = canvasElement.querySelector('[data-testid="submit-button"]')
      if (submitButton) {
        console.log('Попытка отправки формы с ошибками...')
      }
    }, 2000)
  }
}

// Заполненная форма
export const FilledForm: Story = {
  args: {
    master: baseMaster,
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Форма с заполненными данными, готовая к отправке'
      }
    }
  },
  play: async ({ canvasElement }) => {
    setTimeout(() => {
      const nameInput = canvasElement.querySelector('[data-testid="client-name-input"]')
      if (nameInput) {
        nameInput.value = 'Иван Петров'
        nameInput.dispatchEvent(new Event('input'))
      }
    }, 1000)
  }
}

// Отправка формы
export const SubmittingForm: Story = {
  args: {
    master: baseMaster,
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Состояние отправки формы бронирования'
      }
    }
  },
  play: async ({ canvasElement }) => {
    setTimeout(() => {
      const submitButton = canvasElement.querySelector('[data-testid="submit-button"]')
      if (submitButton) {
        console.log('Отправка формы бронирования...')
        // Имитируем нажатие кнопки отправки
        submitButton.click()
      }
    }, 1500)
  }
}

// Расчет стоимости
export const PriceCalculation: Story = {
  args: {
    master: baseMaster,
    service: mockServices[1] // Более дорогая услуга
  },
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация расчета итоговой стоимости с выездом на дом'
      }
    }
  },
  play: async ({ canvasElement }) => {
    setTimeout(() => {
      const priceElements = canvasElement.querySelectorAll('[data-testid="service-price"], [data-testid="home-service-fee"], [data-testid="total-price"]')
      if (priceElements.length > 0) {
        console.log('Демонстрация расчета стоимости:')
        console.log('- Услуга: 4000₽')
        console.log('- Выезд: +500₽')
        console.log('- Итого: 4500₽')
      }
    }, 1000)
  }
}

// Мобильная версия
export const Mobile: Story = {
  args: {
    master: baseMaster,
    service: mockServices[0]
  },
  parameters: {
    viewport: {
      defaultViewport: 'mobile2'
    },
    docs: {
      description: {
        story: 'Модальное окно в мобильной версии'
      }
    }
  }
}

// Планшетная версия
export const Tablet: Story = {
  args: {
    master: baseMaster,
    service: mockServices[0]
  },
  parameters: {
    viewport: {
      defaultViewport: 'tablet'
    },
    docs: {
      description: {
        story: 'Модальное окно на планшете'
      }
    }
  }
}

// Интерактивная версия
export const Interactive: Story = {
  args: {
    master: baseMaster,
    service: mockServices[0]
  },
  parameters: {
    docs: {
      description: {
        story: 'Интерактивная версия для тестирования всех функций модального окна'
      }
    }
  },
  play: async ({ canvasElement, args }) => {
    console.log('BookingModal Interactive story loaded')
    console.log('Master:', args.master.display_name)
    console.log('Services count:', args.master.services.length)
    
    const modal = canvasElement.querySelector('[data-testid="booking-modal-wrapper"]')
    const form = canvasElement.querySelector('[data-testid="booking-form"]')
    const serviceSelect = canvasElement.querySelector('[data-testid="service-select"]')
    const submitButton = canvasElement.querySelector('[data-testid="submit-button"]')
    
    if (modal && form && serviceSelect && submitButton) {
      console.log('All booking elements found and ready')
      console.log('Available for testing:')
      console.log('- Service selection')
      console.log('- Date and time picking')
      console.log('- Contact information')
      console.log('- Home/salon service type')
      console.log('- Price calculation')
      console.log('- Form validation')
      console.log('- Booking submission')
    }
  }
}

// Состояния доступного времени
export const TimeSlotStates: Story = {
  render: () => ({
    components: { BookingModal },
    template: `
      <div class="space-y-8 p-6">
        <div class="text-lg font-semibold mb-4">Состояния выбора времени:</div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          <div class="border rounded-lg p-4">
            <h3 class="font-medium mb-3">Нет выбранной даты</h3>
            <BookingModal :master="master" :service="service" />
          </div>
          
          <div class="border rounded-lg p-4">
            <h3 class="font-medium mb-3">Загрузка слотов</h3>
            <BookingModal :master="master" :service="service" />
          </div>
        </div>
      </div>
    `,
    data() {
      return {
        master: baseMaster,
        service: mockServices[0]
      }
    }
  }),
  parameters: {
    docs: {
      description: {
        story: 'Различные состояния компонента выбора времени'
      }
    }
  }
}

// Сравнение мастеров
export const MasterComparison: Story = {
  render: () => ({
    components: { BookingModal },
    template: `
      <div class="space-y-8 p-6">
        <div class="text-lg font-semibold mb-4">Разные типы мастеров:</div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="border rounded-lg p-4">
            <h3 class="font-medium mb-3">Премиум мастер</h3>
            <BookingModal :master="premiumMaster" />
          </div>
          
          <div class="border rounded-lg p-4">
            <h3 class="font-medium mb-3">Стандартный мастер</h3>
            <BookingModal :master="standardMaster" />
          </div>
          
          <div class="border rounded-lg p-4">
            <h3 class="font-medium mb-3">Начинающий мастер</h3>
            <BookingModal :master="budgetMaster" />
          </div>
        </div>
      </div>
    `,
    data() {
      return {
        premiumMaster: {
          ...baseMaster,
          display_name: 'Елена VIP',
          services: [{
            id: 1,
            name: 'Премиум массаж',
            price: 8000,
            duration: 120
          }]
        },
        standardMaster: baseMaster,
        budgetMaster: {
          ...baseMaster,
          display_name: 'Мария Начинающая',
          services: [{
            id: 1,
            name: 'Базовый массаж',
            price: 1500,
            duration: 45
          }]
        }
      }
    }
  }),
  parameters: {
    docs: {
      description: {
        story: 'Сравнение бронирования у разных типов мастеров'
      }
    }
  }
}

// Все состояния формы
export const AllFormStates: Story = {
  render: () => ({
    components: { BookingModal },
    template: `
      <div class="space-y-6 p-6">
        <div class="text-lg font-semibold mb-4">Все состояния формы бронирования:</div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="border rounded-lg p-4">
            <h4 class="font-medium mb-2">Пустая форма</h4>
            <p class="text-sm text-gray-600 mb-3">Начальное состояние</p>
            <BookingModal :master="master" />
          </div>
          
          <div class="border rounded-lg p-4">
            <h4 class="font-medium mb-2">С предзаполненной услугой</h4>
            <p class="text-sm text-gray-600 mb-3">Услуга выбрана заранее</p>
            <BookingModal :master="master" :service="service" />
          </div>
          
          <div class="border rounded-lg p-4">
            <h4 class="font-medium mb-2">Только домашние услуги</h4>
            <p class="text-sm text-gray-600 mb-3">Мастер работает только на выезде</p>
            <BookingModal :master="homeOnlyMaster" :service="service" />
          </div>
          
          <div class="border rounded-lg p-4">
            <h4 class="font-medium mb-2">Только салонные услуги</h4>
            <p class="text-sm text-gray-600 mb-3">Мастер работает только в салоне</p>
            <BookingModal :master="salonOnlyMaster" :service="service" />
          </div>
        </div>
      </div>
    `,
    data() {
      return {
        master: baseMaster,
        service: mockServices[0],
        homeOnlyMaster: {
          ...baseMaster,
          home_service: true,
          salon_service: false
        },
        salonOnlyMaster: {
          ...baseMaster,
          home_service: false,
          salon_service: true
        }
      }
    }
  }),
  parameters: {
    docs: {
      description: {
        story: 'Все возможные состояния и конфигурации формы бронирования'
      }
    }
  }
}