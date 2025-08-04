/**
 * Storybook истории для Toast компонента
 */

import type { Meta, StoryObj } from '@storybook/vue3'
import Toast from './Toast.vue'
import type { ToastProps } from './Toast.types'

const meta: Meta<typeof Toast> = {
  title: 'Shared/UI/Molecules/Toast',
  component: Toast,
  parameters: {
    layout: 'fullscreen',
    docs: {
      description: {
        component: 'Toast компонент для показа уведомлений. Заменяет alert() для лучшего UX.'
      }
    }
  },
  argTypes: {
    type: {
      control: { type: 'select' },
      options: ['success', 'error', 'warning', 'info'],
      description: 'Тип уведомления'
    },
    position: {
      control: { type: 'select' },
      options: ['top-left', 'top-center', 'top-right', 'bottom-left', 'bottom-center', 'bottom-right'],
      description: 'Позиция на экране'
    },
    duration: {
      control: { type: 'number', min: 0, max: 10000, step: 500 },
      description: 'Длительность показа в мс (0 = не скрывать)'
    },
    closable: {
      control: { type: 'boolean' },
      description: 'Показывать кнопку закрытия'
    }
  },
  args: {
    message: 'Это уведомление',
    type: 'info',
    position: 'top-right',
    duration: 4000,
    closable: true
  }
}

export default meta
type Story = StoryObj<typeof Toast>

// Базовая история
export const Default: Story = {
  args: {
    message: 'Это обычное уведомление'
  }
}

// Типы уведомлений
export const Success: Story = {
  args: {
    type: 'success',
    title: 'Успешно',
    message: 'Операция выполнена успешно!'
  }
}

export const Error: Story = {
  args: {
    type: 'error',
    title: 'Ошибка',
    message: 'Произошла ошибка при выполнении операции'
  }
}

export const Warning: Story = {
  args: {
    type: 'warning',
    title: 'Предупреждение',
    message: 'Пожалуйста, проверьте введённые данные'
  }
}

export const Info: Story = {
  args: {
    type: 'info',
    title: 'Информация',
    message: 'У вас есть новые уведомления'
  }
}

// Позиции
export const TopLeft: Story = {
  args: {
    position: 'top-left',
    message: 'Уведомление в левом верхнем углу'
  }
}

export const TopCenter: Story = {
  args: {
    position: 'top-center',
    message: 'Уведомление по центру сверху'
  }
}

export const BottomRight: Story = {
  args: {
    position: 'bottom-right',
    type: 'success',
    message: 'Уведомление справа снизу'
  }
}

// Без автозакрытия
export const Persistent: Story = {
  args: {
    duration: 0,
    type: 'warning',
    title: 'Важное уведомление',
    message: 'Это уведомление не исчезнет автоматически',
    closable: true
  }
}

// Без кнопки закрытия
export const NotClosable: Story = {
  args: {
    closable: false,
    duration: 2000,
    type: 'info',
    message: 'Это уведомление нельзя закрыть вручную'
  }
}

// Длинное сообщение
export const LongMessage: Story = {
  args: {
    type: 'error',
    title: 'Подробная ошибка',
    message: 'Это очень длинное сообщение об ошибке, которое содержит много деталей и должно корректно отображаться в уведомлении с переносом строк.'
  }
}

// Быстрое уведомление
export const Quick: Story = {
  args: {
    duration: 1000,
    type: 'success',
    message: 'Быстрое уведомление (1 сек)'
  }
}

// Медленное уведомление
export const Slow: Story = {
  args: {
    duration: 8000,
    type: 'info',
    title: 'Медленное уведомление',
    message: 'Это уведомление будет показано 8 секунд'
  }
}

// Множественные уведомления (демонстрация)
export const Multiple: Story = {
  render: () => ({
    components: { Toast },
    template: `
      <div>
        <Toast 
          type="success" 
          message="Первое уведомление" 
          position="top-right"
          :duration="6000"
        />
        <Toast 
          type="warning" 
          message="Второе уведомление" 
          position="top-right"
          :duration="6000"
          style="top: 90px;"
        />
        <Toast 
          type="error" 
          message="Третье уведомление" 
          position="top-right"
          :duration="6000"
          style="top: 160px;"
        />
      </div>
    `
  })
}