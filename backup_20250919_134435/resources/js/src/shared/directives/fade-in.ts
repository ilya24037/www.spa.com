export const fadeIn = { mounted(el) { el.style.opacity = "0"; el.style.transition = "opacity 0.3s ease"; setTimeout(() => { el.style.opacity = "1" }, 50) } }
