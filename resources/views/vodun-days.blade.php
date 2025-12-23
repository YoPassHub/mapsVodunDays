<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vodun Days - Carte Google Maps</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Instrument Sans', sans-serif;
        }
        #map {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 100%;
            height: 100vh;
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
            transition: opacity 0.5s ease, transform 0.5s ease;
        }
        
        .info-panel.hide {
            opacity: 0;
            transform: translateY(-20px);
            pointer-events: none;
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
        
        .search-box {
            position: absolute;
            top: 30px;
            right: 20px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            padding: 8px;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1), 0 0 0 1px rgba(0, 0, 0, 0.05);
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .search-box:hover {
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(0, 0, 0, 0.08);
        }
        
        .search-box input {
            background: transparent;
            border: none;
            color: #1b1b18;
            padding: 12px 20px;
            border-radius: 50px;
            width: 250px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-box input::placeholder {
            color: rgba(27, 27, 24, 0.4);
        }
        
        .search-box input:focus {
            outline: none;
            background: rgba(0, 0, 0, 0.02);
        }

        /* Panneau de détails de l'événement */
        .event-details-panel {
            position: absolute;
            bottom: 30px;
            right: 30px;
            width: 400px;
            max-height: 80vh;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            transform: translateY(calc(100% + 50px));
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
        }
        
        .event-details-panel.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        /* Styles pour marqueurs Google Maps */
        .google-marker {
            position: relative;
            width: 60px;
            height: 60px;
        }
        
        .google-marker-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background-size: cover;
            background-position: center;
            border: 4px solid #ff8800; /* Orange par défaut */
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .google-marker-circle:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.7);
        }
        
        /* Bordure rouge pour VodunDays */
        .google-marker.vodundays-event .google-marker-circle {
            border-color: #ff0000;
            box-shadow: 0 0 20px rgba(255, 0, 0, 0.6);
        }
        
        /* Animation clignotante pour événements en cours */
        .google-marker.ongoing-event .google-marker-circle {
            animation: pulse-marker 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse-marker {
            0%, 100% {
                box-shadow: 0 0 15px rgba(255, 136, 0, 0.8);
                transform: scale(1);
            }
            50% {
                box-shadow: 0 0 30px rgba(255, 136, 0, 1), 0 0 40px rgba(255, 0, 0, 0.5);
                transform: scale(1.05);
            }
        }
        
        /* Badge "Démarre bientôt" */
        .google-marker-badge {
            position: absolute;
            top: -45px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #ff6600, #ff3333);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
            box-shadow: 0 4px 12px rgba(255, 51, 51, 0.4);
            z-index: 5;
            pointer-events: none;
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateX(-50%) translateY(0); }
            50% { transform: translateX(-50%) translateY(-5px); }
        }
        
        .event-details-header {
            position: relative;
            height: 200px;
            background-size: cover;
            background-position: center;
        }
        
        .event-details-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
        }
        
        .event-details-close {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            z-index: 1;
            transition: all 0.3s ease;
        }
        
        .event-details-close:hover {
            background: white;
            transform: rotate(90deg);
        }
        
        .event-details-content {
            padding: 25px;
            max-height: calc(80vh - 200px);
            overflow-y: auto;
        }
        
        .event-details-title {
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 10px 0;
            color: #1b1b18;
        }
        
        .event-details-location {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .event-details-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }
        
        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #666;
        }
        
        .event-meta-item strong {
            color: #1b1b18;
        }
        
        .event-details-description {
            font-size: 15px;
            line-height: 1.6;
            color: #444;
            margin-bottom: 25px;
        }
        
        .event-details-button {
            width: 100%;
            padding: 15px;
            background: #ff6600;
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
            background: #ff5500;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 102, 0, 0.4);
        }
        
        .event-details-button:active {
            transform: translateY(0);
        }
        
        /* Media Queries pour Mobile */
        @media (max-width: 768px) {
            .info-panel {
                top: 10px;
                left: 10px;
                right: 10px;
                max-width: none;
                padding: 12px 15px;
            }
            
            .info-panel h1 {
                font-size: 16px;
            }
            
            .info-panel p {
                font-size: 11px;
                margin-bottom: 8px;
            }
            
            /* Afficher seulement 3 filtres sur mobile */
            .nav-btn:nth-child(n+4) {
                display: none;
            }
            
            .search-box {
                top: auto;
                bottom: 100px;
                right: 10px;
                left: 10px;
                padding: 6px;
            }
            
            .search-box input {
                width: 100%;
                font-size: 13px;
                padding: 10px 16px;
            }
            
            .legend {
                gap: 6px;
            }
            
            .legend-item {
                font-size: 10px;
                padding: 3px 8px;
            }
            
            .bottom-nav {
                bottom: 15px;
                left: 10px;
                right: 10px;
                transform: none;
                padding: 10px;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .nav-btn {
                padding: 8px 12px;
                font-size: 11px;
                min-width: auto;
            }
            
            .nav-btn span {
                font-size: 14px;
            }
            
            .search-container {
                top: auto;
                bottom: 100px;
                left: 10px;
                right: 10px;
                width: auto;
            }
            
            .search-input {
                font-size: 14px;
                padding: 10px 40px 10px 15px;
            }
            
            .search-icon {
                right: 12px;
                font-size: 16px;
            }
            
            .event-details-panel {
                position: fixed;
                top: 20px;
                left: 20px;
                bottom: auto;
                right: auto;
                width: 90%;
                max-width: 380px;
                max-height: calc(100vh - 140px);
                border-radius: 20px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
                background: rgba(255, 255, 255, 0.85);
                backdrop-filter: blur(15px);
            }
            
            .event-details-header {
                height: 150px;
                border-radius: 20px 20px 0 0;
            }
            
            .event-details-content {
                padding: 16px;
                max-height: calc(100vh - 290px);
                overflow-y: auto;
            }
            
            .event-details-title {
                font-size: 20px;
                margin-bottom: 8px;
            }
            
            .event-details-location {
                font-size: 13px;
                margin-bottom: 12px;
            }
            
            .event-details-meta {
                flex-direction: column;
                gap: 10px;
                margin-bottom: 16px;
                padding-bottom: 16px;
            }
            
            .event-meta-item {
                font-size: 13px;
            }
            
            .event-details-description {
                font-size: 14px;
                line-height: 1.5;
                margin-bottom: 20px;
            }
            
            .event-details-button {
                font-size: 15px;
                padding: 14px;
                border-radius: 14px;
            }
            
            .event-details-close {
                width: 40px;
                height: 40px;
                top: 12px;
                right: 12px;
                font-size: 22px;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
            }
            
            /* Masquer certains éléments sur mobile */
            .legend {
                display: none;
            }
        }
        
        @media (max-width: 480px) {
            .info-panel {
                padding: 10px 12px;
            }
            
            .info-panel h1 {
                font-size: 14px;
                margin-bottom: 4px;
            }
            
            .info-panel p {
                font-size: 10px;
                margin-bottom: 6px;
                line-height: 1.3;
            }
            
            .nav-btn {
                padding: 6px 10px;
                font-size: 10px;
            }
            
            .nav-btn span:first-child {
                font-size: 12px;
            }
            
            .search-box {
                padding: 5px;
            }
            
            .search-box input {
                font-size: 12px;
                padding: 8px 14px;
            }
            
            .event-details-panel {
                top: 10px;
                left: 10px;
                width: calc(100% - 20px);
                max-width: calc(100% - 20px);
                max-height: calc(100vh - 120px);
                background: rgba(255, 255, 255, 0.80);
            }
            
            .event-details-header {
                height: 130px;
            }
            
            .event-details-content {
                padding: 14px;
                max-height: calc(100vh - 250px);
            }
            
            .event-details-title {
                font-size: 18px;
            }
            
            .event-meta-item {
                font-size: 12px;
            }
            
            .event-details-description {
                font-size: 13px;
            }
            
            .event-details-button {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <!-- Panneau d'information -->
    <div class="info-panel">
        <h1>🗺️ Vodun Days</h1>
        <p>Carte Google Maps interactive des événements culturels du Bénin</p>
        <div class="legend">
            <div class="legend-item">
                <span class="legend-color" style="background: #ff3333;"></span>
                <span>Vodun</span>
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background: #ff8800;"></span>
                <span>Concert</span>
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background: #ffbb00;"></span>
                <span>Stand</span>
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background: #ffdd00;"></span>
                <span>Activité</span>
            </div>
        </div>
    </div>

    <!-- Boîte de recherche -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="🔍 Rechercher un événement...">
    </div>

    <!-- Navigation des filtres -->
    <div class="bottom-nav">
        <button class="nav-btn active" data-filter="all">
            <span>📍</span>
            <span>Tous</span>
        </button>
        <button class="nav-btn" data-filter="vodur">
            <img src="{{ asset('vodun-days.png') }}" alt="VodunDays" style="width: 20px; height: 20px; object-fit: contain;">
            <span>Vodundays</span>
        </button>
        @foreach($categories->take(4) as $category)
        <button class="nav-btn" data-filter="category-{{ $category['id'] }}" data-category-label="{{ $category['label'] }}">
            <span>🎯</span>
            <span>{{ $category['label'] }}</span>
        </button>
        @endforeach
    </div>

    <!-- Carte Google Maps -->
    <div id="map"></div>

    <!-- Panneau de détails de l'événement -->
    <div id="eventDetailsPanel" class="event-details-panel">
        <div id="panelHeader" class="event-details-header">
            <button id="closePanel" class="event-details-close">✕</button>
        </div>
        <div class="event-details-content">
            <h2 id="panelTitle" class="event-details-title"></h2>
            <div class="event-details-location">
                <span>📍</span>
                <span id="panelLocation"></span>
            </div>
            <div class="event-details-meta">
                <div class="event-meta-item">
                    <span>📅</span>
                    <strong id="panelDate"></strong>
                </div>
                <div class="event-meta-item">
                    <span>⏰</span>
                    <span id="panelTime"></span>
                </div>
            </div>
            <p id="panelDescription" class="event-details-description"></p>
            <button id="directionsBtn" class="event-details-button">
                <span>🧭</span>
                <span>Comment s'y rendre</span>
            </button>
        </div>
    </div>

    <script>
        // Données des événements depuis PHP
        const events = @json($events);
        
        console.log('========================================');
        console.log('🗺️ GOOGLE MAPS - VODUN DAYS');
        console.log('========================================');
        console.log('📊 Nombre d\'événements:', events.length);
        console.log('📋 Événements:', events);
        console.log('========================================');

        let map;
        let markers = [];
        let currentEvent = null;
        
        // Masquer le panneau d'information après 5 secondes
        setTimeout(() => {
            const infoPanel = document.querySelector('.info-panel');
            if (infoPanel) {
                infoPanel.classList.add('hide');
            }
        }, 5000);

        function initMap() {
            // Créer la carte centrée sur Ouidah
            map = new google.maps.Map(document.getElementById('map'), {
                center: { lat: 6.3611, lng: 2.0850 },
                zoom: 14,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
                zoomControl: false,
                mapTypeId: 'roadmap',
                styles: [
                    {
                        featureType: 'poi',
                        elementType: 'labels',
                        stylers: [{ visibility: 'on' }]
                    }
                ]
            });

            // Ajouter les marqueurs
            events.forEach(event => {
                addMarker(event);
            });

            // Ajuster la vue pour inclure tous les marqueurs
            if (markers.length > 0) {
                const bounds = new google.maps.LatLngBounds();
                markers.forEach(({ marker }) => {
                    bounds.extend(marker.getPosition());
                });
                map.fitBounds(bounds);
            }
        }

        function addMarker(event) {
            const position = { 
                lat: event.coordinates[1], // Google Maps: lat, lng
                lng: event.coordinates[0] 
            };

            // Créer l'élément HTML du marqueur
            const markerEl = document.createElement('div');
            markerEl.className = 'google-marker';
            
            // Ajouter classes spéciales
            if (event.isVodunDays) {
                markerEl.classList.add('vodundays-event');
            }
            if (event.status === 'ongoing') {
                markerEl.classList.add('ongoing-event');
            }
            
            // Badge "Démarre bientôt"
            if (event.status === 'starting-soon') {
                const badge = document.createElement('div');
                badge.className = 'google-marker-badge';
                badge.textContent = '⏰ Démarre bientôt';
                markerEl.appendChild(badge);
            }
            
            // Cercle du marqueur avec image
            const circle = document.createElement('div');
            circle.className = 'google-marker-circle';
            circle.style.backgroundImage = `url('${event.image}')`;
            markerEl.appendChild(circle);

            // Créer le marqueur Google Maps avec élément HTML personnalisé
            const marker = new google.maps.Marker({
                map: map,
                position: position,
                title: event.name,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                        <svg width="1" height="1" xmlns="http://www.w3.org/2000/svg"></svg>
                    `),
                    anchor: new google.maps.Point(0, 0),
                },
                animation: google.maps.Animation.DROP
            });

            // Overlay personnalisé pour afficher le HTML
            class CustomOverlay extends google.maps.OverlayView {
                constructor(position, content) {
                    super();
                    this.position = position;
                    this.content = content;
                }

                onAdd() {
                    this.div = this.content;
                    const panes = this.getPanes();
                    panes.overlayMouseTarget.appendChild(this.div);
                }

                draw() {
                    const overlayProjection = this.getProjection();
                    const pos = overlayProjection.fromLatLngToDivPixel(this.position);
                    this.div.style.left = (pos.x - 30) + 'px';
                    this.div.style.top = (pos.y - 30) + 'px';
                    this.div.style.position = 'absolute';
                }

                onRemove() {
                    if (this.div) {
                        this.div.parentNode.removeChild(this.div);
                        this.div = null;
                    }
                }
            }

            const overlay = new CustomOverlay(position, markerEl);
            overlay.setMap(map);

            // Au clic, afficher le panneau de détails
            markerEl.addEventListener('click', () => {
                openEventDetails(event);
            });

            markers.push({ 
                marker, 
                overlay,
                event 
            });
        }

        // Fonction pour ouvrir le panneau de détails
        function openEventDetails(event) {
            currentEvent = event;
            
            const panel = document.getElementById('eventDetailsPanel');
            const header = document.getElementById('panelHeader');
            const title = document.getElementById('panelTitle');
            const location = document.getElementById('panelLocation');
            const date = document.getElementById('panelDate');
            const time = document.getElementById('panelTime');
            const description = document.getElementById('panelDescription');
            
            // Remplir les informations
            header.style.backgroundImage = `url(${event.image})`;
            title.textContent = `${event.icon} ${event.name}`;
            location.textContent = event.location;
            date.textContent = event.date || 'Date à confirmer';
            time.textContent = event.time;
            description.textContent = event.description;
            
            // Afficher le panneau
            panel.classList.add('show');
            
            // Centrer la carte sur l'événement
            map.panTo({ lat: event.coordinates[1], lng: event.coordinates[0] });
            map.setZoom(16);
        }

        // Fonction pour fermer le panneau
        function closeEventDetails() {
            const panel = document.getElementById('eventDetailsPanel');
            panel.classList.remove('show');
            currentEvent = null;
        }

        // Bouton de fermeture du panneau
        document.getElementById('closePanel').addEventListener('click', closeEventDetails);

        // Bouton "Comment s'y rendre"
        document.getElementById('directionsBtn').addEventListener('click', () => {
            if (currentEvent) {
                const destination = `${currentEvent.coordinates[1]},${currentEvent.coordinates[0]}`;
                const url = `https://www.google.com/maps/dir/?api=1&destination=${destination}`;
                window.open(url, '_blank');
            }
        });

        // Filtrage des événements
        document.querySelectorAll('.nav-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.nav-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const filter = this.dataset.filter;
                const categoryLabel = this.dataset.categoryLabel;
                
                markers.forEach(({ marker, overlay, event }) => {
                    let shouldShow = false;
                    
                    if (filter === 'all') {
                        shouldShow = true;
                    } else if (filter === 'vodur') {
                        shouldShow = event.isVodunDays === true;
                    } else if (filter.startsWith('category-')) {
                        // Filtrer par catégorie API
                        shouldShow = event.category === categoryLabel;
                    } else {
                        shouldShow = event.type === filter;
                    }
                    
                    if (overlay) {
                        overlay.setMap(shouldShow ? map : null);
                    } else {
                        marker.setMap(shouldShow ? map : null);
                    }
                });
            });
        });

        // Recherche d'événements
        const searchInput = document.getElementById('searchInput');
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            markers.forEach(({ marker, overlay, event }) => {
                const matchesSearch = 
                    event.name.toLowerCase().includes(searchTerm) ||
                    event.location.toLowerCase().includes(searchTerm) ||
                    event.description.toLowerCase().includes(searchTerm);
                
                if (overlay) {
                    overlay.setMap(matchesSearch ? map : null);
                } else {
                    marker.setMap(matchesSearch ? map : null);
                }
            });
        });
    </script>

    <!-- Google Maps API -->
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key={{ env('GOOGLE_MAPS_API_KEY', 'AIzaSyDummy') }}&callback=initMap&loading=async">
    </script>
</body>
</html>
