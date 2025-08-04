// ItemCard.test.ts
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import ItemCard from './ItemCard.vue'
import type { ItemCardProps, Item, ItemImage } from './ItemCard.types'

// Mock composables and components
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
      if (options?.onSuccess) {
        options.onSuccess()
      }
      return Promise.resolve()
    }),
    delete: vi.fn().mockImplementation((url, options) => {
      if (options?.onSuccess) {
        options.onSuccess({ url: '/my-ads', props: {} })
      }
      return Promise.resolve()
    })
  },
  Link: {
    template: '<a :href="href"><slot /></a>',
    props: ['href']
  }
}))

// Mock child components
vi.mock('../Cards/ItemImage.vue', () => ({
  default: {
    name: 'ItemImage',
    template: '<div data-testid="item-image">Item Image</div>',
    props: ['item', 'itemUrl']
  }
}))

vi.mock('../Cards/ItemContent.vue', () => ({
  default: {
    name: 'ItemContent', 
    template: '<div data-testid="item-content">Item Content</div>',
    props: ['item', 'itemUrl']
  }
}))

vi.mock('../Cards/ItemStats.vue', () => ({
  default: {
    name: 'ItemStats',
    template: '<div data-testid="item-stats">Item Stats</div>',
    props: ['item']
  }
}))

vi.mock('../Cards/ItemActions.vue', () => ({
  default: {
    name: 'ItemActions',
    template: `
      <div data-testid="item-actions">
        <button @click="$emit('pay')" data-testid="pay-button">Pay</button>
        <button @click="$emit('promote')" data-testid="promote-button">Promote</button>
        <button @click="$emit('edit')" data-testid="edit-button">Edit</button>
        <button @click="$emit('deactivate')" data-testid="deactivate-button">Deactivate</button>
        <button @click="$emit('delete', $event)" data-testid="delete-button">Delete</button>
      </div>
    `,
    props: ['item'],
    emits: ['pay', 'promote', 'edit', 'deactivate', 'delete']
  }
}))

vi.mock('../UI/ConfirmModal.vue', () => ({
  default: {
    name: 'ConfirmModal',
    template: `
      <div v-if="show" data-testid="confirm-modal">
        <div>{{ title }}</div>
        <div>{{ message }}</div>
        <button @click="$emit('confirm')" data-testid="confirm-button">{{ confirmText }}</button>
        <button @click="$emit('cancel')" data-testid="cancel-button">{{ cancelText }}</button>
      </div>
    `,
    props: ['show', 'title', 'message', 'confirmText', 'cancelText'],
    emits: ['confirm', 'cancel']
  }
}))

