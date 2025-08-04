// PhoneModal.stories.ts
import type { Meta, StoryObj } from '@storybook/vue3'
import PhoneModal from './PhoneModal.vue'
import type { PhoneModalProps } from './PhoneModal.types'

const meta: Meta<typeof PhoneModal> = {
  title: 'Components/Modals/PhoneModal',
  component: PhoneModal,
  parameters: {
    layout: 'fullscreen',
    docs: {
      description: {
        component: 'Модальное окно для отображения телефона мастера с возможностью звонка и копирования. Поддерживает форматирование и валидацию номеров.'
      }
    }
  },
  argTypes: {
    show: {
      control: 'boolean',
      description: 'Показывать ли модальное окно'
    },
    phone: {
      control: 'text',
      description: 'Номер телефона для отображения'
    },
    onClose: {
      action: 'close',
      description: 'Событие закрытия модального окна'
    },
    onCalled: {
      action: 'called',
      description: 'Событие клика по кнопке звонка'
    },
    onCopied: {
      action: 'copied',
      description: 'Событие копирования номера'
    }
  },
  tags: ['autodocs']
}

export default meta
type Story = StoryObj<typeof PhoneModal>

// Базовый вариант
export const Default: Story = {
  args: {
    show: true,
    phone: '+7 (999) 123-45-67'
  },
  parameters: {
    docs: {
      description: {
        story: 'Стандартное модальное окно с отформатированным номером телефона'
      }
    }
  }
}

// Неформатированный номер
export const UnformattedPhone: Story = {
  args: {
    show: true,
    phone: '79991234567'
  },
  parameters: {
    docs: {
      description: {
        story: 'Автоматическое форматирование неформатированного номера'
      }
    }
  }
}

// Номер с пробелами и символами
export const PhoneWithSpaces: Story = {
  args: {
    show: true,
    phone: '+7 999 123 45 67'
  },
  parameters: {
    docs: {
      description: {
        story: 'Номер с различными символами - автоматически форматируется'
      }
    }
  }
}

// Номер с 8 вместо +7
export const PhoneWith8: Story = {
  args: {
    show: true,
    phone: '8 (999) 123-45-67'
  },
  parameters: {
    docs: {
      description: {
        story: 'Номер начинающийся с 8 - конвертируется в +7 формат'
      }
    }
  }
}

// Невалидный номер
export const InvalidPhone: Story = {
  args: {
    show: true,
    phone: '123-456'
  },
  parameters: {
    docs: {
      description: {
        story: 'Невалидный номер - показывается ошибка валидации и отключается кнопка звонка'
      }
    }
  }
}

// Пустой номер
export const EmptyPhone: Story = {
  args: {
    show: true,
    phone: ''
  },
  parameters: {
    docs: {
      description: {
        story: 'Пустой номер - обе кнопки отключены'
      }
    }
  }
}

// Короткий невалидный номер
export const ShortInvalidPhone: Story = {
  args: {
    show: true,
    phone: '123'
  },
  parameters: {
    docs: {
      description: {
        story: 'Короткий номер - показывается как есть с ошибкой валидации'
      }
    }
  }
}

// Длинный номер
export const LongPhone: Story = {
  args: {
    show: true,
    phone: '7999123456789'
  },
  parameters: {
    docs: {
      description: {
        story: 'Слишком длинный номер - не проходит валидацию'
      }
    }
  }
}

// Номер с буквами
export const PhoneWithLetters: Story = {
  args: {
    show: true,
    phone: '7999abc4567'
  },
  parameters: {
    docs: {
      description: {
        story: 'Номер с буквами - показывается ошибка валидации'
      }
    }
  }
}

// Скрытое модальное окно
export const Hidden: Story = {
  args: {
    show: false,
    phone: '+7 (999) 123-45-67'
  },
  parameters: {
    docs: {
      description: {
        story: 'Скрытое модальное окно (show=false) - ничего не отображается'
      }
    }
  }
}

// Московский номер
export const MoscowPhone: Story = {
  args: {
    show: true,
    phone: '+7 (495) 123-45-67'
  },
  parameters: {
    docs: {
      description: {
        story: 'Московский городской номер'
      }
    }
  }
}

// СПб номер
export const SpbPhone: Story = {
  args: {
    show: true,
    phone: '+7 (812) 987-65-43'
  },
  parameters: {
    docs: {
      description: {
        story: 'Санкт-Петербургский номер'
      }
    }
  }
}

// Интерактивная версия
export const Interactive: Story = {
  args: {
    show: true,
    phone: '+7 (999) 123-45-67'
  },
  parameters: {
    docs: {
      description: {
        story: 'Интерактивная версия для тестирования всех функций'
      }
    }
  },
  play: async ({ canvasElement, args }) => {
    console.log('PhoneModal Interactive story loaded')
    console.log('Phone number:', args.phone)
    
    // Проверяем наличие элементов
    const modal = canvasElement.querySelector('[data-testid="phone-modal-overlay"]')
    const phoneDisplay = canvasElement.querySelector('[data-testid="phone-display"]')
    const callButton = canvasElement.querySelector('[data-testid="call-button"]')
    const copyButton = canvasElement.querySelector('[data-testid="copy-button"]')
    const closeButton = canvasElement.querySelector('[data-testid="phone-modal-close"]')
    
    if (modal && phoneDisplay && callButton && copyButton && closeButton) {
      console.log('All modal elements found and ready')
      console.log('Available actions: call, copy, close')
      
      // Проверяем форматирование номера
      const phoneNumber = canvasElement.querySelector('[data-testid="phone-number"]')
      if (phoneNumber) {
        console.log('Formatted phone:', phoneNumber.textContent)
      }
    }
  }
}

