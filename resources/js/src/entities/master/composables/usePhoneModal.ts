import { ref } from 'vue'

// Типы для телефонных данных
interface PhoneData {
  phone: string
  whatsapp?: string
  telegram?: string
  masterName?: string
}

export function usePhoneModal() {
  const isOpen = ref(false)
  const phoneData = ref<PhoneData>({
    phone: '',
    whatsapp: '',
    telegram: '',
    masterName: ''
  })

  // Форматирование телефона
  const formatPhone = (phone: string): string => {
    if (!phone) return ''
    
    // Удаляем все нецифровые символы
    const cleaned = phone.replace(/\D/g, '')
    
    // Форматируем в зависимости от длины
    if (cleaned.length === 11 && cleaned.startsWith('7')) {
      // Российский формат: +7 (XXX) XXX-XX-XX
      return `+7 (${cleaned.slice(1, 4)}) ${cleaned.slice(4, 7)}-${cleaned.slice(7, 9)}-${cleaned.slice(9, 11)}`
    } else if (cleaned.length === 10) {
      // Без кода страны: (XXX) XXX-XX-XX
      return `(${cleaned.slice(0, 3)}) ${cleaned.slice(3, 6)}-${cleaned.slice(6, 8)}-${cleaned.slice(8, 10)}`
    }
    
    // Возвращаем как есть если не подходит под форматы
    return phone
  }

  // Копирование в буфер обмена
  const copyToClipboard = async (text: string): Promise<boolean> => {
    try {
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text)
        return true
      } else {
        // Fallback для старых браузеров
        const textArea = document.createElement('textarea')
        textArea.value = text
        textArea.style.position = 'fixed'
        textArea.style.left = '-999999px'
        document.body.appendChild(textArea)
        textArea.focus()
        textArea.select()
        
        try {
          document.execCommand('copy')
          return true
        } finally {
          textArea.remove()
        }
      }
    } catch (error) {
      console.error('Ошибка копирования:', error)
      return false
    }
  }

  // Открыть модалку
  const openModal = (data: PhoneData) => {
    // Нормализуем данные - заполняем пустые поля значениями по умолчанию
    phoneData.value = {
      phone: data.phone || '',
      whatsapp: data.whatsapp || '',
      telegram: data.telegram || '',
      masterName: data.masterName || ''
    }
    isOpen.value = true
  }

  // Закрыть модалку
  const closeModal = () => {
    isOpen.value = false
  }

  return {
    isOpen,
    phoneData,
    formatPhone,
    copyToClipboard,
    openModal,
    closeModal
  }
}