describe('ItemCard', () => {
  const mockImages: ItemImage[] = [
    {
      id: 1,
      url: '/image1.jpg',
      thumb_url: '/thumb1.jpg',
      alt: 'Фото 1'
    }
  ]

  const mockItem: Item = {
    id: 1,
    title: 'Расслабляющий массаж',
    name: 'Классический массаж',
    display_name: 'Анна - Массаж',
    description: 'Профессиональный массаж для расслабления',
    price: 5000,
    price_from: 4000,
    status: 'active',
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
    images: mockImages,
    created_at: '2024-01-01T00:00:00Z',
    updated_at: '2024-01-02T00:00:00Z',
    views_count: 100,
    favorites_count: 15
  }

  const defaultProps: ItemCardProps = {
    item: mockItem
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('renders correctly with all item data', () => {
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    expect(wrapper.find('[data-testid="item-card"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="item-image"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="item-content"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="item-stats"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="item-actions"]').exists()).toBe(true)
  })

  it('generates correct URL for active item', () => {
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    expect(vm.itemUrl).toBe('/ads/1')
  })

  it('generates correct URL for draft item', () => {
    const draftItem = {
      ...mockItem,
      status: 'draft' as const
    }

    const wrapper = mount(ItemCard, {
      props: { item: draftItem }
    })

    const vm = wrapper.vm as any
    expect(vm.itemUrl).toBe('/draft/1')
  })

  it('handles pay action', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const payButton = wrapper.find('[data-testid="pay-button"]')
    await payButton.trigger('click')

    expect(router.visit).toHaveBeenCalledWith('/payment/select-plan?item_id=1')
  })

  it('handles promote action', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const promoteButton = wrapper.find('[data-testid="promote-button"]')
    await promoteButton.trigger('click')

    expect(router.visit).toHaveBeenCalledWith('/payment/promotion?item_id=1')
  })

  it('handles edit action', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const editButton = wrapper.find('[data-testid="edit-button"]')
    await editButton.trigger('click')

    expect(router.visit).toHaveBeenCalledWith('/ads/1/edit')
  })

  it('blocks edit when delete modal is open', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    // Open delete modal first
    const vm = wrapper.vm as any
    vm.showDeleteModal = true
    await wrapper.vm.$nextTick()

    const editButton = wrapper.find('[data-testid="edit-button"]')
    await editButton.trigger('click')

    // Should not navigate when modal is open
    expect(router.visit).not.toHaveBeenCalledWith('/ads/1/edit')
  })

  it('handles deactivate action', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const deactivateButton = wrapper.find('[data-testid="deactivate-button"]')
    await deactivateButton.trigger('click')

    expect(router.post).toHaveBeenCalledWith(
      '/my-ads/1/deactivate',
      {},
      expect.objectContaining({
        preserveState: true
      })
    )

    expect(wrapper.emitted('item-updated')).toBeTruthy()
    expect(wrapper.emitted('item-updated')[0][0]).toMatchObject({
      ...mockItem,
      status: 'archived'
    })
  })

  it('handles delete button click', async () => {
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const deleteButton = wrapper.find('[data-testid="delete-button"]')
    await deleteButton.trigger('click')

    const vm = wrapper.vm as any
    expect(vm.showDeleteModal).toBe(true)
  })

  it('shows delete modal when delete is clicked', async () => {
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const deleteButton = wrapper.find('[data-testid="delete-button"]')
    await deleteButton.trigger('click')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="confirm-modal"]').exists()).toBe(true)
  })

  it('handles delete confirmation', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    // Open delete modal
    const deleteButton = wrapper.find('[data-testid="delete-button"]')
    await deleteButton.trigger('click')
    await wrapper.vm.$nextTick()

    // Confirm deletion
    const confirmButton = wrapper.find('[data-testid="confirm-button"]')
    await confirmButton.trigger('click')

    expect(router.delete).toHaveBeenCalledWith(
      '/my-ads/1',
      expect.objectContaining({
        preserveScroll: false,
        preserveState: false
      })
    )

    expect(wrapper.emitted('item-deleted')).toBeTruthy()
    expect(wrapper.emitted('item-deleted')[0][0]).toBe(1)
  })

  it('handles delete cancellation', async () => {
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    // Open delete modal
    const deleteButton = wrapper.find('[data-testid="delete-button"]')
    await deleteButton.trigger('click')
    await wrapper.vm.$nextTick()

    // Cancel deletion
    const cancelButton = wrapper.find('[data-testid="cancel-button"]')
    await cancelButton.trigger('click')

    const vm = wrapper.vm as any
    expect(vm.showDeleteModal).toBe(false)
  })

  it('uses correct delete URL for draft items', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    const draftItem = {
      ...mockItem,
      status: 'draft' as const
    }

    const wrapper = mount(ItemCard, {
      props: { item: draftItem }
    })

    // Open and confirm delete modal
    const deleteButton = wrapper.find('[data-testid="delete-button"]')
    await deleteButton.trigger('click')
    await wrapper.vm.$nextTick()

    const confirmButton = wrapper.find('[data-testid="confirm-button"]')
    await confirmButton.trigger('click')

    expect(router.delete).toHaveBeenCalledWith(
      '/draft/1',
      expect.any(Object)
    )
  })

  it('handles container click', async () => {
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const container = wrapper.find('[data-testid="item-card"]')
    await container.trigger('click')

    // Should not throw any errors
    expect(wrapper.exists()).toBe(true)
  })

  it('displays correct ARIA labels', () => {
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const card = wrapper.find('[data-testid="item-card"]')
    const imageLink = wrapper.find('[data-testid="item-image-link"]')
    const contentLink = wrapper.find('[data-testid="item-content-link"]')

    expect(card.attributes('aria-label')).toContain('Объявление: Расслабляющий массаж')
    expect(imageLink.attributes('aria-label')).toContain('Посмотреть объявление Расслабляющий массаж')
    expect(contentLink.attributes('aria-label')).toContain('Открыть объявление Расслабляющий массаж')
  })

  it('handles items with different statuses', () => {
    const statuses: Array<Item['status']> = ['draft', 'active', 'archived', 'pending', 'rejected']
    
    statuses.forEach(status => {
      const itemWithStatus = {
        ...mockItem,
        status
      }

      const wrapper = mount(ItemCard, {
        props: { item: itemWithStatus }
      })

      const vm = wrapper.vm as any
      const expectedUrl = status === 'draft' ? '/draft/1' : '/ads/1'
      expect(vm.itemUrl).toBe(expectedUrl)
    })
  })

  it('handles items without optional fields', () => {
    const minimalItem: Item = {
      id: 2,
      status: 'active'
    }

    const wrapper = mount(ItemCard, {
      props: { item: minimalItem }
    })

    expect(wrapper.exists()).toBe(true)
    
    const vm = wrapper.vm as any
    expect(vm.itemUrl).toBe('/ads/2')
  })

  it('handles click events with proper typing', async () => {
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    
    // Test handleContainerClick
    const mockEvent = {
      target: document.createElement('div'),
      currentTarget: document.createElement('div')
    }
    
    expect(() => vm.handleContainerClick(mockEvent)).not.toThrow()
  })

  it('handles delete click with event prevention', async () => {
    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const vm = wrapper.vm as any
    
    const mockEvent = {
      stopPropagation: vi.fn(),
      preventDefault: vi.fn()
    }
    
    vm.handleDeleteClick(mockEvent)
    
    expect(mockEvent.stopPropagation).toHaveBeenCalled()
    expect(mockEvent.preventDefault).toHaveBeenCalled()
    expect(vm.showDeleteModal).toBe(true)
  })

  it('handles API errors gracefully', async () => {
    const { router } = await import('@inertiajs/vue3')
    
    // Mock router to simulate error
    router.post.mockImplementationOnce((url, data, options) => {
      if (options?.onError) {
        options.onError('Network error')
      }
      return Promise.resolve()
    })

    const wrapper = mount(ItemCard, {
      props: defaultProps
    })

    const deactivateButton = wrapper.find('[data-testid="deactivate-button"]')
    await deactivateButton.trigger('click')

    // Should handle error without crashing
    expect(wrapper.exists()).toBe(true)
  })
})