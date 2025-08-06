// Footer configuration - centralized data
export interface FooterLink {
  id: string
  text: string
  href: string
  external?: boolean
  icon?: string
  description?: string
}

export interface FooterSection {
  id: string
  title: string
  links: FooterLink[]
  order: number
}

export interface SocialLink {
  id: string
  name: string
  href: string
  icon: string
  color: string
}

export interface AppStore {
  id: string
  name: 'appStore' | 'googlePlay'
  href: string
  image: string
  alt: string
}

export interface FooterConfig {
  sections: FooterSection[]
  socialLinks: SocialLink[]
  appStores: AppStore[]
  companyInfo: {
    name: string
    description: string
    logo: string
    currentYear: number
  }
  quickActions: FooterLink[]
  legalLinks: FooterLink[]
  accessibility: {
    enabled: boolean
    buttonText: string
    callback?: () => void
  }
}

// Default Footer Configuration
export const defaultFooterConfig: FooterConfig = {
  companyInfo: {
    name: 'SPA.COM',
    description: 'Работа для мастеров',
    logo: 'SPA.COM',
    currentYear: new Date().getFullYear()
  },
  
  quickActions: [
    {
      id: 'become-master',
      text: 'Стать мастером',
      href: '/additem',
      icon: 'user-plus',
      description: 'Присоединяйтесь к нашей платформе'
    },
    {
      id: 'pricing',
      text: 'Тарифы',
      href: '/pricing',
      icon: 'currency'
    },
    {
      id: 'benefits',
      text: 'Преимущества',
      href: '/masters/benefits',
      icon: 'benefits'
    },
    {
      id: 'support',
      text: 'Поддержка',
      href: '/support',
      icon: 'chat'
    }
  ],
  
  sections: [
    {
      id: 'platform',
      title: 'SPA.COM',
      order: 1,
      links: [
        { id: 'about', text: 'О платформе', href: '/about' },
        { id: 'how-it-works', text: 'Как это работает', href: '/how-it-works' },
        { id: 'safety', text: 'Безопасность', href: '/safety' },
        { id: 'blog', text: 'Блог о массаже', href: '/blog' },
        { id: 'reviews', text: 'Отзывы о сервисе', href: '/reviews' },
        { id: 'contacts', text: 'Контакты', href: '/contacts' }
      ]
    },
    {
      id: 'clients',
      title: 'Клиентам',
      order: 2,
      links: [
        { id: 'search', text: 'Найти мастера', href: '/search' },
        { id: 'categories', text: 'Виды массажа', href: '/categories' },
        { id: 'gift-certificates', text: 'Подарочные сертификаты', href: '/gift-certificates' },
        { id: 'loyalty', text: 'Программа лояльности', href: '/loyalty' },
        { id: 'faq', text: 'Частые вопросы', href: '/faq' },
        { id: 'booking-help', text: 'Как забронировать', href: '/booking-help' }
      ]
    },
    {
      id: 'masters',
      title: 'Мастерам',
      order: 3,
      links: [
        { id: 'add-profile', text: 'Разместить анкету', href: '/additem' },
        { id: 'pricing', text: 'Тарифы и цены', href: '/pricing' },
        { id: 'promotion', text: 'Продвижение анкеты', href: '/promotion' },
        { id: 'education', text: 'Обучение', href: '/education' },
        { id: 'success', text: 'Истории успеха', href: '/masters/success' },
        { id: 'rules', text: 'Правила платформы', href: '/rules' }
      ]
    },
    {
      id: 'help',
      title: 'Помощь',
      order: 4,
      links: [
        { id: 'support', text: 'Служба поддержки', href: '/support' },
        { id: 'payment-methods', text: 'Способы оплаты', href: '/payment-methods' },
        { id: 'cancellation', text: 'Отмена и возврат', href: '/cancellation' },
        { id: 'disputes', text: 'Решение споров', href: '/disputes' },
        { id: 'mobile-app', text: 'Мобильное приложение', href: '/mobile-app' },
        { id: 'api', text: 'API для бизнеса', href: '/api' }
      ]
    }
  ],
  
  socialLinks: [
    {
      id: 'vk',
      name: 'VKontakte',
      href: 'https://vk.com/spa_platform',
      icon: 'vk',
      color: '#0077FF'
    },
    {
      id: 'telegram',
      name: 'Telegram',
      href: 'https://t.me/spa_platform',
      icon: 'telegram',
      color: '#2AABEE'
    },
    {
      id: 'instagram',
      name: 'Instagram',
      href: 'https://instagram.com/spa_platform',
      icon: 'instagram',
      color: '#E4405F'
    }
  ],
  
  appStores: [
    {
      id: 'app-store',
      name: 'appStore',
      href: 'https://apps.apple.com/app/spa-platform',
      image: '/images/app-store-badge.svg',
      alt: 'Скачать в App Store'
    },
    {
      id: 'google-play',
      name: 'googlePlay',
      href: 'https://play.google.com/store/apps/details?id=com.spa.platform',
      image: '/images/google-play-badge.svg',
      alt: 'Скачать в Google Play'
    }
  ],
  
  legalLinks: [
    { id: 'privacy', text: 'Конфиденциальность', href: '/privacy' },
    { id: 'terms', text: 'Условия использования', href: '/terms' },
    { id: 'sitemap', text: 'Карта сайта', href: '/sitemap' }
  ],
  
  accessibility: {
    enabled: true,
    buttonText: 'Для слабовидящих'
  }
}

// Utility functions for footer config
export const getFooterSectionById = (sections: FooterSection[], id: string): FooterSection | null => {
  return sections.find(section => section.id === id) || null
}

export const getSortedFooterSections = (sections: FooterSection[]): FooterSection[] => {
  return [...sections].sort((a, b) => a.order - b.order)
}

export const filterVisibleLinks = (links: FooterLink[]): FooterLink[] => {
  return links.filter(link => link.href && link.text)
}

export const isExternalLink = (href: string): boolean => {
  return href.startsWith('http://') || href.startsWith('https://') || href.startsWith('//')
}