<template>
    <div class="form-field">
        <div class="radio-group">
            <div class="radio-item" @click="selectFormat('private')">
                <div 
                    class="custom-radio"
                    :class="{ 'checked': form.work_format === 'private' }"
                    :style="{ 
                        width: '20px', 
                        height: '20px', 
                        border: '2px solid #d9d9d9', 
                        borderRadius: '50%',
                        marginRight: '12px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        background: '#fff'
                    }"
                >
                </div>
                <input 
                    v-model="form.work_format"
                    type="radio"
                    value="private"
                    style="display: none;"
                >
                <div class="radio-content">
                    <div class="radio-title">Частный мастер</div>
                    <div class="radio-description">Работаете в одиночку</div>
                </div>
            </div>
            
            <div class="radio-item" @click="selectFormat('salon')">
                <div 
                    class="custom-radio"
                    :class="{ 'checked': form.work_format === 'salon' }"
                    :style="{ 
                        width: '20px', 
                        height: '20px', 
                        border: '2px solid #d9d9d9', 
                        borderRadius: '50%',
                        marginRight: '12px',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        background: '#fff'
                    }"
                >
                </div>
                <input 
                    v-model="form.work_format"
                    type="radio"
                    value="salon"
                    style="display: none;"
                >
                <div class="radio-content">
                    <div class="radio-title">Салон</div>
                    <div class="radio-description">У вас есть отдельное помещение и штат мастеров</div>
                </div>
            </div>
            

        </div>
        
        <div v-if="errors.work_format" class="error-message">
            {{ errors.work_format }}
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

// Функция выбора формата
const selectFormat = (format) => {
    props.form.work_format = format
}
</script>

<style scoped>
.radio-group {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.radio-item {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    padding: 12px 0;
}

.custom-radio {
    width: 20px !important;
    height: 20px !important;
    border: 2px solid #d9d9d9 !important;
    border-radius: 50% !important;
    margin-right: 12px !important;
    margin-top: 2px !important;
    flex-shrink: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    transition: all 0.2s ease !important;
    background: #fff !important;
}

.custom-radio.checked {
    border-color: #1890ff !important;
}

.custom-radio.checked::after {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #1890ff;
}

.radio-content {
    flex: 1;
}

.radio-title {
    font-size: 16px;
    font-weight: 500;
    color: #1a1a1a;
    margin: 0 0 4px 0;
    line-height: 1.4;
}

.radio-description {
    font-size: 14px;
    color: #8c8c8c;
    margin: 0;
    line-height: 1.4;
}

.error-message {
    margin-top: 8px;
    color: #ff4d4f;
    font-size: 14px;
    line-height: 1.4;
}
</style> 