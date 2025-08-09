export function useImages() {
    const getImageUrl = (path, defaultImage = '/images/no-photo.jpg') => {
        if (!path) return defaultImage
        
        // Если это уже полный URL
        if (path.startsWith('http://') || path.startsWith('https://')) {
            return path
        }
        
        // Если путь начинается с /storage/
        if (path.startsWith('/storage/')) {
            return path
        }
        
        // Если путь начинается с /
        if (path.startsWith('/')) {
            return path
        }
        
        // Иначе добавляем /storage/
        return `/storage/${path}`
    }
    
    const handleImageError = (event, defaultImage = '/images/no-photo.jpg') => {
        event.target.src = defaultImage
        event.target.onerror = null // Предотвращаем бесконечный цикл
    }
    
    const validateImageArray = (images, defaultImage = '/images/no-photo.jpg') => {
        if (!images || !Array.isArray(images) || images.length === 0) {
            return [defaultImage]
        }
        
        return images.map(img => getImageUrl(img, defaultImage))
    }
    
    return {
        getImageUrl,
        handleImageError,
        validateImageArray
    }
}