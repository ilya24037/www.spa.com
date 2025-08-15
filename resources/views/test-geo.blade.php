<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Тест География секции</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div style="max-width: 800px; margin: 20px auto; padding: 20px; background: white;">
            <h1 style="margin-bottom: 20px;">Тест секции География</h1>
            
            <!-- Компонент GeoSection -->
            <geo-section 
                :geo="geoData"
                @update:geo="handleGeoUpdate"
            ></geo-section>
            
            <div style="margin-top: 20px; padding: 16px; background: #f0f0f0; border-radius: 8px;">
                <h3 style="margin: 0 0 10px 0; font-size: 16px;">Данные (JSON):</h3>
                <pre style="font-size: 12px; background: white; padding: 10px; border-radius: 4px; overflow-x: auto;">{{ geoDataString }}</pre>
            </div>
        </div>
    </div>

    <script>
        // Инициализация Vue приложения для тестирования
        import { createApp } from 'vue'
        import GeoSection from '@/src/features/AdSections/GeoSection/ui/GeoSection.vue'
        
        createApp({
            components: {
                GeoSection
            },
            data() {
                return {
                    geoData: JSON.stringify({
                        address: '',
                        coordinates: null,
                        outcall: 'none',
                        zones: []
                    })
                }
            },
            computed: {
                geoDataString() {
                    try {
                        return JSON.stringify(JSON.parse(this.geoData), null, 2)
                    } catch (e) {
                        return this.geoData
                    }
                }
            },
            methods: {
                handleGeoUpdate(value) {
                    this.geoData = value
                    console.log('География обновлена:', value)
                }
            }
        }).mount('#app')
    </script>
</body>
</html>