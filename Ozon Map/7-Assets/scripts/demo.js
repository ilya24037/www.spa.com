/**
 * OZON Map Widget Demo Script
 */

// Initialize demo when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeMaps();
    initializeInteractions();
});

/**
 * Initialize all demo maps
 */
function initializeMaps() {
    // Basic map
    initBasicMap();
    
    // Component demos
    initComponentDemos();
    
    // Widget demos
    initWidgetDemos();
    
    // Example maps
    initExampleMaps();
}

/**
 * Initialize basic map demo
 */
function initBasicMap() {
    const container = document.getElementById('basic-map');
    if (!container) return;

    try {
        const map = new maplibregl.Map({
            container: 'basic-map',
            style: {
                version: 8,
                sources: {
                    'osm': {
                        type: 'raster',
                        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                        tileSize: 256,
                        attribution: '© OpenStreetMap contributors'
                    }
                },
                layers: [
                    {
                        id: 'osm',
                        type: 'raster',
                        source: 'osm'
                    }
                ]
            },
            center: [37.6176, 55.7558], // Moscow
            zoom: 12
        });

        // Add controls
        map.addControl(new maplibregl.NavigationControl());
        map.addControl(new maplibregl.GeolocateControl());
        map.addControl(new maplibregl.FullscreenControl());

        // Add marker for Moscow center
        new maplibregl.Marker({ color: '#005bff' })
            .setLngLat([37.6176, 55.7558])
            .setPopup(new maplibregl.Popup().setHTML('<h3>Москва</h3><p>Центр столицы России</p>'))
            .addTo(map);

    } catch (error) {
        console.error('Error initializing basic map:', error);
        container.innerHTML = '<div class="map-error">Ошибка загрузки карты</div>';
    }
}

/**
 * Initialize component demo maps
 */
function initComponentDemos() {
    const demos = [
        { id: 'zoom-demo', center: [37.6176, 55.7558], zoom: 10 },
        { id: 'geolocation-demo', center: [37.6176, 55.7558], zoom: 12 },
        { id: 'fullscreen-demo', center: [37.6176, 55.7558], zoom: 11 },
        { id: 'compass-demo', center: [37.6176, 55.7558], zoom: 13, bearing: 45 }
    ];

    demos.forEach(demo => {
        const container = document.getElementById(demo.id);
        if (!container) return;

        try {
            const map = new maplibregl.Map({
                container: demo.id,
                style: createSimpleStyle(),
                center: demo.center,
                zoom: demo.zoom,
                bearing: demo.bearing || 0,
                interactive: true
            });

            // Add specific controls based on demo type
            if (demo.id === 'zoom-demo') {
                map.addControl(new maplibregl.NavigationControl({ showCompass: false }));
            } else if (demo.id === 'geolocation-demo') {
                map.addControl(new maplibregl.GeolocateControl());
            } else if (demo.id === 'fullscreen-demo') {
                map.addControl(new maplibregl.FullscreenControl());
            } else if (demo.id === 'compass-demo') {
                map.addControl(new maplibregl.NavigationControl({ showZoom: false }));
            }

        } catch (error) {
            console.error(`Error initializing ${demo.id}:`, error);
            container.innerHTML = '<div class="map-error">Ошибка загрузки</div>';
        }
    });
}

/**
 * Initialize widget demo maps
 */
function initWidgetDemos() {
    // Search demo map
    initSearchDemo();
    
    // Pickup points demo map
    initPickupDemo();
}

/**
 * Initialize search demo
 */
function initSearchDemo() {
    const container = document.getElementById('search-demo-map');
    if (!container) return;

    try {
        const map = new maplibregl.Map({
            container: 'search-demo-map',
            style: createSimpleStyle(),
            center: [37.6176, 55.7558],
            zoom: 10
        });

        // Add search result markers
        const searchResults = [
            { coordinates: [37.6176, 55.7558], name: 'Красная площадь' },
            { coordinates: [37.6156, 55.7539], name: 'ГУМ' },
            { coordinates: [37.6200, 55.7520], name: 'Мавзолей' }
        ];

        searchResults.forEach(result => {
            new maplibregl.Marker({ color: '#005bff' })
                .setLngLat(result.coordinates)
                .setPopup(new maplibregl.Popup().setHTML(`<h4>${result.name}</h4>`))
                .addTo(map);
        });

    } catch (error) {
        console.error('Error initializing search demo:', error);
        container.innerHTML = '<div class="map-error">Ошибка загрузки</div>';
    }
}

/**
 * Initialize pickup points demo
 */
