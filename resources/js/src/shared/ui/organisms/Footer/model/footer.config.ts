// TypeScript интерфейсы
export interface FooterLink {
  id?: string
  text: string
  href: string
  icon?: string
  target?: string
  visible?: boolean
  description?: string
  external?: boolean
}

export interface FooterSection {
  id?: string
  title: string
  links: FooterLink[]
  order?: number
  visible?: boolean
}

export interface SocialLink {
  id?: string
  platform: string
  name?: string
  url: string
  href?: string
  icon?: string
  color?: string
}

export interface AppStore {
  id: string
  name: 'appStore' | 'googlePlay'
  href: string
  image: string
  alt: string
}

export interface FooterConfig {
  company: {
    name: string
    description: string
    year: number
  }
  companyInfo?: {
    name: string
    description: string
    year: number
  }
  sections: FooterSection[]
  social: Record<string, string>
  accessibility?: {
    ariaLabel?: string
    role?: string
  }
}

// Конфигурация футера по умолчанию
export const defaultFooterConfig: FooterConfig = {
  company: {
    name: 'SPA.COM',
    description: 'Платформа для поиска мастеров массажа',
    year: new Date().getFullYear()
  },
  companyInfo: {
    name: 'SPA.COM',
    description: 'Платформа для поиска мастеров массажа',
    year: new Date().getFullYear()
  },
  sections: [
    {
      id: 'about',
      title: 'О компании',
      order: 1,
      visible: true,
      links: [
        { text: 'О нас', href: '/about' },
        { text: 'Контакты', href: '/contacts' },
        { text: 'Вакансии', href: '/careers' },
        { text: 'Пресс-центр', href: '/press' }
      ]
    },
    {
      id: 'users',
      title: 'Пользователям',
      order: 2,
      visible: true,
      links: [
        { text: 'Как это работает', href: '/how-it-works' },
        { text: 'Гарантии', href: '/guarantees' },
        { text: 'Отзывы', href: '/reviews' },
        { text: 'Блог', href: '/blog' }
      ]
    },
    {
      id: 'masters',
      title: 'Мастерам',
      order: 3,
      visible: true,
      links: [
        { text: 'Стать мастером', href: '/become-master' },
        { text: 'Тарифы', href: '/pricing' },
        { text: 'Обучение', href: '/education' },
        { text: 'FAQ', href: '/faq' }
      ]
    },
    {
      id: 'support',
      title: 'Поддержка',
      order: 4,
      visible: true,
      links: [
        { text: 'Помощь', href: '/help' },
        { text: 'Правила', href: '/rules' },
        { text: 'Конфиденциальность', href: '/privacy' },
        { text: 'Условия использования', href: '/terms' }
      ]
    }
  ],
  social: {
    telegram: 'https://t.me/spacom',
    whatsapp: 'https://wa.me/1234567890',
    vk: 'https://vk.com/spacom',
    instagram: 'https://instagram.com/spacom'
  },
  accessibility: {
    ariaLabel: 'Подвал сайта',
    role: 'contentinfo'
  }
}

// Вспомогательные функции
export function getFooterSectionById(config: FooterConfig, id: string): FooterSection | undefined {
  return config.sections.find(section => section.id === id)
}

export function getSortedFooterSections(config: FooterConfig): FooterSection[] {
  return [...config.sections]
    .filter(section => section.visible !== false)
    .sort((a, b) => (a.order || 0) - (b.order || 0))
}

export function filterVisibleLinks(links: FooterLink[]): FooterLink[] {
  return links.filter(link => link.visible !== false)
}

export function isExternalLink(href: string): boolean {
  return href.startsWith('http://') || href.startsWith('https://')
}