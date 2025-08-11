export function useBookingFormatter() {
  // Форматирование даты и времени
  const formatDateTime = (date: string, time?: string): string => {
    try {
      const dateObj = new Date(date)
      
      // Проверяем валидность даты
      if (isNaN(dateObj.getTime())) {
        return 'Дата не указана'
      }
      
      const options: Intl.DateTimeFormatOptions = {
        day: 'numeric',
        month: 'long',
        weekday: 'long'
      }
      
      let formatted = dateObj.toLocaleDateString('ru-RU', options)
      
      // Добавляем время если есть
      if (time) {
        formatted += ` в ${time}`
      }
      
      return formatted
    } catch (error) {
      console.error('Ошибка форматирования даты:', error)
      return 'Дата не указана'
    }
  }
  
  // Форматирование цены
  const formatPrice = (price: number | string): string => {
    const numPrice = typeof price === 'string' ? parseFloat(price) : price
    
    if (isNaN(numPrice)) {
      return 'Цена не указана'
    }
    
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(numPrice)
  }
  
  // Форматирование статуса бронирования
  const formatStatus = (status: string): { text: string; color: string } => {
    const statusMap: Record<string, { text: string; color: string }> = {
      pending: { text: 'Ожидает подтверждения', color: 'yellow' },
      confirmed: { text: 'Подтверждено', color: 'green' },
      cancelled: { text: 'Отменено', color: 'red' },
      completed: { text: 'Завершено', color: 'gray' }
    }
    
    return statusMap[status] || { text: 'Неизвестно', color: 'gray' }
  }
  
  // Форматирование длительности
  const formatDuration = (minutes: number): string => {
    if (minutes < 60) {
      return `${minutes} мин`
    }
    
    const hours = Math.floor(minutes / 60)
    const mins = minutes % 60
    
    if (mins === 0) {
      return `${hours} ч`
    }
    
    return `${hours} ч ${mins} мин`
  }
  
  // Получение цвета для статуса
  const getStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
      pending: 'bg-yellow-100 text-yellow-800',
      confirmed: 'bg-green-100 text-green-800',
      cancelled: 'bg-red-100 text-red-800',
      completed: 'bg-gray-100 text-gray-800'
    }
    
    return colors[status] || 'bg-gray-100 text-gray-800'
  }
  
  return {
    formatDateTime,
    formatPrice,
    formatStatus,
    formatDuration,
    getStatusColor
  }
}