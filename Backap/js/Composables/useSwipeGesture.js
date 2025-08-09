import { ref } from 'vue'

export function useSwipeGesture(options = {}) {
  const {
    threshold = 50,
    maxTime = 300,
    onSwipeLeft = null,
    onSwipeRight = null,
    onSwipeUp = null,
    onSwipeDown = null,
  } = options

  const dragOffset = ref(0)
  const isAnimating = ref(false)
  
  let startX = null
  let startY = null
  let startTime = null

  function onTouchStart(e) {
    startX = e.touches[0].clientX
    startY = e.touches[0].clientY
    startTime = Date.now()
    dragOffset.value = 0
  }

  function onTouchMove(e) {
    if (startX === null || startY === null) return

    const currentX = e.touches[0].clientX
    const currentY = e.touches[0].clientY
    const diffX = currentX - startX
    const diffY = currentY - startY

    // Определяем направление свайпа
    if (Math.abs(diffX) > Math.abs(diffY)) {
      // Горизонтальный свайп
      e.preventDefault()
      
      // Ограничиваем drag только влево
      if (diffX < 0) {
        dragOffset.value = Math.max(diffX, -100)
      }
    }
  }

  function onTouchEnd(e) {
    if (startX === null || startY === null) return

    const endX = e.changedTouches[0].clientX
    const endY = e.changedTouches[0].clientY
    const endTime = Date.now()

    const diffX = endX - startX
    const diffY = endY - startY
    const elapsedTime = endTime - startTime

    // Анимация возврата
    isAnimating.value = true
    dragOffset.value = 0
    setTimeout(() => {
      isAnimating.value = false
    }, 300)

    // Быстрый свайп или длинный свайп
    if (elapsedTime < maxTime || Math.abs(diffX) > threshold * 2) {
      if (Math.abs(diffX) > Math.abs(diffY)) {
        if (diffX > threshold && onSwipeRight) {
          onSwipeRight()
        } else if (diffX < -threshold && onSwipeLeft) {
          onSwipeLeft()
        }
      } else {
        if (diffY > threshold && onSwipeDown) {
          onSwipeDown()
        } else if (diffY < -threshold && onSwipeUp) {
          onSwipeUp()
        }
      }
    }

    // Сброс
    startX = null
    startY = null
    startTime = null
  }

  return {
    dragOffset,
    isAnimating,
    onTouchStart,
    onTouchMove,
    onTouchEnd,
  }
}