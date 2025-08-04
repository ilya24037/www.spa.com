// MediaUploader.stories.ts
import type { Meta, StoryObj } from '@storybook/vue3'
import MediaUploader from './MediaUploader.vue'
import type { MediaUploaderProps, Photo, Video } from './MediaUploader.types'

const meta: Meta<typeof MediaUploader> = {
  title: 'Components/MediaUpload/MediaUploader',
  component: MediaUploader,
  parameters: {
    layout: 'padded',
    docs: {
      description: {
        component: 'Компонент для загрузки медиа файлов (аватар, фото, видео) с поддержкой TypeScript и современным UX'
      }
    }
  },
  argTypes: {
    masterId: {
      control: 'number',
      description: 'ID мастера для загрузки медиа'
    },
    masterName: {
      control: 'text',
      description: 'Имя мастера для alt текста'
    },
    initialPhotos: {
      control: 'object',
      description: 'Массив начальных фотографий'
    },
    initialVideo: {
      control: 'object',
      description: 'Начальное видео'
    }
  },
  tags: ['autodocs']
}

export default meta
type Story = StoryObj<typeof MediaUploader>

// Mock данные
const mockPhotos: Photo[] = [
  {
    id: 1,
    thumb_url: 'https://picsum.photos/200/300?random=1',
    sort_order: 1,
    is_main: true
  },
  {
    id: 2,
    thumb_url: 'https://picsum.photos/200/300?random=2',
    sort_order: 2,
    is_main: false
  },
  {
    id: 3,
    thumb_url: 'https://picsum.photos/200/300?random=3',
    sort_order: 3,
    is_main: false
  }
]

const mockVideo: Video = {
  id: 1,
  video_url: 'https://www.w3schools.com/html/mov_bbb.mp4',
  poster_url: 'https://picsum.photos/400/300?random=video',
  duration: '2:30',
  file_size: 1024000
}

// Базовая история
export const Default: Story = {
  args: {
    masterId: 1,
    masterName: 'Анна Иванова',
    initialPhotos: [],
    initialVideo: null
  },
  parameters: {
    docs: {
      description: {
        story: 'Базовое состояние компонента без загруженных медиа файлов'
      }
    }
  }
}

// С фотографиями
export const WithPhotos: Story = {
  args: {
    masterId: 1,
    masterName: 'Анна Иванова',
    initialPhotos: mockPhotos,
    initialVideo: null
  },
  parameters: {
    docs: {
      description: {
        story: 'Компонент с предзагруженными фотографиями. Одна из фотографий отмечена как главная.'
      }
    }
  }
}

// С видео
export const WithVideo: Story = {
  args: {
    masterId: 1,
    masterName: 'Анна Иванова',
    initialPhotos: [],
    initialVideo: mockVideo
  },
  parameters: {
    docs: {
      description: {
        story: 'Компонент с предзагруженным видео-файлом'
      }
    }
  }
}

// Полный набор медиа
export const WithAllMedia: Story = {
  args: {
    masterId: 1,
    masterName: 'Анна Иванова',
    initialPhotos: mockPhotos,
    initialVideo: mockVideo
  },
  parameters: {
    docs: {
      description: {
        story: 'Компонент с полным набором медиа: аватар, фотографии и видео'
      }
    }
  }
}

// Максимальное количество фотографий
export const MaxPhotos: Story = {
  args: {
    masterId: 1,
    masterName: 'Анна Иванова',
    initialPhotos: Array.from({ length: 10 }, (_, i) => ({
      id: i + 1,
      thumb_url: `https://picsum.photos/200/300?random=${i + 10}`,
      sort_order: i + 1,
      is_main: i === 0
    })),
    initialVideo: null
  },
  parameters: {
    docs: {
      description: {
        story: 'Компонент с максимальным количеством фотографий (10 штук). Кнопка добавления фото скрыта.'
      }
    }
  }
}

// Состояние загрузки (для демонстрации)
export const LoadingState: Story = {
  args: {
    masterId: 1,
    masterName: 'Анна Иванова',
    initialPhotos: mockPhotos.slice(0, 2),
    initialVideo: null
  },
  parameters: {
    docs: {
      description: {
        story: 'Демонстрация интерфейса во время загрузки файлов'
      }
    }
  }
}

// Мобильная версия
export const Mobile: Story = {
  args: {
    masterId: 1,
    masterName: 'Анна Иванова',
    initialPhotos: mockPhotos,
    initialVideo: mockVideo
  },
  parameters: {
    viewport: {
      defaultViewport: 'mobile1'
    },
    docs: {
      description: {
        story: 'Компонент адаптирован для мобильных устройств с responsive grid'
      }
    }
  }
}

// Интерактивная история для тестирования
export const Interactive: Story = {
  args: {
    masterId: 1,
    masterName: 'Анна Иванова',
    initialPhotos: [mockPhotos[0]],
    initialVideo: null
  },
  parameters: {
    docs: {
      description: {
        story: 'Интерактивная версия для тестирования функциональности загрузки'
      }
    }
  },
  play: async ({ canvasElement }) => {
    // Можно добавить интерактивные действия для тестирования
    console.log('MediaUploader mounted on', canvasElement)
  }
}