function initPickupDemo() {
    const container = document.getElementById('pickup-demo-map');
    if (!container) return;

    try {
        const map = new maplibregl.Map({
            container: 'pickup-demo-map',
            style: createSimpleStyle(),
            center: [37.6176, 55.7558],
            zoom: 11
        });

        // Add pickup point markers
        const pickupPoints = [
            { coordinates: [37.6100, 55.7600], type: 'ozon', name: 'OZON Пункт выдачи' },
            { coordinates: [37.6250, 55.7500], type: 'ozon', name: 'OZON Экспресс' },
            { coordinates: [37.6050, 55.7450], type: 'pvz', name: 'ПВЗ Сбермаркет' },
            { coordinates: [37.6300, 55.7650], type: 'locker', name: 'Постамат Почта России' },
            { coordinates: [37.5950, 55.7550], type: 'ozon', name: 'OZON Логистика' }
        ];

        const colors = {
            'ozon': '#005bff',
            'pvz': '#ff6b35',
            'locker': '#00a651'
        };

        pickupPoints.forEach(point => {
            new maplibregl.Marker({ color: colors[point.type] })
                .setLngLat(point.coordinates)
                .setPopup(new maplibregl.Popup().setHTML(`<h4>${point.name}</h4><p>Тип: ${point.type}</p>`))
                .addTo(map);
        });

    } catch (error) {
        console.error('Error initializing pickup demo:', error);
        container.innerHTML = '<div class="map-error">Ошибка загрузки</div>';
    }
}

/**
 * Initialize example maps
 */
function initExampleMaps() {
    const examples = [
        { id: 'logistics-example', theme: 'logistics' },
        { id: 'store-locator-example', theme: 'stores' },
        { id: 'delivery-tracking-example', theme: 'delivery' }
    ];

    examples.forEach(example => {
        const container = document.getElementById(example.id);
        if (!container) return;

        try {
            const map = new maplibregl.Map({
                container: example.id,
                style: createSimpleStyle(),
                center: [37.6176, 55.7558],
                zoom: 9,
                interactive: false
            });

            // Add theme-specific markers
            addThemeMarkers(map, example.theme);

        } catch (error) {
            console.error(`Error initializing ${example.id}:`, error);
            container.innerHTML = '<div class="map-error">Ошибка</div>';
        }
    });
}

/**
 * Add theme-specific markers to map
 */
function addThemeMarkers(map, theme) {
    const themes = {
        logistics: [
            { coordinates: [37.5000, 55.7000], color: '#ff6b35', popup: 'Склад №1' },
            { coordinates: [37.7000, 55.8000], color: '#ff6b35', popup: 'Склад №2' },
            { coordinates: [37.6000, 55.6000], color: '#00a651', popup: 'Сортировочный центр' }
        ],
        stores: [
            { coordinates: [37.5500, 55.7200], color: '#005bff', popup: 'Магазин OZON' },
            { coordinates: [37.6500, 55.7800], color: '#005bff', popup: 'Магазин OZON' },
            { coordinates: [37.6800, 55.7300], color: '#005bff', popup: 'Магазин OZON' }
        ],
        delivery: [
            { coordinates: [37.6100, 55.7500], color: '#28a745', popup: 'Курьер' },
            { coordinates: [37.6300, 55.7400], color: '#ffc107', popup: 'Пункт назначения' }
        ]
    };

    const markers = themes[theme] || [];
    
    markers.forEach(marker => {
        new maplibregl.Marker({ color: marker.color })
            .setLngLat(marker.coordinates)
            .setPopup(new maplibregl.Popup().setHTML(`<p>${marker.popup}</p>`))
            .addTo(map);
    });
}

/**
 * Create simple map style
 */
function createSimpleStyle() {
    return {
        version: 8,
        sources: {
            'osm': {
                type: 'raster',
                tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
                tileSize: 256,
                attribution: '© OpenStreetMap contributors'
            }
        },
        layers: [
            {
                id: 'osm',
                type: 'raster',
                source: 'osm'
            }
        ]
    };
}

/**
 * Initialize interactive elements
 */
function initializeInteractions() {
    // Smooth scrolling for navigation links
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Search input simulation
    const searchInput = document.querySelector('.demo-search-input');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const resultsContainer = document.querySelector('.search-results');
            if (this.value.length > 2) {
                resultsContainer.style.display = 'block';
            } else {
                resultsContainer.style.display = 'none';
            }
        });
    }

    // Filter button interactions
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
        });
    });

    // Feature card hover effects
    document.querySelectorAll('.feature-card').forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Example card interactions
    document.querySelectorAll('.example-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Код примера откроется в новом окне (демо)');
        });
    });
}

/**
 * Error handler for map loading
 */
function handleMapError(containerId, error) {
    console.error(`Map error in ${containerId}:`, error);
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="map-error">
                <p>⚠️ Ошибка загрузки карты</p>
                <small>${error.message || 'Неизвестная ошибка'}</small>
            </div>
        `;
    }
}

/**
 * Utility function to check if MapLibre is loaded
 */
function checkMapLibreLoaded() {
    return typeof maplibregl !== 'undefined';
}

// Add some basic error handling
window.addEventListener('error', function(e) {
    if (e.message.includes('maplibregl') || e.message.includes('MapLibre')) {
        console.warn('MapLibre GL JS may not be loaded properly');
        
        // Show fallback message
        document.querySelectorAll('[id$="-map"], [id$="-demo"], [id$="-example"]').forEach(container => {
            if (container.children.length === 0) {
                container.innerHTML = `
                    <div class="map-error">
                        <p>📍 Карта временно недоступна</p>
                        <small>Проверьте подключение к интернету</small>
                    </div>
                `;
            }
        });
    }
});

// Initialize on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeMaps);
} else {
    initializeMaps();
}