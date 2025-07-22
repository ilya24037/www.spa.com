<template>
    <div class="form-field">
        <div class="promo-fields">
            <div class="discount-field">
                <label class="field-label">Скидка новым клиентам</label>
                <div class="discount-input-group">
                    <input 
                        v-model="form.new_client_discount"
                        type="number"
                        min="0"
                        max="100"
                        class="avito-input discount-input"
                        placeholder="0"
                    >
                    <span class="discount-symbol">%</span>
                </div>
                <p class="field-hint">
                    Можно не заполнять, если скидки нет
                </p>
            </div>

            <div class="gift-field">
                <label class="field-label">Подарок</label>
                <input 
                    v-model="form.gift"
                    type="text"
                    class="avito-input"
                    placeholder="Что вы можете подарить? Например: дарим чай"
                    maxlength="100"
                >
                <p class="field-hint">
                    Можно не заполнять, если подарка нет
                </p>
            </div>
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
</script>

<style scoped>
.promo-fields {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.discount-field,
.gift-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.field-label {
    font-size: 16px;
    font-weight: 400;
    color: #1a1a1a;
    margin: 0;
}

.discount-input-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.discount-input {
    width: 80px;
}

.discount-symbol {
    font-size: 16px;
    color: #1a1a1a;
    font-weight: 500;
}

@media (max-width: 768px) {
    .discount-input-group {
        gap: 6px;
    }
    
    .discount-input {
        width: 70px;
    }
}
</style> 