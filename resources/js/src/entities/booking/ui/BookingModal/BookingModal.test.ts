// BookingModal.test.ts
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import BookingModal from './BookingModal.vue'
import type { BookingModalProps, Master, Service } from './BookingModal.types'

// Mock composables
vi.mock('@/src/shared/composables/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn()
  })
}))

// Mock Inertia router
vi.mock('@inertiajs/vue3', () => ({
  router: {
    post: vi.fn().mockImplementation((url, data, options) => {
      if (options?.onSuccess) {
        options.onSuccess()
      }
      return Promise.resolve()
    })
  }
}))

// Mock axios
vi.mock('axios', () => ({
  default: {
    get: vi.fn().mockResolvedValue({
      data: {
        slots: ['10:00', '11:00', '12:00', '14:00', '15:00']
      }
    })
  }
}))

// Mock icons
vi.mock('@heroicons/vue/24/outline', () => ({
  XMarkIcon: {
    name: 'XMarkIcon',
    template: '<svg data-testid="x-mark-icon"></svg>'
  }
}))

// Mock VueDatePicker
vi.mock('@vuepic/vue-datepicker', () => ({
  default: {
    name: 'VueDatePicker',
    template: '<input data-testid="date-picker" />',
    props: ['modelValue', 'minDate', 'disabledDates', 'locale', 'format', 'placeholder', 'enableTimePicker'],
    emits: ['update:modelValue']
  }
}))

// Mock vue-tel-input
vi.mock('vue-tel-input', () => ({
  default: {
    name: 'VueTelInput',
    template: '<input data-testid="tel-input" />',
    props: ['modelValue', 'preferredCountries', 'onlyCountries', 'mode'],
    emits: ['update:modelValue']
  }
}))

