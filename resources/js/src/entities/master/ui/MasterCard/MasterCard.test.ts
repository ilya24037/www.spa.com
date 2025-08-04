// MasterCard.test.ts
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import MasterCard from './MasterCard.vue'
import type { MasterCardProps, Master, Service } from './MasterCard.types'

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

describe('MasterCard', () => {
  const mockServices: Service[] = [
    {
      id: 1,
      name: 'Классический массаж',
      price: 5000,
      duration: 60,
      description: 'Расслабляющий массаж'
    },
    {
      id: 2,
      name: 'Спортивный массаж',
      price: 6000,
      duration: 90,
      description: 'Массаж для спортсменов'
    }
  ]

  const mockMaster: Master = {
    id: 1,
    name: 'Анна Иванова',
    display_name: 'Анна',
    avatar: '/avatar.jpg',
    specialty: 'Массаж и релаксация',
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

  const defaultProps: MasterCardProps = {
    master: mockMaster
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders correctly with all master data', () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    expect(wrapper.find('[data-testid="master-card"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="master-avatar"]').attributes('src')).toBe('/avatar.jpg')
    expect(wrapper.text()).toContain('Анна')
    expect(wrapper.text()).toContain('5 000 ₽')
    expect(wrapper.text()).toContain('4.8')
    expect(wrapper.text()).toContain('(125)')
  })

  it('displays premium and verified badges', () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    expect(wrapper.text()).toContain('Premium')
    expect(wrapper.text()).toContain('Проверен')
  })

  it('shows online status when master is online', () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    expect(wrapper.text()).toContain('Онлайн')
  })

  it('displays location information', () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    expect(wrapper.text()).toContain('Центральный')
    expect(wrapper.text()).toContain('м. Площадь Революции')
  })

  it('shows services text from specialty', () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    expect(wrapper.text()).toContain('Массаж и релаксация')
  })

  it('shows services text from services array when no specialty', () => {
    const masterWithoutSpecialty = {
      ...mockMaster,
      specialty: undefined
    }

    const wrapper = mount(MasterCard, {
      props: { master: masterWithoutSpecialty }
    })

    expect(wrapper.text()).toContain('Классический массаж, Спортивный массаж')
  })

  it('shows default services text when no specialty or services', () => {
    const masterWithoutServices = {
      ...mockMaster,
      specialty: undefined,
      services: []
    }

    const wrapper = mount(MasterCard, {
      props: { master: masterWithoutServices }
    })

    expect(wrapper.text()).toContain('Массаж и SPA услуги')
  })

  it('handles image error correctly', async () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    const img = wrapper.find('[data-testid="master-avatar"]')
    await img.trigger('error')

    expect((wrapper.vm as any).imageError).toBe(true)
  })

  it('uses placeholder image when image error occurs', async () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    // Trigger image error
    const vm = wrapper.vm as any
    vm.imageError = true
    await wrapper.vm.$nextTick()

    expect(vm.masterPhoto).toBe('/images/placeholders/master-1.jpg')
  })

  it('formats price correctly', () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    expect(vm.formatPrice(5000)).toBe('5 000')
    expect(vm.formatPrice(0)).toBe('0')
    expect(vm.formatPrice(undefined)).toBe('0')
  })

  it('handles favorite toggle', async () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    const favoriteButton = wrapper.find('[data-testid="favorite-button"]')
    await favoriteButton.trigger('click')

    expect(wrapper.emitted('favoriteToggled')).toBeTruthy()
    expect(wrapper.emitted('favoriteToggled')[0]).toEqual([1, true])
  })

  it('handles phone button click when contacts shown', async () => {
    // Mock window.location
    const mockHref = vi.fn()
    Object.defineProperty(window, 'location', {
      value: { href: mockHref },
      writable: true
    })

    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    const phoneButton = wrapper.find('[data-testid="phone-button"]')
    await phoneButton.trigger('click')

    expect(wrapper.emitted('phoneRequested')).toBeTruthy()
    expect(wrapper.emitted('phoneRequested')[0]).toEqual([1])
  })

  it('handles phone button click when contacts hidden', async () => {
    const masterWithoutContacts = {
      ...mockMaster,
      show_contacts: false
    }

    const wrapper = mount(MasterCard, {
      props: { master: masterWithoutContacts }
    })

    const phoneButton = wrapper.find('[data-testid="phone-button"]')
    await phoneButton.trigger('click')

    expect(wrapper.emitted('phoneRequested')).toBeTruthy()
    // Should show toast instead of opening tel link
  })

  it('handles booking button click', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    const bookingButton = wrapper.find('[data-testid="booking-button"]')
    await bookingButton.trigger('click')

    expect(wrapper.emitted('bookingRequested')).toBeTruthy()
    expect(wrapper.emitted('bookingRequested')[0]).toEqual([1])
    expect(router.visit).toHaveBeenCalledWith('/masters/1?booking=true')
  })

  it('handles card click to go to profile', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    const card = wrapper.find('[data-testid="master-card"]')
    await card.trigger('click')

    expect(wrapper.emitted('profileVisited')).toBeTruthy()
    expect(wrapper.emitted('profileVisited')[0]).toEqual([1])
    expect(router.visit).toHaveBeenCalledWith('/masters/1')
  })

  it('displays correct favorite state', () => {
    const favoriteMaster = {
      ...mockMaster,
      is_favorite: true
    }

    const wrapper = mount(MasterCard, {
      props: { master: favoriteMaster }
    })

    const favoriteButton = wrapper.find('[data-testid="favorite-button"]')
    expect(favoriteButton.classes()).toContain('text-red-500')
  })

  it('displays correct non-favorite state', () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    const favoriteButton = wrapper.find('[data-testid="favorite-button"]')
    expect(favoriteButton.classes()).toContain('text-gray-400')
  })

  it('handles master without rating', () => {
    const masterWithoutRating = {
      ...mockMaster,
      rating: undefined,
      reviews_count: undefined
    }

    const wrapper = mount(MasterCard, {
      props: { master: masterWithoutRating }
    })

    expect(wrapper.text()).toContain('5.0') // Default rating
    expect(wrapper.text()).toContain('(0)') // Default reviews count
  })

  it('handles master without photo', () => {
    const masterWithoutPhoto = {
      ...mockMaster,
      avatar: undefined,
      main_photo: undefined,
      photo: undefined
    }

    const wrapper = mount(MasterCard, {
      props: { master: masterWithoutPhoto }
    })

    const vm = wrapper.vm as any
    expect(vm.masterPhoto).toBe('/images/placeholders/master-1.jpg')
  })

  it('uses main_photo when avatar is not available', () => {
    const masterWithMainPhoto = {
      ...mockMaster,
      avatar: undefined,
      main_photo: '/main-photo.jpg'
    }

    const wrapper = mount(MasterCard, {
      props: { master: masterWithMainPhoto }
    })

    const vm = wrapper.vm as any
    expect(vm.masterPhoto).toBe('/main-photo.jpg')
  })

  it('displays correct ARIA labels', () => {
    const wrapper = mount(MasterCard, {
      props: defaultProps
    })

    const card = wrapper.find('[data-testid="master-card"]')
    const favoriteButton = wrapper.find('[data-testid="favorite-button"]')
    const phoneButton = wrapper.find('[data-testid="phone-button"]')
    const bookingButton = wrapper.find('[data-testid="booking-button"]')

    expect(card.attributes('aria-label')).toContain('Профиль мастера Анна')
    expect(favoriteButton.attributes('aria-label')).toBe('Добавить в избранное')
    expect(phoneButton.attributes('aria-label')).toBe('Показать телефон мастера')
    expect(bookingButton.attributes('aria-label')).toBe('Записаться к мастеру')
  })
})