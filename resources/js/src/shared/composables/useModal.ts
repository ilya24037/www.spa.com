import { ref, type Ref } from 'vue'

export interface UseModalReturn {
  isOpen: Ref<boolean>
  open: () => void
  close: () => void
  toggle: () => void
}

export function useModal(initialState = false): UseModalReturn {
  const isOpen: Ref<boolean> = ref(initialState)
  
  const open = (): void => {
    isOpen.value = true
  }
  
  const close = (): void => {
    isOpen.value = false
  }

  const toggle = (): void => {
    isOpen.value = !isOpen.value
  }
  
  return {
    isOpen,
    open,
    close,
    toggle
  }
}

export default useModal