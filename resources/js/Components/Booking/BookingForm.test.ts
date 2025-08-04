// BookingForm.test.ts
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BookingForm from './BookingForm.vue'
import type { 
  BookingFormProps,
  Master,
  Service,
  TimeSlot,
  BookingResult 
} from './BookingForm.types'

// Mock composables
vi.mock('@/stores/bookingStore', () => ({
  useBookingStore: () => ({
    loadTimeSlots: vi.fn().mockResolvedValue([
      { time: '10:00', available: true },
      { time: '11:00', available: true },
      { time: '12:00', available: false }
    ]),
    loadAvailableDates: vi.fn().mockResolvedValue([
      '2024-02-15',
      '2024-02-16',
      '2024-02-17'
    ]),
    createBooking: vi.fn().mockResolvedValue({
      id: 1,
      status: 'confirmed',
      masterId: 1,
      serviceId: 1,
      date: '2024-02-15',
      time: '10:00',
      totalPrice: 5000
    }),
    generateTestDates: vi.fn().mockReturnValue([
      '2024-02-15',
      '2024-02-16'
    ]),
    resetCurrentBooking: vi.fn()
  })
}))

vi.mock('@/src/shared/composables/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn()
  })
}))

vi.mock('@inertiajs/vue3', () => ({
  router: {
    visit: vi.fn()
  }
}))

// Mock v-maska directive
vi.mock('maska', () => ({
  vMaska: {
    mounted: vi.fn(),
    updated: vi.fn()
  }
}))

