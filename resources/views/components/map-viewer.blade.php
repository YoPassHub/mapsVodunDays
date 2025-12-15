{{-- Composant de carte interactive Mapbox réutilisable --}}
@props([
    'events' => [],
    'center' => [2.0850, 6.3611],
    'zoom' => 14,
    'style' => 'mapbox://styles/mapbox/streets-v12',
    'showInfo' => true,
    'infoTitle' => 'Vodun Days',
    'infoDescription' => 'Découvrez les événements et lieux spirituels',
    'showFilters' => true,
    'filters' => [
        ['label' => 'Tout', 'value' => 'all', 'icon' => '⭐'],
        ['label' => 'Vodundays', 'value' => 'vodur', 'icon' => '🏛️'],
        ['label' => 'Événements', 'value' => 'concert', 'icon' => '🎵'],
        ['label' => 'Bon plans', 'value' => 'stand', 'icon' => '🏪'],
    ],
    'showSearch' => true,
    'mapId' => 'map',
])

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Mapbox GL JS -->
    <link href='https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.css' rel='stylesheet' />
    <script src='https://api.mapbox.com/mapbox-gl-js/v3.0.1/mapbox-gl.js'></script>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Instrument Sans', sans-serif;
        }
        #{{ $mapId }} {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
        }
        
        .info-panel {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            color: #1b1b18;
            padding: 20px;
            border-radius: 12px;
            max-width: 320px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 1;
            opacity: 0;
            pointer-events: none;
            transform: translateY(-10px);
            transition: all 0.3s ease;
        }
        
        .info-panel.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
        
        .info-panel h1 {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 8px 0;
            color: #1b1b18;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-panel h1 img.logo {
            width: 24px;
            height: 24px;
            object-fit: contain;
        }
        
        .info-panel p {
            font-size: 13px;
            line-height: 1.5;
            margin: 0 0 12px 0;
            color: rgba(27, 27, 24, 0.85);
        }
        
        .legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            background: rgba(0, 0, 0, 0.05);
            padding: 4px 10px;
            border-radius: 16px;
            color: #1b1b18;
        }
        
        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        .bottom-nav {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 12px 20px;
            border-radius: 50px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.1);
            z-index: 1;
        }
        
        .nav-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: transparent;
            color: #1b1b18;
            border: 1px solid rgba(0, 0, 0, 0.15);
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover {
            background: rgba(0, 0, 0, 0.05);
            border-color: rgba(0, 0, 0, 0.3);
        }
        
        .nav-btn.active {
            background: #1b1b18;
            color: white;
            border-color: #1b1b18;
        }
        
        .nav-btn .icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .search-box {
            position: absolute;
            top: 30px;
            right: 20px;
            z-index: 1;
        }
        
        .search-box input {
            width: 180px;
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 25px;
            font-size: 14px;
            color: #1b1b18;
            outline: none;
            transition: all 0.3s ease;
        }
        
        .search-box input:focus {
            border-color: #1b1b18;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }
        
        .search-box input::placeholder {
            color: rgba(27, 27, 24, 0.5);
        }
        
        .mapboxgl-popup-content {
            background: rgba(255, 255, 255, 0.98);
            color: #1b1b18;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(0, 0, 0, 0.1);
            min-width: 250px;
        }
        
        .mapboxgl-popup-content h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 600;
            color: #1b1b18;
        }
        
        .mapboxgl-popup-content p {
            margin: 5px 0;
            font-size: 14px;
            color: rgba(27, 27, 24, 0.85);
        }
        
        .mapboxgl-popup-content .time {
            display: inline-block;
            background: rgba(255, 102, 0, 0.2);
            color: #ff6600;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
            margin-top: 8px;
        }
        
        .mapboxgl-popup-close-button {
            color: #1b1b18;
            font-size: 24px;
            padding: 5px 10px;
        }
        
        .mapboxgl-popup-close-button:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        
        /* Marqueurs personnalisés */
        .custom-marker {
            background-size: cover;
            background-position: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            cursor: pointer;
            position: relative;
            border: 4px solid;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }
        
        .custom-marker:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.4);
        }
        
        .custom-marker.vodur {
            border-color: #9333ea;
        }
        
        .custom-marker.concert {
            border-color: #ff6b35;
        }
        
        .custom-marker.stand {
            border-color: #10b981;
        }
        
        .custom-marker.activite {
            border-color: #3b82f6;
        }
        
        /* Animation de clignotement pour les événements en cours */
        .custom-marker.ongoing {
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3), 0 0 0 0 rgba(255, 107, 53, 0.7);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3), 0 0 0 15px rgba(255, 107, 53, 0);
                transform: scale(1.1);
            }
        }
        
        .marker-label {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.85);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 10;
        }
        
        .custom-marker:hover .marker-label {
            opacity: 1;
        }
        
        /* Tooltip au survol prolongé */
        .marker-tooltip {
            position: fixed;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
            max-width: 300px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }
        
        .marker-tooltip.show {
            opacity: 1;
        }
        
        .marker-tooltip h4 {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #1b1b18;
        }
        
        .marker-tooltip p {
            margin: 4px 0;
            font-size: 13px;
            color: rgba(27, 27, 24, 0.85);
        }
        
        .marker-tooltip .time {
            display: inline-block;
            background: rgba(255, 102, 0, 0.2);
            color: #ff6600;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 500;
            margin-top: 6px;
        }
        
        /* Panneau de détails de l'événement */
        .event-details-panel {
            position: absolute;
            bottom: 30px;
            right: 30px;
            width: 400px;
            max-height: 80vh;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transform: translateY(calc(100% + 50px));
            opacity: 0;
            transition: all 0.4s ease;
            z-index: 1000;
        }
        
        .event-details-panel.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .event-details-header {
            position: relative;
            height: 200px;
            background-size: cover;
            background-position: center;
        }
        
        .event-details-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));
        }
        
        .event-details-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 35px;
            height: 35px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1b1b18;
            transition: all 0.3s ease;
            z-index: 10;
        }
        
        .event-details-close:hover {
            background: white;
            transform: scale(1.1);
        }
        
        .event-details-content {
            padding: 25px;
            overflow-y: auto;
            max-height: calc(80vh - 200px);
        }
        
        .event-details-content h2 {
            margin: 0 0 20px 0;
            font-size: 24px;
            color: #1b1b18;
            font-weight: 700;
        }
        
        .event-details-meta {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: rgba(27, 27, 24, 0.85);
        }
        
        .meta-icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .event-details-description {
            font-size: 15px;
            line-height: 1.7;
            color: rgba(27, 27, 24, 0.8);
            margin-bottom: 25px;
        }
        
        .event-details-button {
            width: 100%;
            padding: 15px;
            background: #FF6B35;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .event-details-button:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.4);
        }
        
        .event-details-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .info-panel {
                max-width: calc(100% - 40px);
                padding: 20px;
            }
            
            .info-panel h1 {
                font-size: 24px;
            }
            
            .bottom-nav {
                flex-direction: column;
                bottom: 20px;
                padding: 10px;
            }
            
            .search-box {
                top: 30px;
                right: 10px;
            }
            
            .search-box input {
                width: 150px;
                font-size: 12px;
                padding: 8px 12px;
            }
            
            .event-details-panel {
                width: calc(100% - 40px);
                right: 20px;
                bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <div id="{{ $mapId }}"></div>
    
    <!-- Tooltip pour afficher les infos au survol prolongé -->
    <div id="markerTooltip" class="marker-tooltip"></div>

    <!-- Event Details Panel -->
    <div id="eventDetailsPanel" class="event-details-panel">
        <div class="event-details-header" id="panelHeader">
            <button class="event-details-close" id="closePanel">×</button>
        </div>
        <div class="event-details-content">
            <h2 id="panelTitle"></h2>
            <div class="event-details-meta">
                <div class="meta-item">
                    <span class="meta-icon">📍</span>
                    <span id="panelLocation"></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">🗓️</span>
                    <span id="panelDate"></span>
                </div>
                <div class="meta-item">
                    <span class="meta-icon">🕐</span>
                    <span id="panelTime"></span>
                </div>
            </div>
            <div class="event-details-description" id="panelDescription"></div>
            <button class="event-details-button" id="directionsBtn">
                <span>🧭</span> S'y rendre
            </button>
        </div>
    </div>

    @if($showInfo)
    <div class="info-panel">
        <h1>
            <img src="{{ asset('vodun-days.png') }}" alt="Logo Vodun Days" class="logo">
            {{ $infoTitle }}
        </h1>
        <p>{{ $infoDescription }}</p>
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #9333ea;"></div>
                <span>Temples</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ff6b35;"></div>
                <span>Concerts</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #10b981;"></div>
                <span>Stands</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #3b82f6;"></div>
                <span>Activités</span>
            </div>
        </div>
    </div>
    @endif

    @if($showSearch)
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="🔍 Rechercher un événement...">
    </div>
    @endif

    @if($showFilters)
    <div class="bottom-nav">
        @foreach($filters as $filter)
        <button class="nav-btn {{ $loop->first ? 'active' : '' }}" data-filter="{{ $filter['value'] }}">
            <span class="icon">{{ $filter['icon'] }}</span>
            <span>{{ $filter['label'] }}</span>
        </button>
        @endforeach
    </div>
    @endif

    <script>
        // Configuration Mapbox - Token depuis .env
        mapboxgl.accessToken = '{{ env('MAPBOX_TOKEN') }}';
        
        // Données des événements depuis PHP
        const events = @json($events);
        
        // Initialisation de la carte
        const map = new mapboxgl.Map({
            container: '{{ $mapId }}',
            style: '{{ $style }}',
            center: @json($center),
            zoom: {{ $zoom }},
            pitch: 0,
            bearing: 0
        });
        
        // Ajouter les contrôles de navigation
        map.addControl(new mapboxgl.NavigationControl(), 'bottom-right');
        
        // Stockage des marqueurs
        const markers = [];
        const tooltip = document.getElementById('markerTooltip');
        let tooltipTimeout = null;
        
        // Fonction pour vérifier si un événement est en cours
        function isEventOngoing(event) {
            if (!event.time || !event.date) return false;
            
            try {
                const now = new Date();
                const eventDate = new Date(event.date);
                
                // Vérifier si c'est aujourd'hui
                const isToday = eventDate.toDateString() === now.toDateString();
                if (!isToday) return false;
                
                // Parser les horaires (format: "HH:MM - HH:MM")
                const timeMatch = event.time.match(/(\d{1,2}):(\d{2})\s*-\s*(\d{1,2}):(\d{2})/);
                if (!timeMatch) return false;
                
                const startHour = parseInt(timeMatch[1]);
                const startMin = parseInt(timeMatch[2]);
                const endHour = parseInt(timeMatch[3]);
                const endMin = parseInt(timeMatch[4]);
                
                const currentHour = now.getHours();
                const currentMin = now.getMinutes();
                const currentTime = currentHour * 60 + currentMin;
                const startTime = startHour * 60 + startMin;
                const endTime = endHour * 60 + endMin;
                
                return currentTime >= startTime && currentTime <= endTime;
            } catch (e) {
                return false;
            }
        }
        
        // Créer les marqueurs pour chaque événement
        events.forEach(event => {
            const el = document.createElement('div');
            el.className = `custom-marker ${event.type}`;
            
            // Ajouter la classe 'ongoing' si l'événement est en cours
            if (isEventOngoing(event)) {
                el.classList.add('ongoing');
            }
            
            el.style.backgroundImage = `url(${event.image})`;
            el.style.cursor = 'pointer';
            
            // Ajouter le label au survol
            const label = document.createElement('div');
            label.className = 'marker-label';
            label.textContent = event.name;
            el.appendChild(label);
            
            const marker = new mapboxgl.Marker({
                element: el,
                anchor: 'center',
                draggable: false
            })
                .setLngLat(event.coordinates)
                .addTo(map);
            
            // Au clic, ouvrir le panneau de détails
            el.addEventListener('click', function() {
                openEventDetails(event);
            });
            
            // Gestion du tooltip au survol prolongé
            el.addEventListener('mouseenter', function(e) {
                tooltipTimeout = setTimeout(() => {
                    tooltip.innerHTML = `
                        <h4>${event.icon} ${event.name}</h4>
                        <p><strong>📍 ${event.location}</strong></p>
                        <p>${event.description}</p>
                        <span class="time">⏰ ${event.time}</span>
                    `;
                    
                    const rect = el.getBoundingClientRect();
                    tooltip.style.left = (rect.right + 10) + 'px';
                    tooltip.style.top = rect.top + 'px';
                    
                    tooltip.classList.add('show');
                }, 800);
            });
            
            el.addEventListener('mouseleave', function() {
                if (tooltipTimeout) {
                    clearTimeout(tooltipTimeout);
                    tooltipTimeout = null;
                }
                tooltip.classList.remove('show');
            });
            
            el.addEventListener('mousemove', function(e) {
                if (tooltip.classList.contains('show')) {
                    tooltip.style.left = (e.clientX + 15) + 'px';
                    tooltip.style.top = (e.clientY - tooltip.offsetHeight / 2) + 'px';
                }
            });
            
            marker._originalSize = 60;
            marker._element = el;
            markers.push({ marker, event });
        });
        
        // Fonction pour ajuster la taille des marqueurs selon le zoom
        function updateMarkerSizes() {
            const zoom = map.getZoom();
            let scale;
            if (zoom < 12) {
                scale = 0.5;
            } else if (zoom < 14) {
                scale = 0.5 + (zoom - 12) * 0.25;
            } else if (zoom > 16) {
                scale = 1 + (zoom - 16) * 0.2;
            } else {
                scale = 1;
            }
            
            markers.forEach(({ marker }) => {
                const el = marker.getElement();
                if (el) {
                    el.style.transform = `scale(${scale})`;
                }
            });
        }
        
        map.on('zoom', updateMarkerSizes);
        map.on('load', updateMarkerSizes);
        
        // Mettre à jour l'état des événements en cours toutes les 30 secondes
        function updateOngoingEvents() {
            markers.forEach(({ marker, event }) => {
                const el = marker._element;
                if (el) {
                    if (isEventOngoing(event)) {
                        el.classList.add('ongoing');
                    } else {
                        el.classList.remove('ongoing');
                    }
                }
            });
        }
        
        // Mettre à jour immédiatement et toutes les 30 secondes
        updateOngoingEvents();
        setInterval(updateOngoingEvents, 30000);
        
        @if($showFilters)
        // Filtrage des événements et gestion du panneau d'info
        const filterButtons = document.querySelectorAll('.nav-btn');
        const infoPanel = document.querySelector('.info-panel');
        
        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.dataset.filter;
                
                filterButtons.forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
                
                // Afficher le panneau uniquement pour le filtre 'vodur'
                if (filter === 'vodur' && infoPanel) {
                    infoPanel.classList.add('show');
                } else if (infoPanel) {
                    infoPanel.classList.remove('show');
                }
                
                markers.forEach(({ marker, event }) => {
                    const el = marker.getElement();
                    if (filter === 'all' || event.type === filter) {
                        el.style.display = 'block';
                    } else {
                        el.style.display = 'none';
                    }
                });
            });
        });
        @endif
        
        @if($showSearch)
        // Recherche d'événements
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            
            markers.forEach(({ marker, event }) => {
                const el = marker.getElement();
                const matches = event.name.toLowerCase().includes(searchTerm) ||
                              event.location.toLowerCase().includes(searchTerm) ||
                              event.description.toLowerCase().includes(searchTerm);
                
                el.style.display = matches ? 'block' : 'none';
            });
        });
        @endif
        
        // Gestion du panneau de détails
        const eventPanel = document.getElementById('eventDetailsPanel');
        const panelHeader = document.getElementById('panelHeader');
        const panelTitle = document.getElementById('panelTitle');
        const panelLocation = document.getElementById('panelLocation');
        const panelDate = document.getElementById('panelDate');
        const panelTime = document.getElementById('panelTime');
        const panelDescription = document.getElementById('panelDescription');
        const closePanel = document.getElementById('closePanel');
        const directionsBtn = document.getElementById('directionsBtn');
        
        let currentEvent = null;
        let directionsLayer = null;
        
        function openEventDetails(event) {
            currentEvent = event;
            
            panelHeader.style.backgroundImage = `url(${event.image})`;
            panelTitle.textContent = `${event.icon} ${event.name}`;
            panelLocation.textContent = event.location;
            panelDate.textContent = event.date || 'Date à confirmer';
            panelTime.textContent = event.time;
            panelDescription.textContent = event.description;
            
            eventPanel.classList.add('show');
            
            map.flyTo({
                center: event.coordinates,
                zoom: 16,
                duration: 1000
            });
        }
        
        function closeEventDetails() {
            eventPanel.classList.remove('show');
            currentEvent = null;
            
            if (directionsLayer && map.getLayer('route')) {
                map.removeLayer('route');
                map.removeSource('route');
                directionsLayer = null;
            }
        }
        
        closePanel.addEventListener('click', closeEventDetails);
        
        // Fonction pour afficher l'itinéraire
        directionsBtn.addEventListener('click', function() {
            if (!currentEvent) return;
            
            if (navigator.geolocation) {
                directionsBtn.innerHTML = '<span>⏳</span> Calcul en cours...';
                directionsBtn.disabled = true;
                
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const userLocation = [position.coords.longitude, position.coords.latitude];
                        const eventLocation = currentEvent.coordinates;
                        
                        const url = `https://api.mapbox.com/directions/v5/mapbox/driving/${userLocation[0]},${userLocation[1]};${eventLocation[0]},${eventLocation[1]}?geometries=geojson&access_token=${mapboxgl.accessToken}`;
                        
                        fetch(url)
                            .then(response => response.json())
                            .then(data => {
                                if (data.routes && data.routes.length > 0) {
                                    const route = data.routes[0].geometry;
                                    const duration = Math.round(data.routes[0].duration / 60);
                                    const distance = (data.routes[0].distance / 1000).toFixed(1);
                                    
                                    if (map.getLayer('route')) {
                                        map.removeLayer('route');
                                        map.removeSource('route');
                                    }
                                    
                                    map.addSource('route', {
                                        'type': 'geojson',
                                        'data': {
                                            'type': 'Feature',
                                            'properties': {},
                                            'geometry': route
                                        }
                                    });
                                    
                                    map.addLayer({
                                        'id': 'route',
                                        'type': 'line',
                                        'source': 'route',
                                        'layout': {
                                            'line-join': 'round',
                                            'line-cap': 'round'
                                        },
                                        'paint': {
                                            'line-color': '#FF6B35',
                                            'line-width': 5,
                                            'line-opacity': 0.8
                                        }
                                    });
                                    
                                    directionsLayer = 'route';
                                    
                                    const coordinates = route.coordinates;
                                    const bounds = coordinates.reduce((bounds, coord) => {
                                        return bounds.extend(coord);
                                    }, new mapboxgl.LngLatBounds(coordinates[0], coordinates[0]));
                                    
                                    map.fitBounds(bounds, {
                                        padding: { top: 100, bottom: 100, left: 100, right: 450 }
                                    });
                                    
                                    new mapboxgl.Marker({ color: '#4CAF50' })
                                        .setLngLat(userLocation)
                                        .setPopup(new mapboxgl.Popup().setHTML('<p>Votre position</p>'))
                                        .addTo(map);
                                    
                                    directionsBtn.innerHTML = `<span>✅</span> ${distance} km • ${duration} min`;
                                    directionsBtn.disabled = false;
                                } else {
                                    directionsBtn.innerHTML = '<span>❌</span> Itinéraire introuvable';
                                    directionsBtn.disabled = false;
                                }
                            })
                            .catch(error => {
                                console.error('Erreur lors du calcul de l\'itinéraire:', error);
                                directionsBtn.innerHTML = '<span>❌</span> Erreur';
                                directionsBtn.disabled = false;
                            });
                    },
                    function(error) {
                        alert('Impossible d\'obtenir votre position. Veuillez autoriser la géolocalisation.');
                        directionsBtn.innerHTML = '<span>🧭</span> S\'y rendre';
                        directionsBtn.disabled = false;
                    }
                );
            } else {
                alert('La géolocalisation n\'est pas supportée par votre navigateur.');
            }
        });
        
        // Ajouter les bâtiments 3D
        map.on('load', () => {
            const layers = map.getStyle().layers;
            const labelLayerId = layers.find(
                (layer) => layer.type === 'symbol' && layer.layout['text-field']
            ).id;
            
            map.addLayer({
                    'id': 'add-3d-buildings',
                    'source': 'composite',
                    'source-layer': 'building',
                    'filter': ['==', 'extrude', 'true'],
                    'type': 'fill-extrusion',
                    'minzoom': 15,
                    'paint': {
                        'fill-extrusion-color': '#444',
                        'fill-extrusion-height': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            15,
                            0,
                            15.05,
                            ['get', 'height']
                        ],
                        'fill-extrusion-base': [
                            'interpolate',
                            ['linear'],
                            ['zoom'],
                            15,
                            0,
                            15.05,
                            ['get', 'min_height']
                        ],
                        'fill-extrusion-opacity': 0.6
                    }
                },
                labelLayerId
            );
        });
    </script>
</body>
</html>