describe('BookingModal', () => {
  const mockServices: Service[] = [
    {
      id: 1,
      name: 'Классический массаж',
      price: 3000,
      duration: 60,
      description: 'Расслабляющий массаж'
    },
    {
      id: 2,
      name: 'Антицеллюлитный массаж',
      price: 4000,
      duration: 90,
      description: 'Коррекция фигуры'
    }
  ]

  const mockMaster: Master = {
    id: 1,
    display_name: 'Анна Петрова',
    avatar: '/images/master1.jpg',
    district: 'Центральный район',
    home_service: true,
    salon_service: true,
    salon_address: 'ул. Пушкина, д. 10',
    services: mockServices
  }

  const defaultProps: BookingModalProps = {
    master: mockMaster,
    service: mockServices[0]
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders correctly with master and service data', () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    expect(wrapper.find('[data-testid="booking-modal-wrapper"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="booking-modal-title"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="master-info"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="booking-form"]').exists()).toBe(true)
  })

  it('displays master information correctly', () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const masterName = wrapper.find('[data-testid="master-name"]')
    const masterDistrict = wrapper.find('[data-testid="master-district"]')
    const masterAvatar = wrapper.find('[data-testid="master-avatar"]')

    expect(masterName.text()).toBe('Анна Петрова')
    expect(masterDistrict.text()).toBe('Центральный район')
    expect(masterAvatar.attributes('src')).toBe('/images/master1.jpg')
  })

  it('pre-selects service when provided', () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const serviceSelect = wrapper.find('[data-testid="service-select"]')
    expect(serviceSelect.element.value).toBe('1')
  })

  it('calculates total price correctly', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    // Test service price
    const servicePrice = wrapper.find('[data-testid="service-price"]')
    expect(servicePrice.text()).toBe('3000₽')

    // Test total without home service
    let totalPrice = wrapper.find('[data-testid="total-price"]')
    expect(totalPrice.text()).toBe('3000₽')

    // Enable home service
    const vm = wrapper.vm as any
    vm.form.is_home_service = true
    await wrapper.vm.$nextTick()

    // Test home service fee
    const homeServiceFee = wrapper.find('[data-testid="home-service-fee"]')
    expect(homeServiceFee.text()).toBe('500₽')

    // Test total with home service
    totalPrice = wrapper.find('[data-testid="total-price"]')
    expect(totalPrice.text()).toBe('3500₽')
  })

  it('validates form fields correctly', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    
    // Clear required fields
    vm.form.service_id = ''
    vm.form.client_name = ''
    vm.form.client_phone = ''
    await wrapper.vm.$nextTick()

    // Try to submit
    const submitButton = wrapper.find('[data-testid="submit-button"]')
    await submitButton.trigger('click')

    // Check if validation errors appear
    await wrapper.vm.$nextTick()
    
    expect(wrapper.find('[data-testid="service-error"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="name-error"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="phone-error"]').exists()).toBe(true)
  })

  it('validates address when home service is selected', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.form.is_home_service = true
    vm.form.address = ''
    await wrapper.vm.$nextTick()

    // Try to submit
    const submitButton = wrapper.find('[data-testid="submit-button"]')
    await submitButton.trigger('click')

    await wrapper.vm.$nextTick()
    
    // Should show address error when home service is selected
    expect(wrapper.find('[data-testid="address-error"]').exists()).toBe(true)
  })

  it('disables submit button when form is invalid', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.form.client_name = ''
    await wrapper.vm.$nextTick()

    const submitButton = wrapper.find('[data-testid="submit-button"]')
    expect(submitButton.attributes('disabled')).toBeDefined()
  })

  it('enables submit button when form is valid', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    // Fill all required fields
    vm.form.service_id = 1
    vm.form.booking_date = new Date()
    vm.form.start_time = '10:00'
    vm.form.client_name = 'Иван Иванов'
    vm.form.client_phone = '+79991234567'
    vm.form.is_home_service = false
    await wrapper.vm.$nextTick()

    const submitButton = wrapper.find('[data-testid="submit-button"]')
    expect(submitButton.attributes('disabled')).toBeUndefined()
  })

  it('fetches available slots when date and service are selected', async () => {
    const axios = await import('axios')
    
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.form.booking_date = new Date('2024-02-01')
    vm.form.service_id = 1
    
    await vm.fetchAvailableSlots()

    expect(axios.default.get).toHaveBeenCalledWith('/api/bookings/available-slots', {
      params: {
        master_profile_id: 1,
        service_id: 1,
        date: vm.form.booking_date
      }
    })
  })

  it('displays loading state while fetching slots', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.state.loadingSlots = true
    await wrapper.vm.$nextTick()

    const timeSelect = wrapper.find('[data-testid="time-select"]')
    expect(timeSelect.text()).toContain('Загрузка...')
  })

  it('submits booking with correct data', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    // Fill form with valid data
    vm.form.service_id = 1
    vm.form.booking_date = new Date('2024-02-01')
    vm.form.start_time = '10:00'
    vm.form.client_name = 'Иван Иванов'
    vm.form.client_phone = '+79991234567'
    vm.form.client_email = 'ivan@example.com'
    vm.form.is_home_service = false
    vm.form.payment_method = 'cash'

    await vm.submitBooking()

    expect(router.post).toHaveBeenCalledWith('/bookings', vm.form, expect.any(Object))
  })

  it('emits success and close events after successful booking', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    // Fill form with valid data
    vm.form.service_id = 1
    vm.form.booking_date = new Date('2024-02-01')
    vm.form.start_time = '10:00'
    vm.form.client_name = 'Иван Иванов'
    vm.form.client_phone = '+79991234567'
    vm.form.is_home_service = false

    await vm.submitBooking()

    expect(wrapper.emitted('success')).toBeTruthy()
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('handles API errors gracefully', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    // Mock API error
    router.post.mockImplementationOnce((url, data, options) => {
      if (options?.onError) {
        options.onError({ client_name: 'Имя обязательно' })
      }
      return Promise.resolve()
    })

    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    await vm.submitBooking()

    // Should handle error without crashing
    expect(wrapper.exists()).toBe(true)
  })

  it('closes modal when close button is clicked', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const closeButton = wrapper.find('[data-testid="booking-modal-close"]')
    await closeButton.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('closes modal when cancel button is clicked', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const cancelButton = wrapper.find('[data-testid="cancel-button"]')
    await cancelButton.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('closes modal when overlay is clicked', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const overlay = wrapper.find('[data-testid="booking-modal-overlay"]')
    await overlay.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('does not close when modal content is clicked', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const content = wrapper.find('[data-testid="booking-modal-content"]')
    await content.trigger('click')

    expect(wrapper.emitted('close')).toBeFalsy()
  })

  it('handles keyboard navigation (Escape)', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const modal = wrapper.find('[data-testid="booking-modal-wrapper"]')
    await modal.trigger('keydown', { key: 'Escape' })

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('shows loading state during form submission', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.state.loading = true
    await wrapper.vm.$nextTick()

    const submitButton = wrapper.find('[data-testid="submit-button"]')
    expect(submitButton.text()).toContain('Отправка...')
    expect(submitButton.attributes('disabled')).toBeDefined()
  })

  it('validates email format when provided', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.form.client_email = 'invalid-email'
    
    const isValid = vm.validateForm()
    expect(isValid).toBe(false)
    expect(vm.formErrors.client_email).toBe('Некорректный email')
  })

  it('handles service selection change', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const serviceSelect = wrapper.find('[data-testid="service-select"]')
    await serviceSelect.setValue('2')

    const servicePrice = wrapper.find('[data-testid="service-price"]')
    expect(servicePrice.text()).toBe('4000₽')
  })

  it('clears time slots when date changes', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.form.start_time = '10:00'
    vm.state.availableSlots = ['10:00', '11:00']
    
    // Change date
    vm.form.booking_date = new Date('2024-02-02')
    await wrapper.vm.$nextTick()

    expect(vm.form.start_time).toBe('')
    expect(vm.state.availableSlots).toEqual([])
  })

  it('resets address when switching from home to salon service', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.form.is_home_service = true
    vm.form.address = 'ул. Тестовая, 123'
    
    // Switch to salon service
    vm.form.is_home_service = false
    vm.handleServiceTypeChange()

    expect(vm.form.address).toBe('')
  })

  it('displays general error message', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    vm.formErrors.general = 'Общая ошибка системы'
    await wrapper.vm.$nextTick()

    const generalError = wrapper.find('[data-testid="general-error"]')
    expect(generalError.exists()).toBe(true)
    expect(generalError.text()).toBe('Общая ошибка системы')
  })

  it('has proper ARIA attributes', () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const modal = wrapper.find('[data-testid="booking-modal-wrapper"]')
    const title = wrapper.find('[data-testid="booking-modal-title"]')
    const closeButton = wrapper.find('[data-testid="booking-modal-close"]')

    expect(modal.attributes('role')).toBe('dialog')
    expect(modal.attributes('aria-modal')).toBe('true')
    expect(modal.attributes('aria-labelledby')).toBe('booking-modal-title')
    expect(title.attributes('id')).toBe('booking-modal-title')
    expect(closeButton.attributes('aria-label')).toBe('Закрыть модальное окно')
  })

  it('handles form reset correctly', async () => {
    const wrapper = mount(BookingModal, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    // Fill form
    vm.form.client_name = 'Тест'
    vm.form.client_phone = '+79991234567'
    vm.form.client_email = 'test@example.com'
    vm.formErrors.client_name = 'Ошибка'

    // Clear errors
    vm.clearErrors()

    expect(vm.formErrors).toEqual({})
  })

  it('handles missing services gracefully', () => {
    const masterWithoutServices = {
      ...mockMaster,
      services: []
    }

    const wrapper = mount(BookingModal, {
      props: {
        master: masterWithoutServices
      }
    })

    const serviceSelect = wrapper.find('[data-testid="service-select"]')
    expect(serviceSelect.exists()).toBe(true)
  })
})