describe('BookingForm', () => {
  const mockService: Service = {
    id: 1,
    name: 'Классический массаж',
    price: 5000,
    duration: 60,
    description: 'Расслабляющий массаж всего тела'
  }

  const mockMaster: Master = {
    id: 1,
    name: 'Анна Иванова',
    display_name: 'Анна',
    services: [mockService],
    home_service: true,
    salon_service: true,
    salon_address: 'ул. Пушкина, д. 10',
    avatar: '/avatar.jpg',
    rating: 4.8,
    reviewsCount: 125
  }

  const defaultProps: BookingFormProps = {
    master: mockMaster
  }

  beforeEach(() => {
    // Mock CSRF token
    const metaElement = document.createElement('meta')
    metaElement.name = 'csrf-token'
    metaElement.content = 'test-csrf-token'
    document.head.appendChild(metaElement)

    vi.clearAllMocks()
  })

  afterEach(() => {
    // Clean up meta tag
    const metaElement = document.querySelector('meta[name="csrf-token"]')
    if (metaElement) {
      document.head.removeChild(metaElement)
    }
  })

  it('renders correctly with default props', () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    expect(wrapper.find('h3').text()).toBe('Выберите услугу')
    expect(wrapper.findAll('.text-lg.font-semibold')).toHaveLength(1)
    expect(wrapper.find('[data-testid="service-option"]')).toBeDefined()
  })

  it('displays master services correctly', () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const serviceLabels = wrapper.findAll('label')
    expect(serviceLabels).toHaveLength.greaterThan(0)
    
    const serviceName = wrapper.find('h4')
    expect(serviceName.text()).toBe(mockService.name)
  })

  it('handles service selection', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const serviceRadio = wrapper.find('input[type="radio"]')
    await serviceRadio.trigger('change')

    expect(serviceRadio.element.checked).toBe(true)
  })

  it('calculates total price correctly', () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    // Simulate service selection
    const vm = wrapper.vm as any
    vm.formData.service = mockService
    vm.formData.locationType = 'salon'

    expect(vm.totalPrice).toBe(5000)

    // Test with home service (should add 500)
    vm.formData.locationType = 'home'
    expect(vm.totalPrice).toBe(5500)
  })

  it('validates form fields correctly', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any

    // Test empty form validation
    const isValid = vm.validateForm()
    expect(isValid).toBe(false)
    expect(vm.errors.name).toBe('Введите ваше имя')
    expect(vm.errors.phone).toBe('Введите корректный номер телефона')
  })

  it('handles step navigation', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any

    // Initial step should be 0
    expect(vm.currentStep).toBe(0)

    // Select service to enable next step
    vm.formData.service = mockService
    await wrapper.vm.$nextTick()

    // Click next button
    const nextButton = wrapper.find('button[type="button"]:not(:disabled)')
    await nextButton.trigger('click')

    expect(vm.currentStep).toBe(1)
  })

  it('formats price correctly', () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any
    const formattedPrice = vm.formatPrice(5000)
    
    expect(formattedPrice).toMatch(/5\s?000\s?₽/)
  })

  it('formats date correctly', () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any
    const formattedDate = vm.formatDate('2024-02-15')
    
    expect(formattedDate).toContain('февраль')
    expect(formattedDate).toContain('15')
  })

  it('loads available time slots when date is selected', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any
    await vm.loadAvailableSlots('2024-02-15')

    expect(vm.availableSlots).toHaveLength(3)
    expect(vm.availableSlots[0].time).toBe('10:00')
    expect(vm.availableSlots[0].available).toBe(true)
  })

  it('handles booking submission successfully', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any

    // Fill form data
    vm.formData = {
      service: mockService,
      date: '2024-02-15',
      time: '10:00',
      locationType: 'salon',
      name: 'Иван Петров',
      phone: '+7 (999) 123-45-67',
      email: 'ivan@test.com',
      address: '',
      comment: 'Тестовый комментарий',
      paymentMethod: 'cash',
      agreement: true
    }

    await vm.handleSubmit()

    expect(wrapper.emitted('success')).toBeTruthy()
    expect(wrapper.emitted('success')[0][0]).toMatchObject({
      id: 1,
      status: 'confirmed'
    })
  })

  it('shows loading state during submission', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any
    vm.isSubmitting = true
    await wrapper.vm.$nextTick()

    const submitButton = wrapper.find('button[type="submit"]')
    expect(submitButton.text()).toContain('Оформляем...')
    expect(submitButton.attributes('disabled')).toBeDefined()
  })

  it('displays different location options for home service', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any
    
    // Select service and move to step 1
    vm.formData.service = mockService
    vm.currentStep = 1
    await wrapper.vm.$nextTick()

    const locationOptions = wrapper.findAll('input[type="radio"][value]')
    const salonOption = locationOptions.find(option => 
      option.attributes('value') === 'salon'
    )
    const homeOption = locationOptions.find(option => 
      option.attributes('value') === 'home'
    )

    expect(salonOption).toBeDefined()
    expect(homeOption).toBeDefined()
  })

  it('shows address field when home service is selected', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any
    
    // Move to contacts step and select home service
    vm.currentStep = 2
    vm.formData.locationType = 'home'
    await wrapper.vm.$nextTick()

    const addressField = wrapper.find('input[placeholder*="Улица"]')
    expect(addressField.exists()).toBe(true)
  })

  it('validates required fields in contact form', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any
    
    // Test phone validation
    vm.formData.phone = '+7 (999) 123'  // Too short
    const isValid = vm.validateForm()
    
    expect(isValid).toBe(false)
    expect(vm.errors.phone).toBe('Введите корректный номер телефона')
  })

  it('shows booking summary on final step', async () => {
    const wrapper = mount(BookingForm, {
      props: defaultProps,
      global: {
        stubs: {
          BookingCalendar: true
        }
      }
    })

    const vm = wrapper.vm as any
    
    // Fill data and go to final step
    vm.formData = {
      service: mockService,
      date: '2024-02-15',
      time: '10:00',
      locationType: 'salon',
      name: 'Иван Петров',
      phone: '+7 (999) 123-45-67',
      email: '',
      address: '',
      comment: '',
      paymentMethod: 'cash',
      agreement: true
    }
    vm.currentStep = 3
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Проверьте данные записи')
    expect(wrapper.text()).toContain(mockService.name)
    expect(wrapper.text()).toContain('Иван Петров')
  })
})