// Демонстрация состояний копирования
export const CopyStates: Story = {
  render: () => ({
    components: { PhoneModal },
    template: `
      <div class="space-y-4 p-4">
        <div class="text-lg font-semibold mb-4">Состояния кнопки копирования:</div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <h3 class="text-md font-medium mb-2">Обычное состояние</h3>
            <PhoneModal :show="true" phone="+7 (999) 123-45-67" />
          </div>
          
          <div>
            <h3 class="text-md font-medium mb-2">С невалидным номером</h3>
            <PhoneModal :show="true" phone="invalid-phone" />
          </div>
        </div>
      </div>
    `
  }),
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация различных состояний кнопки копирования'
      }
    }
  }
}

// Различные форматы номеров
export const PhoneFormats: Story = {
  render: () => ({
    components: { PhoneModal },
    template: `
      <div class="space-y-4 p-4">
        <div class="text-lg font-semibold mb-4">Поддерживаемые форматы номеров:</div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div v-for="(phone, index) in phoneNumbers" :key="index" class="border rounded-lg p-4">
            <h4 class="font-medium mb-2">{{ phone.title }}</h4>
            <p class="text-sm text-gray-600 mb-3">Ввод: {{ phone.input }}</p>
            <PhoneModal :show="true" :phone="phone.input" />
          </div>
        </div>
      </div>
    `,
    data() {
      return {
        phoneNumbers: [
          { title: 'Стандартный формат', input: '+7 (999) 123-45-67' },
          { title: 'Без форматирования', input: '79991234567' },
          { title: 'С восьмеркой', input: '89991234567' },
          { title: 'С пробелами', input: '+7 999 123 45 67' },
          { title: 'Московский номер', input: '74951234567' },
          { title: 'Невалидный', input: '123-456-789' }
        ]
      }
    }
  }),
  parameters: {
    docs: {
      description: {
        story: 'Различные форматы входных номеров и их обработка'
      }
    }
  }
}

// Мобильная версия
export const Mobile: Story = {
  args: {
    show: true,
    phone: '+7 (999) 123-45-67'
  },
  parameters: {
    viewport: {
      defaultViewport: 'mobile1'
    },
    docs: {
      description: {
        story: 'Модальное окно в мобильной версии'
      }
    }
  }
}

// Темная тема
export const DarkTheme: Story = {
  args: {
    show: true,
    phone: '+7 (999) 123-45-67'
  },
  parameters: {
    backgrounds: {
      default: 'dark'
    },
    docs: {
      description: {
        story: 'Модальное окно на темном фоне'
      }
    }
  },
  decorators: [
    () => ({
      template: '<div class="dark"><story /></div>'
    })
  ]
}

// Тестирование клавиатурной навигации
export const KeyboardNavigation: Story = {
  args: {
    show: true,
    phone: '+7 (999) 123-45-67'
  },
  parameters: {
    docs: {
      description: {
        story: 'Тестирование клавиатурной навигации (Escape для закрытия)'
      }
    }
  },
  play: async ({ canvasElement }) => {
    // Демонстрация клавиатурной навигации
    setTimeout(() => {
      const modal = canvasElement.querySelector('[data-testid="phone-modal-overlay"]')
      if (modal) {
        console.log('Нажмите Escape для закрытия модального окна')
        
        // Имитируем нажатие Escape через 3 секунды
        setTimeout(() => {
          const escapeEvent = new KeyboardEvent('keydown', { key: 'Escape' })
          modal.dispatchEvent(escapeEvent)
        }, 3000)
      }
    }, 1000)
  }
}

// Состояние ошибки
export const ErrorState: Story = {
  args: {
    show: true,
    phone: 'error-phone-123'
  },
  parameters: {
    docs: {
      description: {
        story: 'Состояние с ошибкой валидации номера'
      }
    }
  }
}

// Длинный номер с переносом
export const LongPhoneNumber: Story = {
  args: {
    show: true,
    phone: '+7 (999) 123-45-67 доб. 1234'
  },
  parameters: {
    docs: {
      description: {
        story: 'Очень длинный номер с дополнительной информацией'
      }
    }
  }
}

// Все состояния в одном
export const AllStates: Story = {
  render: () => ({
    components: { PhoneModal },
    template: `
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4">
        <div class="border rounded-lg p-4">
          <h4 class="font-semibold mb-2">Валидный номер</h4>
          <PhoneModal :show="true" phone="+7 (999) 123-45-67" />
        </div>
        
        <div class="border rounded-lg p-4">
          <h4 class="font-semibold mb-2">Неформатированный</h4>
          <PhoneModal :show="true" phone="79991234567" />
        </div>
        
        <div class="border rounded-lg p-4">
          <h4 class="font-semibold mb-2">Невалидный</h4>
          <PhoneModal :show="true" phone="123-456" />
        </div>
        
        <div class="border rounded-lg p-4">
          <h4 class="font-semibold mb-2">Пустой</h4>
          <PhoneModal :show="true" phone="" />
        </div>
        
        <div class="border rounded-lg p-4">
          <h4 class="font-semibold mb-2">С буквами</h4>
          <PhoneModal :show="true" phone="7999abc4567" />
        </div>
        
        <div class="border rounded-lg p-4">
          <h4 class="font-semibold mb-2">Московский</h4>
          <PhoneModal :show="true" phone="74951234567" />
        </div>
      </div>
    `
  }),
  parameters: {
    docs: {
      description: {
        story: 'Все возможные состояния модального окна в одном представлении'
      }
    }
  }
}