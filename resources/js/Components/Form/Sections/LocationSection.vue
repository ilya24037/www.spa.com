<template>
    <div class="form-field">
        <div class="checkbox-group">
            <div class="checkbox-item" @click="toggleLocation('home')">
                <div 
                    class="custom-checkbox"
                    :class="{ 'checked': form.service_location.includes('home') }"
                >
                    <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <input 
                    v-model="form.service_location"
                    type="checkbox"
                    value="home"
                    style="display: none;"
                >
                <span class="checkbox-label">У заказчика дома</span>
            </div>
            
            <div class="checkbox-item" @click="toggleLocation('salon')">
                <div 
                    class="custom-checkbox"
                    :class="{ 'checked': form.service_location.includes('salon') }"
                >
                    <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <input 
                    v-model="form.service_location"
                    type="checkbox"
                    value="salon"
                    style="display: none;"
                >
                <span class="checkbox-label">У себя дома</span>
            </div>
            
            <div class="checkbox-item" @click="toggleLocation('office')">
                <div 
                    class="custom-checkbox"
                    :class="{ 'checked': form.service_location.includes('office') }"
                >
                    <svg class="check-icon" width="100%" height="100%" viewBox="0 0 10 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4.35714L3.4 6.5L9 1.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                    </svg>
                </div>
                <input 
                    v-model="form.service_location"
                    type="checkbox"
                    value="office"
                    style="display: none;"
                >
                <span class="checkbox-label">В салоне</span>
            </div>
            

        </div>
        
        <p class="field-hint">
            Выберите места, где вы оказываете услуги
        </p>
        
        <div v-if="errors.service_location" class="error-message">
            {{ errors.service_location }}
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    form: {
        type: Object,
        required: true
    },
    errors: {
        type: Object,
        default: () => ({})
    }
})

// Функция переключения локации
const toggleLocation = (location) => {
    if (!props.form.service_location.includes(location)) {
        props.form.service_location.push(location)
    } else {
        const index = props.form.service_location.indexOf(location)
        props.form.service_location.splice(index, 1)
    }
}

// Инициализируем массив если его нет
if (!props.form.service_location) {
    props.form.service_location = []
}
</script> 