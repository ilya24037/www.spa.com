// MediaUploader.test.ts
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import MediaUploader from './MediaUploader.vue'
import type { MediaUploaderProps, Photo, Video } from './MediaUploader.types'

// Mock Toast composable
vi.mock('@/src/shared/composables/useToast', () => ({
  useToast: () => ({
    success: vi.fn(),
    error: vi.fn(),
    info: vi.fn()
  })
}))

// Mock fetch
global.fetch = vi.fn()

describe('MediaUploader', () => {
  const defaultProps: MediaUploaderProps = {
    masterId: 1,
    masterName: 'Test Master',
    initialPhotos: [],
    initialVideo: null
  }

  const mockPhoto: Photo = {
    id: 1,
    thumb_url: '/thumb/1.jpg',
    sort_order: 1,
    is_main: false
  }

  const mockVideo: Video = {
    id: 1,
    video_url: '/video/1.mp4',
    poster_url: '/poster/1.jpg',
    duration: '2:30',
    file_size: 1024000
  }

  beforeEach(() => {
    // Mock CSRF token
    const metaElement = document.createElement('meta')
    metaElement.name = 'csrf-token'
    metaElement.content = 'test-token'
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
    const wrapper = mount(MediaUploader, {
      props: defaultProps
    })

    expect(wrapper.find('h3').text()).toBe('Аватар')
    expect(wrapper.findAll('h3')).toHaveLength(3)
    expect(wrapper.find('img').attributes('alt')).toBe(defaultProps.masterName)
  })

  it('displays initial photos', () => {
    const propsWithPhotos = {
      ...defaultProps,
      initialPhotos: [mockPhoto]
    }

    const wrapper = mount(MediaUploader, {
      props: propsWithPhotos
    })

    expect(wrapper.findAll('.relative.group')).toHaveLength(1)
    expect(wrapper.find('img[alt*="Фото"]').attributes('src')).toBe(mockPhoto.thumb_url)
  })

  it('displays initial video', () => {
    const propsWithVideo = {
      ...defaultProps,
      initialVideo: mockVideo
    }

    const wrapper = mount(MediaUploader, {
      props: propsWithVideo
    })

    expect(wrapper.find('video').attributes('src')).toBe(mockVideo.video_url)
    expect(wrapper.find('video').attributes('poster')).toBe(mockVideo.poster_url)
  })

  it('handles avatar upload successfully', async () => {
    const mockFetch = vi.mocked(fetch)
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        success: true,
        avatar_url: '/new-avatar.jpg'
      })
    } as Response)

    const wrapper = mount(MediaUploader, {
      props: defaultProps
    })

    const fileInput = wrapper.find('input[type="file"][accept="image/*"]:not([multiple])')
    const file = new File(['avatar'], 'avatar.jpg', { type: 'image/jpeg' })

    // Mock file input
    Object.defineProperty(fileInput.element, 'files', {
      value: [file],
      writable: false,
    })

    await fileInput.trigger('change')
    await wrapper.vm.$nextTick()

    expect(mockFetch).toHaveBeenCalledWith(
      '/masters/1/upload/avatar',
      expect.objectContaining({
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': 'test-token'
        }
      })
    )
  })

  it('emits events when media is updated', async () => {
    const wrapper = mount(MediaUploader, {
      props: defaultProps
    })

    // Simulate avatar upload
    const mockFetch = vi.mocked(fetch)
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        success: true,
        avatar_url: '/new-avatar.jpg'
      })
    } as Response)

    const fileInput = wrapper.find('input[type="file"][accept="image/*"]:not([multiple])')
    const file = new File(['avatar'], 'avatar.jpg', { type: 'image/jpeg' })

    Object.defineProperty(fileInput.element, 'files', {
      value: [file],
      writable: false,
    })

    await fileInput.trigger('change')
    await wrapper.vm.$nextTick()

    expect(wrapper.emitted('avatarUpdated')).toBeTruthy()
  })

  it('formats file size correctly', () => {
    const wrapper = mount(MediaUploader, {
      props: {
        ...defaultProps,
        initialVideo: mockVideo
      }
    })

    // Test file size formatting through component display
    expect(wrapper.text()).toContain('1000.0 KB')
  })

  it('shows loading state during upload', async () => {
    const mockFetch = vi.mocked(fetch)
    mockFetch.mockImplementation(() => new Promise(() => {})) // Never resolves

    const wrapper = mount(MediaUploader, {
      props: defaultProps
    })

    const fileInput = wrapper.find('input[type="file"][accept="image/*"]:not([multiple])')
    const file = new File(['avatar'], 'avatar.jpg', { type: 'image/jpeg' })

    Object.defineProperty(fileInput.element, 'files', {
      value: [file],
      writable: false,
    })

    await fileInput.trigger('change')
    await wrapper.vm.$nextTick()

    expect(wrapper.find('button').text()).toBe('Загрузка...')
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })

  it('handles API errors gracefully', async () => {
    const mockFetch = vi.mocked(fetch)
    mockFetch.mockResolvedValueOnce({
      ok: true,
      json: async () => ({
        success: false,
        error: 'Upload failed'
      })
    } as Response)

    const wrapper = mount(MediaUploader, {
      props: defaultProps
    })

    const fileInput = wrapper.find('input[type="file"][accept="image/*"]:not([multiple])')
    const file = new File(['avatar'], 'avatar.jpg', { type: 'image/jpeg' })

    Object.defineProperty(fileInput.element, 'files', {
      value: [file],
      writable: false,
    })

    await fileInput.trigger('change')
    await wrapper.vm.$nextTick()

    // Component should handle error gracefully without crashing
    expect(wrapper.exists()).toBe(true)
  })

  it('validates file type restrictions', () => {
    const wrapper = mount(MediaUploader, {
      props: defaultProps
    })

    const avatarInput = wrapper.find('input[accept="image/*"]:not([multiple])')
    const photosInput = wrapper.find('input[accept="image/*"][multiple]')
    const videoInput = wrapper.find('input[accept="video/*"]')

    expect(avatarInput.attributes('accept')).toBe('image/*')
    expect(photosInput.attributes('accept')).toBe('image/*')
    expect(videoInput.attributes('accept')).toBe('video/*')
  })
})