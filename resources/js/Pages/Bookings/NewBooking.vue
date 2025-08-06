<template>
     <div>
        <Head title="РћС„РѕСЂРјР»РµРЅРёРµ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ" />

        <div class="container mx-auto px-4 py-8 max-w-4xl">
            <!-- РҐР»РµР±РЅС‹Рµ РєСЂРѕС€РєРё -->
            <nav class="mb-6 text-sm">
                <Link :href="route('home')" class="text-gray-500 hover:text-gray-700">
                    Р“Р»Р°РІРЅР°СЏ
                </Link>
                <span class="mx-2 text-gray-400">/</span>
                <Link :href="route('masters.show', masterProfile.id)" class="text-gray-500 hover:text-gray-700">
                    {{ masterProfile.user.name }}
                </Link>
                <span class="mx-2 text-gray-400">/</span>
                <span class="text-gray-700">Р‘СЂРѕРЅРёСЂРѕРІР°РЅРёРµ</span>
            </nav>

            <div class="grid lg:grid-cols-3 gap-8">
                <!-- РћСЃРЅРѕРІРЅР°СЏ С„РѕСЂРјР° -->
                <div class="lg:col-span-2">
                    <h1 class="text-2xl font-bold mb-6">РћС„РѕСЂРјР»РµРЅРёРµ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёСЏ</h1>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Р’С‹Р±РѕСЂ РґР°С‚С‹ Рё РІСЂРµРјРµРЅРё -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">Р’С‹Р±РµСЂРёС‚Рµ РґР°С‚Сѓ Рё РІСЂРµРјСЏ</h2>
                            
                            <!-- РљР°Р»РµРЅРґР°СЂСЊ -->
                            <div class="mb-4">
                                <label class="block text-sm font-medium mb-2">Р”Р°С‚Р°</label>
                                <div class="grid grid-cols-7 gap-1 mb-4">
                                    <div v-for="day in weekDays" :key="day" class="text-center text-xs font-medium text-gray-500 py-2">
                                        {{ day }}
                                    </div>
                                    <button
                                        v-for="date in calendarDates"
                                        :key="date.date"
                                        type="button"
                                        @click="selectDate(date)"
                                        :disabled="!date.available"
                                        :class="[
                                            'p-2 text-sm rounded-lg transition',
                                            date.available ? 'hover:bg-blue-50' : 'opacity-50 cursor-not-allowed',
                                            form.booking_date === date.date ? 'bg-blue-600 text-white hover:bg-blue-700' : '',
                                            date.isToday ? 'font-bold' : '',
                                            !date.isCurrentMonth ? 'text-gray-400' : ''
                                        ]"
                                    >
                                        {{ date.day }}
                                    </button>
                                </div>
                            </div>

                            <!-- РЎР»РѕС‚С‹ РІСЂРµРјРµРЅРё -->
                            <div v-if="form.booking_date && availableTimeSlots.length > 0">
                                <label class="block text-sm font-medium mb-2">Р’СЂРµРјСЏ</label>
                                <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                                    <button
                                        v-for="slot in availableTimeSlots"
                                        :key="slot.time"
                                        type="button"
                                        @click="form.booking_time = slot.time"
                                        :disabled="!slot.available"
                                        :class="[
                                            'py-2 px-3 text-sm rounded-lg transition',
                                            slot.available 
                                                ? form.booking_time === slot.time 
                                                    ? 'bg-blue-600 text-white' 
                                                    : 'bg-gray-100 hover:bg-gray-200'
                                                : 'bg-gray-50 text-gray-400 cursor-not-allowed'
                                        ]"
                                    >
                                        {{ slot.time }}
                                    </button>
                                </div>
                                <div v-if="form.errors.booking_time" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.booking_time }}
                                </div>
                            </div>

                            <div v-else-if="form.booking_date" class="text-gray-500 text-sm mt-4">
                                РќР° РІС‹Р±СЂР°РЅРЅСѓСЋ РґР°С‚Сѓ РЅРµС‚ СЃРІРѕР±РѕРґРЅС‹С… СЃР»РѕС‚РѕРІ
                            </div>
                        </div>

                        <!-- РњРµСЃС‚Рѕ РѕРєР°Р·Р°РЅРёСЏ СѓСЃР»СѓРіРё -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">Р“РґРµ Р±СѓРґРµС‚ РѕРєР°Р·Р°РЅР° СѓСЃР»СѓРіР°?</h2>
                            
                            <div class="space-y-3">
                                <label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="form.service_location === 'home' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                    <input 
                                        type="radio" 
                                        v-model="form.service_location" 
                                        value="home"
                                        class="mt-1"
                                    >
                                    <div class="ml-3">
                                        <p class="font-medium">РќР° РІС‹РµР·РґРµ (Сѓ РјРµРЅСЏ РґРѕРјР°)</p>
                                        <p class="text-sm text-gray-600">РњР°СЃС‚РµСЂ РїСЂРёРµРґРµС‚ РїРѕ РІР°С€РµРјСѓ Р°РґСЂРµСЃСѓ</p>
                                        <p class="text-sm text-green-600 mt-1">
                                            + {{ formatPrice(500) }} Р·Р° РІС‹РµР·Рґ
                                        </p>
                                    </div>
                                </label>

                                <label v-if="masterProfile.salon_name" 
                                       class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                       :class="form.service_location === 'salon' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                    <input 
                                        type="radio" 
                                        v-model="form.service_location" 
                                        value="salon"
                                        class="mt-1"
                                    >
                                    <div class="ml-3">
                                        <p class="font-medium">Р’ СЃР°Р»РѕРЅРµ</p>
                                        <p class="text-sm text-gray-600">{{ masterProfile.salon_name }}</p>
                                        <p class="text-sm text-gray-500">{{ masterProfile.salon_address }}</p>
                                    </div>
                                </label>
                            </div>

                            <!-- РђРґСЂРµСЃ РґР»СЏ РІС‹РµР·РґР° -->
                            <div v-if="form.service_location === 'home'" class="mt-4">
                                <label class="block text-sm font-medium mb-2">РђРґСЂРµСЃ *</label>
                                <input 
                                    v-model="form.address"
                                    type="text"
                                    class="w-full border-gray-300 rounded-lg"
                                    placeholder="Р“РѕСЂРѕРґ, СѓР»РёС†Р°, РґРѕРј, РєРІР°СЂС‚РёСЂР°"
                                >
                                <div v-if="form.errors.address" class="text-red-500 text-sm mt-1">
                                    {{ form.errors.address }}
                                </div>
                            </div>
                        </div>

                        <!-- РљРѕРЅС‚Р°РєС‚РЅС‹Рµ РґР°РЅРЅС‹Рµ -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">РљРѕРЅС‚Р°РєС‚РЅС‹Рµ РґР°РЅРЅС‹Рµ</h2>
                            
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-2">РРјСЏ *</label>
                                    <input 
                                        v-model="form.client_name"
                                        type="text"
                                        class="w-full border-gray-300 rounded-lg"
                                        placeholder="РљР°Рє Рє РІР°Рј РѕР±СЂР°С‰Р°С‚СЊСЃСЏ"
                                    >
                                    <div v-if="form.errors.client_name" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.client_name }}
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-2">РўРµР»РµС„РѕРЅ *</label>
                                    <input 
                                        v-model="form.client_phone"
                                        type="tel"
                                        class="w-full border-gray-300 rounded-lg"
                                        placeholder="+7 (999) 123-45-67"
                                    >
                                    <div v-if="form.errors.client_phone" class="text-red-500 text-sm mt-1">
                                        {{ form.errors.client_phone }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium mb-2">РљРѕРјРјРµРЅС‚Р°СЂРёР№ Рє Р·Р°РєР°Р·Сѓ</label>
                                <textarea 
                                    v-model="form.client_comment"
                                    rows="3"
                                    class="w-full border-gray-300 rounded-lg"
                                    placeholder="РћСЃРѕР±С‹Рµ РїРѕР¶РµР»Р°РЅРёСЏ, РєР°Рє РІР°СЃ РЅР°Р№С‚Рё Рё С‚.Рґ."
                                ></textarea>
                            </div>
                        </div>

                        <!-- РЎРїРѕСЃРѕР± РѕРїР»Р°С‚С‹ -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold mb-4">РЎРїРѕСЃРѕР± РѕРїР»Р°С‚С‹</h2>
                            
                            <div class="space-y-2">
                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                                       :class="form.payment_method === 'cash' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                    <input 
                                        type="radio" 
                                        v-model="form.payment_method" 
                                        value="cash"
                                    >
                                    <span class="ml-3">РќР°Р»РёС‡РЅС‹РјРё РјР°СЃС‚РµСЂСѓ</span>
                                </label>

                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                                       :class="form.payment_method === 'card' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                    <input 
                                        type="radio" 
                                        v-model="form.payment_method" 
                                        value="card"
                                    >
                                    <span class="ml-3">РљР°СЂС‚РѕР№ РјР°СЃС‚РµСЂСѓ</span>
                                </label>

                                <label class="flex items-center p-3 border rounded-lg cursor-pointer hover:bg-gray-50"
                                       :class="form.payment_method === 'online' ? 'border-blue-500 bg-blue-50' : 'border-gray-200'">
                                    <input 
                                        type="radio" 
                                        v-model="form.payment_method" 
                                        value="online"
                                    >
                                    <span class="ml-3">РћРЅР»Р°Р№РЅ РЅР° СЃР°Р№С‚Рµ (СЃРєРѕСЂРѕ)</span>
                                    <span class="ml-auto text-xs bg-gray-100 px-2 py-1 rounded">РќРµРґРѕСЃС‚СѓРїРЅРѕ</span>
                                </label>
                            </div>
                        </div>

                        <!-- РЎРѕРіР»Р°СЃРёРµ СЃ РїСЂР°РІРёР»Р°РјРё -->
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <label class="flex items-start">
                                <input 
                                    v-model="form.agree_terms"
                                    type="checkbox"
                                    class="mt-1"
                                >
                                <span class="ml-3 text-sm text-gray-600">
                                    РЇ СЃРѕРіР»Р°СЃРµРЅ СЃ 
                                    <a href="#" class="text-blue-600 hover:underline">РїСЂР°РІРёР»Р°РјРё СЃРµСЂРІРёСЃР°</a>
                                    Рё РґР°СЋ СЃРѕРіР»Р°СЃРёРµ РЅР° РѕР±СЂР°Р±РѕС‚РєСѓ РїРµСЂСЃРѕРЅР°Р»СЊРЅС‹С… РґР°РЅРЅС‹С…
                                </span>
                            </label>
                        </div>

                        <!-- РљРЅРѕРїРєР° РѕС‚РїСЂР°РІРєРё -->
                        <button 
                            type="submit"
                            :disabled="form.processing || !canSubmit"
                            :class="[
                                'w-full py-3 rounded-lg font-medium transition',
                                canSubmit 
                                    ? 'bg-blue-600 text-white hover:bg-blue-700' 
                                    : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                            ]"
                        >
                            {{ form.processing ? 'РћС‚РїСЂР°РІРєР°...' : 'РћС‚РїСЂР°РІРёС‚СЊ Р·Р°СЏРІРєСѓ' }}
                        </button>
                    </form>
                </div>

                <!-- РЎР°Р№РґР±Р°СЂ СЃ РёРЅС„РѕСЂРјР°С†РёРµР№ -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm p-6 sticky top-4">
                        <!-- РРЅС„РѕСЂРјР°С†РёСЏ Рѕ РјР°СЃС‚РµСЂРµ -->
                        <div class="flex items-start gap-4 mb-6 pb-6 border-b">
                            <img 
                                :src="masterProfile.user.avatar_url || '/images/default-avatar.png'"
                                :alt="masterProfile.user.name"
                                class="w-16 h-16 rounded-full object-cover"
                            >
                            <div>
                                <h3 class="font-semibold">{{ masterProfile.user.name }}</h3>
                                <div class="flex items-center gap-1 text-sm text-gray-600">
                                    <StarIcon class="w-4 h-4 text-yellow-400 fill-current" />
                                    <span>{{ masterProfile.rating }}</span>
                                    <span class="text-gray-400">({{ masterProfile.reviews_count }} РѕС‚Р·С‹РІРѕРІ)</span>
                                </div>
                            </div>
                        </div>

                        <!-- РРЅС„РѕСЂРјР°С†РёСЏ РѕР± СѓСЃР»СѓРіРµ -->
                        <div class="mb-6 pb-6 border-b">
                            <h4 class="font-semibold mb-2">{{ service.name }}</h4>
                            <p class="text-sm text-gray-600 mb-2">
                                Р”Р»РёС‚РµР»СЊРЅРѕСЃС‚СЊ: {{ service.duration }} РјРёРЅ
                            </p>
                            <p class="text-2xl font-bold text-blue-600">
                                {{ formatPrice(service.price) }}
                            </p>
                        </div>

                        <!-- РС‚РѕРіРѕРІР°СЏ СЃС‚РѕРёРјРѕСЃС‚СЊ -->
                        <div>
                            <h4 class="font-semibold mb-3">РС‚РѕРіРѕ Рє РѕРїР»Р°С‚Рµ:</h4>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>{{ service.name }}</span>
                                    <span>{{ formatPrice(service.price) }}</span>
                                </div>
                                
                                <div v-if="form.service_location === 'home'" class="flex justify-between text-green-600">
                                    <span>Р’С‹РµР·Рґ РјР°СЃС‚РµСЂР°</span>
                                    <span>+ {{ formatPrice(500) }}</span>
                                </div>
                                
                                <div class="pt-2 border-t font-semibold text-lg flex justify-between">
                                    <span>РС‚РѕРіРѕ:</span>
                                    <span class="text-blue-600">{{ formatPrice(totalPrice) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- РРЅС„РѕСЂРјР°С†РёСЏ Рѕ Р±СЂРѕРЅРёСЂРѕРІР°РЅРёРё -->
                        <div class="mt-6 p-4 bg-yellow-50 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <ExclamationTriangleIcon class="w-4 h-4 inline mr-1" />
                                РџРѕСЃР»Рµ РѕС‚РїСЂР°РІРєРё Р·Р°СЏРІРєРё РјР°СЃС‚РµСЂ СЃРІСЏР¶РµС‚СЃСЏ СЃ РІР°РјРё РґР»СЏ РїРѕРґС‚РІРµСЂР¶РґРµРЅРёСЏ
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { StarIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/solid'
import { format, addDays, startOfMonth, endOfMonth, eachDayOfInterval, getDay, isSameDay, isToday, isSameMonth } from 'date-fns'
import { ru } from 'date-fns/locale'

const props = defineProps({
    masterProfile: Object,
    service: Object,
    availableSlots: Object
})

// Р¤РѕСЂРјР°
const form = useForm({
    master_profile_id: props.masterProfile.id,
    service_id: props.service.id,
    booking_date: null,
    booking_time: null,
    service_location: 'home',
    address: '',
    client_name: '',
    client_phone: '',
    client_comment: '',
    payment_method: 'cash',
    agree_terms: false
})

// Р”РЅРё РЅРµРґРµР»Рё
const weekDays = ['РџРЅ', 'Р’С‚', 'РЎСЂ', 'Р§С‚', 'РџС‚', 'РЎР±', 'Р’СЃ']

// РўРµРєСѓС‰РёР№ РјРµСЃСЏС† РґР»СЏ РєР°Р»РµРЅРґР°СЂСЏ
const currentMonth = ref(new Date())

// Р”Р°С‚С‹ РєР°Р»РµРЅРґР°СЂСЏ
const calendarDates = computed(() => {
    const start = startOfMonth(currentMonth.value)
    const end = endOfMonth(currentMonth.value)
    const days = eachDayOfInterval({ start, end })
    
    // Р”РѕР±Р°РІР»СЏРµРј РґРЅРё РёР· РїСЂРµРґС‹РґСѓС‰РµРіРѕ РјРµСЃСЏС†Р°
    const startDay = getDay(start) || 7 // Р’РѕСЃРєСЂРµСЃРµРЅСЊРµ = 7
    const previousMonthDays = []
    for (let i = startDay - 1; i > 0; i--) {
        previousMonthDays.push(addDays(start, -i))
    }
    
    // РћР±СЉРµРґРёРЅСЏРµРј РІСЃРµ РґРЅРё
    const allDays = [...previousMonthDays, ...days]
    
    return allDays.map(date => ({
        date: format(date, 'yyyy-MM-dd'),
        day: format(date, 'd'),
        isToday: isToday(date),
        isCurrentMonth: isSameMonth(date, currentMonth.value),
        available: props.availableSlots[format(date, 'yyyy-MM-dd')]?.length > 0
    }))
})

// Р”РѕСЃС‚СѓРїРЅС‹Рµ СЃР»РѕС‚С‹ РІСЂРµРјРµРЅРё РґР»СЏ РІС‹Р±СЂР°РЅРЅРѕР№ РґР°С‚С‹
const availableTimeSlots = computed(() => {
    if (!form.booking_date) return []
    return props.availableSlots[form.booking_date] || []
})

// РћР±С‰Р°СЏ СЃС‚РѕРёРјРѕСЃС‚СЊ
const totalPrice = computed(() => {
    let total = props.service.price
    if (form.service_location === 'home') {
        total += 500 // Р”РѕРїР»Р°С‚Р° Р·Р° РІС‹РµР·Рґ
    }
    return total
})

// РњРѕР¶РЅРѕ Р»Рё РѕС‚РїСЂР°РІРёС‚СЊ С„РѕСЂРјСѓ
const canSubmit = computed(() => {
    return form.booking_date && 
           form.booking_time && 
           form.client_name && 
           form.client_phone && 
           form.agree_terms &&
           (form.service_location === 'salon' || form.address)
})

// Р’С‹Р±РѕСЂ РґР°С‚С‹
const selectDate = (date) => {
    if (!date.available) return
    form.booking_date = date.date
    form.booking_time = null // РЎР±СЂР°СЃС‹РІР°РµРј РІСЂРµРјСЏ РїСЂРё СЃРјРµРЅРµ РґР°С‚С‹
}

// Р¤РѕСЂРјР°С‚РёСЂРѕРІР°РЅРёРµ С†РµРЅС‹
const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB',
        minimumFractionDigits: 0
    }).format(price)
}

// РћС‚РїСЂР°РІРєР° С„РѕСЂРјС‹
const submit = () => {
    form.post(route('bookings.store'), {
        onSuccess: () => {
            // РџРµСЂРµРЅР°РїСЂР°РІР»РµРЅРёРµ РїСЂРѕРёР·РѕР№РґС‘С‚ Р°РІС‚РѕРјР°С‚РёС‡РµСЃРєРё
        }
    })
}

// РџСЂРё РјРѕРЅС‚РёСЂРѕРІР°РЅРёРё СѓСЃС‚Р°РЅР°РІР»РёРІР°РµРј РґР°РЅРЅС‹Рµ РїРѕР»СЊР·РѕРІР°С‚РµР»СЏ РµСЃР»Рё РµСЃС‚СЊ
onMounted(() => {
    if (window.auth?.user) {
        form.client_name = window.auth.user.name || ''
        form.client_phone = window.auth.user.phone || ''
    }
})
</script>
