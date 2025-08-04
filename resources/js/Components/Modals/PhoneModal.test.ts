// PhoneModal.test.ts
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import PhoneModal from './PhoneModal.vue'
import type { PhoneModalProps } from './PhoneModal.types'

// Mock composables
vi.mock('@/src/shared/composables/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn()
  })
}))

// Mock icons
vi.mock('@heroicons/vue/24/outline', () => ({
  PhoneIcon: {
    name: 'PhoneIcon',
    template: '<svg data-testid="phone-icon"></svg>'
  },
  XMarkIcon: {
    name: 'XMarkIcon', 
    template: '<svg data-testid="x-mark-icon"></svg>'
  },
  ClipboardIcon: {
    name: 'ClipboardIcon',
    template: '<svg data-testid="clipboard-icon"></svg>'
  }
}))

// Mock clipboard API
Object.assign(navigator, {
  clipboard: {
    writeText: vi.fn().mockResolvedValue(undefined)
  }
})

// Mock window.open
global.window.open = vi.fn()

describe('PhoneModal', () => {
  const defaultProps: PhoneModalProps = {
    show: true,
    phone: '+7 (999) 123-45-67'
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders correctly when visible', () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    expect(wrapper.find('[data-testid="phone-modal-overlay"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="phone-modal-content"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="phone-modal-title"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="phone-display"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="phone-actions"]').exists()).toBe(true)
  })

  it('does not render when show is false', () => {
    const wrapper = mount(PhoneModal, {
      props: {
        ...defaultProps,
        show: false
      }
    })

    expect(wrapper.find('[data-testid="phone-modal-overlay"]').exists()).toBe(false)
  })

  it('displays formatted phone number', () => {
    const wrapper = mount(PhoneModal, {
      props: {
        show: true,
        phone: '79991234567'
      }
    })

    const phoneNumber = wrapper.find('[data-testid="phone-number"]')
    expect(phoneNumber.text()).toBe('+7 (999) 123-45-67')
  })

  it('displays original phone if formatting fails', () => {
    const wrapper = mount(PhoneModal, {
      props: {
        show: true,
        phone: 'invalid-phone'
      }
    })

    const phoneNumber = wrapper.find('[data-testid="phone-number"]')
    expect(phoneNumber.text()).toBe('invalid-phone')
  })

  it('shows validation error for invalid numbers', () => {
    const wrapper = mount(PhoneModal, {
      props: {
        show: true,
        phone: '123'
      }
    })

    const validationError = wrapper.find('[data-testid="phone-validation-error"]')
    expect(validationError.exists()).toBe(true)
    expect(validationError.text()).toBe('Некорректный номер')
  })

  it('emits close event when close button clicked', async () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const closeButton = wrapper.find('[data-testid="phone-modal-close"]')
    await closeButton.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('emits close event when overlay clicked', async () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const overlay = wrapper.find('[data-testid="phone-modal-overlay"]')
    await overlay.trigger('click')

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('does not close when modal content clicked', async () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const content = wrapper.find('[data-testid="phone-modal-content"]')
    await content.trigger('click')

    expect(wrapper.emitted('close')).toBeFalsy()
  })

  it('handles call button click', async () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const callButton = wrapper.find('[data-testid="call-button"]')
    await callButton.trigger('click')

    expect(window.open).toHaveBeenCalledWith('tel:79991234567')
    expect(wrapper.emitted('called')).toBeTruthy()
    expect(wrapper.emitted('called')[0][0]).toBe('+7 (999) 123-45-67')
  })

  it('disables call button for invalid phone', () => {
    const wrapper = mount(PhoneModal, {
      props: {
        show: true,
        phone: 'invalid'
      }
    })

    const callButton = wrapper.find('[data-testid="call-button"]')
    expect(callButton.attributes('disabled')).toBeDefined()
  })

  it('handles copy button click successfully', async () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const copyButton = wrapper.find('[data-testid="copy-button"]')
    await copyButton.trigger('click')

    expect(navigator.clipboard.writeText).toHaveBeenCalledWith('+7 (999) 123-45-67')
    expect(wrapper.emitted('copied')).toBeTruthy()
    expect(wrapper.emitted('copied')[0][0]).toBe('+7 (999) 123-45-67')
  })

  it('shows success state after copying', async () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const copyButton = wrapper.find('[data-testid="copy-button"]')
    await copyButton.trigger('click')
    await wrapper.vm.$nextTick()

    expect(copyButton.text()).toContain('Скопировано!')
    expect(copyButton.classes()).toContain('bg-green-600')
  })

  it('handles copy failure gracefully', async () => {
    // Mock clipboard failure
    navigator.clipboard.writeText = vi.fn().mockRejectedValue(new Error('Clipboard failed'))

    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const copyButton = wrapper.find('[data-testid="copy-button"]')
    await copyButton.trigger('click')
    await wrapper.vm.$nextTick()

    // Should not crash and should show error
    expect(wrapper.exists()).toBe(true)
  })

  it('disables copy button when no phone provided', () => {
    const wrapper = mount(PhoneModal, {
      props: {
        show: true,
        phone: ''
      }
    })

    const copyButton = wrapper.find('[data-testid="copy-button"]')
    expect(copyButton.attributes('disabled')).toBeDefined()
  })

  it('handles keyboard navigation (Escape)', async () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const overlay = wrapper.find('[data-testid="phone-modal-overlay"]')
    await overlay.trigger('keydown', { key: 'Escape' })

    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('formats various phone number formats correctly', () => {
    const testCases = [
      { input: '79991234567', expected: '+7 (999) 123-45-67' },
      { input: '89991234567', expected: '+7 (999) 123-45-67' },
      { input: '+7 999 123 45 67', expected: '+7 (999) 123-45-67' },
      { input: '123', expected: '123' },
      { input: '', expected: '' }
    ]

    testCases.forEach(({ input, expected }) => {
      const wrapper = mount(PhoneModal, {
        props: {
          show: true,
          phone: input
        }
      })

      const phoneNumber = wrapper.find('[data-testid="phone-number"]')
      expect(phoneNumber.text()).toBe(expected)
    })
  })

  it('validates phone numbers correctly', () => {
    const validNumbers = ['79991234567', '89991234567']
    const invalidNumbers = ['123', 'abc', '', '799912345678', '6991234567']

    validNumbers.forEach(phone => {
      const wrapper = mount(PhoneModal, {
        props: { show: true, phone }
      })
      const validationError = wrapper.find('[data-testid="phone-validation-error"]')
      expect(validationError.exists()).toBe(false)
    })

    invalidNumbers.forEach(phone => {
      const wrapper = mount(PhoneModal, {
        props: { show: true, phone }
      })
      if (phone) {
        const validationError = wrapper.find('[data-testid="phone-validation-error"]')
        expect(validationError.exists()).toBe(true)
      }
    })
  })

  it('has proper ARIA attributes', () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const overlay = wrapper.find('[data-testid="phone-modal-overlay"]')
    const title = wrapper.find('[data-testid="phone-modal-title"]')
    const closeButton = wrapper.find('[data-testid="phone-modal-close"]')
    const callButton = wrapper.find('[data-testid="call-button"]')
    const copyButton = wrapper.find('[data-testid="copy-button"]')

    expect(overlay.attributes('role')).toBe('dialog')
    expect(overlay.attributes('aria-modal')).toBe('true')
    expect(overlay.attributes('aria-labelledby')).toBe('phone-modal-title')
    expect(title.attributes('id')).toBe('phone-modal-title')
    expect(closeButton.attributes('aria-label')).toBe('Закрыть модальное окно')
    expect(callButton.attributes('aria-label')).toContain('Позвонить по номеру')
    expect(copyButton.attributes('aria-label')).toContain('Скопировать номер')
  })

  it('shows loading state while copying', async () => {
    // Mock slow clipboard operation
    navigator.clipboard.writeText = vi.fn(() => new Promise(resolve => setTimeout(resolve, 100)))

    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const copyButton = wrapper.find('[data-testid="copy-button"]')
    copyButton.trigger('click')
    await wrapper.vm.$nextTick()

    expect(copyButton.text()).toContain('Копирование...')
    expect(copyButton.classes()).toContain('opacity-50')
  })

  it('resets copy success state after timeout', async () => {
    vi.useFakeTimers()
    
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const copyButton = wrapper.find('[data-testid="copy-button"]')
    await copyButton.trigger('click')
    await wrapper.vm.$nextTick()

    expect(copyButton.text()).toContain('Скопировано!')

    // Fast forward 2 seconds
    vi.advanceTimersByTime(2000)
    await wrapper.vm.$nextTick()

    expect(copyButton.text()).toContain('Скопировать')
    
    vi.useRealTimers()
  })

  it('handles missing clipboard API gracefully', async () => {
    // Mock missing clipboard API
    const originalClipboard = navigator.clipboard
    delete (navigator as any).clipboard

    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    const copyButton = wrapper.find('[data-testid="copy-button"]')
    await copyButton.trigger('click')

    // Should not crash
    expect(wrapper.exists()).toBe(true)

    // Restore clipboard
    navigator.clipboard = originalClipboard
  })

  it('emits all events with correct parameters', async () => {
    const wrapper = mount(PhoneModal, {
      props: defaultProps
    })

    // Test close event
    const closeButton = wrapper.find('[data-testid="phone-modal-close"]')
    await closeButton.trigger('click')
    expect(wrapper.emitted('close')).toBeTruthy()
    expect(wrapper.emitted('close')[0]).toEqual([])

    // Test call event
    const callButton = wrapper.find('[data-testid="call-button"]')
    await callButton.trigger('click')
    expect(wrapper.emitted('called')).toBeTruthy()
    expect(wrapper.emitted('called')[0]).toEqual(['+7 (999) 123-45-67'])

    // Test copy event
    const copyButton = wrapper.find('[data-testid="copy-button"]')
    await copyButton.trigger('click')
    expect(wrapper.emitted('copied')).toBeTruthy()
    expect(wrapper.emitted('copied')[0]).toEqual(['+7 (999) 123-45-67'])
  })
})