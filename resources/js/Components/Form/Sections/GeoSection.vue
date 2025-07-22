<template>
    <div class="form-field">
        <div class="geo-fields">
            <div class="city-field">
                <label class="field-label">Город *</label>
                <GeoSuggest 
                    v-model="form.city"
                    placeholder="Выберите город"
                    :error="errors.city"
                />
                <p v-if="errors.city" class="error-message">
                    {{ errors.city }}
                </p>
            </div>
            
            <div class="district-field">
                <label class="field-label">Район</label>
                <input 
                    v-model="form.district"
                    type="text"
                    class="avito-input"
                    placeholder="Укажите район"
                >
            </div>
        </div>

        <div class="address-field">
            <label class="field-label">Адрес</label>
            <input 
                v-model="form.address"
                type="text"
                class="avito-input"
                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.address }"
                placeholder="Улица, дом"
            >
            <p class="field-hint">
                Точный адрес не будет показан в объявлении
            </p>
            <p v-if="errors.address" class="error-message">
                {{ errors.address }}
            </p>
        </div>

        <div class="travel-area-field">
            <label class="field-label">Зона выезда</label>
            <select 
                v-model="form.travel_area"
                class="avito-select"
                :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.travel_area }"
            >
                <option value="">Выберите зону выезда</option>
                <option value="no_travel">Не выезжаю</option>
                <option value="nearby">Ближайшие районы</option>
                <option value="city">По всему городу</option>
                <option value="region">По области</option>
            </select>
            <p class="field-hint">
                Где вы готовы оказывать услуги с выездом
            </p>
            <p v-if="errors.travel_area" class="error-message">
                {{ errors.travel_area }}
            </p>
        </div>
    </div>
</template>

<script setup>
import GeoSuggest from '../Geo/GeoSuggest.vue'

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
.geo-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
}

.city-field,
.district-field,
.address-field,
.travel-area-field {
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

@media (max-width: 768px) {
    .geo-fields {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}
</style> 