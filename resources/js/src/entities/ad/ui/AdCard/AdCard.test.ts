// AdCard.test.ts
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import AdCard from './AdCard.vue'
import type { AdCardProps, Ad, AdImage } from './AdCard.types'

// Mock composables
vi.mock('@/src/shared/composables/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn()
  })
}))

vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn(),
    post: vi.fn().mockImplementation((url, data, options) => {
      // Simulate successful response
      if (options?.onSuccess) {
        options.onSuccess()
      }
      return Promise.resolve()
    })
  }
}))

describe('AdCard', () => {
  const mockImages: AdImage[] = [
    {
      id: 1,
      url: '/image1.jpg',
      thumb_url: '/thumb1.jpg',
      alt: 'Фото 1'
    },
    {
      id: 2,
      url: '/image2.jpg',
      thumb_url: '/thumb2.jpg',
      alt: 'Фото 2'
    },
    {
      id: 3,
      url: '/image3.jpg',
      thumb_url: '/thumb3.jpg',
      alt: 'Фото 3'
    }
  ]

  const mockAd: Ad = {
    id: 1,
    title: 'Расслабляющий массаж',
    name: 'Классический массаж',
    display_name: 'Анна - Массаж',
    description: 'Профессиональный массаж для полного расслабления',
    specialty: 'Классический и спортивный массаж',
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

  const defaultProps: AdCardProps = {
    ad: mockAd
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders correctly with all ad data', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    expect(wrapper.find('[data-testid="ad-card"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="ad-image"]').exists()).toBe(true)
    expect(wrapper.text()).toContain('Расслабляющий массаж')
    expect(wrapper.text()).toContain('5 000 ₽')
    expect(wrapper.text()).toContain('4.8')
    expect(wrapper.text()).toContain('(125)')
  })

  it('displays premium and verified badges', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    expect(wrapper.text()).toContain('Premium')
    expect(wrapper.text()).toContain('Проверен')
  })

  it('displays sale badge when discount exists', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    expect(wrapper.text()).toContain('Распродажа')
    expect(wrapper.text()).toContain('-15%')
  })

  it('shows old price when discount is applied', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    expect(wrapper.text()).toContain('6 000 ₽') // old price
  })

  it('displays image gallery with indicators', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const indicators = wrapper.findAll('.block.h-1.rounded-full')
    expect(indicators).toHaveLength(3) // 3 images = 3 indicators
  })

  it('handles mouse move for image gallery', async () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const imageContainer = wrapper.find('[data-testid="ad-image"]')
    
    // Mock getBoundingClientRect
    const mockGetBoundingClientRect = vi.fn(() => ({
      left: 0,
      width: 300
    }))
    
    Object.defineProperty(imageContainer.element, 'getBoundingClientRect', {
      value: mockGetBoundingClientRect
    })

    // Simulate mouse move to second image
    await imageContainer.trigger('mousemove', {
      clientX: 150 // Middle of container
    })

    const vm = wrapper.vm as any
    expect(vm.currentImage).toBe(1)
  })

  it('resets to first image on mouse leave', async () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.currentImage = 2

    const imageContainer = wrapper.find('[data-testid="ad-image"]')
    await imageContainer.trigger('mouseleave')

    expect(vm.currentImage).toBe(0)
  })

  it('formats price correctly', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    expect(vm.formatPrice(5000)).toBe('5 000')
    expect(vm.formatPrice(0)).toBe('0')
    expect(vm.formatPrice(undefined)).toBe('0')
  })

  it('returns correct description', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    expect(vm.getDescription()).toBe('Профессиональный массаж для полного расслабления')
  })

  it('falls back to specialty when no description', () => {
    const adWithoutDescription = {
      ...mockAd,
      description: undefined
    }

    const wrapper = mount(AdCard, {
      props: { ad: adWithoutDescription }
    })

    const vm = wrapper.vm as any
    expect(vm.getDescription()).toBe('Классический и спортивный массаж')
  })

  it('shows default description when no description or specialty', () => {
    const adWithoutDescriptionOrSpecialty = {
      ...mockAd,
      description: undefined,
      specialty: undefined
    }

    const wrapper = mount(AdCard, {
      props: { ad: adWithoutDescriptionOrSpecialty }
    })

    const vm = wrapper.vm as any
    expect(vm.getDescription()).toBe('Массаж и SPA услуги')
  })

  it('handles favorite toggle', async () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const favoriteButton = wrapper.find('[data-testid="favorite-button"]')
    await favoriteButton.trigger('click')

    expect(wrapper.emitted('favoriteToggled')).toBeTruthy()
    expect(wrapper.emitted('favoriteToggled')[0]).toEqual([1, true])
  })

  it('handles contact button click when contacts shown', async () => {
    // Mock window.location
    const mockHref = vi.fn()
    Object.defineProperty(window, 'location', {
      value: { href: mockHref },
      writable: true
    })

    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const contactButton = wrapper.find('[data-testid="contact-button"]')
    await contactButton.trigger('click')

    expect(wrapper.emitted('contactRequested')).toBeTruthy()
    expect(wrapper.emitted('contactRequested')[0]).toEqual([1])
  })

  it('handles contact button click when contacts hidden', async () => {
    const adWithoutContacts = {
      ...mockAd,
      show_contacts: false
    }

    const wrapper = mount(AdCard, {
      props: { ad: adWithoutContacts }
    })

    const contactButton = wrapper.find('[data-testid="contact-button"]')
    await contactButton.trigger('click')

    expect(wrapper.emitted('contactRequested')).toBeTruthy()
    // Should show toast instead of opening tel link
  })

  it('handles booking button click', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const bookingButton = wrapper.find('[data-testid="booking-button"]')
    await bookingButton.trigger('click')

    expect(wrapper.emitted('bookingRequested')).toBeTruthy()
    expect(wrapper.emitted('bookingRequested')[0]).toEqual([1])
    expect(router.visit).toHaveBeenCalledWith('/ads/1?booking=true')
  })

  it('handles ad click to open ad page', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const adImage = wrapper.find('[data-testid="ad-image"]')
    await adImage.trigger('click')

    expect(wrapper.emitted('adOpened')).toBeTruthy()
    expect(wrapper.emitted('adOpened')[0]).toEqual([1])
    expect(router.visit).toHaveBeenCalledWith('/ads/1')
  })

  it('displays correct favorite state', () => {
    const favoriteAd = {
      ...mockAd,
      is_favorite: true
    }

    const wrapper = mount(AdCard, {
      props: { ad: favoriteAd }
    })

    const favoriteButton = wrapper.find('[data-testid="favorite-button"]')
    expect(favoriteButton.classes()).toContain('text-[#f91155]')
  })

  it('displays correct non-favorite state', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const favoriteButton = wrapper.find('[data-testid="favorite-button"]')
    expect(favoriteButton.classes()).toContain('text-gray-400')
  })

  it('handles ad without rating', () => {
    const adWithoutRating = {
      ...mockAd,
      rating: undefined,
      reviews_count: undefined
    }

    const wrapper = mount(AdCard, {
      props: { ad: adWithoutRating }
    })

    expect(wrapper.text()).toContain('5.0') // Default rating
    expect(wrapper.text()).toContain('(0)') // Default reviews count
  })

  it('handles ad without images', () => {
    const adWithoutImages = {
      ...mockAd,
      images: [],
      photos: []
    }

    const wrapper = mount(AdCard, {
      props: { ad: adWithoutImages }
    })

    const vm = wrapper.vm as any
    expect(vm.currentImageUrl).toBe('/images/placeholders/master-1.jpg')
  })

  it('handles ad with mixed image formats', () => {
    const adWithMixedImages = {
      ...mockAd,
      images: [
        { url: '/image1.jpg' },
        { path: '/image2.jpg' },
        '/image3.jpg' // string format
      ]
    }

    const wrapper = mount(AdCard, {
      props: { ad: adWithMixedImages }
    })

    const vm = wrapper.vm as any
    expect(vm.allImages).toHaveLength(3)
    expect(vm.currentImageUrl).toBe('/image1.jpg')
  })

  it('displays correct location information', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    expect(wrapper.text()).toContain('Центральный')
  })

  it('falls back to location when no district', () => {
    const adWithoutDistrict = {
      ...mockAd,
      district: undefined
    }

    const wrapper = mount(AdCard, {
      props: { ad: adWithoutDistrict }
    })

    expect(wrapper.text()).toContain('Москва, Центр')
  })

  it('shows default location when no district or location', () => {
    const adWithoutLocation = {
      ...mockAd,
      district: undefined,
      location: undefined
    }

    const wrapper = mount(AdCard, {
      props: { ad: adWithoutLocation }
    })

    expect(wrapper.text()).toContain('Центр')
  })

  it('displays correct ARIA labels', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    const card = wrapper.find('[data-testid="ad-card"]')
    const favoriteButton = wrapper.find('[data-testid="favorite-button"]')
    const contactButton = wrapper.find('[data-testid="contact-button"]')
    const bookingButton = wrapper.find('[data-testid="booking-button"]')
    const adImage = wrapper.find('[data-testid="ad-image"]')

    expect(card.attributes('aria-label')).toContain('Объявление: Расслабляющий массаж')
    expect(favoriteButton.attributes('aria-label')).toBe('Добавить в избранное')
    expect(contactButton.attributes('aria-label')).toBe('Связаться с мастером')
    expect(bookingButton.attributes('aria-label')).toBe('Записаться на услугу')
    expect(adImage.attributes('aria-label')).toContain('Открыть объявление Расслабляющий массаж')
  })

  it('handles discount badge display', () => {
    const wrapper = mount(AdCard, {
      props: defaultProps
    })

    // Should show discount badge on image
    expect(wrapper.text()).toContain('-15%')
  })

  it('handles ad without discount', () => {
    const adWithoutDiscount = {
      ...mockAd,
      discount: undefined,
      discount_percent: undefined,
      old_price: undefined
    }

    const wrapper = mount(AdCard, {
      props: { ad: adWithoutDiscount }
    })

    // Should not show sale badge or discount
    expect(wrapper.text()).not.toContain('Распродажа')
    expect(wrapper.text()).not.toContain('-15%')
  })
})